<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OperationsController extends Controller
{
    public function monitoring(Request $request): View
    {
        $centerId = $request->user()->logisticsCenterDetail->id;
        $orders = Order::query()
            ->where(fn ($query) => $query->where('logistics_center_id', $centerId)
                ->orWhere('destination_logistics_center_id', $centerId))
            ->whereIn('status', [
                'ready_for_pickup', 'picked_up', 'at_sorting_center', 'sorted', 'to_soc5', 'to_soc6',
                'in_transit_to_hub', 'at_destination_hub', 'assigned_to_rider', 'out_for_delivery',
                'delivered', 'delivery_failed',
            ])
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

        return view('logistics.monitoring', compact('orders'));
    }

    public function reports(Request $request): View
    {
        $centerId = $request->user()->logisticsCenterDetail->id;
        $since = now()->subDays(30)->startOfDay();
        $scope = fn () => Order::query()
            ->where(fn ($query) => $query->where('logistics_center_id', $centerId)
                ->orWhere('destination_logistics_center_id', $centerId))
            ->where('updated_at', '>=', $since);

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

        return view('logistics.reports', compact(
            'total', 'readyForPickup', 'outForDelivery', 'delivered', 'exceptions', 'statusCounts', 'since'
        ));
    }
}
