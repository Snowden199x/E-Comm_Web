<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RiderEarningsController extends Controller
{
    public function rider(Request $request): JsonResponse
    {
        return $this->payload((int) $request->user()->id);
    }

    public function transportDriver(Request $request): JsonResponse
    {
        return $this->payload((int) $request->user()->id);
    }

    private function payload(int $userId): JsonResponse
    {
        $base = DB::table('order_shipping_earning_lines as line')
            ->join('order_shipping_settlements as settlement', 'settlement.id', '=', 'line.settlement_id')
            ->join('orders', 'orders.id', '=', 'settlement.order_id')
            ->where('line.rider_user_id', $userId)->where('settlement.status', 'accrued')
            // Delivered or completed: the accrual must not disappear once the Buyer confirms receipt.
            ->whereIn('orders.status', Order::SALES_STATUSES);
        $summary = (clone $base)->selectRaw('COALESCE(SUM(line.amount), 0) as accrued_total, COUNT(*) as hub_credits')->first();
        $rows = (clone $base)->leftJoin('logistics_facilities as facility', 'facility.id', '=', 'line.facility_id')
            ->orderByDesc('line.created_at')->limit(100)
            ->get(['line.id', 'line.amount', 'line.created_at', 'line.route_position', 'facility.name as facility_name', 'orders.id as order_id'])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'order_number' => Order::numberFor((int) $row->order_id),
                'hub' => $row->facility_name,
                'route_position' => (int) $row->route_position,
                'amount' => number_format((float) $row->amount, 2, '.', ''),
                'earned_at' => $row->created_at,
                'status' => 'accrued',
                'type' => 'delivery',
            ]);

        // Return pickups pay a separate fixed fee after the parcel is collected, not on delivery.
        $returnBase = DB::table('order_return_pickup_earnings as earning')
            ->where('earning.rider_user_id', $userId);
        $returnSummary = (clone $returnBase)->selectRaw('COALESCE(SUM(amount), 0) as total, COUNT(*) as lines')->first();
        $returnRows = (clone $returnBase)->leftJoin('logistics_facilities as facility', 'facility.id', '=', 'earning.facility_id')
            ->orderByDesc('earning.created_at')->limit(100)
            ->get(['earning.id', 'earning.amount', 'earning.created_at', 'facility.name as facility_name', 'earning.order_id'])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'order_number' => Order::numberFor((int) $row->order_id),
                'hub' => $row->facility_name,
                'route_position' => null,
                'amount' => number_format((float) $row->amount, 2, '.', ''),
                'earned_at' => $row->created_at,
                'status' => 'accrued',
                'type' => 'return_pickup',
            ]);

        $earnings = $rows->concat($returnRows)
            ->sortByDesc(fn ($row) => (string) $row['earned_at'])->take(100)->values();

        return response()->json([
            'summary' => [
                'accrued_total' => number_format(((float) $summary->accrued_total + (float) $returnSummary->total), 2, '.', ''),
                'hub_credits' => (int) $summary->hub_credits,
                'return_pickups' => (int) $returnSummary->lines,
            ],
            'earnings' => $earnings,
        ]);
    }
}
