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
 * The Seller's earnings per delivered order. Money is display-only in this project: an order's earnings
 * are credited as soon as it is delivered (or confirmed received by the Buyer), and the cash chain in
 * `orders.money_status` is shown elsewhere for information only.
 */
class EarningsController extends Controller
{
    public function index(Request $request, SellerEarningsCalculator $calculator): View
    {
        $rows = Order::query()->where('seller_id', $request->user()->id)->whereIn('status', Order::SALES_STATUSES)
            ->orderByDesc('delivered_at')->orderByDesc('id')
            ->get(['id', 'tracking_number', 'total_amount', 'voucher_discount', 'seller_commission_rate', 'delivered_at'])
            ->map(function (Order $order) use ($calculator) {
                $rate = (float) ($order->seller_commission_rate ?? CommissionSetting::FIXED_RATE);
                $items = (int) round(((float) $order->total_amount) * 100);
                $voucher = (int) round(((float) $order->voucher_discount) * 100);
                $earnings = $calculator->earnings($items, $voucher, $rate);

                return [
                    'order' => $order, 'rate' => $rate, 'items_cents' => $items, 'voucher_cents' => $voucher,
                    'commission_cents' => $earnings['commission_cents'], 'net_cents' => $earnings['net_cents'],
                ];
            });

        $page = max(1, (int) $request->query('page', 1));
        $orders = new LengthAwarePaginator($rows->forPage($page, 15)->values(), $rows->count(), 15, $page, [
            'path' => $request->url(), 'query' => $request->query(),
        ]);

        return view('seller.earnings.index', [
            'orders' => $orders,
            'creditedCents' => (int) $rows->sum('net_cents'),
            'commissionCents' => (int) $rows->sum('commission_cents'),
        ]);
    }
}
