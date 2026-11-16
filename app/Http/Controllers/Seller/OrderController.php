<?php

namespace App\Http\Controllers\Seller;

use App\Support\SellerSearch;
use App\Http\Controllers\Controller;
use App\Models\Communication\Notification;
use App\Models\Ecommerce\Order;
use App\Services\SellerOrderWorkflow;
use App\Services\ShippingLabelCodeGenerator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(array_merge(['all'], array_keys(Order::SELLER_GROUPS)))],
            'search' => 'nullable|string|max:100', 'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('date_from') ? ['after_or_equal:date_from'] : [])],
            'page' => 'nullable|integer|min:1', 'order' => 'nullable|integer|min:1',
        ], [
            'status.in' => 'Choose All or one of the status tabs.',
            'search.max' => 'Search is too long. Use 100 characters or fewer.',
            'date_from.date_format' => 'Start date is not valid. Pick a date from the calendar.',
            'date_to.date_format' => 'End date is not valid. Pick a date from the calendar.',
            'date_to.after_or_equal' => 'End date is before the start date. Pick an end date on or after the start date.',
            'page.min' => 'That page does not exist. Go back to the first page.',
        ]);
        // Search and dates narrow the list and the tab counts. The status tab narrows only the list.
        $base = Order::where('seller_id', $request->user()->id);
        if ($search = SellerSearch::clean($filters['search'] ?? '')) {
            $base->where(function ($q) use ($search) {
                $q->whereHas('buyer', fn ($b) => $b->where('name', 'like', SellerSearch::like($search)))
                    ->orWhereHas('items.product', fn ($p) => $p->where('name', 'like', SellerSearch::like($search)));
                SellerSearch::orOrderColumns($q, $search);
            });
        }
        if (! empty($filters['date_from'])) {
            $base->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $base->whereDate('created_at', '<=', $filters['date_to']);
        }
        $raw = (clone $base)->selectRaw('status, COUNT(*) total')->groupBy('status')->pluck('total', 'status');
        $counts = ['all' => (int) $raw->sum()];
        foreach (Order::SELLER_GROUPS as $group => $statuses) {
            $counts[$group] = collect($statuses)->sum(fn ($status) => (int) ($raw[$status] ?? 0));
        }
        $query = (clone $base)->with('buyer')->withSum('items', 'quantity');
        $status = $filters['status'] ?? 'all';
        if ($status !== 'all') {
            $query->whereIn('status', Order::SELLER_GROUPS[$status]);
        }
        $orders = $query->latest()->orderByDesc('id')->paginate(10)->withQueryString();
        if ($request->expectsJson()) {
            return response()->json(['html' => view('seller.order-management-orders.rows', compact('orders'))->render(), 'counts' => $counts, 'pagination' => ['page' => $orders->currentPage(), 'last' => $orders->lastPage(), 'total' => $orders->total(), 'from' => $orders->firstItem(), 'to' => $orders->lastItem()]]);
        }

        return view('seller.order-management-orders.index', compact('orders', 'counts', 'filters'));
    }

    private function ownedOrder(Request $request, int $id): Order
    {
        return Order::where('seller_id', $request->user()->id)->with(['buyer', 'courier', 'items.product.images', 'statusEvents'])->findOrFail($id);
    }

    public function show(Request $request, int $order)
    {
        $order = $this->ownedOrder($request, $order);

        if (! $request->expectsJson()) {
            return redirect()->route('seller.orders.index', ['order' => $order->id]);
        }

        // How many of this seller's orders this buyer cancelled, shown beside the report button.
        $buyerCancelCount = Order::where('seller_id', $order->seller_id)->where('buyer_id', $order->buyer_id)
            ->where('status', 'cancelled')
            ->whereHas('statusEvents', fn ($events) => $events->where('to_status', 'cancelled')->whereColumn('order_status_events.user_id', 'orders.buyer_id'))
            ->count();

        return response()->json(['html' => view('seller.order-management-orders.drawer', compact('order', 'buyerCancelCount'))->render()]);
    }

    /** The seller adds a photo of the packed item. The buyer sees it, and the order cannot be marked ready without it. */
    public function uploadPickupPhoto(Request $request, int $order)
    {
        $request->validate([
            'pickup_photo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'pickup_photo.required' => 'Choose a photo of the packed item.',
            'pickup_photo.mimes' => 'Use a JPG, PNG, or WEBP photo.',
            'pickup_photo.max' => 'The photo is over 5 MB. Choose a smaller one.',
            'pickup_photo.uploaded' => 'The photo could not be uploaded. Try a smaller photo.',
        ]);

        $path = null;
        try {
            DB::transaction(function () use ($request, $order, &$path) {
                $record = Order::where('seller_id', $request->user()->id)->lockForUpdate()->findOrFail($order);
                abort_unless($record->status === 'preparing', 409, 'Add the photo while the order is being prepared.');
                $path = $request->file('pickup_photo')->store('private/pickup-photos/'.$record->id, 'local');
                $previous = $record->pickup_photo_path;
                $record->update(['pickup_photo_path' => $path, 'pickup_photo_at' => now()]);
                if ($previous && $previous !== $path) {
                    Storage::disk('local')->delete($previous);
                }
            }, 3);
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }

        return response()->json(['message' => 'Photo added.']);
    }

    public function waybill(Request $request, int $order, ShippingLabelCodeGenerator $codes)
    {
        $order = $this->ownedOrder($request, $order);
        abort_unless($order->canPrintShippingLabel(), 409, 'Mark the order ready for pickup and wait for the pickup logistics assignment before printing.');
        $order->load(['logisticsCenter', 'destinationLogisticsCenter']);
        $seller = $request->user()->load('sellerDetail');
        $labelCodes = $codes->forTrackingNumber($order->tracking_number);

        return view('seller.order-management-orders.waybill', compact('order', 'seller', 'labelCodes'));
    }

    public function update(Request $request, int $order)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['accept', 'decline', 'prepare', 'ready', 'cancel'])],
            'expected_status' => ['required', Rule::in(array_keys(Order::STATUSES))],
            'reason' => 'required_if:action,decline,cancel|nullable|string|max:500',
            'reason_details' => 'required_if:reason,other|nullable|string|max:450',
            'pickup_scheduled_at' => ['nullable', 'date', 'after:now'],
        ]);
        app(SellerOrderWorkflow::class)->transition(
            $request->user(), $order, $data['action'], $data['expected_status'],
            $data['reason'] ?? null, $data['reason_details'] ?? null,
            $data['pickup_scheduled_at'] ?? null,
        );

        return response()->json(['message' => 'Order updated.']);
    }

    /**
     * Move a pickup slot. The seller gets three moves per order, and only while
     * the parcel is still waiting for the rider's pickup scan.
     */
    public function reschedulePickup(Request $request, int $order)
    {
        $data = $request->validate([
            'pickup_scheduled_at' => ['required', 'date', 'after:now'],
        ]);

        $result = DB::transaction(function () use ($request, $order, $data) {
            $record = Order::where('seller_id', $request->user()->id)->lockForUpdate()->findOrFail($order);

            abort_unless($record->status === 'ready_for_pickup',
                409, 'Pickup can only be moved while the parcel is waiting for the rider.');
            abort_unless($record->canSellerReschedulePickup(), 409,
                'This pickup already used its '.Order::PICKUP_RESCHEDULE_LIMIT.' reschedules.');

            $previous = $record->pickup_scheduled_at;
            $record->pickup_scheduled_at = Carbon::parse($data['pickup_scheduled_at']);
            $record->pickup_reschedule_count = (int) $record->pickup_reschedule_count + 1;
            $record->save();

            $record->statusEvents()->create([
                'user_id' => $request->user()->id,
                'from_status' => 'ready_for_pickup',
                'to_status' => 'ready_for_pickup',
                'note' => 'Seller moved the pickup from '
                    .($previous?->format('M j, Y g:i A') ?? 'the previous slot').' to '
                    .$record->pickup_scheduled_at->format('M j, Y g:i A').'. '
                    .$record->pickupReschedulesLeft().' reschedule(s) left.',
            ]);

            $hubUserId = $record->logisticsCenter?->user_id;
            if ($hubUserId) {
                Notification::create([
                    'user_id' => $hubUserId,
                    'type' => 'pickup_rescheduled',
                    'title' => 'Pickup moved '.$record->number,
                    'message' => 'The seller moved the pickup to '.$record->pickup_scheduled_at->format('M j, Y g:i A').'.',
                    'link' => route('lgp.station.pickups'),
                ]);
            }
            Notification::create([
                'user_id' => $record->buyer_id,
                'type' => 'order_update',
                'title' => 'Pickup rescheduled '.$record->number,
                'message' => 'The seller moved the pickup to '.$record->pickup_scheduled_at->format('M j, Y g:i A').'. Your delivery estimate is unchanged for now.',
                'link' => route('buyer.orders.show', $record),
            ]);

            return [
                'message' => 'Pickup moved to '.$record->pickup_scheduled_at->format('M j, Y g:i A').'. '
                    .$record->pickupReschedulesLeft().' reschedule(s) left.',
                'pickup_scheduled_at' => $record->pickup_scheduled_at->toIso8601String(),
                'remaining' => $record->pickupReschedulesLeft(),
            ];
        }, 3);

        return $request->expectsJson()
            ? response()->json($result)
            : back()->with('success', $result['message']);
    }

    /** Tell the pickup hub that repeated assignments still have not collected the parcel. */
    public function reportPickup(Request $request, int $order)
    {
        $data = $request->validate(['details' => ['required', 'string', 'min:10', 'max:600']]);

        $result = DB::transaction(function () use ($request, $order, $data) {
            $record = Order::where('seller_id', $request->user()->id)->lockForUpdate()->findOrFail($order);

            abort_unless($record->canSellerReportPickup(), 409,
                'A pickup problem can be reported after a rider assignment expires without a scan.');
            abort_if($record->pickup_reported_at && $record->pickup_reported_at->greaterThan(now()->subHours(6)),
                409, 'You already reported this pickup. The hub is reviewing it.');

            $record->pickup_reported_at = now();
            $record->save();

            $record->statusEvents()->create([
                'user_id' => $request->user()->id,
                'from_status' => 'ready_for_pickup',
                'to_status' => 'ready_for_pickup',
                'note' => 'Seller reported a pickup problem after '.$record->pickup_attempts.' failed assignment(s): '.$data['details'],
            ]);

            $hubUserId = $record->logisticsCenter?->user_id;
            if ($hubUserId) {
                Notification::create([
                    'user_id' => $hubUserId,
                    'type' => 'pickup_problem',
                    'title' => 'Pickup problem '.$record->number,
                    'message' => 'The seller reports that the parcel is still uncollected after '
                        .$record->pickup_attempts.' assignment(s). '.$data['details'],
                    'link' => route('lgp.station.pickups'),
                ]);
            }
            Notification::create([
                'user_id' => $record->buyer_id,
                'type' => 'order_update',
                'title' => 'Pickup delay '.$record->number,
                'message' => 'We are still arranging the pickup. The seller reported the delay to logistics.',
                'link' => route('buyer.orders.show', $record),
            ]);

            return ['message' => 'Reported to logistics. The hub will review the pickup.'];
        }, 3);

        return $request->expectsJson()
            ? response()->json($result)
            : back()->with('success', $result['message']);
    }
}
