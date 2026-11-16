<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Communication\Notification;
use App\Models\Ecommerce\Order;
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

        return redirect($this->destination($request, $notification));
    }

    /**
     * Where a notification should open for this user.
     * - A link into another portal area (company, province, station) is moved to the same page in the user's own area.
     * - A link to the older Main Hub pages (/logistics/dispatch and so on) opens the portal tracking page for that parcel.
     * - Anything unknown opens the notifications page instead of an error page.
     */
    private function destination(Request $request, Notification $notification): string
    {
        $fallback = route('lgp.notifications.index');
        $link = (string) $notification->link;
        $path = parse_url($link, PHP_URL_PATH);
        $host = parse_url($link, PHP_URL_HOST);
        if (! is_string($path) || ! str_starts_with($path, '/') || str_starts_with($path, '//')
            || ($host && $host !== $request->getHost())) {
            return $fallback;
        }
        $query = parse_url($link, PHP_URL_QUERY);
        $fragment = parse_url($link, PHP_URL_FRAGMENT);
        $suffix = ($query ? '?'.$query : '').($fragment ? '#'.$fragment : '');

        $area = ['logistics_center' => 'company', 'province_hub' => 'province', 'station_hub' => 'station'][$request->user()->role] ?? null;
        if (! $area) {
            return $fallback;
        }

        if (preg_match('~^/logistics/portal/(company|province|station)(/.*)?$~', $path, $match)) {
            if ($match[1] !== $area) {
                $path = '/logistics/portal/'.$area.($match[2] ?? '');
            }

            return $this->routeExists($path) ? url($path.$suffix) : $fallback;
        }

        if (preg_match('~^/logistics/portal/(notifications|account)(/.*)?$~', $path)) {
            return $this->routeExists($path) ? url($path.$suffix) : $fallback;
        }

        // Older Main Hub pages: open the portal tracking page, filtered to the parcel when the title names the order.
        if (preg_match('~^/logistics/(dashboard|dispatch|incoming-parcels|parcel-sorting|delivery-assignments|delivery-monitoring|riders|facilities(/\d+)?|reports)/?$~', $path)) {
            $tracking = null;
            if (preg_match('/[A-Z]{2,}-\d{4}-\d+/', (string) $notification->title, $number)) {
                $tracking = Order::query()->where('order_number', $number[0])->value('tracking_number');
            }

            return route('lgp.'.$area.'.tracking', $tracking ? ['q' => $tracking] : []);
        }

        return $fallback;
    }

    private function routeExists(string $path): bool
    {
        try {
            app('router')->getRoutes()->match(Request::create($path, 'GET'));

            return true;
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException) {
            return false;
        }
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
