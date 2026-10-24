<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Order;
use App\Models\Profiles\LogisticsFacility;
use App\Models\Profiles\LogisticsTransportTrip;
use App\Models\Profiles\RiderTask;
use App\Models\Profiles\LogisticsParcelException;
use App\Models\Profiles\CrossCompanyManifest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PortalReportController extends Controller
{
    public function index(Request $request): View
    {
        $role = (string) $request->route('portal_role');
        abort_unless(in_array($role, ['company', 'province', 'station'], true), 404);
        $data = $request->validate(['days' => ['nullable', 'in:7,30,90']]);
        $days = (int) ($data['days'] ?? 30);
        $since = now()->subDays($days)->startOfDay();
        $facility = $request->attributes->get('logistics_facility');
        $company = $role === 'company'
            ? $request->user()->logisticsCenterDetail->company
            : $request->attributes->get('logistics_company');
        $facilityIds = $role === 'company'
            ? $company->facilities()->pluck('id')
            : ($role === 'province'
                ? $facility->children()->pluck('id')->prepend($facility->id)
                : collect([$facility->id]));

        $orders = Order::query()->where('created_at', '>=', $since)
            ->where(fn ($query) => $query
                ->whereIn('origin_station_id', $facilityIds)
                ->orWhereIn('destination_station_id', $facilityIds)
                ->orWhereHas('facilityStops', fn ($stops) => $stops->whereIn('facility_id', $facilityIds)));
        $byStatus = (clone $orders)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $total = $byStatus->sum();
        $trips = LogisticsTransportTrip::query()->where('logistics_company_id', $company->id)
            ->where('created_at', '>=', $since);
        if ($role !== 'company') {
            $trips->where(fn ($query) => $query->whereIn('origin_facility_id', $facilityIds)
                ->orWhereIn('destination_facility_id', $facilityIds));
        }
        $tasks = RiderTask::query()->whereIn('station_hub_id', $facilityIds)
            ->where('created_at', '>=', $since);
        $tripRows = (clone $trips)->with(['driver.user', 'vehicle'])->get();
        $manifests = CrossCompanyManifest::query()
            ->where(fn ($query) => $query->where('origin_company_id', $company->id)
                ->orWhere('destination_company_id', $company->id))
            ->where('created_at', '>=', $since);
        if ($role !== 'company') {
            $manifests->where(fn ($query) => $query->whereIn('origin_facility_id', $facilityIds)
                ->orWhereIn('destination_facility_id', $facilityIds));
        }
        $manifestRows = $manifests->with(['driver.user', 'vehicle'])->get();
        $taskRows = (clone $tasks)->with('rider.user')->get();
        $facilityRows = LogisticsFacility::query()->whereIn('id', $facilityIds)->get()->map(function ($item) use ($since) {
            $stops = $item->isProvinceHub() ? $item->children()->pluck('id')->prepend($item->id) : collect([$item->id]);
            $orders = Order::query()->where('created_at', '>=', $since)
                ->whereHas('facilityStops', fn ($query) => $query->whereIn('facility_id', $stops));
            return ['name' => $item->name, 'kind' => $item->isProvinceHub() ? 'Province Hub' : 'Station Hub',
                'parcels' => (clone $orders)->count(),
                'delivered' => (clone $orders)->whereIn('status', ['delivered', 'completed'])->count(),
                'failed' => (clone $orders)->where('status', 'delivery_failed')->count()];
        });
        $runs = $tripRows->map(fn ($trip) => [
            'driver_id' => $trip->driver_id, 'driver_name' => $trip->driver?->user?->name ?? 'Unknown Driver',
            'vehicle_id' => $trip->vehicle_id, 'vehicle_code' => $trip->vehicle?->vehicle_code ?? 'Unknown vehicle',
            'status' => $trip->status, 'expected' => $trip->expected_arrival_at, 'finished' => $trip->arrived_at,
        ])->concat($manifestRows->map(fn ($manifest) => [
            'driver_id' => $manifest->driver_id, 'driver_name' => $manifest->driver?->user?->name ?? 'Unknown Driver',
            'vehicle_id' => $manifest->vehicle_id, 'vehicle_code' => $manifest->vehicle?->vehicle_code ?? 'Unknown vehicle',
            'status' => $manifest->status, 'expected' => $manifest->expected_arrival_at,
            'finished' => $manifest->completed_at,
        ]));
        $isLate = fn ($run) => $run['expected']
            && ($run['status'] === 'completed'
                ? $run['finished']?->greaterThan($run['expected'])
                : (in_array($run['status'], ['scheduled', 'in_transit'], true)
                    && $run['expected']->isPast()));
        $summarizeRuns = fn ($groups, $name) => $groups->map(fn ($group) => [
            'name' => $group->first()[$name], 'trips' => $group->count(),
            'completed' => $group->where('status', 'completed')->count(),
            'late' => $group->filter($isLate)->count(),
        ])->sortByDesc('trips')->values();
        $driverRows = $summarizeRuns($runs->groupBy('driver_id'), 'driver_name');
        $vehicleRows = $summarizeRuns($runs->groupBy('vehicle_id'), 'vehicle_code');
        $riderRows = $taskRows->groupBy('rider_id')->map(fn ($group) => [
            'name' => $group->first()->rider?->user?->name ?? 'Unknown Rider',
            'tasks' => $group->count(), 'completed' => $group->where('status', 'completed')->count(),
            'failed' => $group->where('status', 'failed')->count(),
        ])->sortByDesc('tasks')->values();
        $barangayRows = (clone $orders)->whereNotNull('shipping_barangay')
            ->selectRaw('shipping_barangay, count(*) as total')->groupBy('shipping_barangay')
            ->orderByDesc('total')->pluck('total', 'shipping_barangay');

        $financialSummary = null;
        if ($role === 'company') {
            $financialOrderIds = Order::query()
                ->where(fn ($query) => $query
                    ->whereIn('origin_station_id', $facilityIds)
                    ->orWhereIn('destination_station_id', $facilityIds)
                    ->orWhereHas('facilityStops', fn ($stops) => $stops->whereIn('facility_id', $facilityIds)))
                ->select('orders.id');
            $settlements = DB::table('order_shipping_settlements as settlement')
                ->where('settlement.settled_at', '>=', $since)
                ->whereIn('settlement.order_id', $financialOrderIds);
            $settlementTotals = (clone $settlements)
                ->selectRaw('status, COUNT(*) as orders, SUM(shipping_fee) as shipping_fee')
                ->groupBy('status')->get()->keyBy('status');
            $accruedSettlementIds = (clone $settlements)->where('status', 'accrued')->select('id');

            $riderAccruals = DB::table('order_shipping_earning_lines as earning')
                ->join('logistics_facilities as facility', 'facility.id', '=', 'earning.facility_id')
                ->where('facility.logistics_company_id', $company->id)
                ->whereIn('earning.settlement_id', $accruedSettlementIds)
                ->selectRaw('COUNT(*) as earning_line_count, COALESCE(SUM(earning.amount), 0) as amount')
                ->first();

            $singleCompanyRemainder = DB::table('order_shipping_settlements as settlement')
                ->where('settlement.settled_at', '>=', $since)->where('settlement.status', 'accrued')
                ->whereIn('settlement.order_id', $financialOrderIds)
                ->whereExists(fn ($query) => $query->selectRaw('1')->from('order_facility_stops as own_stop')
                    ->join('logistics_facilities as own_facility', 'own_facility.id', '=', 'own_stop.facility_id')
                    ->whereColumn('own_stop.order_id', 'settlement.order_id')
                    ->where('own_facility.logistics_company_id', $company->id))
                ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('order_facility_stops as other_stop')
                    ->leftJoin('logistics_facilities as other_facility', 'other_facility.id', '=', 'other_stop.facility_id')
                    ->whereColumn('other_stop.order_id', 'settlement.order_id')
                    ->where(fn ($other) => $other->whereNull('other_facility.logistics_company_id')
                        ->orWhere('other_facility.logistics_company_id', '!=', $company->id)))
                ->sum('settlement.logistics_remainder');

            $crossCompanyRemainder = DB::table('order_shipping_settlements as settlement')
                ->where('settlement.settled_at', '>=', $since)->where('settlement.status', 'accrued')
                ->whereIn('settlement.order_id', $financialOrderIds)
                ->whereExists(fn ($query) => $query->selectRaw('1')->from('order_facility_stops as own_stop')
                    ->join('logistics_facilities as own_facility', 'own_facility.id', '=', 'own_stop.facility_id')
                    ->whereColumn('own_stop.order_id', 'settlement.order_id')
                    ->where('own_facility.logistics_company_id', $company->id))
                ->whereExists(fn ($query) => $query->selectRaw('1')->from('order_facility_stops as other_stop')
                    ->join('logistics_facilities as other_facility', 'other_facility.id', '=', 'other_stop.facility_id')
                    ->whereColumn('other_stop.order_id', 'settlement.order_id')
                    ->where('other_facility.logistics_company_id', '!=', $company->id))
                ->sum('settlement.logistics_remainder');

            $financialSummary = [
                'settled_orders' => (int) $settlementTotals->sum('orders'),
                'shipping_fees' => (float) $settlementTotals->sum('shipping_fee'),
                'rider_accruals' => (float) ($riderAccruals->amount ?? 0),
                'rider_accrual_lines' => (int) ($riderAccruals->earning_line_count ?? 0),
                'company_pool' => (float) $singleCompanyRemainder,
                'cross_company_pool' => (float) $crossCompanyRemainder,
                'review_orders' => (int) ($settlementTotals['review']->orders ?? 0),
                'review_shipping' => (float) ($settlementTotals['review']->shipping_fee ?? 0),
            ];
        }

        return view('logistics.shared.live-reports', [
            'role' => $role, 'days' => $days, 'total' => $total,
            'delivered' => ($byStatus['delivered'] ?? 0) + ($byStatus['completed'] ?? 0),
            'failed' => $byStatus['delivery_failed'] ?? 0,
            'inTransit' => ($byStatus['in_transit_to_hub'] ?? 0) + ($byStatus['picked_up'] ?? 0),
            'statusRows' => $byStatus->sortDesc(), 'facilityRows' => $facilityRows,
            'tripTotal' => $tripRows->count(),
            'tripCompleted' => (clone $trips)->where('status', 'completed')->count(),
            'tripActive' => (clone $trips)->whereIn('status', ['scheduled', 'in_transit'])->count(),
            'pickupTasks' => (clone $tasks)->where('type', 'pickup')->count(),
            'deliveryTasks' => (clone $tasks)->where('type', 'delivery')->count(),
            'completedTasks' => (clone $tasks)->where('status', 'completed')->count(),
            'delayedTrips' => $runs->filter($isLate)->count(),
            'manifestTotal' => $manifestRows->count(),
            'manifestCompleted' => $manifestRows->where('status', 'completed')->count(),
            'openExceptions' => LogisticsParcelException::query()->whereIn('facility_id', $facilityIds)
                ->where('status', 'open')->count(),
            'driverRows' => $driverRows, 'vehicleRows' => $vehicleRows,
            'riderRows' => $riderRows, 'barangayRows' => $barangayRows,
            'financialSummary' => $financialSummary,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $report = $this->index($request)->getData();
        $filename = 'vendo-logistics-'.$report['role'].'-'.$report['days'].'-days-'.today()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($report) {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Vendo Logistics operational report', ucfirst($report['role'])]);
            fputcsv($output, ['Period', 'Last '.$report['days'].' days']);
            fputcsv($output, ['Generated', now()->toDateTimeString()]);
            foreach (['total' => 'Parcels', 'delivered' => 'Delivered', 'failed' => 'Failed deliveries',
                'inTransit' => 'In transit', 'tripTotal' => 'Trips', 'tripCompleted' => 'Completed trips',
                'delayedTrips' => 'Late trips and manifests', 'manifestTotal' => 'Cross-company manifests',
                'manifestCompleted' => 'Completed cross-company manifests', 'openExceptions' => 'Open exceptions',
                'completedTasks' => 'Completed Rider tasks'] as $key => $label) {
                fputcsv($output, [$label, $report[$key]]);
            }
            if ($report['financialSummary']) {
                $this->csvSection($output, 'Shipping settlement summary (PHP)', ['Measure', 'Amount / count'], collect([
                    ['Orders with settlement records', $report['financialSummary']['settled_orders']],
                    ['Full order shipping fees on network parcels', number_format($report['financialSummary']['shipping_fees'], 2, '.', '')],
                    ['Rider and Driver accruals at company facilities', number_format($report['financialSummary']['rider_accruals'], 2, '.', '')],
                    ['Logistics pool on single-company routes', number_format($report['financialSummary']['company_pool'], 2, '.', '')],
                    ['Cross-company Logistics pool awaiting allocation', number_format($report['financialSummary']['cross_company_pool'], 2, '.', '')],
                    ['Settlement review orders', $report['financialSummary']['review_orders']],
                    ['Shipping fees held for review', number_format($report['financialSummary']['review_shipping'], 2, '.', '')],
                ]));
            }
            $this->csvSection($output, 'Parcel stages', ['Status', 'Parcels'],
                $report['statusRows']->map(fn ($count, $status) => [$status, $count]));
            $this->csvSection($output, 'Facilities', ['Facility', 'Type', 'Parcels', 'Delivered', 'Failed'],
                $report['facilityRows']->map(fn ($row) => array_values($row)));
            $this->csvSection($output, 'Transport Drivers', ['Driver', 'Trips', 'Completed', 'Late'],
                $report['driverRows']->map(fn ($row) => array_values($row)));
            $this->csvSection($output, 'Vehicles', ['Vehicle', 'Trips', 'Completed', 'Late'],
                $report['vehicleRows']->map(fn ($row) => array_values($row)));
            $this->csvSection($output, 'Riders', ['Rider', 'Tasks', 'Completed', 'Failed'],
                $report['riderRows']->map(fn ($row) => array_values($row)));
            $this->csvSection($output, 'Delivery barangays', ['Barangay', 'Parcels'],
                $report['barangayRows']->map(fn ($count, $name) => [$name, $count]));
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }

    private function csvSection($output, string $title, array $headings, $rows): void
    {
        fputcsv($output, []);
        fputcsv($output, [$title]);
        fputcsv($output, $headings);
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
    }
}
