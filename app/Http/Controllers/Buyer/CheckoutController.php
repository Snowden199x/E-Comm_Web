<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Communication\Notification;
use App\Models\Ecommerce\CartItem;
use App\Models\Ecommerce\Order;
use App\Models\Ecommerce\Product;
use App\Models\Profiles\BuyerDetail;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function index()
    {
        $cartItems = CartItem::with('product')->where('user_id', auth()->id())->orderBy('id')->get();
        $checkoutRevision = $this->revision($cartItems, $cartItems->mapWithKeys(fn ($item) => [$item->product_id => $item->product]));
        $buyerDetail = BuyerDetail::where('user_id', auth()->id())->first();

        $defaultAddress = $buyerDetail
            ? trim(implode(', ', array_filter([
                $buyerDetail->house_no,
                $buyerDetail->street,
                $buyerDetail->barangay,
                $buyerDetail->municipality,
                $buyerDetail->province,
                $buyerDetail->zip_code,
            ])))
            : '';

        return view('buyer.checkout', compact('cartItems', 'defaultAddress', 'checkoutRevision'));
    }

    public function store(Request $request)
    {
        $request->validate(['shipping_address' => 'required|string|max:2000', 'payment_mode' => 'required|in:cod', 'checkout_revision' => ['required', 'regex:/^[a-f0-9]{64}$/']]);
        DB::transaction(function () use ($request) {
            $cartItems = CartItem::where('user_id', $request->user()->id)->orderBy('id')->lockForUpdate()->get();
            if ($cartItems->isEmpty()) {
                throw ValidationException::withMessages(['quantity' => 'Your cart is empty.']);
            }
            $products = Product::with('seller')->whereIn('id', $cartItems->pluck('product_id'))
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach ($cartItems->groupBy('product_id') as $id => $items) {
                $product = $products->get($id);
                if (! $product || $product->status !== 'approved' || $product->seller?->status !== 'approved'
                    || $product->seller->archived_at || ($product->seller->account_status && $product->seller->account_status !== 'active')) {
                    throw ValidationException::withMessages(['quantity' => 'A product in your cart is no longer available. Please review your cart.']);
                }
                if ($items->sum('quantity') > $product->stock) {
                    throw ValidationException::withMessages(['quantity' => $product->name.' only has '.$product->stock.' left in stock.']);
                }
            }
            if (! hash_equals($this->revision($cartItems, $products), $request->input('checkout_revision'))) {
                throw ValidationException::withMessages(['checkout_revision' => 'An item or price changed while you were checking out. Review your updated cart before placing the order.']);
            }
            foreach ($cartItems as $item) {
                $product = $products[$item->product_id];
                foreach (['color' => 'colors', 'size' => 'sizes'] as $choice => $field) {
                    $available = array_values(array_filter(array_map('trim', explode(',', $product->{$field} ?? ''))));
                    if (($available && ! in_array($item->{$choice}, $available, true))
                        || (! $available && trim((string) $item->{$choice}) !== '')) {
                        throw ValidationException::withMessages(['checkout_revision' => 'An item option changed. Review your updated cart before placing the order.']);
                    }
                }
            }
            foreach ($cartItems->groupBy(fn ($item) => $products[$item->product_id]->seller_id) as $sellerId => $items) {
                $order = Order::create([
                    'buyer_id' => $request->user()->id, 'seller_id' => $sellerId,
                    'total_amount' => $items->sum(fn ($item) => $item->quantity * $products[$item->product_id]->price),
                    'shipping_fee' => 0, 'status' => 'placed', 'payment_mode' => $request->payment_mode,
                    'shipping_address' => $request->shipping_address,
                ]);
                $order->statusEvents()->create(['user_id' => $request->user()->id, 'to_status' => 'placed']);
                Notification::create(['user_id' => $sellerId, 'type' => 'new_order', 'title' => 'New order '.$order->number, 'message' => 'A buyer placed an order. Review it in Orders.', 'link' => route('seller.orders.show', $order)]);
                Notification::create(['user_id' => null, 'type' => 'new_order', 'title' => 'New order '.$order->number, 'message' => 'A buyer placed an order with a seller.', 'link' => route('admin.dashboard')]);
                foreach ($items as $item) {
                    $product = $products[$item->product_id];
                    $order->items()->create(['product_id' => $product->id, 'quantity' => $item->quantity, 'color' => $item->color, 'size' => $item->size, 'price' => $product->price]);
                    app(InventoryService::class)->changeLocked($product, -$item->quantity, 'checkout', $request->user()->id, $order->id);
                }
            }
            CartItem::whereIn('id', $cartItems->pluck('id'))->where('user_id', $request->user()->id)->delete();
        }, 3);

        return redirect()->route('buyer.orders.index')->with('success', 'Order placed.');
    }

    private function revision($cartItems, $products): string
    {
        return hash('sha256', json_encode($cartItems->map(function ($item) use ($products) {
            $product = $products->get($item->product_id);
            return [
                $item->id, $item->product_id, $item->quantity, $item->color, $item->size,
                $product?->price, $product?->stock, $product?->status,
                $product?->colors, $product?->sizes, $product?->seller_id,
            ];
        })->all()));
    }
}
