<?php

namespace App\Http\Controllers\Api\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\CartItem;
use App\Models\Ecommerce\Product;
use App\Services\BuyerApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Cart for the Buyer mobile app. Same rules as the web cart: approved buyer-visible products, the
 * variant (or colour/size) must exist and be in stock, and quantity can never pass the stock.
 */
class BuyerCartController extends Controller
{
    public function __construct(private BuyerApiPresenter $presenter) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->payload($request));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'color' => ['nullable', 'string', 'max:30'],
            'size' => ['nullable', 'string', 'max:30'],
            'variant_id' => ['nullable', 'integer'],
        ]);

        $product = Product::with('seller')->findOrFail($request->product_id);
        $quantity = (int) ($request->quantity ?? 1);

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
            throw ValidationException::withMessages(['quantity' => $product->name.' is currently unavailable.']);
        }
        if ($quantity > $availableStock) {
            throw ValidationException::withMessages(['quantity' => 'Only '.$availableStock.' item(s) left in stock.']);
        }

        CartItem::create([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'color' => $request->color,
            'size' => $request->size,
            'quantity' => $quantity,
        ]);

        return response()->json($this->payload($request) + ['message' => 'Added to cart.'], 201);
    }

    public function update(Request $request, CartItem $cartItem): JsonResponse
    {
        abort_unless((int) $cartItem->user_id === (int) $request->user()->id, 404);
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1']]);

        $otherQty = CartItem::where('user_id', $request->user()->id)
            ->where('product_id', $cartItem->product_id)
            ->where('product_variant_id', $cartItem->product_variant_id)
            ->where('id', '!=', $cartItem->id)
            ->sum('quantity');

        $availableStock = $cartItem->product_variant_id ? $cartItem->variant?->stock : $cartItem->product->stock;
        if ($availableStock === null || ($otherQty + $data['quantity']) > $availableStock) {
            throw ValidationException::withMessages(['quantity' => 'Only '.($availableStock ?? 0).' item(s) left in stock (you have '.$otherQty.' of this item elsewhere in cart).']);
        }

        $cartItem->update(['quantity' => $data['quantity']]);

        return response()->json($this->payload($request) + ['message' => 'Cart updated.']);
    }

    public function destroy(Request $request, CartItem $cartItem): JsonResponse
    {
        abort_unless((int) $cartItem->user_id === (int) $request->user()->id, 404);
        $cartItem->delete();

        return response()->json($this->payload($request) + ['message' => 'Removed from cart.']);
    }

    private function payload(Request $request): array
    {
        $items = CartItem::with('product.images', 'product.seller.sellerDetail', 'variant')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->get();

        $categoryIds = $items->pluck('product.category_id')->filter()->unique();
        $alsoLike = $categoryIds->isEmpty() ? collect() : Product::query()
            ->availableToBuy()->withCardMetrics()->with(['images', 'seller.sellerDetail'])
            ->whereIn('category_id', $categoryIds)
            ->whereNotIn('id', $items->pluck('product_id'))
            ->latest()->take(6)->get();

        return $this->presenter->cartPayload($items) + [
            'also_like' => $alsoLike->map(fn (Product $product) => $this->presenter->productCard($product))->values()->all(),
        ];
    }
}
