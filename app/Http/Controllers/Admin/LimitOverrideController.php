<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActionLog;
use App\Models\Communication\Notification;
use App\Models\Ecommerce\Order;
use App\Services\DeliveryCutoff;
use App\Services\OrderReturnService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Admin overrides for the two money/lifecycle limits: the 7-day Buyer return window and the two delivery
 * attempts. Every override is locked, recorded in the order timeline with the Admin and the reason, and
 * notified to the affected side.
 */
class LimitOverrideController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $q = trim($filters['q'] ?? '');

        $base = Order::query()->with(['buyer:id,name', 'seller:id,name', 'latestReturnRequest']);

        if ($q !== '') {
            $id = preg_match('/^(?:#?VN-)?0*(\d+)$/i', $q, $match) ? (int) $match[1] : null;
            $orders = $base->when($id !== null, fn ($query) => $query->where('id', $id))
                ->when($id === null, fn ($query) => $query->where('tracking_number', 'like', '%'.$q.'%'))
                ->latest('id')->limit(20)->get();
        } else {
            $orders = $base->where(function ($query) {
                $query->where('delivery_attempts', '>', 0)
                    ->orWhere('delivery_extra_attempts', '>', 0)
                    ->orWhereNotNull('return_window_until')
                    ->orWhereHas('returnRequests');
            })->latest('id')->limit(30)->get();
        }

        return view('admin.limit-overrides.index', [
            'orders' => $orders,
            'q' => $q,
            'maxAttempts' => DeliveryCutoff::MAX_DELIVERY_ATTEMPTS,
            'defaultWindowDays' => OrderReturnService::RETURN_WINDOW_DAYS,
            'windows' => $orders->mapWithKeys(fn (Order $order) => [
                $order->id => app(OrderReturnService::class)->windowClosesAt($order),
            ]),
        ]);
    }

    public function grantAttempt(Request $request, int $order)
    {
        $data = $request->validate([
            'note' => ['required', 'string', 'min:5', 'max:450'],
        ], [
            'note.required' => 'Add a short reason. It is kept in the order timeline.',
            'note.min' => 'Add a little more detail for the audit note.',
        ]);

        $admin = $request->user('admin');

        DB::transaction(function () use ($order, $data, $admin) {
            $record = Order::query()->lockForUpdate()->findOrFail($order);
            abort_unless(in_array($record->status, ['delivery_failed', 'assigned_to_rider'], true), 409,
                'Only a parcel whose attempt failed or is scheduled can be granted another attempt.');
            abort_unless((int) $record->delivery_attempts >= 1, 409, 'This parcel has no failed attempt to extend.');
            abort_if((int) $record->delivery_extra_attempts >= 3, 409, 'This parcel already received the maximum extra attempts.');

            $record->forceFill([
                'delivery_extra_attempts' => (int) $record->delivery_extra_attempts + 1,
                'delivery_failed_at' => now(),
                'limit_override_by' => $admin?->id,
                'limit_override_at' => now(),
                'limit_override_note' => $data['note'],
            ])->save();

            $max = DeliveryCutoff::MAX_DELIVERY_ATTEMPTS + (int) $record->delivery_extra_attempts;
            $record->statusEvents()->create([
                'user_id' => $admin?->id,
                'from_status' => $record->status,
                'to_status' => $record->status,
                'note' => 'Admin '.(($admin?->name) ?? 'staff').' granted one more delivery attempt ('.$max.' allowed): '.$data['note'],
            ]);

            Notification::create([
                'user_id' => $record->buyer_id,
                'type' => 'order_update',
                'title' => $record->number.': One more delivery attempt',
                'message' => 'Our team allowed one more delivery attempt for this parcel. Keep your phone reachable.',
                'link' => route('buyer.orders.show', $record),
            ]);
            if ($record->delivery_courier_id) {
                Notification::create([
                    'user_id' => $record->delivery_courier_id,
                    'type' => 'shipment_update',
                    'title' => 'Extra attempt granted '.$record->number,
                    'message' => 'Admin granted one more delivery attempt. Book it with the reschedule action.',
                ]);
            }
            if ($record->destinationLogisticsCenter?->user_id) {
                Notification::create([
                    'user_id' => $record->destinationLogisticsCenter->user_id,
                    'type' => 'shipment_update',
                    'title' => 'Extra attempt granted '.$record->number,
                    'message' => 'Admin granted one more delivery attempt. Schedule it from the delivery queue.',
                    'link' => route('lgp.station.delivery-queue'),
                ]);
            }

            AdminActionLog::record($record->buyer, 'limit_attempt_granted', $record->number.': '.$data['note']);
        }, 3);

        return back()->with('success', 'One extra delivery attempt granted and recorded in the order timeline.');
    }

    public function extendReturnWindow(Request $request, int $order)
    {
        $data = $request->validate([
            'until' => ['required', 'date', 'after:today'],
            'note' => ['required', 'string', 'min:5', 'max:450'],
        ], [
            'until.required' => 'Pick the new return deadline.',
            'until.after' => 'The new deadline must be a later date.',
            'note.required' => 'Add a short reason. It is kept in the order timeline.',
            'note.min' => 'Add a little more detail for the audit note.',
        ]);

        $admin = $request->user('admin');
        $until = Carbon::parse($data['until'])->endOfDay();

        DB::transaction(function () use ($order, $data, $admin, $until) {
            $record = Order::query()->lockForUpdate()->findOrFail($order);
            abort_unless(in_array($record->status, Order::SALES_STATUSES, true), 409,
                'Only a delivered or received order has a return window.');
            abort_unless($record->delivered_at !== null, 409, 'This order has no delivery date to measure from.');

            $current = app(OrderReturnService::class)->windowClosesAt($record);
            if ($current && $until->lessThanOrEqualTo($current)) {
                throw ValidationException::withMessages([
                    'until' => 'Choose a date later than the current deadline ('.$current->format('M j, Y').').',
                ]);
            }

            $record->forceFill([
                'return_window_until' => $until,
                'limit_override_by' => $admin?->id,
                'limit_override_at' => now(),
                'limit_override_note' => $data['note'],
            ])->save();

            $record->statusEvents()->create([
                'user_id' => $admin?->id,
                'from_status' => $record->status,
                'to_status' => $record->status,
                'note' => 'Admin '.(($admin?->name) ?? 'staff').' extended the return window to '.$until->format('M j, Y').': '.$data['note'],
            ]);

            Notification::create([
                'user_id' => $record->buyer_id,
                'type' => 'order_update',
                'title' => $record->number.': Return window extended',
                'message' => 'You can still request a return until '.$until->format('M j, Y').'.',
                'link' => route('buyer.orders.show', $record),
            ]);

            AdminActionLog::record($record->buyer, 'return_window_extended',
                $record->number.' until '.$until->format('M j, Y').': '.$data['note']);
        }, 3);

        return back()->with('success', 'Return window extended and recorded in the order timeline.');
    }
}
