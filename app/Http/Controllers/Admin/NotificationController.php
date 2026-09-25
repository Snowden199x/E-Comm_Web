<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Communication\Notification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class NotificationController extends Controller
{
    private function adminNotifications()
    {
        return Notification::query()->whereNull('user_id');
    }

    public function index(Request $request): View
    {
        $filter = $request->validate(['filter' => ['nullable', Rule::in(['all', 'unread'])]])['filter'] ?? 'all';
        $notifications = $this->adminNotifications()
            ->when($filter === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->latest()->paginate(15)->withQueryString();
        $unreadCount = $this->adminNotifications()->whereNull('read_at')->count();
        $selectedNotification = $request->query('show')
            ? $this->adminNotifications()->findOrFail($request->query('show')) : null;
        $policy = null;

        return view('admin.notifications.index', compact('notifications', 'unreadCount', 'filter', 'selectedNotification', 'policy'));
    }

    public function recent()
    {
        $notifications = $this->adminNotifications()->latest()->limit(5)->get();

        return response()->json([
            'html' => view('admin.notifications.recent', compact('notifications'))->render(),
            'latest_id' => $this->adminNotifications()->max('id') ?? 0,
            'unread_count' => $this->adminNotifications()->whereNull('read_at')->count(),
        ]);
    }

    public function markRead(int $notification)
    {
        $record = $this->adminNotifications()->findOrFail($notification);
        $record->update(['read_at' => $record->read_at ?? now()]);

        return back();
    }

    public function readAll()
    {
        $this->adminNotifications()->whereNull('read_at')->update(['read_at' => now()]);

        return back();
    }

    public function open(int $notification)
    {
        $record = $this->adminNotifications()->findOrFail($notification);
        $record->update(['read_at' => $record->read_at ?? now()]);
        $path = parse_url((string) $record->link, PHP_URL_PATH);
        $query = parse_url((string) $record->link, PHP_URL_QUERY);

        if (! is_string($path) || $path === '/admin/notifications'
            || ! preg_match('~^/admin(?:/|$)~', $path)) {
            return redirect()->route('admin.notifications.index', ['show' => $record->id]);
        }
        return redirect()->to(url($path.($query ? '?'.$query : '')));
    }
}
