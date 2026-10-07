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
        $cartItems = CartItem::with('product.images', 'product.seller.sellerDetail', 'variant')
            ->where('user_id', auth()->id())
            ->get();

        $totalsByProduct = $cartItems->groupBy(fn ($item) => $item->product_variant_id ?: 'product-'.$item->product_id)
            ->map(fn ($items) => $items->sum('quantity'));

        $categoryIds = $cartItems->pluck('product.category_id')->filter()->unique();
        $alsoLike = $categoryIds->isEmpty() ? collect() : Product::query()
            ->availableToBuy()->withCardMetrics()
            ->with(['images', 'seller.sellerDetail'])
            ->whereIn('category_id', $categoryIds)
            ->whereNotIn('id', $cartItems->pluck('product_id'))
            ->latest()->take(6)->get();

        return view('buyer.cart', compact('cartItems', 'totalsByProduct', 'alsoLike'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'nullable|integer|min:1',
            'color' => 'nullable|string|max:30',
            'size' => 'nullable|string|max:30',
            'variant_id' => 'nullable|integer',
        ]);

        $product = Product::findOrFail($request->product_id);
        $quantity = $request->quantity ?? 1;

        abort_unless($product->status === 'approved', 422, 'This product is not currently available.');
        abort_unless($product->seller?->status === 'approved' && ! $product->seller->archived_at
            && (! $product->seller->account_status || $product->seller->account_status === 'active'), 422,
            'This seller is not currently available.');

        $variant = null;
        if ($product->has_variations) {
            $variant = $product->variants()->find($request->input('variant_id'));
            if (! $variant) {
                throw ValidationException::withMessages(['variant_id' => 'Choose an available product variant.']);
            }
        } elseif ($request->filled('variant_id')) {
            throw ValidationException::withMessages(['variant_id' => 'This product has no variants.']);
        }

        foreach ($variant ? [] : ['color' => 'colors', 'size' => 'sizes'] as $input => $attribute) {
            $available = array_values(array_filter(array_map('trim', explode(',', $product->{$attribute} ?? ''))));
            if ($available && ! in_array($request->input($input), $available, true)) {
                throw ValidationException::withMessages([$input => 'Choose an available '.$input.'.']);
            }
            if (! $available && $request->filled($input)) {
                throw ValidationException::withMessages([$input => 'This product has no '.$input.' options.']);
            }
        }

        $availableStock = $variant?->stock ?? $product->stock;
        if ($availableStock <= 0) {
            return back()->withErrors(['quantity' => $product->name.' is currently unavailable.']);
        }

        if ($quantity > $availableStock) {
            return back()->withErrors(['quantity' => 'Only '.$availableStock.' item(s) left in stock.']);
        }

        CartItem::create([
            'user_id' => auth()->id(),
            'product_id' => $request->product_id,
            'product_variant_id' => $variant?->id,
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
            ->where('product_variant_id', $cartItem->product_variant_id)
            ->where('id', '!=', $cartItem->id)
            ->sum('quantity');

        $availableStock = $cartItem->product_variant_id ? $cartItem->variant?->stock : $cartItem->product->stock;
        if ($availableStock === null || ($otherQty + $request->quantity) > $availableStock) {
            return back()->withErrors(['quantity' => 'Only '.($availableStock ?? 0).' item(s) left in stock (you have '.$otherQty.' of this item elsewhere in cart).']);
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
