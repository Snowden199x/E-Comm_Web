<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Communication\Notification;
use App\Models\Communication\PlatformPolicy;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeBuyer($request);
        $validated = $request->validate(['filter' => ['nullable', Rule::in(['all', 'unread'])]]);
        $filter = $validated['filter'] ?? 'all';
        $notifications = $this->owned($request)
            ->when($filter === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->latest()->paginate(15)->withQueryString();
        $unreadCount = $this->owned($request)->whereNull('read_at')->count();
        $selectedNotification = $request->query('show')
            ? $this->owned($request)->findOrFail($request->query('show')) : null;
        $policy = null;
        if ($selectedNotification?->title === 'Policy Updated') {
            $policy = PlatformPolicy::query()->get()->first(
                fn (PlatformPolicy $item) => str_starts_with($selectedNotification->message, $item->name.' has been updated')
            );
        }

        return view('buyer.notifications.index', compact('notifications', 'unreadCount', 'filter', 'selectedNotification', 'policy'));
    }

    public function recent(Request $request)
    {
        $this->authorizeBuyer($request);
        $notifications = $this->owned($request)->latest()->limit(5)->get();

        return response()->json([
            'unread_count' => $this->owned($request)->whereNull('read_at')->count(),
            'latest_id' => $notifications->first()?->id,
            'html' => view('buyer.notifications.recent', compact('notifications'))->render(),
        ]);
    }

    public function open(Request $request, int $notification)
    {
        $this->authorizeBuyer($request);
        $record = $this->owned($request)->findOrFail($notification);
        if (! $record->read_at) $record->update(['read_at' => now()]);
        $destination = $this->destination($record);
        if (in_array($record->type, ['account_warning', 'platform_announcement'], true)
            || $destination === route('buyer.notifications.index')) {
            return redirect()->route('buyer.notifications.index', ['show' => $record->id]);
        }
        return redirect()->to($destination);
    }

    public function markRead(Request $request, int $notification)
    {
        $this->authorizeBuyer($request);
        $record = $this->owned($request)->findOrFail($notification);
        if (! $record->read_at) $record->update(['read_at' => now()]);
        return back();
    }

    public function readAll(Request $request)
    {
        $this->authorizeBuyer($request);
        $this->owned($request)->whereNull('read_at')->update(['read_at' => now()]);
        return back();
    }

    private function authorizeBuyer(Request $request): void
    {
        abort_unless($request->user()?->role === 'buyer', 403);
    }

    private function owned(Request $request)
    {
        return Notification::query()->where('user_id', $request->user()->id);
    }

    private function destination(Notification $notification): string
    {
        $path = parse_url((string) $notification->link, PHP_URL_PATH);
        $query = parse_url((string) $notification->link, PHP_URL_QUERY);
        if (! is_string($path) || ! preg_match('~^/buyer/(dashboard|orders|products|sellers|messages|account|notifications)(/|$)~', $path)) {
            return route('buyer.notifications.index');
        }
        return url($path.($query ? '?'.$query : ''));
    }
}
