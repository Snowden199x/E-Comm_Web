<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Communication\Notification;
use App\Models\Ecommerce\Order;
use App\Models\Ecommerce\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function show(Order $order)
    {
        abort_unless($order->buyer_id === auth()->id(), 403);

        $order->load('items.product.images', 'items.review.reply', 'seller.sellerDetail', 'statusEvents');

        return view('buyer.orders.show', compact('order'));
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
