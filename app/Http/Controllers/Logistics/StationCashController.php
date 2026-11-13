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
        ], [
            'order_ids.required' => 'No parcels selected. Tick the parcels the rider handed in.',
            'order_ids.array' => 'The parcel list is not valid. Reload the page and try again.',
            'order_ids.min' => 'No parcels selected. Tick the parcels the rider handed in.',
            'order_ids.*.integer' => 'A selected parcel is not valid. Reload the page and try again.',
            'received_amount.required' => 'Amount received is empty. Enter the cash you counted, in pesos.',
            'received_amount.numeric' => 'Amount received must be a number, like 1250.50. Remove any symbols or letters.',
            'received_amount.min' => 'Amount received is below zero. Enter 0 or more.',
            'received_amount.max' => 'Amount received is too large. Check for an extra digit.',
            'note.max' => 'Note is longer than 500 characters. Shorten it.',
        ]);
        $orderIds = array_values(array_unique(array_map('intval', $data['order_ids'])));

        $result = DB::transaction(function () use ($request, $station, $courierDetail, $data, $orderIds) {
            $orders = Order::query()->whereIn('id', $orderIds)->where('money_status', 'collected_by_rider')
                ->lockForUpdate()->get();
            $collections = OrderCodCollection::query()->whereIn('order_id', $orders->pluck('id'))
                ->where('rider_user_id', $courierDetail->user_id)->where('station_hub_id', $station->id)->get()->keyBy('order_id');
            if ($orders->count() !== count($orderIds) || $collections->count() !== $orders->count()) {
                throw ValidationException::withMessages(['order_ids' => 'Some of these parcels are no longer waiting for cash from this rider. Reload the page to see the current list, then try again.']);
            }

            $expectedCents = (int) $collections->sum(fn ($c) => (int) round(((float) $c->amount) * 100));
            $receivedCents = (int) round(((float) $data['received_amount']) * 100);
            $matches = $expectedCents === $receivedCents;
            $note = trim((string) ($data['note'] ?? ''));
            if (! $matches && $note === '') {
                throw ValidationException::withMessages(['note' => 'Amount differs from what was collected. Add a note that explains the difference.']);
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
        $rider = $courierDetail->user?->name ?? 'the rider';
        if ($matches) {
            return back()->with('success', 'Received ₱'.number_format($receivedCents / 100, 2).' from '.$rider.' for '.$count.($count === 1 ? ' parcel' : ' parcels').'.');
        }

        return back()->with('success', 'Recorded ₱'.number_format(abs($expectedCents - $receivedCents) / 100, 2).' '
            .($receivedCents < $expectedCents ? 'short' : 'over').' from '.$rider.'. Resolve it on the Cash discrepancies page before sending this cash.');
    }

    public function remit(Request $request): RedirectResponse
    {
        $station = $request->attributes->get('logistics_facility');
        $province = $station->parent;
        if (! $province || ! $province->is_active) {
            throw ValidationException::withMessages(['remit' => 'This Station Hub has no active Province Hub to send cash to. Ask your company admin to assign one.']);
        }

        [$count, $cents] = DB::transaction(function () use ($request, $station, $province) {
            $ids = $this->readyToRemit($station->id)->pluck('c.order_id');
            $orders = Order::query()->whereIn('id', $ids)->where('money_status', 'remitted_to_station')
                ->lockForUpdate()->with('codCollection')->get();
            if ($orders->isEmpty()) {
                throw ValidationException::withMessages(['remit' => 'There is no verified cash to send. Confirm cash from a rider first.']);
            }

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

        return back()->with('success', 'Sent ₱'.number_format($cents / 100, 2).' for '.$count.($count === 1 ? ' parcel' : ' parcels').' to '.$province->name.'. It stays pending until the Province Hub confirms receipt.');
    }

    public function discrepancies(Request $request): View|RedirectResponse
    {
        $station = $request->attributes->get('logistics_facility');
        $term = trim((string) $request->query('q', ''));
        if (mb_strlen($term) > 100) {
            return redirect()->route('lgp.station.cash.discrepancies')
                ->withErrors(['q' => 'The search is too long. Use a receipt reference or rider name of 100 characters or fewer.']);
        }

        $base = fn () => CashRemittance::query()->where('level', CashRemittance::LEVEL_RIDER_TO_STATION)
            ->where('to_facility_id', $station->id)
            ->whereIn('status', [CashRemittance::STATUS_DISCREPANCY, CashRemittance::STATUS_RESOLVED]);
        $statusCounts = $base()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        // Chips: Open (discrepancy) and Resolved. The list is paged, so the filters run here.
        $status = in_array($request->query('status'), ['discrepancy', 'resolved'], true) ? $request->query('status') : null;
        $like = '%'.addcslashes($term, '%_\\').'%';
        $remittances = $base()
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($term !== '', fn ($query) => $query->where(fn ($match) => $match->where('reference', 'like', $like)
                ->orWhereHas('fromRider', fn ($rider) => $rider->where('name', 'like', $like))))
            ->with(['fromRider', 'verifiedBy', 'resolvedBy', 'items.order:id,tracking_number'])
            ->orderByRaw("CASE WHEN status = 'discrepancy' THEN 0 ELSE 1 END")->latest()->paginate(20)->withQueryString();

        return view('logistics.station.cash-discrepancies', [
            'role' => 'station', 'remittances' => $remittances,
            'statusCounts' => $statusCounts, 'status' => $status, 'term' => $term,
            'openDifference' => (float) $base()->where('status', CashRemittance::STATUS_DISCREPANCY)
                ->selectRaw('COALESCE(SUM(ABS(received_amount - expected_amount)), 0) as difference')->value('difference'),
        ]);
    }

    public function resolve(Request $request, CashRemittance $remittance): RedirectResponse
    {
        $station = $request->attributes->get('logistics_facility');
        abort_unless($remittance->level === CashRemittance::LEVEL_RIDER_TO_STATION
            && (int) $remittance->to_facility_id === (int) $station->id, 404);
        $data = $request->validate(['resolution_note' => ['required', 'string', 'min:5', 'max:1000']], [
            'resolution_note.required' => 'Resolution note is empty. Say how the difference was settled, so the next person knows.',
            'resolution_note.min' => 'Resolution note is too short. Use at least 5 characters to say how it was settled.',
            'resolution_note.max' => 'Resolution note is longer than 1,000 characters. Shorten it.',
        ]);

        DB::transaction(function () use ($request, $remittance, $data) {
            $locked = CashRemittance::query()->whereKey($remittance->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== CashRemittance::STATUS_DISCREPANCY) {
                throw ValidationException::withMessages(['resolution_note' => 'This discrepancy is already resolved. Reload the page to see its latest status.']);
            }
            $locked->update([
                'status' => CashRemittance::STATUS_RESOLVED, 'resolution_note' => $data['resolution_note'],
                'resolved_by' => $request->user()->id, 'resolved_at' => now(),
            ]);
            // Once resolved, the parcels' cash counts in full and can move on to the Province Hub.
            Order::query()->whereIn('id', $locked->items()->pluck('order_id'))
                ->where('money_status', 'discrepancy')->update(['money_status' => 'remitted_to_station']);
        }, 3);

        return back()->with('success', 'Resolved '.$remittance->reference.'. Its parcels are now counted in full and ready to send to your Province Hub on Cash collections.');
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
