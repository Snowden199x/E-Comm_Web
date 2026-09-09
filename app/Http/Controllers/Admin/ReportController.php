<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Order;
use App\Models\Ecommerce\OrderItem;
use App\Models\Finance\CommissionSetting;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $data = $this->buildReport($request);

        return view('admin.reports.index', $data);
    }

    public function preview(Request $request): View
    {
        $data = $this->buildReport($request);

        return view('admin.reports.partials.report-content', $data);
    }

    public function download(Request $request)
    {
        $data = $this->buildReport($request);

        $pdf = Pdf::loadView('admin.reports.pdf', $data)->setPaper('a4');

        return $pdf->download("vendo-report-{$data['periodLabel']}.pdf");
    }

    private function buildReport(Request $request): array
    {
        $view = $request->get('view', 'daily');
        $rate = CommissionSetting::currentRate();

        if ($view === 'monthly') {
            $year = $request->get('year', now()->format('Y'));

            return array_merge(
                $this->monthlyData($rate, $year),
                ['view' => 'monthly', 'dateFilter' => 'monthly', 'customDate' => '', 'year' => $year]
            );
        }

        $dateFilter = $request->get('date_filter') ?: session('last_date_filter', 'today');
        $customDate = $request->get('custom_date') ?: session('last_custom_date', now()->toDateString());

        session(['last_date_filter' => $dateFilter, 'last_custom_date' => $customDate]);

        [$start, $end] = $this->resolveRange($dateFilter, $customDate);

        return array_merge(
            $this->dailyData($rate, $start, $end),
            ['view' => 'daily', 'dateFilter' => $dateFilter, 'customDate' => $customDate, 'year' => now()->format('Y')]
        );
    }

    private function resolveRange(string $filter, string $customDate): array
    {
        return match ($filter) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'custom' => [Carbon::parse($customDate)->startOfDay(), Carbon::parse($customDate)->endOfDay()],
            default => [now()->startOfDay(), now()->endOfDay()],
        };
    }

    private function dailyData(float $rate, Carbon $start, Carbon $end): array
    {
        $ordersQuery = Order::whereBetween('created_at', [$start, $end]);
        $salesSummary = $this->salesSummary($ordersQuery);
        $sellers = $this->sellerBreakdown($rate, fn ($q) => $q->whereBetween('created_at', [$start, $end]));

        $labels = [];
        $sales = [];
        $commission = [];

        $cursor = $start->copy()->startOfDay();
        $lastDay = $end->copy()->startOfDay();

        while ($cursor->lte($lastDay)) {
            $dayTotal = OrderItem::whereHas('order', function ($q) use ($cursor) {
                    $q->whereIn('status', ['delivered', 'completed'])->whereDate('created_at', $cursor->toDateString());
                })
                ->selectRaw('SUM(quantity * price) as total')->value('total') ?? 0;

            $labels[] = $cursor->format('M j');
            $sales[] = round($dayTotal, 2);
            $commission[] = round($dayTotal * ($rate / 100), 2);

            $cursor->addDay();
        }

        $periodLabel = $start->isSameDay($end)
            ? $start->format('F j, Y')
            : $start->format('M j') . ' – ' . $end->format('M j, Y');

        return [
            'periodLabel' => $periodLabel,
            'breakdownTitle' => 'Daily Sales Breakdown',
            'rate' => $rate,
            'salesSummary' => $salesSummary,
            'sellers' => $sellers,
            'totalCommission' => $sellers->sum('commission'),
            'topSeller' => $sellers->first(),
            'chartLabels' => $labels,
            'chartSales' => $sales,
            'chartCommission' => $commission,
        ];
    }

    private function monthlyData(float $rate, string $year): array
    {
        $ordersQuery = Order::whereYear('created_at', $year);
        $salesSummary = $this->salesSummary($ordersQuery);
        $sellers = $this->sellerBreakdown($rate, fn ($q) => $q->whereYear('created_at', $year));

        $labels = [];
        $sales = [];
        $commission = [];

        for ($mon = 1; $mon <= 12; $mon++) {
            $monthTotal = OrderItem::whereHas('order', function ($q) use ($year, $mon) {
                    $q->whereIn('status', ['delivered', 'completed'])
                        ->whereYear('created_at', $year)->whereMonth('created_at', $mon);
                })
                ->selectRaw('SUM(quantity * price) as total')->value('total') ?? 0;

            $labels[] = Carbon::create($year, $mon, 1)->format('M');
            $sales[] = round($monthTotal, 2);
            $commission[] = round($monthTotal * ($rate / 100), 2);
        }

        return [
            'periodLabel' => $year,
            'breakdownTitle' => 'Monthly Sales Breakdown',
            'rate' => $rate,
            'salesSummary' => $salesSummary,
            'sellers' => $sellers,
            'totalCommission' => $sellers->sum('commission'),
            'topSeller' => $sellers->first(),
            'chartLabels' => $labels,
            'chartSales' => $sales,
            'chartCommission' => $commission,
        ];
    }

        private function salesSummary(\Illuminate\Database\Eloquent\Builder $ordersQuery): array
    {
        return [
            'gross_sales' => (clone $ordersQuery)->where('status', '!=', 'cancelled')->sum('total_amount'),
            'total_orders' => (clone $ordersQuery)->count(),
            'average_order_value' => (clone $ordersQuery)->where('status', '!=', 'cancelled')->avg('total_amount') ?? 0,
            'completed_orders' => (clone $ordersQuery)->whereIn('status', ['delivered', 'completed'])->count(),
            'return_refund' => (clone $ordersQuery)->where('status', 'returned')->count(),
        ];
    }

    private function sellerBreakdown(float $rate, \Closure $dateConstraint)
    {
        return User::where('role', 'seller')
            ->get()
            ->map(function ($seller) use ($rate, $dateConstraint) {
                $itemsTotal = OrderItem::whereHas('order', function ($q) use ($seller, $dateConstraint) {
                        $q->where('seller_id', $seller->id)->whereIn('status', ['delivered', 'completed']);
                        $dateConstraint($q);
                    })
                    ->selectRaw('SUM(quantity * price) as total')->value('total') ?? 0;

                return ['name' => $seller->name, 'sales' => $itemsTotal, 'commission' => $itemsTotal * ($rate / 100)];
            })
            ->filter(fn ($s) => $s['sales'] > 0)
            ->sortByDesc('sales')
            ->values();
    }
}