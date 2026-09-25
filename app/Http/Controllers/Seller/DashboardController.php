<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Communication\Notification;
use App\Models\Ecommerce\Order;
use App\Models\Ecommerce\OrderItem;
use App\Models\Ecommerce\Product;
use App\Models\Ecommerce\ProductReview;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $sellerId = $request->user()->id;
        $orders = Order::where('seller_id', $sellerId);
        $stats = [
            'total_orders' => (clone $orders)->count(),
            'total_sales' => (clone $orders)->whereIn('status', Order::SALES_STATUSES)->sum('total_amount'),
            'pending_orders' => (clone $orders)->where('status', 'placed')->count(),
            'to_ship' => (clone $orders)->whereIn('status', ['confirmed', 'preparing', 'ready_for_pickup'])->count(),
        ];
        $ratingSummary = ProductReview::query()->where('seller_id', $sellerId)->where('visibility', 'published')
            ->select([])->selectRaw('COUNT(*) AS total, AVG(rating) AS average')->first();
        $stats['average_rating'] = $ratingSummary->total ? round((float) $ratingSummary->average, 1) : null;
        $stats['review_count'] = (int) $ratingSummary->total;
        $recentOrders = (clone $orders)->with('buyer')->latest()->orderByDesc('id')->limit(7)->get();
        $chart = [];
        for ($i = 5; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekEnd = $weekStart->copy()->endOfWeek();
            $chart[] = [
                'label' => $weekStart->format('M j'),
                'sales' => (float) (clone $orders)->whereIn('status', Order::SALES_STATUSES)
                    ->whereBetween('delivered_at', [$weekStart, $weekEnd])->sum('total_amount'),
                'orders' => (int) (clone $orders)->whereBetween('created_at', [$weekStart, $weekEnd])->count(),
            ];
        }
        $topProducts = OrderItem::whereHas('order', fn ($q) => $q->where('seller_id', $sellerId)->whereIn('status', Order::SALES_STATUSES))
            ->selectRaw('product_id, SUM(quantity) sold, SUM(quantity * price) revenue')->groupBy('product_id')->orderByDesc('sold')->limit(7)->with('product.images')->get();
        $approved = Product::where('seller_id', $sellerId)->where('status', 'approved');
        $lowStock = (clone $approved)->whereBetween('stock', [1, Product::LOW_STOCK_THRESHOLD])->orderBy('stock')->limit(5)->get();
        $outOfStock = (clone $approved)->where('stock', 0)->limit(5)->get();
        $notifications = Notification::where('user_id', $sellerId)->latest()->limit(10)->get();

        return view('seller.dashboard', compact('stats', 'recentOrders', 'chart', 'topProducts', 'lowStock', 'outOfStock', 'notifications'));
    }
}
