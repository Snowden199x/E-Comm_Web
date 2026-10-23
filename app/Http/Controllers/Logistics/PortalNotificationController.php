<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Communication\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalNotificationController extends Controller
{
    public function feed(Request $request): JsonResponse
    {
        $this->authorizeUser($request);
        $query = Notification::query()->where('user_id', $request->user()->id)->whereNull('read_at');
        $count = (clone $query)->count();
        $items = (clone $query)->latest()->limit(8)->get()->map(fn ($item) => [
            'id' => $item->id,
            'title' => $item->title,
            'message' => $item->message,
            'open_url' => route('lgp.notifications.open', $item),
        ]);

        return response()->json(['unread_count' => $count, 'items' => $items])
            ->header('Cache-Control', 'private, no-store');
    }

    public function index(Request $request): View
    {
        $this->authorizeUser($request);
        return view('logistics.shared.notifications', [
            'role' => $this->role($request),
            'notifications' => Notification::query()->where('user_id', $request->user()->id)
                ->latest()->paginate(30),
        ]);
    }

    public function open(Request $request, Notification $notification): RedirectResponse
    {
        $this->authorizeUser($request);
        abort_unless((int) $notification->user_id === (int) $request->user()->id, 404);
        if (! $notification->read_at) {
            $notification->update(['read_at' => now()]);
        }
        $path = parse_url((string) $notification->link, PHP_URL_PATH);
        $host = parse_url((string) $notification->link, PHP_URL_HOST);
        if (! is_string($path) || ! str_starts_with($path, '/') || str_starts_with($path, '//')
            || ($host && $host !== $request->getHost())) {
            return redirect()->route('lgp.notifications.index');
        }
        $query = parse_url((string) $notification->link, PHP_URL_QUERY);

        return redirect($path.($query ? '?'.$query : ''));
    }

    public function readAll(Request $request): RedirectResponse
    {
        $this->authorizeUser($request);
        Notification::query()->where('user_id', $request->user()->id)->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back();
    }

    private function authorizeUser(Request $request): void
    {
        $user = $request->user();
        abort_unless($user && in_array($user->role, ['logistics_center', 'province_hub', 'station_hub'], true)
            && $user->status === 'approved' && ! $user->archived_at
            && in_array($user->account_status, [null, 'active'], true), 403);
        if ($user->role === 'logistics_center') {
            abort_unless($user->logisticsCenterDetail?->company?->status === 'approved', 403);
        } else {
            $membership = $user->facilityStaff?->facility;
            abort_unless($membership?->is_active && $membership->company?->status === 'approved', 403);
            $request->attributes->set('logistics_facility', $membership);
        }
    }

    private function role(Request $request): string
    {
        return match ($request->user()->role) {
            'logistics_center' => 'company', 'province_hub' => 'province', default => 'station',
        };
    }
}
