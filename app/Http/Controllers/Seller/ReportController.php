<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Order;
use App\Models\Ecommerce\OrderItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    private const PERIODS = ['today', 'week', 'month', 'year'];

    public function index(Request $request)
    {
        return view('seller.reports.index', $this->build($request));
    }

    public function preview(Request $request)
    {
        return view('seller.reports.modal-content', $this->build($request, true));
    }

    public function download(Request $request)
    {
        $data = $this->build($request, true);
        $pdf = Pdf::loadView('seller.reports.pdf', $data)->setPaper('a4');

        return $pdf->download('vendo-seller-'.$request->user()->id.'-'.$data['period'].'-'.now()->format('Ymd').'.pdf');
    }

    private function build(Request $request, bool $pdf = false): array
    {
        $validated = $request->validate(['period' => ['nullable', Rule::in(self::PERIODS)]]);
        $period = $validated['period'] ?? 'today';
        $now = now('Asia/Manila');
        $start = match ($period) {
            'week' => $now->copy()->startOfWeek(),
            'month' => $now->copy()->startOfMonth(),
            'year' => $now->copy()->startOfYear(),
            default => $now->copy()->startOfDay(),
        };

        $ordersQuery = Order::query()->where('seller_id', $request->user()->id)
            ->whereIn('status', Order::SALES_STATUSES)
            ->whereBetween('delivered_at', [$start, $now]);
        $orderIds = (clone $ordersQuery)->select('orders.id');
        $gross = (float) (clone $ordersQuery)->sum('total_amount');
        $count = (clone $ordersQuery)->count();
        $units = (int) OrderItem::query()->whereIn('order_id', $orderIds)->sum('quantity');
        $shipping = (float) (clone $ordersQuery)->sum('shipping_fee');
        $topProducts = OrderItem::query()->select('product_id')
            ->selectRaw('SUM(quantity) AS units_sold, SUM(quantity * price) AS revenue')
            ->whereIn('order_id', $orderIds)->with('product:id,name')
            ->groupBy('product_id')->orderByDesc('units_sold')->limit(5)->get();
        $outcomes = [];
        foreach (['returned', 'cancelled', 'delivery_failed'] as $status) {
            $outcomes[$status] = Order::query()->where('seller_id', $request->user()->id)
                ->where('status', $status)->whereHas('statusEvents', fn ($events) => $events
                    ->where('to_status', $status)->whereBetween('created_at', [$start, $now]))->count();
        }

        $bucketFormat = match ($period) {
            'today' => '%H',
            'year' => '%Y-%m',
            default => '%Y-%m-%d',
        };
        $bucketTotals = (clone $ordersQuery)
            ->selectRaw("DATE_FORMAT(delivered_at, '{$bucketFormat}') AS bucket, SUM(total_amount) AS revenue")
            ->groupBy('bucket')->pluck('revenue', 'bucket');
        $chart = [];
        $cursor = $start->copy();
        while ($cursor->lessThanOrEqualTo($now)) {
            $key = match ($period) {
                'today' => $cursor->format('H'),
                'year' => $cursor->format('Y-m'),
                default => $cursor->format('Y-m-d'),
            };
            $chart[] = [
                'label' => match ($period) {
                    'today' => $cursor->format('g A'),
                    'year' => $cursor->format('M'),
                    default => $cursor->format('M j'),
                },
                'value' => (float) ($bucketTotals[$key] ?? 0),
            ];
            match ($period) {
                'today' => $cursor->addHour(),
                'year' => $cursor->addMonth(),
                default => $cursor->addDay(),
            };
        }
        $chartMax = max(1, ...array_column($chart, 'value'));

        $orders = (clone $ordersQuery)->with('buyer:id,name')->orderByDesc('delivered_at');
        $orders = $pdf ? $orders->limit(100)->get() : $orders->paginate(10)->withQueryString();

        return [
            'period' => $period,
            'periodLabel' => match ($period) {
                'today' => 'Today', 'week' => 'This Week', 'month' => 'This Month', 'year' => 'This Year',
            },
            'start' => $start,
            'end' => $now,
            'generatedAt' => now('Asia/Manila'),
            'seller' => $request->user()->loadMissing('sellerDetail'),
            'stats' => ['gross' => $gross, 'orders' => $count, 'units' => $units,
                'average' => $count ? $gross / $count : null, 'shipping' => $shipping],
            'chart' => $chart,
            'chartMax' => $chartMax,
            'topProducts' => $topProducts,
            'outcomes' => $outcomes,
            'orders' => $orders,
            'pdf' => $pdf,
        ];
    }
}
