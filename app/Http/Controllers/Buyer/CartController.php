<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\CartItem;
use App\Models\Ecommerce\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function index()
    {
        $cartItems = CartItem::with('product.images')
            ->where('user_id', auth()->id())
            ->get();

        $totalsByProduct = $cartItems->groupBy('product_id')->map(fn ($items) => $items->sum('quantity'));

        return view('buyer.cart', compact('cartItems', 'totalsByProduct'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'nullable|integer|min:1',
            'color' => 'nullable|string|max:30',
            'size' => 'nullable|string|max:30',
        ]);

        $product = Product::findOrFail($request->product_id);
        $quantity = $request->quantity ?? 1;

        abort_unless($product->status === 'approved', 422, 'This product is not currently available.');

        foreach (['color' => 'colors', 'size' => 'sizes'] as $input => $attribute) {
            $available = array_values(array_filter(array_map('trim', explode(',', $product->{$attribute} ?? ''))));
            if ($available && ! in_array($request->input($input), $available, true)) {
                throw ValidationException::withMessages([$input => 'Choose an available '.$input.'.']);
            }
            if (! $available && $request->filled($input)) {
                throw ValidationException::withMessages([$input => 'This product has no '.$input.' options.']);
            }
        }

        if ($product->stock <= 0) {
            return back()->withErrors(['quantity' => $product->name.' is currently unavailable.']);
        }

        if ($quantity > $product->stock) {
            return back()->withErrors(['quantity' => 'Only '.$product->stock.' item(s) left in stock.']);
        }

        CartItem::create([
            'user_id' => auth()->id(),
            'product_id' => $request->product_id,
            'color' => $request->color,
            'size' => $request->size,
            'quantity' => $quantity,
        ]);

        return back()->with('success', 'Added to cart.');
    }

    public function update(Request $request, CartItem $cartItem)
    {
        abort_unless($cartItem->user_id === auth()->id(), 403);

        $request->validate(['quantity' => 'required|integer|min:1']);

        $otherQty = CartItem::where('user_id', auth()->id())
            ->where('product_id', $cartItem->product_id)
            ->where('id', '!=', $cartItem->id)
            ->sum('quantity');

        if (($otherQty + $request->quantity) > $cartItem->product->stock) {
            return back()->withErrors(['quantity' => 'Only '.$cartItem->product->stock.' item(s) left in stock (you have '.$otherQty.' of this item elsewhere in cart).']);
        }

        $cartItem->update(['quantity' => $request->quantity]);

        return back();
    }

    public function destroy(CartItem $cartItem)
    {
        abort_unless($cartItem->user_id === auth()->id(), 403);

        $cartItem->delete();

        return back();
    }
}
