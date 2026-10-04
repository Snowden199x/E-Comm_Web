<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Communication\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $this->owned($request)->latest()->paginate(20);
        $unreadCount = $this->owned($request)->whereNull('read_at')->count();

        return view('logistics.notifications', compact('notifications', 'unreadCount'));
    }

    public function recent(Request $request)
    {
        return response()->json([
            'unread_count' => $this->owned($request)->whereNull('read_at')->count(),
        ]);
    }

    public function open(Request $request, int $notification)
    {
        $record = $this->owned($request)->findOrFail($notification);
        if (! $record->read_at) {
            $record->update(['read_at' => now()]);
        }
        $path = parse_url((string) $record->link, PHP_URL_PATH);
        if (! is_string($path) || ! preg_match('~^/logistics/(dashboard|riders|incoming-parcels|parcel-sorting|delivery-assignments|delivery-monitoring|reports)(/|$)~', $path)) {
            return redirect()->route('logistics.notifications.index');
        }

        return redirect()->to(url($path));
    }

    public function readAll(Request $request)
    {
        $this->owned($request)->whereNull('read_at')->update(['read_at' => now()]);

        return back();
    }

    private function owned(Request $request)
    {
        return Notification::query()->where('user_id', $request->user()->id);
    }
}
