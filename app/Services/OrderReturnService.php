<?php

namespace App\Services;

use App\Models\Communication\Notification;
use App\Models\Ecommerce\Order;
use App\Models\Ecommerce\OrderReturnRequest;
use App\Models\Ecommerce\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Buyer return requests. The Seller decides. Approving a return marks the order `returned`, gives the
 * stock back and records the refund the Buyer is owed; money is display-only, so no cash or transfer
 * is executed here. Both decisions notify the other side.
 */
class OrderReturnService
{
    /** Orders a Buyer may still return: delivered or confirmed received. */
    public const RETURNABLE_STATUSES = Order::SALES_STATUSES;

    /** Days after delivery in which a return can still be requested. */
    public const RETURN_WINDOW_DAYS = 7;

    /** Whether the order is delivered/received and still inside its 7-day return window. */
    public function canRequest(Order $order): bool
    {
        return in_array($order->status, self::RETURNABLE_STATUSES, true)
            && $order->delivered_at !== null
            && $order->delivered_at->greaterThanOrEqualTo(now()->subDays(self::RETURN_WINDOW_DAYS));
    }

    /** When the Buyer's return window closes, e.g. for the order page. */
    public function windowClosesAt(Order $order): ?\Carbon\Carbon
    {
        return $order->delivered_at?->copy()->addDays(self::RETURN_WINDOW_DAYS);
    }

    public function request(User $buyer, int $orderId, string $reasonKey, ?string $details): OrderReturnRequest
    {
        abort_unless($buyer->role === 'buyer', 403);
        abort_unless(isset(OrderReturnRequest::REASONS[$reasonKey]), 422, 'Choose a valid return reason.');

        $details = trim((string) $details);
        abort_if($reasonKey === 'other' && $details === '', 422, 'Add details for the selected reason.');

        return DB::transaction(function () use ($buyer, $orderId, $reasonKey, $details) {
            $order = Order::query()->where('buyer_id', $buyer->id)->lockForUpdate()->findOrFail($orderId);
            abort_unless(in_array($order->status, self::RETURNABLE_STATUSES, true), 409, 'Only delivered orders can be returned.');
            abort_unless($this->canRequest($order), 409, 'The ' . self::RETURN_WINDOW_DAYS . '-day return window for this order has closed.');
            abort_if(
                $order->returnRequests()->where('status', OrderReturnRequest::STATUS_PENDING)->exists(),
                409,
                'You already have a return request waiting for the seller.',
            );

            $request = $order->returnRequests()->create([
                'buyer_id' => $buyer->id,
                'seller_id' => $order->seller_id,
                'reason' => $reasonKey,
                'details' => $details !== '' ? $details : null,
                'status' => OrderReturnRequest::STATUS_PENDING,
            ]);

            Notification::create([
                'user_id' => $order->seller_id,
                'type' => 'return_request',
                'title' => $order->number.': Return requested',
                'message' => 'The buyer asked to return this order. Reason: '.$request->reason_label
                    .($details !== '' ? ' — '.$details : ''),
                'link' => route('seller.returns.index'),
            ]);

            return $request;
        }, 3);
    }

    public function approve(User $seller, int $requestId): OrderReturnRequest
    {
        abort_unless($seller->role === 'seller', 403);

        return DB::transaction(function () use ($seller, $requestId) {
            $request = OrderReturnRequest::query()->where('seller_id', $seller->id)
                ->lockForUpdate()->findOrFail($requestId);
            abort_unless($request->status === OrderReturnRequest::STATUS_PENDING, 409, 'This return request was already handled.');

            $order = Order::query()->whereKey($request->order_id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $order->seller_id === (int) $seller->id, 403);
            abort_unless(in_array($order->status, self::RETURNABLE_STATUSES, true), 409, 'This order can no longer be returned.');

            $this->restoreStock($order, $seller);

            $refundCents = max(0, (int) round($order->payable_total * 100));
            $fromStatus = $order->status;
            $order->update(['status' => 'returned']);
            $order->statusEvents()->create([
                'user_id' => $seller->id,
                'from_status' => $fromStatus,
                'to_status' => 'returned',
                'note' => 'Return approved by seller. Refund ₱'.number_format($refundCents / 100, 2).' recorded for the buyer.',
            ]);

            $request->update([
                'status' => OrderReturnRequest::STATUS_APPROVED,
                'decided_by' => $seller->id,
                'decided_at' => now(),
                'refund_cents' => $refundCents,
            ]);

            Notification::create([
                'user_id' => $order->buyer_id,
                'type' => 'order_update',
                'title' => $order->number.': Return approved',
                'message' => 'The seller approved your return. Refund due: ₱'.number_format($refundCents / 100, 2).'.',
                'link' => route('buyer.orders.show', $order),
            ]);

            return $request->fresh();
        }, 3);
    }

    public function reject(User $seller, int $requestId, string $note): OrderReturnRequest
    {
        abort_unless($seller->role === 'seller', 403);
        $note = trim($note);
        abort_unless($note !== '', 422, 'Add a reason for declining the return.');

        return DB::transaction(function () use ($seller, $requestId, $note) {
            $request = OrderReturnRequest::query()->where('seller_id', $seller->id)
                ->lockForUpdate()->findOrFail($requestId);
            abort_unless($request->status === OrderReturnRequest::STATUS_PENDING, 409, 'This return request was already handled.');

            $order = Order::query()->whereKey($request->order_id)->firstOrFail();
            abort_unless((int) $order->seller_id === (int) $seller->id, 403);

            $request->update([
                'status' => OrderReturnRequest::STATUS_REJECTED,
                'decided_by' => $seller->id,
                'decided_at' => now(),
                'decision_note' => $note,
            ]);

            Notification::create([
                'user_id' => $order->buyer_id,
                'type' => 'order_update',
                'title' => $order->number.': Return declined',
                'message' => 'The seller declined your return request. Reason: '.$note,
                'link' => route('buyer.orders.show', $order),
            ]);

            return $request->fresh();
        }, 3);
    }

    /** Give the returned quantities back to stock, once per approval. */
    private function restoreStock(Order $order, User $actor): void
    {
        $items = $order->items()->orderBy('product_id')->orderBy('product_variant_id')->get();
        $products = Product::query()->whereIn('id', $items->pluck('product_id')->unique())
            ->orderBy('id')->lockForUpdate()->get()->keyBy('id');

        foreach ($items as $item) {
            $product = $products->get($item->product_id);
            abort_unless($product, 409, 'A product on this order is no longer available for stock restoration.');
            $variant = $item->product_variant_id
                ? $product->variants()->lockForUpdate()->findOrFail($item->product_variant_id)
                : null;

            app(InventoryService::class)->changeLocked(
                $product,
                (int) $item->quantity,
                'return',
                $actor->id,
                $order->id,
                'Return approved by seller',
                variant: $variant,
            );
        }
    }
}
