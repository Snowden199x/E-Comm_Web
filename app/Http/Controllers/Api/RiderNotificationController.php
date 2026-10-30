<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Communication\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RiderNotificationController extends Controller
{
    /**
     * Notifications addressed to the signed-in Rider.
     *
     * The app polls this while it is open, so `since` returns only rows newer
     * than the highest id it already has. `latest_id` lets the app move its
     * watermark forward without re-reading everything.
     */
    public function index(Request $request): JsonResponse
    {
        $rider = $request->user();
        $data = $request->validate(['since' => ['nullable', 'integer', 'min:0']]);
        $since = (int) ($data['since'] ?? 0);

        $query = Notification::query()->where('user_id', $rider->id);
        $items = (clone $query)
            ->when($since > 0, fn ($notifications) => $notifications->where('id', '>', $since))
            ->latest('id')->limit(50)->get()
            ->map(fn (Notification $notification) => [
                'id' => $notification->id,
                'type' => $notification->type,
                'title' => $notification->title,
                'message' => $notification->message,
                'read_at' => $notification->read_at?->toIso8601String(),
                'time_ago' => $notification->timeAgo(),
                'created_at' => $notification->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'data' => $items,
            'unread_count' => (clone $query)->whereNull('read_at')->count(),
            'latest_id' => (int) ((clone $query)->max('id') ?? 0),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function read(Request $request, Notification $notification): JsonResponse
    {
        abort_unless((int) $notification->user_id === (int) $request->user()->id, 404);
        if (! $notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json([
            'ok' => true,
            'unread_count' => $this->unreadCount($request),
        ]);
    }

    public function readAll(Request $request): JsonResponse
    {
        Notification::query()->where('user_id', $request->user()->id)
            ->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['ok' => true, 'unread_count' => 0]);
    }

    private function unreadCount(Request $request): int
    {
        return Notification::query()->where('user_id', $request->user()->id)
            ->whereNull('read_at')->count();
    }
}
