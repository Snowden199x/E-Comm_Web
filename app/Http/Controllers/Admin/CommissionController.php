<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Order;
use App\Models\Finance\CommissionSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Ecommerce\OrderItem;

class CommissionController extends Controller
{
    public function index(Request $request): View
    {
        $rate = CommissionSetting::currentRate();
        $month = $request->get('month', now()->format('Y-m'));
        $sellers = $this->filteredSellers($request, $rate, $month);

        $stats = $this->monthStats($rate, $month);

        return view('admin.commission.index', compact('sellers', 'stats', 'rate', 'month'));
    }

    public function table(Request $request): View
    {
        $rate = CommissionSetting::currentRate();
        $month = $request->get('month', now()->format('Y-m'));
        $sellers = $this->filteredSellers($request, $rate, $month);

        return view('admin.commission.partials.commission-table', compact('sellers', 'rate', 'month'));
    }

    private function monthStats(float $rate, string $month): array
    {
        [$year, $mon] = explode('-', $month);

        $totals = OrderItem::whereHas('order', function ($q) use ($year, $mon) {
                $q->whereIn('status', ['delivered', 'completed'])
                    ->whereYear('created_at', $year)
                    ->whereMonth('created_at', $mon);
            })
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->selectRaw('COALESCE(SUM(order_items.quantity * order_items.price), 0) as total, '
                .'COALESCE(SUM(order_items.quantity * order_items.price * '.CommissionSetting::orderRateSql().' / 100), 0) as commission')
            ->first();

        return [
            'total_sales' => (float) $totals->total,
            'total_commission' => (float) $totals->commission,
            'total_sellers' => User::where('role', 'seller')->count(),
        ];
    }

    private function filteredSellers(Request $request, float $rate, string $month)
    {
        [$year, $mon] = explode('-', $month);

        $query = User::where('role', 'seller');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        }

        return $query->withCount(['orders as completed_orders_count' => function ($q) use ($year, $mon) {
                $q->whereIn('status', ['delivered', 'completed'])
                    ->whereYear('created_at', $year)
                    ->whereMonth('created_at', $mon);
            }])
            ->latest()
            ->paginate(8)
            ->withQueryString()
            ->through(function ($seller) use ($rate, $year, $mon) {
                $totals = OrderItem::whereHas('order', function ($q) use ($seller, $year, $mon) {
                        $q->where('seller_id', $seller->id)
                            ->whereIn('status', ['delivered', 'completed'])
                            ->whereYear('created_at', $year)
                            ->whereMonth('created_at', $mon);
                    })
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->selectRaw('COALESCE(SUM(order_items.quantity * order_items.price), 0) as total, '
                        .'COALESCE(SUM(order_items.quantity * order_items.price * '.CommissionSetting::orderRateSql().' / 100), 0) as commission')
                    ->first();

                $seller->total_sales = (float) $totals->total;
                $seller->commission_owed = (float) $totals->commission;

                return $seller;
            });
    }
    
    public function sellerDetail(Request $request, User $seller)
    {
        $rate = CommissionSetting::currentRate();
        $month = $request->get('month', now()->format('Y-m'));
        [$year, $mon] = explode('-', $month);

        $items = OrderItem::with(['product', 'order:id,seller_commission_rate'])
            ->whereHas('order', function ($q) use ($seller, $year, $mon) {
                $q->where('seller_id', $seller->id)
                    ->whereIn('status', ['delivered', 'completed'])
                    ->whereYear('created_at', $year)
                    ->whereMonth('created_at', $mon);
            })
            ->get()
            ->groupBy('product_id')
            ->map(function ($group) use ($rate) {
                $product = $group->first()->product;
                $quantity = $group->sum('quantity');
                $sales = $group->sum(fn ($item) => $item->quantity * $item->price);

                return [
                    'name' => $product->name ?? 'Product removed',
                    'quantity' => $quantity,
                    'sales' => $sales,
                    'commission' => $group->sum(fn ($item) => $item->quantity * $item->price
                        * ((float) ($item->order?->seller_commission_rate ?? CommissionSetting::FIXED_RATE)) / 100),
                ];
            })
            ->sortByDesc('sales')
            ->values();

        return response()->json([
            'seller_name' => $seller->name,
            'month_label' => \Carbon\Carbon::parse($month . '-01')->format('F Y'),
            'items' => $items,
            'total_sales' => $items->sum('sales'),
            'total_commission' => $items->sum('commission'),
        ]);
    }
}
