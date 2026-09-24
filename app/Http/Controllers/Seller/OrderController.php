<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Order;
use App\Services\SellerOrderWorkflow;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(array_merge(['all'], array_keys(Order::SELLER_GROUPS)))],
            'search' => 'nullable|string|max:100', 'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('date_from') ? ['after_or_equal:date_from'] : [])],
            'page' => 'nullable|integer|min:1', 'order' => 'nullable|integer|min:1',
        ]);
        $base = Order::where('seller_id', $request->user()->id);
        $raw = (clone $base)->selectRaw('status, COUNT(*) total')->groupBy('status')->pluck('total', 'status');
        $counts = ['all' => (int) $raw->sum()];
        foreach (Order::SELLER_GROUPS as $group => $statuses) {
            $counts[$group] = collect($statuses)->sum(fn ($status) => (int) ($raw[$status] ?? 0));
        }
        $query = (clone $base)->with('buyer')->withSum('items', 'quantity');
        $status = $filters['status'] ?? 'all';
        if ($status !== 'all') {
            $query->whereIn('status', Order::SELLER_GROUPS[$status]);
        }
        if ($search = trim($filters['search'] ?? '')) {
            $id = preg_match('/^(?:#?VN-)?0*(\d+)$/i', $search, $m) ? (int) $m[1] : null;
            $query->where(function ($q) use ($search, $id) {
                $q->whereHas('buyer', fn ($b) => $b->where('name', 'like', '%'.$search.'%'));
                if ($id !== null) {
                    $q->orWhere('id', $id);
                }
            });
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        $orders = $query->latest()->orderByDesc('id')->paginate(10)->withQueryString();
        if ($request->expectsJson()) {
            return response()->json(['html' => view('seller.order-management-orders.rows', compact('orders'))->render(), 'counts' => $counts, 'pagination' => ['page' => $orders->currentPage(), 'last' => $orders->lastPage(), 'total' => $orders->total(), 'from' => $orders->firstItem(), 'to' => $orders->lastItem()]]);
        }

        return view('seller.order-management-orders.index', compact('orders', 'counts', 'filters'));
    }

    private function ownedOrder(Request $request, int $id): Order
    {
        return Order::where('seller_id', $request->user()->id)->with(['buyer', 'courier', 'items.product.images', 'statusEvents'])->findOrFail($id);
    }

    public function show(Request $request, int $order)
    {
        $order = $this->ownedOrder($request, $order);

        return response()->json(['html' => view('seller.order-management-orders.drawer', compact('order'))->render()]);
    }

    public function waybill(Request $request, int $order)
    {
        $order = $this->ownedOrder($request, $order);
        abort_if(in_array($order->status, ['placed', 'cancelled', 'returned']), 409);
        $seller = $request->user()->load('sellerDetail');

        return view('seller.order-management-orders.waybill', compact('order', 'seller'));
    }

    public function update(Request $request, int $order)
    {
        $data = $request->validate(['action' => ['required', Rule::in(['accept', 'decline', 'prepare', 'ready', 'pickup'])], 'expected_status' => ['required', Rule::in(array_keys(Order::STATUSES))], 'reason' => 'required_if:action,decline|nullable|string|max:500']);
        app(SellerOrderWorkflow::class)->transition($request->user(), $order, $data['action'], $data['expected_status'], $data['reason'] ?? null);

        return response()->json(['message' => 'Order updated.']);
    }
}
