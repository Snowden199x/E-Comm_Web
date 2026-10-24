<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Communication\Notification;
use App\Models\Ecommerce\CartItem;
use App\Models\Ecommerce\Order;
use App\Models\Ecommerce\Product;
use App\Models\Ecommerce\ProductVariant;
use App\Models\Ecommerce\SellerVoucher;
use App\Models\Profiles\BuyerDetail;
use App\Services\InventoryService;
use App\Services\CheckoutShippingQuoteService;
use App\Services\LocationCatalog;
use App\Services\SellerVoucherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function index(Request $request, LocationCatalog $locations)
    {
        $selectedIds = $request->validate(['items' => ['required', 'array', 'min:1'], 'items.*' => ['required', 'integer', 'distinct']])['items'];
        $cartItems = CartItem::with('product.category', 'product.seller.sellerDetail', 'product.images', 'variant')->where('user_id', auth()->id())
            ->whereIn('id', $selectedIds)->orderBy('id')->get();
        if ($cartItems->count() !== count($selectedIds)) {
            throw ValidationException::withMessages(['items' => 'Review your cart selection and try again.']);
        }
        $checkoutRevision = $this->revision($cartItems,
            $cartItems->mapWithKeys(fn ($item) => [$item->product_id => $item->product]),
            $cartItems->filter(fn ($item) => $item->variant)->mapWithKeys(fn ($item) => [$item->variant->id => $item->variant]));
        $products = $cartItems->mapWithKeys(fn ($item) => [$item->product_id => $item->product]);
        $variants = $cartItems->filter(fn ($item) => $item->variant)->mapWithKeys(fn ($item) => [$item->variant->id => $item->variant]);
        $voucherOptions = [];
        foreach ($cartItems->groupBy(fn ($item) => $item->product->seller_id) as $sellerId => $sellerItems) {
            $voucherOptions[$sellerId] = app(SellerVoucherService::class)->checkoutOptions(
                (int) $sellerId, $sellerItems, $products, $variants, (int) $request->user()->id);
        }
        $buyerDetail = BuyerDetail::where('user_id', auth()->id())->first();

        $defaultAddress = $buyerDetail
            ? trim(implode(', ', array_filter([
                $buyerDetail->house_no,
                $buyerDetail->street,
                $buyerDetail->barangay,
                $buyerDetail->zip_code,
            ])))
            : '';

        $locationOptions = $locations->all();
        [$defaultProvinceCode, $defaultCityCode] = $buyerDetail
            ? ($locations->codesForNames($buyerDetail->province, $buyerDetail->municipality) ?? [null, null])
            : [null, null];

        $sellerNotesEnabled = true;

        return view('buyer.checkout', compact('cartItems', 'defaultAddress', 'checkoutRevision', 'locationOptions', 'defaultProvinceCode', 'defaultCityCode', 'sellerNotesEnabled', 'voucherOptions'));
    }

    public function store(Request $request, LocationCatalog $locations, CheckoutShippingQuoteService $shippingQuotes)
    {
        $data = $request->validate(['shipping_address' => 'required|string|max:1800', 'shipping_province_code' => 'required|string|size:9', 'shipping_city_code' => 'required|string|size:9', 'payment_mode' => 'required|in:cod', 'checkout_revision' => ['required', 'regex:/^[a-f0-9]{64}$/'], 'items' => ['required', 'array', 'min:1'], 'items.*' => ['required', 'integer', 'distinct'], 'shipping_quotes' => ['required', 'array'], 'shipping_quotes.*' => ['required', 'numeric', 'min:45'], 'seller_notes' => ['sometimes', 'array'], 'seller_notes.*' => ['nullable', 'string', 'max:300'], 'voucher_codes' => ['sometimes', 'array'], 'voucher_codes.*' => ['nullable', 'string', 'regex:/^[A-Z0-9]{4,12}$/']]);
        $coordinates = $request->validate([
            'shipping_latitude' => ['nullable', 'required_with:shipping_longitude', 'numeric', 'between:-90,90'],
            'shipping_longitude' => ['nullable', 'required_with:shipping_latitude', 'numeric', 'between:-180,180'],
        ]);
        $data = array_merge($data, $coordinates);
        $location = $locations->address($data['shipping_province_code'], $data['shipping_city_code']);
        if (! $location) {
            throw ValidationException::withMessages(['shipping_city_code' => 'Select a city or municipality within the chosen province.']);
        }
        DB::transaction(function () use ($request, $data, $location, $shippingQuotes) {
            $selectedIds = $request->input('items');
            $cartItems = CartItem::where('user_id', $request->user()->id)->whereIn('id', $selectedIds)->orderBy('id')->lockForUpdate()->get();
            if ($cartItems->count() !== count($selectedIds)) {
                throw ValidationException::withMessages(['items' => 'Your cart selection changed. Review your cart and try again.']);
            }
            $products = Product::with(['seller.sellerDetail', 'category'])->whereIn('id', $cartItems->pluck('product_id'))
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $variants = ProductVariant::query()->whereIn('id', $cartItems->pluck('product_variant_id')->filter())
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
            $sellerIds = $cartItems->map(fn ($item) => (string) $products[$item->product_id]->seller_id)->unique();
            if (collect(array_keys($data['shipping_quotes']))->map(fn ($id) => (string) $id)->sort()->values()->all()
                !== $sellerIds->sort()->values()->all()) {
                throw ValidationException::withMessages(['shipping_quotes' => 'Refresh the shipping quote for every shop before placing your order.']);
            }
            if (collect(array_keys($data['seller_notes'] ?? []))->map(fn ($id) => (string) $id)->diff($sellerIds)->isNotEmpty()) {
                throw ValidationException::withMessages(['seller_notes' => 'A seller note does not belong to this checkout. Refresh and try again.']);
            }
            if (collect(array_keys($data['voucher_codes'] ?? []))->map(fn ($id) => (string) $id)->diff($sellerIds)->isNotEmpty()) {
                throw ValidationException::withMessages(['voucher_codes' => 'A voucher does not belong to this checkout. Refresh and try again.']);
            }
            foreach ($cartItems as $item) {
                $product = $products[$item->product_id];
                $variant = $item->product_variant_id ? $variants->get($item->product_variant_id) : null;
                if (($product->has_variations && (! $variant || $variant->product_id !== $product->id))
                    || (! $product->has_variations && $item->product_variant_id)) {
                    throw ValidationException::withMessages(['items' => 'A product variant in your cart is no longer available.']);
                }
            }
            foreach ($cartItems->whereNotNull('product_variant_id')->groupBy('product_variant_id') as $variantId => $items) {
                if ($items->sum('quantity') > $variants[$variantId]->stock) {
                    throw ValidationException::withMessages(['quantity' => 'Only '.$variants[$variantId]->stock.' of the selected variant remain.']);
                }
            }
            if (! hash_equals($this->revision($cartItems, $products, $variants), $request->input('checkout_revision'))) {
                throw ValidationException::withMessages(['checkout_revision' => 'An item or price changed while you were checking out. Review your updated cart before placing the order.']);
            }
            foreach ($cartItems as $item) {
                $product = $products[$item->product_id];
                foreach ($item->product_variant_id ? [] : ['color' => 'colors', 'size' => 'sizes'] as $choice => $field) {
                    $available = array_values(array_filter(array_map('trim', explode(',', $product->{$field} ?? ''))));
                    if (($available && ! in_array($item->{$choice}, $available, true))
                        || (! $available && trim((string) $item->{$choice}) !== '')) {
                        throw ValidationException::withMessages(['checkout_revision' => 'An item option changed. Review your updated cart before placing the order.']);
                    }
                }
            }
            foreach ($cartItems->groupBy(fn ($item) => $products[$item->product_id]->seller_id) as $sellerId => $items) {
                $sellerDetail = $products[$items->first()->product_id]->seller?->sellerDetail;
                $shippingQuote = $shippingQuotes->quote(
                    $sellerDetail?->province,
                    $sellerDetail?->municipality,
                    $location['province'],
                    $location['city'],
                );
                if (number_format((float) $data['shipping_quotes'][$sellerId], 2, '.', '') !== $shippingQuote['shipping_fee']) {
                    throw ValidationException::withMessages(['shipping_quotes.'.$sellerId => 'The Logistics route or shipping fee changed. Refresh the quote and review the total.']);
                }
                $buyerNote = trim((string) ($data['seller_notes'][$sellerId] ?? ''));
                $code = trim((string) ($data['voucher_codes'][$sellerId] ?? ''));
                $voucher = null;
                $discountCents = 0;
                if ($code !== '') {
                    $voucher = SellerVoucher::query()->where('seller_id', $sellerId)->where('code', $code)
                        ->lockForUpdate()->first();
                    if (! $voucher) {
                        throw ValidationException::withMessages(['voucher_codes.'.$sellerId => 'This shop voucher was not found.']);
                    }
                    $discountCents = app(SellerVoucherService::class)->discountCents(
                        $voucher, $items, $products, $variants, $request->user()->id);
                }
                $order = Order::create([
                    'buyer_id' => $request->user()->id, 'seller_id' => $sellerId,
                    'buyer_note' => $buyerNote !== '' ? $buyerNote : null,
                    'total_amount' => $items->sum(fn ($item) => $item->quantity * ($item->product_variant_id
                        ? $variants[$item->product_variant_id]->price : $products[$item->product_id]->price)),
                    'shipping_fee' => $shippingQuote['shipping_fee'],
                    'shipping_hub_count' => $shippingQuote['hub_count'],
                    'shipping_route_snapshot' => $shippingQuote['route'],
                    'status' => 'placed', 'payment_mode' => $request->payment_mode,
                    'voucher_discount' => number_format($discountCents / 100, 2, '.', ''),
                    'voucher_code' => $voucher?->code,
                    'shipping_address' => trim($data['shipping_address']).', '.$location['city'].', '.$location['province'],
                    'shipping_province_code' => $data['shipping_province_code'],
                    'shipping_city_code' => $data['shipping_city_code'],
                    'shipping_province' => $location['province'],
                    'shipping_city' => $location['city'],
                    'shipping_latitude' => $data['shipping_latitude'] ?? null,
                    'shipping_longitude' => $data['shipping_longitude'] ?? null,
                ]);
                if ($voucher) {
                    app(SellerVoucherService::class)->redeemLocked($voucher, $order->id, $request->user()->id, $discountCents);
                }
                $order->statusEvents()->create(['user_id' => $request->user()->id, 'to_status' => 'placed']);
                Notification::create(['user_id' => $sellerId, 'type' => 'new_order', 'title' => 'New order '.$order->number, 'message' => 'A buyer placed an order. Review it in Orders.', 'link' => route('seller.orders.show', $order)]);
                foreach ($items as $item) {
                    $product = $products[$item->product_id];
                    $variant = $item->product_variant_id ? $variants[$item->product_variant_id] : null;
                    $order->items()->create(['product_id' => $product->id, 'product_variant_id' => $variant?->id,
                        'quantity' => $item->quantity, 'color' => $item->color, 'size' => $item->size,
                        'price' => $variant?->price ?? $product->price]);
                    app(InventoryService::class)->changeLocked($product, -$item->quantity, 'checkout',
                        $request->user()->id, $order->id, variant: $variant);
                }
            }
            CartItem::whereIn('id', $cartItems->pluck('id'))->where('user_id', $request->user()->id)->delete();
        }, 3);

        return redirect()->route('buyer.orders.index')->with('success', 'Order placed.');
    }

    public function shippingQuote(Request $request, LocationCatalog $locations, CheckoutShippingQuoteService $shippingQuotes)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'], 'items.*' => ['required', 'integer', 'distinct'],
            'shipping_province_code' => ['required', 'string', 'size:9'],
            'shipping_city_code' => ['required', 'string', 'size:9'],
        ]);
        $location = $locations->address($data['shipping_province_code'], $data['shipping_city_code']);
        if (! $location) {
            throw ValidationException::withMessages(['shipping_city_code' => 'Select a valid delivery municipality.']);
        }

        $items = CartItem::query()->with('product.seller.sellerDetail')
            ->where('user_id', $request->user()->id)->whereIn('id', $data['items'])->get();
        if ($items->count() !== count($data['items']) || $items->contains(fn ($item) => ! $item->product?->seller)) {
            throw ValidationException::withMessages(['items' => 'Your cart changed. Refresh checkout and try again.']);
        }

        $quotes = [];
        foreach ($items->groupBy(fn ($item) => $item->product->seller_id) as $sellerId => $sellerItems) {
            $seller = $sellerItems->first()->product->seller->sellerDetail;
            $quotes[$sellerId] = $shippingQuotes->quote(
                $seller?->province, $seller?->municipality, $location['province'], $location['city'],
            );
        }

        return response()->json(['quotes' => $quotes]);
    }

    public function voucherQuote(Request $request, SellerVoucherService $vouchers)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'], 'items.*' => ['required', 'integer', 'distinct'],
            'seller_id' => ['required', 'integer'], 'code' => ['required', 'string', 'regex:/^[A-Z0-9]{4,12}$/'],
        ]);
        $cartItems = CartItem::query()->where('user_id', $request->user()->id)->whereIn('id', $data['items'])->get();
        if ($cartItems->count() !== count($data['items'])) {
            throw ValidationException::withMessages(['items' => 'Your cart selection changed. Refresh checkout.']);
        }
        $products = Product::query()->with(['category', 'seller'])->whereIn('id', $cartItems->pluck('product_id'))->get()->keyBy('id');
        foreach ($cartItems as $item) {
            $product = $products->get($item->product_id);
            if (! $product || $product->status !== 'approved' || $product->seller?->status !== 'approved'
                || $product->seller->archived_at || ($product->seller->account_status && $product->seller->account_status !== 'active')) {
                throw ValidationException::withMessages(['items' => 'A product in your cart is no longer available. Refresh checkout.']);
            }
        }
        $items = $cartItems->filter(fn ($item) => $products->get($item->product_id)?->seller_id === (int) $data['seller_id']);
        if ($items->isEmpty()) {
            throw ValidationException::withMessages(['code' => 'This shop is not in your checkout.']);
        }
        $variants = ProductVariant::query()->whereIn('id', $items->pluck('product_variant_id')->filter())->get()->keyBy('id');
        $voucher = SellerVoucher::query()->where('seller_id', $data['seller_id'])->where('code', $data['code'])->first();
        if (! $voucher) {
            throw ValidationException::withMessages(['code' => 'This shop voucher was not found.']);
        }
        $cents = $vouchers->discountCents($voucher, $items, $products, $variants, $request->user()->id);

        return response()->json(['discount' => number_format($cents / 100, 2, '.', ''), 'code' => $voucher->code]);
    }

    private function revision($cartItems, $products, $variants): string
    {
        return hash('sha256', json_encode($cartItems->map(function ($item) use ($products, $variants) {
            $product = $products->get($item->product_id);
            $variant = $item->product_variant_id ? $variants->get($item->product_variant_id) : null;
            return [
                $item->id, $item->product_id, $item->product_variant_id, $item->quantity, $item->color, $item->size,
                $product?->price, $product?->stock, $product?->status,
                $product?->colors, $product?->sizes, $product?->seller_id,
                $variant?->price, $variant?->stock, $variant?->options,
            ];
        })->all()));
    }
}
