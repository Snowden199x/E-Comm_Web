<?php

namespace App\Http\Controllers\Api\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Communication\Notification;
use App\Models\Ecommerce\Order;
use App\Models\Ecommerce\OrderItem;
use App\Services\BuyerApiPresenter;
use App\Services\OrderCancellationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** The signed-in Buyer's own orders. Every lookup is scoped to buyer_id; nothing else is readable. */
class BuyerOrderController extends Controller
{
    public function __construct(private BuyerApiPresenter $presenter) {}

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(Order::STATUSES))],
        ]);

        $orders = Order::with(['items.product.images', 'seller.sellerDetail'])
            ->where('buyer_id', $request->user()->id)
            ->when($data['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()->paginate(15);

        return response()->json([
            'data' => collect($orders->items())->map(fn (Order $order) => $this->presenter->orderSummary($order))->values()->all(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
            'statuses' => Order::STATUSES,
        ]);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        abort_unless((int) $order->buyer_id === (int) $request->user()->id, 404);
        $order->load('items.product.images', 'items.review', 'seller.sellerDetail', 'statusEvents');

        return response()->json(['order' => $this->presenter->orderDetail($order)]);
    }

    public function cancel(Request $request, Order $order, OrderCancellationService $cancellations): JsonResponse
    {
        abort_unless((int) $order->buyer_id === (int) $request->user()->id, 404);
        $data = $request->validate([
            'expected_status' => ['required', Rule::in(OrderCancellationService::BUYER_CANCELLABLE_STATUSES)],
            'reason' => ['required', Rule::in(array_keys(OrderCancellationService::BUYER_REASONS))],
            'reason_details' => ['required_if:reason,other', 'nullable', 'string', 'max:450'],
        ]);

        $cancellations->cancelForBuyer(
            $request->user(),
            $order->id,
            $data['expected_status'],
            $data['reason'],
            $data['reason_details'] ?? null,
        );

        $order->refresh()->load('items.product.images', 'items.review', 'seller.sellerDetail', 'statusEvents');

        return response()->json([
            'message' => 'Your order has been cancelled. Reserved stock has been restored.',
            'order' => $this->presenter->orderDetail($order),
        ]);
    }

    public function complete(Request $request, Order $order): JsonResponse
    {
        abort_unless((int) $order->buyer_id === (int) $request->user()->id, 404);

        DB::transaction(function () use ($request, $order) {
            $record = Order::where('buyer_id', $request->user()->id)->lockForUpdate()->findOrFail($order->id);
            abort_unless($record->status === 'delivered', 409, 'Only delivered orders can be confirmed as received.');
            $record->update(['status' => 'completed']);
            $record->statusEvents()->create(['user_id' => $request->user()->id, 'from_status' => 'delivered', 'to_status' => 'completed']);
        });

        $order->refresh()->load('items.product.images', 'items.review', 'seller.sellerDetail', 'statusEvents');

        return response()->json([
            'message' => 'Order received. Thank you!',
            'order' => $this->presenter->orderDetail($order),
        ]);
    }

    public function review(Request $request, int $orderItem): JsonResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $orderItem, $validated) {
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
        });

        return response()->json(['message' => 'Your review was submitted. Thank you!'], 201);
    }
}
