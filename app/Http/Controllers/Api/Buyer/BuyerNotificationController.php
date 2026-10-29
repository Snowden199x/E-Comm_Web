<?php

namespace App\Http\Controllers\Api\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Communication\Notification;
use App\Services\BuyerApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** The signed-in Buyer's notifications. Scoped to user_id; no other Buyer's records are readable. */
class BuyerNotificationController extends Controller
{
    public function __construct(private BuyerApiPresenter $presenter) {}

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate(['filter' => ['nullable', Rule::in(['all', 'unread'])]]);
        $owned = Notification::query()->where('user_id', $request->user()->id);

        $notifications = (clone $owned)
            ->when(($data['filter'] ?? 'all') === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->latest()->paginate(20);

        return response()->json([
            'data' => collect($notifications->items())->map(fn (Notification $item) => $this->presenter->notification($item))->values()->all(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'total' => $notifications->total(),
            ],
            'unread_count' => (clone $owned)->whereNull('read_at')->count(),
        ]);
    }

    public function markRead(Request $request, int $notification): JsonResponse
    {
        $record = Notification::query()->where('user_id', $request->user()->id)->findOrFail($notification);
        if (! $record->read_at) {
            $record->update(['read_at' => now()]);
        }

        return response()->json([
            'notification' => $this->presenter->notification($record->refresh()),
            'unread_count' => Notification::query()->where('user_id', $request->user()->id)->whereNull('read_at')->count(),
        ]);
    }

    public function readAll(Request $request): JsonResponse
    {
        Notification::query()->where('user_id', $request->user()->id)->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'All notifications marked as read.', 'unread_count' => 0]);
    }
}
