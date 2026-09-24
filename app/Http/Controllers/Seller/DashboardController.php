<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Communication\Announcement;
use App\Models\Communication\Notification;
use App\Models\Ecommerce\Order;
use App\Models\Ecommerce\OrderItem;
use App\Models\Ecommerce\Product;
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
        $recentOrders = (clone $orders)->with('buyer')->latest()->orderByDesc('id')->limit(7)->get();
        $start = now()->startOfMonth();
        $daily = (clone $orders)->whereBetween('created_at', [$start, now()])
            ->selectRaw('DATE(created_at) day, COUNT(*) orders_count, SUM(CASE WHEN status IN (?, ?) THEN total_amount ELSE 0 END) sales', Order::SALES_STATUSES)
            ->groupByRaw('DATE(created_at)')->get()->keyBy('day');
        $chart = [];
        for ($day = $start->copy(); $day->lte(now()->startOfDay()); $day->addDay()) {
            $row = $daily->get($day->toDateString());
            $chart[] = ['label' => $day->format('M j'), 'sales' => (float) ($row?->sales ?? 0), 'orders' => (int) ($row?->orders_count ?? 0)];
        }
        $topProducts = OrderItem::whereHas('order', fn ($q) => $q->where('seller_id', $sellerId)->whereIn('status', Order::SALES_STATUSES))
            ->selectRaw('product_id, SUM(quantity) sold, SUM(quantity * price) revenue')->groupBy('product_id')->orderByDesc('sold')->limit(7)->with('product.images')->get();
        $approved = Product::where('seller_id', $sellerId)->where('status', 'approved');
        $lowStock = (clone $approved)->whereBetween('stock', [1, Product::LOW_STOCK_THRESHOLD])->orderBy('stock')->limit(5)->get();
        $outOfStock = (clone $approved)->where('stock', 0)->limit(5)->get();
        $notifications = Notification::where('user_id', $sellerId)->latest()->limit(5)->get();
        $announcements = Announcement::where('status', 'published')->where('is_active', true)->whereIn('audience', ['All Users', 'Sellers'])
            ->where(fn ($q) => $q->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now()))->latest()->limit(3)->get();

        return view('seller.dashboard', compact('stats', 'recentOrders', 'chart', 'topProducts', 'lowStock', 'outOfStock', 'notifications', 'announcements'));
    }
}
