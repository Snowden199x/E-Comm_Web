<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Order;
use App\Models\Finance\CashRemittance;
use App\Models\Finance\CashRemittanceItem;
use App\Services\CashRemittanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Province Hub side of the COD cash chain: confirm cash from its Station Hubs and send it to the Company. */
class ProvinceCashController extends Controller
{
    public function __construct(private CashRemittanceService $remittances) {}

    public function index(Request $request): View
    {
        $province = $request->attributes->get('logistics_facility');
        $incoming = CashRemittance::query()->where('level', CashRemittance::LEVEL_STATION_TO_PROVINCE)
            ->where('to_facility_id', $province->id)->with(['fromFacility', 'verifiedBy', 'resolvedBy'])->withCount('items')
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'discrepancy' THEN 1 ELSE 2 END")->latest()->paginate(15);
        $ready = $this->remittances->provinceReady($province->id)
            ->selectRaw('COUNT(*) as parcels, COALESCE(SUM(i.amount), 0) as total')->first();
        $sent = CashRemittance::query()->where('level', CashRemittance::LEVEL_PROVINCE_TO_COMPANY)
            ->where('from_facility_id', $province->id)->withCount('items')->latest()->limit(10)->get();
        $base = CashRemittance::query()->where('level', CashRemittance::LEVEL_STATION_TO_PROVINCE)->where('to_facility_id', $province->id);

        return view('logistics.province.cash', [
            'role' => 'province', 'province' => $province, 'incoming' => $incoming, 'ready' => $ready, 'sent' => $sent,
            'awaitingConfirm' => (float) (clone $base)->where('status', CashRemittance::STATUS_PENDING)->sum('expected_amount'),
            'openDiscrepancies' => (clone $base)->where('status', CashRemittance::STATUS_DISCREPANCY)->count(),
            'awaitingCompany' => (float) $sent->where('status', CashRemittance::STATUS_PENDING)->sum('expected_amount'),
        ]);
    }

    public function receive(Request $request, CashRemittance $remittance): RedirectResponse
    {
        $province = $request->attributes->get('logistics_facility');
        abort_unless($remittance->level === CashRemittance::LEVEL_STATION_TO_PROVINCE
            && (int) $remittance->to_facility_id === (int) $province->id, 404);
        $data = $request->validate([
            'received_amount' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        [$matches, $expected, $received] = $this->remittances->receive(
            $remittance, (int) round(((float) $data['received_amount']) * 100), $data['note'] ?? null, $request->user());

        return back()->with('success', $matches
            ? 'Received ₱'.number_format($received / 100, 2).' from the Station Hub.'
            : 'Recorded with a discrepancy of ₱'.number_format(abs($expected - $received) / 100, 2).' ('.($received < $expected ? 'short' : 'over').'). Resolve it below.');
    }

    public function resolve(Request $request, CashRemittance $remittance): RedirectResponse
    {
        $province = $request->attributes->get('logistics_facility');
        abort_unless($remittance->level === CashRemittance::LEVEL_STATION_TO_PROVINCE
            && (int) $remittance->to_facility_id === (int) $province->id, 404);
        $data = $request->validate(['resolution_note' => ['required', 'string', 'min:5', 'max:1000']]);
        $this->remittances->resolve($remittance, $data['resolution_note'], $request->user());

        return back()->with('success', 'Discrepancy resolved. The parcels are now ready to send to the Company.');
    }

    public function remit(Request $request): RedirectResponse
    {
        $province = $request->attributes->get('logistics_facility');
        $company = $request->attributes->get('logistics_company');

        [$count, $cents] = DB::transaction(function () use ($request, $province, $company) {
            $rows = $this->remittances->provinceReady($province->id)->get(['i.order_id', 'i.amount']);
            $orders = Order::query()->whereIn('id', $rows->pluck('order_id'))->where('money_status', 'remitted_to_province')
                ->lockForUpdate()->pluck('id')->flip();
            $rows = $rows->filter(fn ($row) => $orders->has($row->order_id))->values();
            abort_if($rows->isEmpty(), 409, 'There is no verified cash waiting to be sent.');

            $cents = (int) $rows->sum(fn ($row) => (int) round(((float) $row->amount) * 100));
            $remittance = CashRemittance::create([
                'reference' => $this->remittances->newReference('PC'),
                'level' => CashRemittance::LEVEL_PROVINCE_TO_COMPANY,
                'from_facility_id' => $province->id, 'logistics_company_id' => $company->id,
                'expected_amount' => number_format($cents / 100, 2, '.', ''),
                'status' => CashRemittance::STATUS_PENDING,
                'submitted_by' => $request->user()->id, 'submitted_at' => now(),
            ]);
            foreach ($rows as $row) {
                CashRemittanceItem::create([
                    'remittance_id' => $remittance->id, 'order_id' => $row->order_id,
                    'level' => CashRemittance::LEVEL_PROVINCE_TO_COMPANY, 'amount' => $row->amount,
                ]);
            }

            return [$rows->count(), $cents];
        }, 3);

        return back()->with('success', 'Sent ₱'.number_format($cents / 100, 2).' ('.$count.' parcel(s)) to the Company. It stays pending until the Company confirms receipt.');
    }
}
