<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
            ->where('orders.status', 'delivered');
        $summary = (clone $base)->selectRaw('COALESCE(SUM(line.amount), 0) as accrued_total, COUNT(*) as hub_credits')->first();
        $rows = (clone $base)->leftJoin('logistics_facilities as facility', 'facility.id', '=', 'line.facility_id')
            ->orderByDesc('line.created_at')->limit(100)
            ->get(['line.id', 'line.amount', 'line.created_at', 'line.route_position', 'facility.name as facility_name', 'orders.number as order_number']);

        return response()->json([
            'summary' => [
                'accrued_total' => number_format((float) $summary->accrued_total, 2, '.', ''),
                'hub_credits' => (int) $summary->hub_credits,
            ],
            'earnings' => $rows->map(fn ($row) => [
                'id' => (int) $row->id,
                'order_number' => $row->order_number,
                'hub' => $row->facility_name,
                'route_position' => (int) $row->route_position,
                'amount' => number_format((float) $row->amount, 2, '.', ''),
                'earned_at' => $row->created_at,
                'status' => 'accrued',
            ])->values(),
        ]);
    }
}
