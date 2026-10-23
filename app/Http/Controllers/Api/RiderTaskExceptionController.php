<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Communication\Notification;
use App\Models\Profiles\RiderTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RiderTaskExceptionController extends Controller
{
    public function store(Request $request, RiderTask $task): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'in:damaged,recipient_unavailable,unsafe_location,vehicle_issue,other'],
            'details' => ['required', 'string', 'min:5', 'max:1000'],
        ]);
        $rider = $request->user();
        $detail = $rider->courierDetail;

        abort_unless($detail && (int) $task->rider_id === (int) $detail->id
            && (int) $task->station_hub_id === (int) $detail->station_hub_id, 404);

        $task = DB::transaction(function () use ($task, $rider, $data) {
            $task = RiderTask::query()->with('station.company.logisticsCenter')
                ->lockForUpdate()->findOrFail($task->id);
            abort_unless(in_array($task->status, ['assigned', 'in_progress'], true), 409,
                'Only an active task can report an exception.');
            $order = $task->order()->lockForUpdate()->firstOrFail();
            $assignedUser = $task->type === 'pickup' ? $order->courier_id : $order->delivery_courier_id;
            abort_unless((int) $assignedUser === (int) $rider->id, 403,
                'This task is no longer assigned to you.');
            abort_if($order->logisticsExceptions()->where('status', 'open')->exists(), 409,
                'This parcel already has an open Logistics exception.');

            $task->update(['status' => 'failed', 'failure_reason' => $data['reason'].': '.$data['details']]);
            $order->statusEvents()->create([
                'user_id' => $rider->id,
                'from_status' => $order->status,
                'to_status' => $order->status,
                'note' => 'Rider reported a task exception ('.str_replace('_', ' ', $data['reason']).'): '.$data['details'],
            ]);
            $note = 'Rider '.$rider->name.' reported an issue on '.$task->type.' task for '.$order->number.'.';
            $recipients = array_unique(array_filter([
                $task->station?->staff?->user_id,
                $task->station?->company?->logisticsCenter?->user_id,
                $order->seller_id,
                $order->buyer_id,
            ]));
            foreach ($recipients as $userId) {
                $link = (int) $userId === (int) $order->seller_id ? route('seller.orders.show', $order)
                    : ((int) $userId === (int) $order->buyer_id ? route('buyer.orders.show', $order)
                        : route('lgp.station.tasks'));
                Notification::create([
                    'user_id' => $userId,
                    'type' => 'shipment_update',
                    'title' => 'Rider task needs attention '.$order->number,
                    'message' => $note,
                    'link' => $link,
                ]);
            }

            return $task->fresh();
        }, 3);

        return response()->json([
            'message' => 'Issue sent to the Station Hub. This task is paused until Logistics reassigns it.',
            'task' => ['id' => $task->id, 'status' => $task->status, 'failure_reason' => $task->failure_reason],
        ], 201);
    }
}
