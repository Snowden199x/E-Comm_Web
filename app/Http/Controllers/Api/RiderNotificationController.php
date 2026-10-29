<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Communication\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Notifications for the authenticated Rider only. */
class RiderNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $owned = Notification::query()->where('user_id', $request->user()->id);
        $notifications = (clone $owned)->latest()->limit(50)->get();

        return response()->json([
            'data' => $notifications->map(fn (Notification $item) => [
                'id' => $item->id,
                'type' => $item->type,
                'title' => $item->title,
                'message' => $item->message,
                'read_at' => $item->read_at?->toIso8601String(),
                'created_at' => $item->created_at?->toIso8601String(),
                'time_ago' => $item->created_at?->diffForHumans(),
            ])->values(),
            'unread_count' => (clone $owned)->whereNull('read_at')->count(),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function markRead(Request $request, int $notification): JsonResponse
    {
        $record = Notification::query()->where('user_id', $request->user()->id)->findOrFail($notification);
        if (! $record->read_at) {
            $record->update(['read_at' => now()]);
        }

        return response()->json(['unread_count' => Notification::query()
            ->where('user_id', $request->user()->id)->whereNull('read_at')->count()]);
    }

    public function readAll(Request $request): JsonResponse
    {
        Notification::query()->where('user_id', $request->user()->id)->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['unread_count' => 0]);
    }
}
