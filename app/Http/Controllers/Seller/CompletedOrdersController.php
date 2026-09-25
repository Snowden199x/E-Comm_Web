<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Order;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompletedOrdersController extends Controller
{
    public function index(Request $request)
    {
        $sellerId = $request->user()->id;
        $courierIds = Order::query()->where('seller_id', $sellerId)->whereIn('status', Order::SALES_STATUSES)
            ->whereNotNull('courier_id')->distinct()->pluck('courier_id')->all();
        $paymentValues = Order::query()->where('seller_id', $sellerId)->whereIn('status', Order::SALES_STATUSES)
            ->whereNotNull('payment_mode')->distinct()->pluck('payment_mode')->all();
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'courier' => ['nullable', Rule::in(array_merge(['0'], $courierIds))],
            'payment' => ['nullable', Rule::in($paymentValues)],
            'page' => ['nullable', 'integer', 'min:1'],
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

        $orders = (clone $base)->with(['buyer:id,name', 'courier:id,name', 'items.product.images'])
            ->orderByDesc('delivered_at')->orderByDesc('id')->paginate(8)->withQueryString();
        $couriers = Order::query()->where('seller_id', $request->user()->id)
            ->whereIn('status', Order::SALES_STATUSES)->whereNotNull('courier_id')
            ->with('courier:id,name')->get()->pluck('courier')->filter()->unique('id')->sortBy('name')->values();
        $payments = Order::query()->where('seller_id', $request->user()->id)->whereIn('status', Order::SALES_STATUSES)
            ->whereNotNull('payment_mode')->distinct()->orderBy('payment_mode')->pluck('payment_mode');
        $topProducts = $this->topProducts($request->user()->id, $filters);

        return view('seller.completed-orders.index', compact('orders', 'stats', 'outcomes', 'couriers', 'payments', 'topProducts', 'filters'));
    }

    public function show(Request $request, int $order)
    {
        $record = Order::query()->where('seller_id', $request->user()->id)
            ->whereIn('status', Order::SALES_STATUSES)->with([
                'buyer:id,name,phone_number', 'courier:id,name', 'items.product.images',
                'statusEvents', 'items.review.reply', 'reviews',
            ])->findOrFail($order);

        return view('seller.completed-orders.detail', ['order' => $record]);
    }

    private function scopedOrders(int $sellerId, array $filters)
    {
        return Order::query()->where('seller_id', $sellerId)->whereIn('status', Order::SALES_STATUSES)
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $term = trim($search);
                $id = preg_replace('/^VN-0*/i', '', $term);
                $query->where(function ($query) use ($term, $id) {
                    if ($id !== '' && ctype_digit($id)) {
                        $query->orWhere('orders.id', (int) $id);
                    }
                    $query->orWhere('tracking_number', 'like', "%{$term}%")
                        ->orWhere('carrier_tracking_number', 'like', "%{$term}%")
                        ->orWhereHas('buyer', fn ($buyer) => $buyer->where('name', 'like', "%{$term}%"))
                        ->orWhereHas('items.product', fn ($product) => $product->where('name', 'like', "%{$term}%"));
                });
            })
            ->when($filters['from'] ?? null, fn ($query, $date) => $query->whereDate('delivered_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereDate('delivered_at', '<=', $date))
            ->when(isset($filters['courier']), function ($query) use ($filters) {
                return $filters['courier'] === '0' ? $query->whereNull('courier_id') : $query->where('courier_id', $filters['courier']);
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
                    return $filters['courier'] === '0' ? $q->whereNull('courier_id') : $q->where('courier_id', $filters['courier']);
                })
                ->when($filters['payment'] ?? null, fn ($q, $payment) => $q->where('payment_mode', $payment))
                ->when($filters['search'] ?? null, function ($q, string $search) {
                    $term = trim($search);
                    $id = preg_replace('/^VN-0*/i', '', $term);
                    $q->where(function ($match) use ($term, $id) {
                        if ($id !== '' && ctype_digit($id)) $match->orWhere('orders.id', (int) $id);
                        $match->orWhere('tracking_number', 'like', "%{$term}%")
                            ->orWhere('carrier_tracking_number', 'like', "%{$term}%")
                            ->orWhereHas('buyer', fn ($buyer) => $buyer->where('name', 'like', "%{$term}%"))
                            ->orWhereHas('items.product', fn ($product) => $product->where('name', 'like', "%{$term}%"));
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
            ->with('product:id,name')
            ->whereIn('order_id', $orderIds)->groupBy('product_id')
            ->orderByDesc('units_sold')->limit(5)->get();
    }
}
