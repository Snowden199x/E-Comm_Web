<?php

namespace App\Http\Controllers\Api\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\CartItem;
use App\Models\Ecommerce\Product;
use App\Models\Ecommerce\ProductVariant;
use App\Models\Ecommerce\SellerVoucher;
use App\Models\Profiles\BuyerDetail;
use App\Services\BuyerApiPresenter;
use App\Services\BuyerCheckoutService;
use App\Services\CheckoutRevisionService;
use App\Services\CheckoutShippingQuoteService;
use App\Services\LocationCatalog;
use App\Services\SellerVoucherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * COD checkout for the Buyer mobile app. The cart revision, shipping quote and voucher discount
 * are validated by the same services the web checkout uses, and the order is placed by
 * BuyerCheckoutService so both channels create identical orders.
 */
class BuyerCheckoutController extends Controller
{
    public function __construct(
        private BuyerApiPresenter $presenter,
        private CheckoutRevisionService $revisions,
    ) {}

    public function preview(Request $request, SellerVoucherService $vouchers, LocationCatalog $locations): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['required', 'integer', 'distinct'],
        ]);

        $cartItems = CartItem::with('product.images', 'product.category', 'product.seller.sellerDetail', 'variant')
            ->where('user_id', $request->user()->id)->whereIn('id', $data['items'])->orderBy('id')->get();
        if ($cartItems->count() !== count($data['items'])) {
            throw ValidationException::withMessages(['items' => 'Review your cart selection and try again.']);
        }

        $products = $cartItems->mapWithKeys(fn (CartItem $item) => [$item->product_id => $item->product]);
        $variants = $cartItems->filter(fn (CartItem $item) => $item->variant)
            ->mapWithKeys(fn (CartItem $item) => [$item->variant->id => $item->variant]);

        $options = [];
        $totals = [];
        foreach ($cartItems->groupBy(fn (CartItem $item) => $item->product->seller_id) as $sellerId => $sellerItems) {
            $options[(string) $sellerId] = $vouchers->checkoutOptions(
                (int) $sellerId, $sellerItems, $products, $variants, (int) $request->user()->id);
            $total = $sellerItems->sum(fn (CartItem $item) => $item->quantity * $item->unit_price);
            $totals[(string) $sellerId] = [
                'shop' => null,
                'items' => $this->presenter->money($total),
                'items_value' => $this->presenter->amount($total),
            ];
        }
        // Fill the shop name per seller for the checkout groups.
        foreach ($cartItems as $item) {
            $totals[(string) $item->product->seller_id]['shop'] =
                $item->product->seller?->sellerDetail?->business_name ?: $item->product->seller?->name;
        }

        $detail = BuyerDetail::query()->where('user_id', $request->user()->id)->first();
        [$provinceCode, $cityCode] = BuyerLocationController::codesForNames(
            $locations, $detail?->province, $detail?->municipality);

        return response()->json([
            'checkout_revision' => $this->revisions->forLoadedCart($cartItems),
            'cart' => $this->presenter->cartPayload($cartItems),
            'shops' => collect($totals)->map(fn (array $row, string $sellerId) => [
                'seller_id' => (int) $sellerId,
                'shop' => $row['shop'],
                'items' => $row['items'],
                'items_value' => $row['items_value'],
                'vouchers' => $options[$sellerId] ?? [],
            ])->values()->all(),
            'default_address' => [
                'shipping_address' => $detail ? trim(implode(', ', array_filter([
                    $detail->house_no, $detail->street, $detail->barangay, $detail->zip_code,
                ]))) : '',
                'province' => $detail?->province,
                'city' => $detail?->municipality,
                'province_code' => $provinceCode,
                'city_code' => $cityCode,
            ],
        ]);
    }

    public function shippingQuote(Request $request, LocationCatalog $locations, CheckoutShippingQuoteService $shippingQuotes): JsonResponse
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
        if ($items->count() !== count($data['items']) || $items->contains(fn (CartItem $item) => ! $item->product?->seller)) {
            throw ValidationException::withMessages(['items' => 'Your cart changed. Refresh checkout and try again.']);
        }

        $quotes = [];
        $totalCents = 0;
        foreach ($items->groupBy(fn (CartItem $item) => $item->product->seller_id) as $sellerId => $sellerItems) {
            $seller = $sellerItems->first()->product->seller->sellerDetail;
            $quote = collect($shippingQuotes->quote(
                $seller?->province, $seller?->municipality, $location['province'], $location['city'],
            ))->except('delivery_company_id')->all();
            $quote['shipping_fee_label'] = $this->presenter->money($quote['shipping_fee']);
            $quotes[(string) $sellerId] = $quote;
            $totalCents += (int) round(((float) $quote['shipping_fee']) * 100);
        }

        return response()->json([
            'quotes' => $quotes,
            'shipping_total' => number_format($totalCents / 100, 2, '.', ''),
            'shipping_total_label' => $this->presenter->money($totalCents / 100),
            'destination' => ['province' => $location['province'], 'city' => $location['city']],
        ]);
    }

    public function voucherQuote(Request $request, SellerVoucherService $vouchers): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'], 'items.*' => ['required', 'integer', 'distinct'],
            'seller_id' => ['required', 'integer'],
            'code' => ['required', 'string', 'regex:/^[A-Z0-9]{4,12}$/'],
        ]);
        $cartItems = CartItem::query()->where('user_id', $request->user()->id)->whereIn('id', $data['items'])->get();
        if ($cartItems->count() !== count($data['items'])) {
            throw ValidationException::withMessages(['items' => 'Your cart selection changed. Refresh checkout.']);
        }
        $products = Product::query()->with(['category', 'seller'])->whereIn('id', $cartItems->pluck('product_id'))->get()->keyBy('id');
        $items = $cartItems->filter(fn (CartItem $item) => $products->get($item->product_id)?->seller_id === (int) $data['seller_id']);
        if ($items->isEmpty()) {
            throw ValidationException::withMessages(['code' => 'This shop is not in your checkout.']);
        }
        $variants = ProductVariant::query()->whereIn('id', $items->pluck('product_variant_id')->filter())->get()->keyBy('id');
        $voucher = SellerVoucher::query()->where('seller_id', $data['seller_id'])->where('code', $data['code'])->first();
        if (! $voucher) {
            throw ValidationException::withMessages(['code' => 'This shop voucher was not found.']);
        }
        $cents = $vouchers->discountCents($voucher, $items, $products, $variants, (int) $request->user()->id);

        return response()->json([
            'code' => $voucher->code,
            'name' => $voucher->name,
            'discount' => number_format($cents / 100, 2, '.', ''),
            'discount_label' => $this->presenter->money($cents / 100),
        ]);
    }

    public function store(Request $request, LocationCatalog $locations, BuyerCheckoutService $checkout): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'], 'items.*' => ['required', 'integer', 'distinct'],
            'shipping_address' => ['required', 'string', 'max:1800'],
            'shipping_province_code' => ['required', 'string', 'size:9'],
            'shipping_city_code' => ['required', 'string', 'size:9'],
            'payment_mode' => ['required', Rule::in(['cod'])],
            'checkout_revision' => ['required', 'regex:/^[a-f0-9]{64}$/'],
            'seller_notes' => ['sometimes', 'array'],
            'seller_notes.*' => ['nullable', 'string', 'max:300'],
            'voucher_codes' => ['sometimes', 'array'],
            'voucher_codes.*' => ['nullable', 'string', 'regex:/^[A-Z0-9]{4,12}$/'],
            'shipping_latitude' => ['nullable', 'required_with:shipping_longitude', 'numeric', 'between:-90,90'],
            'shipping_longitude' => ['nullable', 'required_with:shipping_latitude', 'numeric', 'between:-180,180'],
        ]);

        $location = $locations->address($data['shipping_province_code'], $data['shipping_city_code']);
        if (! $location) {
            throw ValidationException::withMessages(['shipping_city_code' => 'Select a city or municipality within the chosen province.']);
        }

        $orders = $checkout->place($request->user(), $data, $location);
        $orders = collect($orders)->map(fn ($order) => $order->load('items.product.images', 'seller.sellerDetail'));

        return response()->json([
            'message' => 'Order placed. Pay in cash when the rider arrives.',
            'orders' => $orders->map(fn ($order) => $this->presenter->orderSummary($order))->values()->all(),
        ], 201);
    }
}
