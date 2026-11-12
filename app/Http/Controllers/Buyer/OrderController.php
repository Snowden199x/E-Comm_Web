<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Communication\Notification;
use App\Models\Ecommerce\Order;
use App\Models\Ecommerce\OrderItem;
use App\Models\Ecommerce\OrderReturnRequest;
use App\Services\DeliveryCutoff;
use App\Services\OrderCancellationService;
use App\Services\OrderReturnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('items.product')
            ->where('buyer_id', auth()->id())
            ->latest()
            ->get();

        return view('buyer.orders.index', compact('orders'));
    }

    public function show(Order $order, OrderReturnService $returns)
    {
        abort_unless($order->buyer_id === auth()->id(), 403);

        $order->load('items.product.images', 'items.review.reply', 'seller.sellerDetail', 'statusEvents', 'latestReturnRequest');
        $canRequestReturn = $returns->canRequest($order);
        $returnWindowClosesAt = $returns->windowClosesAt($order);
        $returnDaysLeft = $returns->daysLeft($order);
        $maxDeliveryAttempts = DeliveryCutoff::MAX_DELIVERY_ATTEMPTS + (int) $order->delivery_extra_attempts;

        return view('buyer.orders.show', compact(
            'order', 'canRequestReturn', 'returnWindowClosesAt', 'returnDaysLeft', 'maxDeliveryAttempts',
        ));
    }

    public function cancel(Request $request, int $order, OrderCancellationService $cancellations)
    {
        $data = $request->validate([
            'expected_status' => ['required', Rule::in(OrderCancellationService::BUYER_CANCELLABLE_STATUSES)],
            'reason' => ['required', Rule::in(array_keys(OrderCancellationService::BUYER_REASONS))],
            'reason_details' => ['required_if:reason,other', 'nullable', 'string', 'max:450'],
        ]);

        try {
            $cancellations->cancelForBuyer(
                $request->user(),
                $order,
                $data['expected_status'],
                $data['reason'],
                $data['reason_details'] ?? null,
            );
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            if ($e->getStatusCode() === 409) {
                return redirect()->route('buyer.orders.show', $order)
                    ->with('error', $e->getMessage().' The page now shows the latest status.');
            }

            throw $e;
        }

        return redirect()->route('buyer.orders.show', $order)->with('success', 'Your order has been cancelled. Reserved stock has been restored.');
    }

    /** Return request form for a delivered or received order. */
    public function returnForm(Request $request, int $order, OrderReturnService $returns)
    {
        $record = Order::query()->where('buyer_id', $request->user()->id)
            ->with('latestReturnRequest')->findOrFail($order);
        abort_unless(in_array($record->status, OrderReturnService::RETURNABLE_STATUSES, true), 409, 'Only delivered orders can be returned.');
        abort_unless($returns->canRequest($record), 409, 'The 7-day return window for this order has closed.');
        abort_if(
            $record->returnRequests()->where('status', OrderReturnRequest::STATUS_PENDING)->exists(),
            409,
            'Your return request is already waiting for the seller.',
        );

        return view('buyer.orders.return', [
            'order' => $record,
            'reasons' => OrderReturnRequest::REASONS,
            'windowClosesAt' => $returns->windowClosesAt($record),
            'daysLeft' => $returns->daysLeft($record),
        ]);
    }

    public function requestReturn(Request $request, int $order, OrderReturnService $returns)
    {
        $data = $request->validate([
            'reason' => ['required', Rule::in(array_keys(OrderReturnRequest::REASONS))],
            'details' => ['required_if:reason,other', 'nullable', 'string', 'max:450'],
        ]);

        $returns->request($request->user(), $order, $data['reason'], $data['details'] ?? null);

        return redirect()->route('buyer.orders.show', $order)
            ->with('success', 'Return request sent. The seller will review it.');
    }

    public function complete(\Illuminate\Http\Request $request, int $order)
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $order) {
            $record = Order::where('buyer_id', $request->user()->id)->lockForUpdate()->findOrFail($order);
            abort_unless($record->status === 'delivered', 409, 'Only delivered orders can be confirmed as received.');
            $record->update(['status' => 'completed']);
            $record->statusEvents()->create(['user_id' => $request->user()->id, 'from_status' => 'delivered', 'to_status' => 'completed']);
        });
        return back()->with('success', 'Order received. Thank you!');
    }

    public function storeReview(Request $request, int $orderItem): \Illuminate\Http\RedirectResponse
    {
        abort_unless($request->user()->role === 'buyer', 403);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $review = DB::transaction(function () use ($request, $orderItem, $validated) {
            $item = OrderItem::query()->whereKey($orderItem)->firstOrFail();
            $order = Order::query()->whereKey($item->order_id)
                ->where('buyer_id', $request->user()->id)
                ->lockForUpdate()->firstOrFail();

            abort_unless($order->status === 'completed', 409, 'Reviews are available after you confirm receipt.');
            abort_unless($item->product && (int) $item->product->seller_id === (int) $order->seller_id, 409);
            abort_if($item->review()->exists(), 409, 'You have already reviewed this item.');

            $review = $item->review()->create([
                'order_id' => $order->id,
                'product_id' => $item->product_id,
                'seller_id' => $order->seller_id,
                'buyer_id' => $request->user()->id,
                'rating' => $validated['rating'],
                'comment' => trim($validated['comment']),
                'visibility' => 'published',
            ]);

            Notification::create([
                'user_id' => $order->seller_id,
                'type' => 'product_review',
                'title' => 'New product review',
                'message' => $request->user()->name.' left a '.$review->rating.'-star review.',
                'link' => route('seller.feedback.show', $review),
            ]);

            return $review;
        });

        return back()->with('success', 'Your review was submitted. Thank you!');
    }
}
