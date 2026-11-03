<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\OrderItem;
use App\Models\Finance\CommissionSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommissionController extends Controller
{
    /** Orders in these statuses count as completed sales. */
    private const COUNTED_STATUSES = ['delivered', 'completed'];

    private const PER_PAGE = 10;

    public function index(Request $request): View
    {
        $month = $this->resolveMonth($request);

        return view('admin.commission.index', [
            'sellers' => $this->filteredSellers($request, $month),
            'stats' => $this->monthStats($month),
            'previous' => $this->monthStats($this->previousMonth($month)),
            'rate' => CommissionSetting::currentRate(),
            'month' => $month,
        ]);
    }

    public function table(Request $request): View
    {
        $month = $this->resolveMonth($request);

        return view('admin.commission.partials.commission-table', [
            'sellers' => $this->filteredSellers($request, $month),
            'stats' => $this->monthStats($month),
            'rate' => CommissionSetting::currentRate(),
            'month' => $month,
        ]);
    }

    public function sellerDetail(Request $request, User $seller): JsonResponse
    {
        abort_unless($seller->role === 'seller', 404);

        $month = $this->resolveMonth($request);

        $items = $this->itemsInMonth($month)
            ->where('orders.seller_id', $seller->id)
            ->with('product')
            ->select('order_items.*', 'orders.seller_commission_rate as order_rate')
            ->get()
            ->groupBy('product_id')
            ->map(function ($group) {
                $product = $group->first()->product;

                return [
                    'name' => $product->name ?? 'Product removed',
                    'quantity' => (int) $group->sum('quantity'),
                    'sales' => (float) $group->sum(fn ($item) => $item->quantity * $item->price),
                    'commission' => (float) $group->sum(fn ($item) => $item->quantity * $item->price
                        * ((float) ($item->order_rate ?? CommissionSetting::FIXED_RATE)) / 100),
                ];
            })
            ->sortByDesc('sales')
            ->values();

        $totalSales = (float) $items->sum('sales');
        $totalCommission = (float) $items->sum('commission');

        return response()->json([
            'seller_name' => $seller->name,
            'month_label' => Carbon::parse($month.'-01')->format('F Y'),
            'items' => $items,
            'total_sales' => $totalSales,
            'total_commission' => $totalCommission,
            // Rates can differ between orders, so this is the blended rate for the month.
            'average_rate' => $totalSales > 0 ? round($totalCommission / $totalSales * 100, 2) : null,
        ]);
    }

    /** A valid Y-m month, never later than the current month. Anything else falls back to this month. */
    private function resolveMonth(Request $request): string
    {
        $current = now()->format('Y-m');
        $month = (string) $request->query('month', $current);

        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            return $current;
        }

        return $month > $current ? $current : $month;
    }

    private function previousMonth(string $month): string
    {
        return Carbon::parse($month.'-01')->subMonth()->format('Y-m');
    }

    /** Order items of delivered and completed orders created in the month, joined to their order. */
    private function itemsInMonth(string $month): Builder
    {
        [$year, $mon] = explode('-', $month);

        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.status', self::COUNTED_STATUSES)
            ->whereYear('orders.created_at', $year)
            ->whereMonth('orders.created_at', $mon);
    }

    private function salesSql(): string
    {
        return 'COALESCE(SUM(order_items.quantity * order_items.price), 0)';
    }

    /** Each order uses the rate saved on it, so a rate change never alters past commission. */
    private function commissionSql(): string
    {
        return 'COALESCE(SUM(order_items.quantity * order_items.price * '.CommissionSetting::orderRateSql().' / 100), 0)';
    }

    private function monthStats(string $month): array
    {
        $totals = $this->itemsInMonth($month)
            ->selectRaw($this->salesSql().' as total, '.$this->commissionSql().' as commission')
            ->first();

        $sellersWithSales = $this->itemsInMonth($month)
            ->distinct()
            ->count('orders.seller_id');

        return [
            'total_sales' => (float) $totals->total,
            'total_commission' => (float) $totals->commission,
            'sellers_with_sales' => (int) $sellersWithSales,
            'total_sellers' => User::where('role', 'seller')->count(),
        ];
    }

    /** One query for the page: top earners first, sellers without sales last (A to Z). */
    private function filteredSellers(Request $request, string $month): LengthAwarePaginator
    {
        [$year, $mon] = explode('-', $month);

        $sales = $this->itemsInMonth($month)
            ->whereColumn('orders.seller_id', 'users.id')
            ->selectRaw($this->salesSql());

        $commission = $this->itemsInMonth($month)
            ->whereColumn('orders.seller_id', 'users.id')
            ->selectRaw($this->commissionSql());

        $query = User::query()
            ->where('role', 'seller')
            ->select('users.*')
            ->selectSub($sales, 'total_sales')
            ->selectSub($commission, 'commission_owed')
            ->withCount(['orders as completed_orders_count' => function ($q) use ($year, $mon) {
                $q->whereIn('status', self::COUNTED_STATUSES)
                    ->whereYear('created_at', $year)
                    ->whereMonth('created_at', $mon);
            }]);

        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        }

        return $query
            ->orderByDesc('commission_owed')
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }
}
