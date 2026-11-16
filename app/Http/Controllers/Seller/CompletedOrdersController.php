<?php

namespace App\Http\Controllers\Seller;

use App\Support\SellerSearch;
use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Order;
use Illuminate\Http\Request;
use App\Models\Profiles\LogisticsCompany;
use Illuminate\Validation\Rule;

class CompletedOrdersController extends Controller
{
    public function index(Request $request)
    {
        $sellerId = $request->user()->id;
        $courierIds = Order::query()->where('seller_id', $sellerId)->whereIn('status', Order::SALES_STATUSES)
            ->whereNotNull('delivery_company_id')->distinct()->pluck('delivery_company_id')->all();
        $paymentValues = Order::query()->where('seller_id', $sellerId)->whereIn('status', Order::SALES_STATUSES)
            ->whereNotNull('payment_mode')->distinct()->pluck('payment_mode')->all();
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'courier' => ['nullable', Rule::in(array_merge(['0'], $courierIds))],
            'payment' => ['nullable', Rule::in($paymentValues)],
            'page' => ['nullable', 'integer', 'min:1'],
        ], [
            'search.max' => 'Search is too long. Use 100 characters or fewer.',
            'from.date' => 'Start date is not valid. Pick a date from the calendar.',
            'to.date' => 'End date is not valid. Pick a date from the calendar.',
            'to.after_or_equal' => 'End date is before the start date. Pick an end date on or after the start date.',
            'courier.in' => 'That courier has no delivered orders. Choose another courier or clear the filter.',
            'payment.in' => 'That payment method has no delivered orders. Choose another method or clear the filter.',
            'page.min' => 'That page does not exist. Go back to the first page.',
        ]);

        $base = $this->scopedOrders($request->user()->id, $filters);
        $stats = [
            'orders' => (clone $base)->count(),
            'revenue' => (clone $base)->sum('total_amount'),
            'customers' => (clone $base)->distinct('buyer_id')->count('buyer_id'),
        ];

        $outcomes = $this->outcomeCounts($request->user()->id, $filters);
        $denominator = array_sum($outcomes);
        $stats['completion_rate'] = $denominator ? round(($outcomes['delivered'] / $denominator) * 100) : 0;

        $orders = (clone $base)->with(['buyer:id,name', 'courier:id,name', 'deliveryCompany:id,business_name', 'items.product.images'])
            ->orderByDesc('delivered_at')->orderByDesc('id')->paginate(8)->withQueryString();
        $couriers = LogisticsCompany::query()->whereIn('id', $courierIds)->orderBy('business_name')->get(['id', 'business_name']);
        $payments = Order::query()->where('seller_id', $request->user()->id)->whereIn('status', Order::SALES_STATUSES)
            ->whereNotNull('payment_mode')->distinct()->orderBy('payment_mode')->pluck('payment_mode');
        $topProducts = $this->topProducts($request->user()->id, $filters);
        $hasAny = Order::query()->where('seller_id', $sellerId)->whereIn('status', Order::SALES_STATUSES)->exists();
        $presets = $this->presets($request);

        return view('seller.completed-orders.index', compact('orders', 'stats', 'outcomes', 'couriers', 'payments', 'topProducts', 'filters', 'hasAny', 'presets'));
    }

    public function show(Request $request, int $order)
    {
        $record = Order::query()->where('seller_id', $request->user()->id)
            ->whereIn('status', Order::SALES_STATUSES)->with([
                'buyer:id,name,phone_number', 'courier:id,name', 'deliveryCompany:id,business_name', 'items.product.images',
                'statusEvents', 'items.review.reply', 'reviews',
            ])->findOrFail($order);

        if (! $request->ajax()) {
            return redirect()->route('seller.orders.show', $record);
        }

        return view('seller.completed-orders.detail', ['order' => $record]);
    }

    /** Quick date ranges. Each keeps the other filters and resets the page. */
    private function presets(Request $request): array
    {
        $keep = $request->except(['from', 'to', 'page']);
        $ranges = [
            '7 days' => [now()->subDays(6), now()],
            '30 days' => [now()->subDays(29), now()],
            'This month' => [now()->startOfMonth(), now()],
        ];
        $presets = [];
        foreach ($ranges as $label => [$from, $to]) {
            $query = array_merge($keep, ['from' => $from->toDateString(), 'to' => $to->toDateString()]);
            $presets[] = [
                'label' => $label,
                'url' => route('seller.completed-orders.index', $query),
                'active' => $request->query('from') === $from->toDateString() && $request->query('to') === $to->toDateString(),
            ];
        }

        return $presets;
    }

    private function scopedOrders(int $sellerId, array $filters)
    {
        return Order::query()->where('seller_id', $sellerId)->whereIn('status', Order::SALES_STATUSES)
            ->when(SellerSearch::clean($filters['search'] ?? ''), function ($query, string $term) {
                $query->where(function ($query) use ($term) {
                    $query->whereHas('buyer', fn ($buyer) => $buyer->where('name', 'like', SellerSearch::like($term)))
                        ->orWhereHas('items.product', fn ($product) => $product->where('name', 'like', SellerSearch::like($term)));
                    SellerSearch::orOrderColumns($query, $term);
                });
            })
            ->when($filters['from'] ?? null, fn ($query, $date) => $query->whereDate('delivered_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereDate('delivered_at', '<=', $date))
            ->when(isset($filters['courier']), function ($query) use ($filters) {
                return $filters['courier'] === '0' ? $query->whereNull('delivery_company_id') : $query->where('delivery_company_id', $filters['courier']);
            })
            ->when($filters['payment'] ?? null, fn ($query, $payment) => $query->where('payment_mode', $payment));
    }

    private function outcomeCounts(int $sellerId, array $filters): array
    {
        $counts = [];
        foreach (['delivered', 'returned', 'cancelled', 'delivery_failed'] as $status) {
            $query = Order::query()->where('seller_id', $sellerId)
                ->when($status === 'delivered', fn ($q) => $q->whereIn('status', Order::SALES_STATUSES), fn ($q) => $q->where('status', $status))
                ->when(($filters['from'] ?? null) || ($filters['to'] ?? null), function ($q) use ($status, $filters) {
                    if ($status === 'delivered') {
                        return $q->when($filters['from'] ?? null, fn ($inner, $date) => $inner->whereDate('delivered_at', '>=', $date))
                            ->when($filters['to'] ?? null, fn ($inner, $date) => $inner->whereDate('delivered_at', '<=', $date));
                    }
                    return $q->whereHas('statusEvents', function ($events) use ($status, $filters) {
                        $events->where('to_status', $status)
                            ->when($filters['from'] ?? null, fn ($inner, $date) => $inner->whereDate('created_at', '>=', $date))
                            ->when($filters['to'] ?? null, fn ($inner, $date) => $inner->whereDate('created_at', '<=', $date));
                    });
                })
                ->when(isset($filters['courier']), function ($q) use ($filters) {
                    return $filters['courier'] === '0' ? $q->whereNull('delivery_company_id') : $q->where('delivery_company_id', $filters['courier']);
                })
                ->when($filters['payment'] ?? null, fn ($q, $payment) => $q->where('payment_mode', $payment))
                ->when(SellerSearch::clean($filters['search'] ?? ''), function ($q, string $term) {
                    $q->where(function ($match) use ($term) {
                        $match->whereHas('buyer', fn ($buyer) => $buyer->where('name', 'like', SellerSearch::like($term)))
                            ->orWhereHas('items.product', fn ($product) => $product->where('name', 'like', SellerSearch::like($term)));
                        SellerSearch::orOrderColumns($match, $term);
                    });
                });
            $counts[$status] = $query->count();
        }

        return $counts;
    }

    private function topProducts(int $sellerId, array $filters)
    {
        $orderIds = $this->scopedOrders($sellerId, $filters)->select('orders.id');

        return \App\Models\Ecommerce\OrderItem::query()->select('product_id')
            ->selectRaw('SUM(order_items.quantity) AS units_sold, SUM(order_items.quantity * order_items.price) AS revenue')
            ->with('product.images')
            ->whereIn('order_id', $orderIds)->groupBy('product_id')
            ->orderByDesc('units_sold')->limit(5)->get();
    }
}
