<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\View\View;

class OperationsController extends Controller
{
    public function monitoring(Request $request): View
    {
        $centerId = $request->user()->logisticsCenterDetail->id;
        $filters = $request->validate([
            'q' => 'nullable|string|max:100', 'status' => ['nullable', Rule::in(array_keys(Order::STATUSES))],
            'stage' => 'nullable|in:hub,transit,out,done,issue', 'area' => 'nullable|string|size:9',
            'date_from' => 'nullable|date', 'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);
        $base = Order::query()
            ->where(fn ($query) => $query->where('logistics_center_id', $centerId)
                ->orWhere('destination_logistics_center_id', $centerId))
            ->whereIn('status', [
                'ready_for_pickup', 'picked_up', 'at_sorting_center', 'sorted', 'to_soc5', 'to_soc6',
                'in_transit_to_hub', 'at_destination_hub', 'assigned_to_rider', 'out_for_delivery',
                'delivered', 'delivery_failed',
            ]);
        $areas = (clone $base)->whereNotNull('shipping_city_code')->whereNotNull('shipping_city')
            ->select('shipping_city_code', 'shipping_city')->distinct()->orderBy('shipping_city')->get();
        $availableStatuses = (clone $base)->select('status')->distinct()->orderBy('status')->pluck('status');
        $search = trim($filters['q'] ?? '');
        $filtered = (clone $base)
            ->when($search !== '', fn ($q) => $q->where(fn ($matched) => $matched
                ->where('tracking_number', 'like', '%'.$search.'%')
                ->orWhere('shipping_address', 'like', '%'.$search.'%')
                ->orWhereHas('seller', fn ($seller) => $seller->where('name', 'like', '%'.$search.'%'))))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['area'] ?? null, fn ($q, $area) => $q->where('shipping_city_code', $area))
            ->when($filters['date_from'] ?? null, fn ($q, $from) => $q->whereDate('updated_at', '>=', $from))
            ->when($filters['date_to'] ?? null, fn ($q, $to) => $q->whereDate('updated_at', '<=', $to));
        $groups = [
            'hub' => ['ready_for_pickup', 'picked_up', 'at_sorting_center', 'sorted', 'to_soc5', 'to_soc6'],
            'transit' => ['in_transit_to_hub', 'at_destination_hub', 'assigned_to_rider'],
            'out' => ['out_for_delivery'], 'done' => ['delivered'], 'issue' => ['delivery_failed'],
        ];
        $groupCounts = collect($groups)->map(fn ($statuses) => (clone $filtered)->whereIn('status', $statuses)->count());
        $selectedStage = $filters['stage'] ?? 'all';
        $orders = ($selectedStage === 'all' ? $filtered : $filtered->whereIn('status', $groups[$selectedStage]))
            ->with([
                'seller:id,name',
                'buyer:id,name',
                'courier:id,name',
                'deliveryCourier:id,name',
                'logisticsCenter:id,business_name,municipality,province',
                'destinationLogisticsCenter:id,business_name,municipality,province',
                'nextRouteCheckpoint:id,code,name,municipality,province',
                'routePlan.stops.checkpoint',
            ])
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('logistics.monitoring', compact('orders', 'filters', 'areas', 'availableStatuses', 'groupCounts', 'selectedStage'));
    }

    public function reports(Request $request): View
    {
        return view('logistics.reports', $this->reportData($request));
    }

    public function exportReport(Request $request)
    {
        $format = $request->validate(['format' => 'required|in:pdf,csv'])['format'];
        $data = $this->reportData($request);
        $filename = 'vendo-logistics-'.$request->user()->id.'-'.$data['since']->format('Ymd').'-'.$data['until']->format('Ymd');
        if ($format === 'pdf') {
            return Pdf::loadView('logistics.report-pdf', $data)->setPaper('a4')->download($filename.'.pdf');
        }

        return response()->streamDownload(function () use ($data) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Section', 'Name', 'Parcels']);
            foreach ($data['statusCounts'] as $row) {
                fputcsv($output, ['Status', $row->status, $row->total]);
            }
            foreach ($data['areaCounts'] as $row) {
                fputcsv($output, ['Area', $this->safeCsv($row->shipping_city ?: 'Unknown'), $row->total]);
            }
            foreach ($data['riderCounts'] as $row) {
                fputcsv($output, ['Rider', $this->safeCsv($row->deliveryCourier?->name ?: 'Unassigned'), $row->total]);
            }
            fclose($output);
        }, $filename.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function safeCsv(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/u', $value) ? "'".$value : $value;
    }

    private function reportData(Request $request): array
    {
        $filters = $request->validate([
            'date_from' => 'nullable|date', 'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);
        $centerId = $request->user()->logisticsCenterDetail->id;
        $since = isset($filters['date_from']) ? \Illuminate\Support\Carbon::parse($filters['date_from'])->startOfDay()
            : now()->subDays(30)->startOfDay();
        $until = isset($filters['date_to']) ? \Illuminate\Support\Carbon::parse($filters['date_to'])->endOfDay()
            : now()->endOfDay();
        abort_if($since->greaterThan($until), 422, 'The report end date must be on or after the start date.');
        abort_if($since->diffInDays($until) > 366, 422, 'Choose a report range of at most one year.');
        $scope = fn () => Order::query()
            ->where(fn ($query) => $query->where('logistics_center_id', $centerId)
                ->orWhere('destination_logistics_center_id', $centerId))
            ->whereBetween('updated_at', [$since, $until]);

        $total = $scope()->count();
        $readyForPickup = $scope()->where('status', 'ready_for_pickup')->count();
        $outForDelivery = $scope()->where('status', 'out_for_delivery')->count();
        $delivered = $scope()->whereIn('status', ['delivered', 'completed'])->count();
        $exceptions = $scope()->whereIn('status', ['delivery_failed', 'returned'])->count();
        $statusCounts = $scope()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->orderByDesc('total')
            ->get()
            ->groupBy(fn ($row) => in_array($row->status, ['to_soc5', 'to_soc6'], true)
                ? 'legacy_route_record'
                : $row->status)
            ->map(fn ($rows, $status) => (object) [
                'status' => $status,
                'total' => $rows->sum('total'),
            ])
            ->sortByDesc('total')
            ->values();

        $areaCounts = $scope()->select('shipping_city_code', 'shipping_city', DB::raw('COUNT(*) as total'))
            ->groupBy('shipping_city_code', 'shipping_city')->orderByDesc('total')->get();
        $riderCounts = $scope()->whereNotNull('delivery_courier_id')
            ->select('delivery_courier_id', DB::raw('COUNT(*) as total'))
            ->groupBy('delivery_courier_id')->with('deliveryCourier:id,name')->orderByDesc('total')->get();

        return compact('total', 'readyForPickup', 'outForDelivery', 'delivered', 'exceptions',
            'statusCounts', 'areaCounts', 'riderCounts', 'since', 'until', 'filters');
    }
}
