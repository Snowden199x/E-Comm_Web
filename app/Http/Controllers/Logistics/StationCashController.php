<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Order;
use App\Models\Finance\CashRemittance;
use App\Models\Finance\CashRemittanceItem;
use App\Models\Finance\OrderCodCollection;
use App\Models\Profiles\CourierDetail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Station Hub side of the COD cash chain: see what each Rider holds, receive their cash, handle
 * discrepancies, and send verified cash up to the Province Hub. No real money moves; these are
 * confirmations recorded in the system.
 */
class StationCashController extends Controller
{
    public function index(Request $request): View
    {
        $station = $request->attributes->get('logistics_facility');

        $collections = fn (string $moneyStatus) => DB::table('order_cod_collections as c')
            ->join('orders as o', 'o.id', '=', 'c.order_id')
            ->where('c.station_hub_id', $station->id)->where('o.money_status', $moneyStatus);

        $held = $collections('collected_by_rider')->orderBy('c.collected_at')
            ->get(['c.rider_user_id', 'c.order_id', 'c.amount', 'c.collected_at', 'o.tracking_number'])
            ->groupBy('rider_user_id');
        $disputed = $collections('discrepancy')->groupBy('c.rider_user_id')
            ->selectRaw('c.rider_user_id, SUM(c.amount) as total')->pluck('total', 'rider_user_id');

        $couriers = $station->riders()->with('user')->get()->keyBy('user_id');
        $userIds = $couriers->keys()->merge($held->keys())->unique()->values();
        $earnings = DB::table('order_shipping_earning_lines as l')
            ->join('order_shipping_settlements as s', 's.id', '=', 'l.settlement_id')
            ->where('s.status', 'accrued')->whereIn('l.rider_user_id', $userIds)
            ->groupBy('l.rider_user_id')->selectRaw('l.rider_user_id, SUM(l.amount) as total')->pluck('total', 'rider_user_id');
        $names = User::query()->whereIn('id', $userIds)->pluck('name', 'id');

        $ledger = $userIds->map(function ($userId) use ($couriers, $held, $disputed, $earnings, $names) {
            $parcels = ($held[$userId] ?? collect())->map(fn ($row) => [
                'id' => (string) $row->order_id, 'tracking' => $row->tracking_number,
                'amount' => (float) $row->amount, 'collected_at' => $row->collected_at,
            ])->values()->all();

            return [
                'courier_id' => $couriers[$userId]->id ?? null,
                'name' => $names[$userId] ?? 'Rider',
                'approved' => ($couriers[$userId]->user->status ?? null) === 'approved',
                'parcels' => $parcels,
                'on_hand' => array_sum(array_column($parcels, 'amount')),
                'disputed' => (float) ($disputed[$userId] ?? 0),
                'earnings' => (float) ($earnings[$userId] ?? 0),
            ];
        })->filter(fn ($row) => $row['courier_id'] && ($row['approved'] || $row['on_hand'] > 0 || $row['earnings'] > 0))
            ->sortByDesc('on_hand')->values()->all();

        $ready = $this->readyToRemit($station->id)->selectRaw('COUNT(*) as parcels, COALESCE(SUM(c.amount), 0) as total')->first();
        $province = $station->parent;
        $sent = CashRemittance::query()->where('level', CashRemittance::LEVEL_STATION_TO_PROVINCE)
            ->where('from_facility_id', $station->id)->withCount('items')->with('toFacility')->latest()->limit(10)->get();
        $receipts = CashRemittance::query()->where('level', CashRemittance::LEVEL_RIDER_TO_STATION)
            ->where('to_facility_id', $station->id)->with('fromRider')->latest()->limit(10)->get();

        return view('logistics.station.cash', [
            'role' => 'station', 'station' => $station, 'province' => $province,
            'ledger' => $ledger, 'ready' => $ready, 'sent' => $sent, 'receipts' => $receipts,
            'openDiscrepancies' => CashRemittance::query()->where('level', CashRemittance::LEVEL_RIDER_TO_STATION)
                ->where('to_facility_id', $station->id)->where('status', CashRemittance::STATUS_DISCREPANCY)->count(),
            'awaitingProvince' => (float) $sent->where('status', CashRemittance::STATUS_PENDING)->sum('expected_amount'),
        ]);
    }

    public function receive(Request $request, CourierDetail $courierDetail): RedirectResponse
    {
        $station = $request->attributes->get('logistics_facility');
        abort_unless((int) $courierDetail->station_hub_id === (int) $station->id, 404);
        $data = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer'],
            'received_amount' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        $orderIds = array_values(array_unique(array_map('intval', $data['order_ids'])));

        $result = DB::transaction(function () use ($request, $station, $courierDetail, $data, $orderIds) {
            $orders = Order::query()->whereIn('id', $orderIds)->where('money_status', 'collected_by_rider')
                ->lockForUpdate()->get();
            $collections = OrderCodCollection::query()->whereIn('order_id', $orders->pluck('id'))
                ->where('rider_user_id', $courierDetail->user_id)->where('station_hub_id', $station->id)->get()->keyBy('order_id');
            abort_unless($orders->count() === count($orderIds) && $collections->count() === $orders->count(), 409,
                'Some of these parcels are no longer waiting for cash from this Rider. Refresh and try again.');

            $expectedCents = (int) $collections->sum(fn ($c) => (int) round(((float) $c->amount) * 100));
            $receivedCents = (int) round(((float) $data['received_amount']) * 100);
            $matches = $expectedCents === $receivedCents;
            $note = trim((string) ($data['note'] ?? ''));
            if (! $matches && $note === '') {
                throw ValidationException::withMessages(['note' => 'Add a note explaining the difference.']);
            }

            $remittance = CashRemittance::create([
                'reference' => 'RS-'.now()->format('ymd').'-'.strtoupper(Str::random(5)),
                'level' => CashRemittance::LEVEL_RIDER_TO_STATION,
                'from_rider_user_id' => $courierDetail->user_id, 'to_facility_id' => $station->id,
                'logistics_company_id' => $request->attributes->get('logistics_company')?->id,
                'expected_amount' => number_format($expectedCents / 100, 2, '.', ''),
                'received_amount' => number_format($receivedCents / 100, 2, '.', ''),
                'status' => $matches ? CashRemittance::STATUS_RECEIVED : CashRemittance::STATUS_DISCREPANCY,
                'verified_by' => $request->user()->id, 'verified_at' => now(), 'submitted_at' => now(),
                'note' => $note !== '' ? $note : null,
            ]);
            foreach ($orders as $order) {
                CashRemittanceItem::create([
                    'remittance_id' => $remittance->id, 'order_id' => $order->id,
                    'level' => CashRemittance::LEVEL_RIDER_TO_STATION, 'amount' => $collections[$order->id]->amount,
                ]);
                $order->money_status = $matches ? 'remitted_to_station' : 'discrepancy';
                $order->save();
            }

            return [$matches, $expectedCents, $receivedCents, $orders->count()];
        }, 3);

        [$matches, $expectedCents, $receivedCents, $count] = $result;
        if ($matches) {
            return back()->with('success', 'Received ₱'.number_format($receivedCents / 100, 2).' for '.$count.' parcel(s).');
        }

        return back()->with('success', 'Recorded with a discrepancy of ₱'.number_format(abs($expectedCents - $receivedCents) / 100, 2)
            .' ('.($receivedCents < $expectedCents ? 'short' : 'over').'). Resolve it on the Cash Discrepancies page.');
    }

    public function remit(Request $request): RedirectResponse
    {
        $station = $request->attributes->get('logistics_facility');
        $province = $station->parent;
        abort_unless($province && $province->is_active, 409, 'This Station Hub has no active Province Hub to send cash to.');

        [$count, $cents] = DB::transaction(function () use ($request, $station, $province) {
            $ids = $this->readyToRemit($station->id)->pluck('c.order_id');
            $orders = Order::query()->whereIn('id', $ids)->where('money_status', 'remitted_to_station')
                ->lockForUpdate()->with('codCollection')->get();
            abort_if($orders->isEmpty(), 409, 'There is no verified cash waiting to be sent.');

            $cents = (int) $orders->sum(fn ($order) => (int) round(((float) $order->codCollection->amount) * 100));
            $remittance = CashRemittance::create([
                'reference' => 'SP-'.now()->format('ymd').'-'.strtoupper(Str::random(5)),
                'level' => CashRemittance::LEVEL_STATION_TO_PROVINCE,
                'from_facility_id' => $station->id, 'to_facility_id' => $province->id,
                'logistics_company_id' => $request->attributes->get('logistics_company')?->id,
                'expected_amount' => number_format($cents / 100, 2, '.', ''),
                'status' => CashRemittance::STATUS_PENDING,
                'submitted_by' => $request->user()->id, 'submitted_at' => now(),
            ]);
            foreach ($orders as $order) {
                CashRemittanceItem::create([
                    'remittance_id' => $remittance->id, 'order_id' => $order->id,
                    'level' => CashRemittance::LEVEL_STATION_TO_PROVINCE, 'amount' => $order->codCollection->amount,
                ]);
            }

            return [$orders->count(), $cents];
        }, 3);

        return back()->with('success', 'Sent ₱'.number_format($cents / 100, 2).' ('.$count.' parcel(s)) to '.$province->name.'. It stays pending until the Province Hub confirms receipt.');
    }

    public function discrepancies(Request $request): View
    {
        $station = $request->attributes->get('logistics_facility');
        $remittances = CashRemittance::query()->where('level', CashRemittance::LEVEL_RIDER_TO_STATION)
            ->where('to_facility_id', $station->id)
            ->whereIn('status', [CashRemittance::STATUS_DISCREPANCY, CashRemittance::STATUS_RESOLVED])
            ->with(['fromRider', 'verifiedBy', 'resolvedBy', 'items.order:id,tracking_number'])
            ->orderByRaw("CASE WHEN status = 'discrepancy' THEN 0 ELSE 1 END")->latest()->paginate(20);

        return view('logistics.station.cash-discrepancies', ['role' => 'station', 'remittances' => $remittances]);
    }

    public function resolve(Request $request, CashRemittance $remittance): RedirectResponse
    {
        $station = $request->attributes->get('logistics_facility');
        abort_unless($remittance->level === CashRemittance::LEVEL_RIDER_TO_STATION
            && (int) $remittance->to_facility_id === (int) $station->id, 404);
        $data = $request->validate(['resolution_note' => ['required', 'string', 'min:5', 'max:1000']]);

        DB::transaction(function () use ($request, $remittance, $data) {
            $locked = CashRemittance::query()->whereKey($remittance->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === CashRemittance::STATUS_DISCREPANCY, 409, 'This discrepancy is already resolved.');
            $locked->update([
                'status' => CashRemittance::STATUS_RESOLVED, 'resolution_note' => $data['resolution_note'],
                'resolved_by' => $request->user()->id, 'resolved_at' => now(),
            ]);
            // Once resolved, the parcels' cash counts in full and can move on to the Province Hub.
            Order::query()->whereIn('id', $locked->items()->pluck('order_id'))
                ->where('money_status', 'discrepancy')->update(['money_status' => 'remitted_to_station']);
        }, 3);

        return back()->with('success', 'Discrepancy resolved. The parcels are now ready to send to the Province Hub.');
    }

    /** Parcels whose cash this Station has verified and not yet put in a batch for the Province Hub. */
    private function readyToRemit(int $stationId)
    {
        return DB::table('order_cod_collections as c')
            ->join('orders as o', 'o.id', '=', 'c.order_id')
            ->where('c.station_hub_id', $stationId)->where('o.money_status', 'remitted_to_station')
            ->whereNotExists(fn ($query) => $query->select(DB::raw(1))->from('cash_remittance_items as i')
                ->whereColumn('i.order_id', 'c.order_id')->where('i.level', CashRemittance::LEVEL_STATION_TO_PROVINCE));
    }
}
