<?php

namespace App\Services;

use App\Models\Communication\Notification;
use App\Models\Ecommerce\Order;
use App\Models\Finance\CashRemittance;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Shared steps for confirming and resolving a cash batch sent up the hub chain. No real money
 * moves; these record that the receiver confirmed (or disputed) the amount.
 */
class CashRemittanceService
{
    public function newReference(string $prefix): string
    {
        return $prefix.'-'.now()->format('ymd').'-'.strtoupper(Str::random(5));
    }

    /**
     * Confirm a pending batch. A matching amount moves its orders to the level's received status;
     * a different amount needs a note and puts the batch and its orders in discrepancy.
     *
     * @return array{0:bool,1:int,2:int} [matches, expectedCents, receivedCents]
     */
    public function receive(CashRemittance $remittance, int $receivedCents, ?string $note, User $user): array
    {
        return DB::transaction(function () use ($remittance, $receivedCents, $note, $user) {
            $locked = CashRemittance::query()->whereKey($remittance->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === CashRemittance::STATUS_PENDING, 409, 'This batch was already confirmed.');

            $expectedCents = (int) round(((float) $locked->expected_amount) * 100);
            $matches = $expectedCents === $receivedCents;
            $note = trim((string) $note);
            if (! $matches && $note === '') {
                throw ValidationException::withMessages(['note' => 'Add a note explaining the difference.']);
            }

            $orderIds = $locked->items()->pluck('order_id');
            Order::query()->whereIn('id', $orderIds)->lockForUpdate()->get();
            // Orders that move forward now (not ones already advanced), used to credit Sellers once.
            $moving = Order::query()->whereIn('id', $orderIds)
                ->where('money_status', CashRemittance::SENT_MONEY_STATUS[$locked->level])->pluck('id');
            $locked->update([
                'received_amount' => number_format($receivedCents / 100, 2, '.', ''),
                'status' => $matches ? CashRemittance::STATUS_RECEIVED : CashRemittance::STATUS_DISCREPANCY,
                'verified_by' => $user->id, 'verified_at' => now(),
                'note' => $note !== '' ? $note : null,
            ]);
            Order::query()->whereIn('id', $orderIds)
                ->where('money_status', CashRemittance::SENT_MONEY_STATUS[$locked->level])
                ->update(['money_status' => $matches ? CashRemittance::RECEIVED_MONEY_STATUS[$locked->level] : 'discrepancy']);
            if ($matches && $locked->level === CashRemittance::LEVEL_COMPANY_TO_VENDO) {
                $this->notifySellersCredited($moving);
            }

            return [$matches, $expectedCents, $receivedCents];
        }, 3);
    }

    /** Resolve a discrepancy with a note; its orders return to the level's received status and count in full. */
    public function resolve(CashRemittance $remittance, string $note, User $user): void
    {
        DB::transaction(function () use ($remittance, $note, $user) {
            $locked = CashRemittance::query()->whereKey($remittance->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === CashRemittance::STATUS_DISCREPANCY, 409, 'This discrepancy is already resolved.');
            $locked->update([
                'status' => CashRemittance::STATUS_RESOLVED, 'resolution_note' => $note,
                'resolved_by' => $user->id, 'resolved_at' => now(),
            ]);
            $held = Order::query()->whereIn('id', $locked->items()->pluck('order_id'))->where('money_status', 'discrepancy')->pluck('id');
            Order::query()->whereIn('id', $held)
                ->update(['money_status' => CashRemittance::RECEIVED_MONEY_STATUS[$locked->level]]);
            if ($locked->level === CashRemittance::LEVEL_COMPANY_TO_VENDO) {
                $this->notifySellersCredited($held);
            }
        }, 3);
    }

    /**
     * Tell each Seller what was just credited. Net earnings are the item price minus the commission saved
     * on the order; nothing is stored beyond the order's money status, and no real money moves.
     *
     * @param  Collection<int, int>  $orderIds
     */
    private function notifySellersCredited(Collection $orderIds): void
    {
        if ($orderIds->isEmpty()) {
            return;
        }

        Order::query()->whereIn('id', $orderIds)->get(['id', 'seller_id', 'total_amount', 'seller_commission_rate'])
            ->groupBy('seller_id')->each(function ($orders, $sellerId) {
                $net = (float) $orders->sum(fn (Order $order) => $order->seller_net_earnings);
                Notification::create([
                    'user_id' => $sellerId,
                    'type' => 'earnings_credited',
                    'title' => 'Earnings credited',
                    'message' => '₱'.number_format($net, 2).' from '.$orders->count().' delivered '
                        .($orders->count() === 1 ? 'order' : 'orders').' is now credited to your earnings.',
                    'link' => route('seller.earnings.index', ['state' => 'credited']),
                ]);
            });
    }

    /** Orders a Province Hub has confirmed cash for and not yet sent to the Company. */
    public function provinceReady(int $provinceId)
    {
        return DB::table('cash_remittance_items as i')
            ->join('cash_remittances as r', 'r.id', '=', 'i.remittance_id')
            ->join('orders as o', 'o.id', '=', 'i.order_id')
            ->where('i.level', CashRemittance::LEVEL_STATION_TO_PROVINCE)->where('r.to_facility_id', $provinceId)
            ->whereIn('r.status', [CashRemittance::STATUS_RECEIVED, CashRemittance::STATUS_RESOLVED])
            ->where('o.money_status', 'remitted_to_province')
            ->whereNotExists(fn ($query) => $query->select(DB::raw(1))->from('cash_remittance_items as n')
                ->whereColumn('n.order_id', 'i.order_id')->where('n.level', CashRemittance::LEVEL_PROVINCE_TO_COMPANY));
    }

    /**
     * What a Company owes Vendo for orders it has received cash for: the cash collected minus what the
     * Company keeps (Rider pay it holds for payout plus its own share of the delivery fee). That equals the
     * item price after any voucher plus Vendo's delivery fee commission.
     *
     * @return Collection<int, array{order_id:int, due_cents:int}>
     */
    public function vendoDue(int $companyId): Collection
    {
        return DB::table('cash_remittance_items as i')
            ->join('cash_remittances as r', 'r.id', '=', 'i.remittance_id')
            ->join('orders as o', 'o.id', '=', 'i.order_id')
            ->leftJoin('order_shipping_settlements as s', 's.order_id', '=', 'o.id')
            ->where('i.level', CashRemittance::LEVEL_PROVINCE_TO_COMPANY)->where('r.logistics_company_id', $companyId)
            ->whereIn('r.status', [CashRemittance::STATUS_RECEIVED, CashRemittance::STATUS_RESOLVED])
            ->where('o.money_status', 'received_by_company')
            ->whereNotExists(fn ($query) => $query->select(DB::raw(1))->from('cash_remittance_items as n')
                ->whereColumn('n.order_id', 'i.order_id')->where('n.level', CashRemittance::LEVEL_COMPANY_TO_VENDO))
            ->get(['o.id as order_id', 'o.cod_amount', 'o.shipping_fee', 's.rider_total', 's.logistics_remainder'])
            ->map(function ($row) {
                $keepCents = $row->logistics_remainder !== null
                    ? (int) round(((float) $row->rider_total + (float) $row->logistics_remainder) * 100)
                    : (int) round(((float) $row->shipping_fee) * 100);

                return ['order_id' => (int) $row->order_id,
                    'due_cents' => max(0, (int) round(((float) $row->cod_amount) * 100) - $keepCents)];
            });
    }
}
