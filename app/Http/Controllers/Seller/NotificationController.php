<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Communication\Notification;
use App\Models\Communication\PlatformPolicy;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
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

        return view('seller.notifications.index', compact('notifications', 'unreadCount', 'filter', 'selectedNotification', 'policy'));
    }

    public function recent(Request $request)
    {
        $notifications = $this->owned($request)->latest()->limit(10)->get();
        $unreadCount = $this->owned($request)->whereNull('read_at')->count();

        return response()->json([
            'unread_count' => $unreadCount,
            'latest_id' => $notifications->first()?->id,
            'html' => view('seller.notifications.recent', compact('notifications'))->render(),
            'dashboard_html' => view('seller.notifications.dashboard', compact('notifications'))->render(),
        ]);
    }

    public function open(Request $request, int $notification)
    {
        $record = $this->owned($request)->findOrFail($notification);
        if (! $record->read_at) {
            $record->update(['read_at' => now()]);
        }

        $destination = self::destination($record);
        if (in_array($record->type, ['account_warning', 'platform_announcement'], true)
            || $destination === route('seller.notifications.index')) {
            return redirect()->route('seller.notifications.index', ['show' => $record->id]);
        }
        return redirect()->to($destination);
    }

    public function markRead(Request $request, int $notification)
    {
        $record = $this->owned($request)->findOrFail($notification);
        if (! $record->read_at) {
            $record->update(['read_at' => now()]);
        }

        return back()->with('success', 'Notification marked as read.');
    }

    public function readAll(Request $request)
    {
        $this->owned($request)->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }

    public static function destination(Notification $notification): string
    {
        $path = parse_url((string) $notification->link, PHP_URL_PATH);
        $query = parse_url((string) $notification->link, PHP_URL_QUERY);
        if (! is_string($path) || ! preg_match('~^/seller/(dashboard|orders|products|shipments|completed-orders|feedback|messages|reports|account|notifications)(/|$)~', $path)) {
            return route('seller.notifications.index');
        }

        return url($path.($query ? '?'.$query : ''));
    }

    private function owned(Request $request)
    {
        return Notification::query()->where('user_id', $request->user()->id);
    }
}
