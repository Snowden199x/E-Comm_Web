<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Order;
use App\Models\Finance\CashRemittance;
use App\Models\Finance\CashRemittanceItem;
use App\Models\Finance\DeliveryFeeSetting;
use App\Services\CashRemittanceService;
use App\Services\CheckoutShippingQuoteService;
use App\Services\DeliveryFeeDistributionCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Company side of the COD cash chain: confirm cash from Province Hubs, send Vendo its share, set rider pay. */
class CompanyCashController extends Controller
{
    public function __construct(private CashRemittanceService $remittances) {}

    public function index(Request $request): View
    {
        $company = $this->company($request);
        $incoming = CashRemittance::query()->where('level', CashRemittance::LEVEL_PROVINCE_TO_COMPANY)
            ->where('logistics_company_id', $company->id)->with(['fromFacility', 'verifiedBy', 'resolvedBy'])->withCount('items')
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'discrepancy' THEN 1 ELSE 2 END")->latest()->paginate(15);
        $due = $this->remittances->vendoDue($company->id);
        $sent = CashRemittance::query()->where('level', CashRemittance::LEVEL_COMPANY_TO_VENDO)
            ->where('logistics_company_id', $company->id)->withCount('items')->latest()->limit(10)->get();
        $settled = DB::table('cash_remittance_items as i')
            ->join('cash_remittances as r', 'r.id', '=', 'i.remittance_id')
            ->join('order_shipping_settlements as s', 's.order_id', '=', 'i.order_id')
            ->where('i.level', CashRemittance::LEVEL_PROVINCE_TO_COMPANY)->where('r.logistics_company_id', $company->id)
            ->whereIn('r.status', [CashRemittance::STATUS_RECEIVED, CashRemittance::STATUS_RESOLVED])
            ->selectRaw('COALESCE(SUM(s.logistics_remainder), 0) as company, COALESCE(SUM(s.rider_total), 0) as rider, COALESCE(SUM(s.vendo_commission), 0) as vendo')->first();
        $base = CashRemittance::query()->where('level', CashRemittance::LEVEL_PROVINCE_TO_COMPANY)->where('logistics_company_id', $company->id);

        return view('logistics.company.cash', [
            'role' => 'company', 'company' => $company, 'incoming' => $incoming, 'sent' => $sent,
            'dueCents' => (int) $due->sum('due_cents'), 'dueCount' => $due->count(), 'settled' => $settled,
            'awaitingConfirm' => (float) (clone $base)->where('status', CashRemittance::STATUS_PENDING)->sum('expected_amount'),
            'openDiscrepancies' => (clone $base)->where('status', CashRemittance::STATUS_DISCREPANCY)->count(),
            'awaitingVendo' => (float) $sent->where('status', CashRemittance::STATUS_PENDING)->sum('expected_amount'),
        ]);
    }

    public function receive(Request $request, CashRemittance $remittance): RedirectResponse
    {
        abort_unless($remittance->level === CashRemittance::LEVEL_PROVINCE_TO_COMPANY
            && (int) $remittance->logistics_company_id === (int) $this->company($request)->id, 404);
        $data = $request->validate([
            'received_amount' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        [$matches, $expected, $received] = $this->remittances->receive(
            $remittance, (int) round(((float) $data['received_amount']) * 100), $data['note'] ?? null, $request->user());

        return back()->with('success', $matches
            ? 'Received ₱'.number_format($received / 100, 2).' from the Province Hub.'
            : 'Recorded with a discrepancy of ₱'.number_format(abs($expected - $received) / 100, 2).' ('.($received < $expected ? 'short' : 'over').'). Resolve it below.');
    }

    public function resolve(Request $request, CashRemittance $remittance): RedirectResponse
    {
        abort_unless($remittance->level === CashRemittance::LEVEL_PROVINCE_TO_COMPANY
            && (int) $remittance->logistics_company_id === (int) $this->company($request)->id, 404);
        $data = $request->validate(['resolution_note' => ['required', 'string', 'min:5', 'max:1000']]);
        $this->remittances->resolve($remittance, $data['resolution_note'], $request->user());

        return back()->with('success', 'Discrepancy resolved. The parcels are now counted in full.');
    }

    /** Send Vendo what it is owed for the cash this Company has received. */
    public function remit(Request $request): RedirectResponse
    {
        $company = $this->company($request);

        [$count, $cents] = DB::transaction(function () use ($request, $company) {
            $due = $this->remittances->vendoDue($company->id);
            $locked = Order::query()->whereIn('id', $due->pluck('order_id'))->where('money_status', 'received_by_company')
                ->lockForUpdate()->pluck('id')->flip();
            $due = $due->filter(fn ($row) => $locked->has($row['order_id']))->values();
            abort_if($due->isEmpty(), 409, 'There is no received cash waiting to be sent to Vendo.');

            $cents = (int) $due->sum('due_cents');
            $remittance = CashRemittance::create([
                'reference' => $this->remittances->newReference('CV'),
                'level' => CashRemittance::LEVEL_COMPANY_TO_VENDO, 'logistics_company_id' => $company->id,
                'expected_amount' => number_format($cents / 100, 2, '.', ''),
                'status' => CashRemittance::STATUS_PENDING,
                'submitted_by' => $request->user()->id, 'submitted_at' => now(),
            ]);
            foreach ($due as $row) {
                CashRemittanceItem::create([
                    'remittance_id' => $remittance->id, 'order_id' => $row['order_id'],
                    'level' => CashRemittance::LEVEL_COMPANY_TO_VENDO,
                    'amount' => number_format($row['due_cents'] / 100, 2, '.', ''),
                ]);
            }

            return [$due->count(), $cents];
        }, 3);

        return back()->with('success', 'Sent ₱'.number_format($cents / 100, 2).' ('.$count.' parcel(s)) to Vendo. It stays pending until Vendo confirms receipt.');
    }

    public function riderPay(Request $request, DeliveryFeeDistributionCalculator $calculator): View
    {
        $company = $this->company($request);
        $vendoRate = (float) DeliveryFeeSetting::current()->vendo_delivery_commission_rate;
        $exampleFeeCents = CheckoutShippingQuoteService::MINIMUM_FEE_CENTS;
        $example = $calculator->split($exampleFeeCents, (int) round(((float) $company->rider_pay_per_parcel) * 100), $vendoRate);

        return view('logistics.company.rider-pay', [
            'role' => 'company', 'company' => $company, 'vendoRate' => $vendoRate,
            'maxPay' => CheckoutShippingQuoteService::MINIMUM_FEE_CENTS / 100,
            'exampleFee' => $exampleFeeCents / 100, 'example' => $example,
        ]);
    }

    public function updateRiderPay(Request $request): RedirectResponse
    {
        $company = $this->company($request);
        // Never above the lowest delivery fee, so a rider payment can always be covered by the fee.
        $max = CheckoutShippingQuoteService::MINIMUM_FEE_CENTS / 100;
        $data = $request->validate(['rider_pay_per_parcel' => ['required', 'numeric', 'min:0', 'max:'.$max]]);
        $company->update(['rider_pay_per_parcel' => number_format((float) $data['rider_pay_per_parcel'], 2, '.', '')]);

        return back()->with('success', 'Rider pay set to ₱'.number_format((float) $data['rider_pay_per_parcel'], 2).' per delivered parcel. It applies to new orders only.');
    }

    private function company(Request $request)
    {
        return $request->user()->logisticsCenterDetail->company;
    }
}
