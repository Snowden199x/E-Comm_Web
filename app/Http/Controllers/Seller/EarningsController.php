<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Order;
use App\Models\Finance\CommissionSetting;
use App\Services\SellerEarningsCalculator;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

/**
 * The Seller's earnings per delivered order. There is no bank or e-wallet payout: earnings are shown as
 * Pending until Vendo confirms the cash for the parcel, then credited automatically.
 */
class EarningsController extends Controller
{
    public function index(Request $request, SellerEarningsCalculator $calculator): View
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:pending,credited,earlier'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $rows = Order::query()->where('seller_id', $request->user()->id)->whereIn('status', Order::SALES_STATUSES)
            ->orderByDesc('delivered_at')->orderByDesc('id')
            ->get(['id', 'tracking_number', 'total_amount', 'voucher_discount', 'seller_commission_rate', 'money_status', 'delivered_at'])
            ->map(function (Order $order) use ($calculator) {
                $rate = (float) ($order->seller_commission_rate ?? CommissionSetting::FIXED_RATE);
                $items = (int) round(((float) $order->total_amount) * 100);
                $voucher = (int) round(((float) $order->voucher_discount) * 100);
                $earnings = $calculator->earnings($items, $voucher, $rate);

                return [
                    'order' => $order, 'rate' => $rate, 'items_cents' => $items, 'voucher_cents' => $voucher,
                    'commission_cents' => $earnings['commission_cents'], 'net_cents' => $earnings['net_cents'],
                    'state' => match (true) {
                        in_array($order->money_status, ['credited_to_seller', 'remitted_to_vendo'], true) => 'credited',
                        $order->money_status === null => 'earlier',
                        default => 'pending',
                    },
                ];
            });

        $sum = fn (string $state, string $key) => (int) $rows->where('state', $state)->sum($key);
        $visible = isset($filters['status']) ? $rows->where('state', $filters['status'])->values() : $rows;
        $page = (int) ($filters['page'] ?? 1);
        $orders = new LengthAwarePaginator($visible->forPage($page, 15)->values(), $visible->count(), 15, $page, [
            'path' => $request->url(), 'query' => $request->query(),
        ]);

        return view('seller.earnings.index', [
            'orders' => $orders, 'filters' => $filters,
            'pendingCents' => $sum('pending', 'net_cents'), 'creditedCents' => $sum('credited', 'net_cents'),
            'commissionCents' => $sum('credited', 'commission_cents'),
            'earlierCount' => $rows->where('state', 'earlier')->count(),
            'moneyStatuses' => Order::MONEY_STATUSES,
        ]);
    }
}