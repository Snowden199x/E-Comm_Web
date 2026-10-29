<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Finance\DeliveryFeeSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Vendo Admin: the two commission rates, plus a read-only view of how delivery fees were split. */
class DeliveryFeeController extends Controller
{
    public function index(Request $request): View
    {
        $setting = DeliveryFeeSetting::current()->load('updatedBy');

        $split = DB::table('order_shipping_settlements as s')->join('orders as o', 'o.id', '=', 's.order_id')
            ->whereNotNull('o.rider_pay_rate');
        $totals = (clone $split)->selectRaw('COUNT(*) as parcels, COALESCE(SUM(s.vendo_commission), 0) as vendo')->first();
        $thisMonth = (clone $split)->whereBetween('s.settled_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->selectRaw('COALESCE(SUM(s.vendo_commission), 0) as vendo')->first();
        $history = (clone $split)->orderByDesc('s.settled_at')->orderByDesc('s.id')->paginate(15, [
            's.id', 's.shipping_fee', 's.rider_total', 's.vendo_commission', 's.logistics_remainder', 's.status', 's.settled_at',
            'o.number as order_number', 'o.rider_pay_rate', 'o.vendo_delivery_commission_rate',
        ]);

        return view('admin.delivery-fees.index', [
            'setting' => $setting,
            'canEdit' => (bool) auth('admin')->user()?->is_super_admin,
            'totalCommission' => (float) $totals->vendo, 'monthCommission' => (float) $thisMonth->vendo,
            'parcels' => (int) $totals->parcels, 'history' => $history,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless(auth('admin')->user()?->is_super_admin, 403, 'Only a super admin can change commission rates.');
        $data = $request->validate([
            'vendo_delivery_commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'seller_commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        DeliveryFeeSetting::current()->update([
            'vendo_delivery_commission_rate' => number_format((float) $data['vendo_delivery_commission_rate'], 2, '.', ''),
            'seller_commission_rate' => number_format((float) $data['seller_commission_rate'], 2, '.', ''),
            'updated_by' => auth('admin')->id(),
        ]);

        return back()->with('success', 'Rates saved. They apply to new orders only; existing orders keep the rates they were placed with.');
    }
}
