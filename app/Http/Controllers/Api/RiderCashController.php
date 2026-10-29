<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RiderCashController extends Controller
{
    /** COD cash the authenticated Rider collected, and how much of it they still hold (not yet received by the Station Hub). */
    public function show(Request $request): JsonResponse
    {
        $base = DB::table('order_cod_collections as collection')
            ->join('orders', 'orders.id', '=', 'collection.order_id')
            ->where('collection.rider_user_id', (int) $request->user()->id);

        $held = (clone $base)->where('orders.money_status', 'collected_by_rider')
            ->selectRaw('COALESCE(SUM(collection.amount), 0) as cash_on_hand, COUNT(*) as parcels')->first();

        $rows = (clone $base)->orderByDesc('collection.collected_at')->limit(100)
            ->get(['collection.id', 'collection.amount', 'collection.collected_at', 'orders.id as order_id', 'orders.money_status']);

        return response()->json([
            'summary' => [
                'cash_on_hand' => number_format((float) $held->cash_on_hand, 2, '.', ''),
                'parcels_on_hand' => (int) $held->parcels,
            ],
            'collections' => $rows->map(fn ($row) => [
                'id' => (int) $row->id,
                'order_number' => Order::numberFor((int) $row->order_id),
                'amount' => number_format((float) $row->amount, 2, '.', ''),
                'collected_at' => $row->collected_at,
                'money_status' => $row->money_status,
                'money_status_label' => Order::MONEY_STATUSES[$row->money_status] ?? $row->money_status,
            ])->values(),
        ]);
    }
}
