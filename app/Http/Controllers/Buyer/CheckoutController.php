<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\CartItem;
use App\Models\Ecommerce\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function index()
    {
        $cartItems = CartItem::with('product')->where('user_id', auth()->id())->get();
        $buyerDetail = \App\Models\Profiles\BuyerDetail::where('user_id', auth()->id())->first();

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

        return view('buyer.checkout', compact('cartItems', 'defaultAddress'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'shipping_address' => 'required|string',
            'payment_mode' => 'required|string',
        ]);

        $cartItems = CartItem::with('product')->where('user_id', auth()->id())->get();

        abort_if($cartItems->isEmpty(), 400, 'Cart is empty.');

        try {
            DB::transaction(function () use ($cartItems, $request) {
                $bySeller = $cartItems->groupBy('product.seller_id');

                // Re-fetch and lock each distinct product row inside the transaction
                // so two simultaneous checkouts can't both pass the stock check.
                $lockedProducts = \App\Models\Ecommerce\Product::whereIn('id', $cartItems->pluck('product_id')->unique())
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $totalsByProduct = $cartItems->groupBy('product_id');

                foreach ($totalsByProduct as $productId => $items) {
                    $product = $lockedProducts[$productId];
                    $totalQty = $items->sum('quantity');

                    if ($totalQty > $product->stock) {
                        throw new \Exception($product->name . ' only has ' . $product->stock . ' left in stock (you have ' . $totalQty . ' in cart).');
                    }
                }

                foreach ($bySeller as $sellerId => $items) {
                    $order = Order::create([
                        'buyer_id' => auth()->id(),
                        'seller_id' => $sellerId,
                        'total_amount' => $items->sum(fn($i) => $i->quantity * $i->product->price),
                        'status' => 'pending',
                        'payment_mode' => $request->payment_mode,
                        'shipping_address' => $request->shipping_address,
                    ]);

                    foreach ($items as $item) {
                        $order->items()->create([
                            'product_id' => $item->product_id,
                            'quantity' => $item->quantity,
                            'color' => $item->color,
                            'size' => $item->size,
                            'price' => $item->product->price,
                        ]);

                        $lockedProducts[$item->product_id]->decrement('stock', $item->quantity);
                    }
                }

                CartItem::where('user_id', auth()->id())->delete();
            });
        } catch (\Exception $e) {
            return back()->withErrors(['quantity' => $e->getMessage()]);
        }

        return redirect()->route('buyer.orders.index')->with('success', 'Order placed.');
    }
}
