<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Order;
use App\Models\Finance\CommissionSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
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

    public function updateRate(Request $request): RedirectResponse
    {
        $request->validate(['rate' => 'required|numeric|min:0|max:100']);

        $setting = CommissionSetting::first();

        if ($setting) {
            $setting->update(['rate' => $request->rate]);
        } else {
            CommissionSetting::create(['rate' => $request->rate]);
        }

        return back()->with('confirmation', 'rate_updated');
    }

    private function monthStats(float $rate, string $month): array
    {
        [$year, $mon] = explode('-', $month);

        $itemsTotal = OrderItem::whereHas('order', function ($q) use ($year, $mon) {
                $q->whereIn('status', ['delivered', 'completed'])
                    ->whereYear('created_at', $year)
                    ->whereMonth('created_at', $mon);
            })
            ->selectRaw('SUM(quantity * price) as total')
            ->value('total') ?? 0;

        return [
            'total_sales' => $itemsTotal,
            'total_commission' => $itemsTotal * ($rate / 100),
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
                $itemsTotal = OrderItem::whereHas('order', function ($q) use ($seller, $year, $mon) {
                        $q->where('seller_id', $seller->id)
                            ->whereIn('status', ['delivered', 'completed'])
                            ->whereYear('created_at', $year)
                            ->whereMonth('created_at', $mon);
                    })
                    ->selectRaw('SUM(quantity * price) as total')
                    ->value('total') ?? 0;

                $seller->total_sales = $itemsTotal;
                $seller->commission_owed = $itemsTotal * ($rate / 100);

                return $seller;
            });
    }
    
    public function sellerDetail(Request $request, User $seller)
    {
        $rate = CommissionSetting::currentRate();
        $month = $request->get('month', now()->format('Y-m'));
        [$year, $mon] = explode('-', $month);

        $items = OrderItem::with('product')
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
                    'commission' => $sales * ($rate / 100),
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