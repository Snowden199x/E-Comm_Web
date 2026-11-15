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
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Admin override for the Buyer return window. Delivery attempt exceptions are owned by Logistics Station Hubs.
 */
class LimitOverrideController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100']], [
            'q.max' => 'Search is too long. Use 100 characters or fewer.',
        ]);
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

    public function extendReturnWindow(Request $request, int $order)
    {
        $data = $request->validate([
            'until' => ['required', 'date', 'after:today'],
            'note' => ['required', 'string', 'min:5', 'max:450'],
        ], [
            'until.required' => 'Deadline is empty. Pick the new return deadline.',
            'until.date' => 'Deadline is not a valid date. Pick a date from the calendar.',
            'until.after' => 'Deadline is not in the future. Pick a date after today.',
            'note.required' => 'Reason is empty. Say why you are extending the window. It is kept in the order timeline.',
            'note.min' => 'Reason is too short. Use at least 5 characters, for example what the buyer reported.',
            'note.max' => 'Reason is too long. Shorten it to 450 characters or fewer.',
        ]);

        $admin = $request->user('admin');
        $until = Carbon::parse($data['until'])->endOfDay();
        $number = '';

        try {
            DB::transaction(function () use ($order, $data, $admin, $until, &$number) {
                $record = Order::query()->lockForUpdate()->findOrFail($order);
                abort_unless(in_array($record->status, Order::SALES_STATUSES, true), 409,
                    'This order has no return window yet because it is not delivered. Reload the page and check the order status.');
                abort_unless($record->delivered_at !== null, 409, 'This order has no delivery date, so no window can be measured. Check the order\'s delivery record.');
                $number = $record->number;

                $current = app(OrderReturnService::class)->windowClosesAt($record);
                if ($current && $until->lessThanOrEqualTo($current)) {
                    throw ValidationException::withMessages([
                        'until' => 'Deadline is not after the current one ('.$current->format('M j, Y').'). Pick a later date.',
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
        } catch (HttpException $e) {
            return $this->refused($e);
        }

        return back()->with('success', 'Return window extended for '.$number.' until '.$until->format('M j, Y').'. The buyer is notified, and it is recorded in the order timeline.');
    }

    /**
     * A business-rule refusal (409) returns to the page with what to do next, and keeps what was typed so
     * the popup reopens. Any other HTTP error keeps its normal page.
     */
    private function refused(HttpException $e)
    {
        if ($e->getStatusCode() !== 409) {
            throw $e;
        }

        return back()->withInput()->with('error', $e->getMessage());
    }
}
