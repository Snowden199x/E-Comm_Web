<?php

namespace App\Http\Controllers\Api\Buyer;

use App\Http\Controllers\Controller;
use App\Models\BuyerAddress;
use App\Models\Ecommerce\CartItem;
use App\Models\Ecommerce\Product;
use App\Models\Ecommerce\ProductVariant;
use App\Models\Ecommerce\SellerVoucher;
use App\Models\Profiles\BuyerDetail;
use App\Services\BuyerApiPresenter;
use App\Services\BuyerCheckoutService;
use App\Services\CheckoutLogisticsOptionsService;
use App\Services\CheckoutRevisionService;
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

        // Same address book the web checkout lists: saved rows carry their own PSGC codes, and an
        // address outside the catalog stays visible but cannot be picked (usable: false).
        $savedAddresses = BuyerAddress::query()->where('user_id', $request->user()->id)
            ->orderByDesc('is_default')->oldest('id')->get()
            ->map(function (BuyerAddress $address) use ($locations) {
                $codes = $locations->codesForNames($address->province, $address->municipality);
                $line = $address->fullLine();

                return [
                    'id' => $address->id,
                    'label' => $address->label,
                    'line' => $line,
                    'province' => $address->province,
                    'city' => $address->municipality,
                    'province_code' => $codes ? (string) $codes[0] : '',
                    'city_code' => $codes ? (string) $codes[1] : '',
                    'is_default' => (bool) $address->is_default,
                    'usable' => (bool) ($codes && filled($line)),
                    'latitude' => $address->latitude,
                    'longitude' => $address->longitude,
                ];
            })->values()->all();

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
                'latitude' => $detail?->latitude,
                'longitude' => $detail?->longitude,
            ],
            'buyer' => [
                'name' => $request->user()->name,
                'phone_number' => $request->user()->phone_number,
            ],
            'saved_addresses' => $savedAddresses,
            'seller_notes_enabled' => true,
        ]);
    }

    public function shippingQuote(Request $request, LocationCatalog $locations, CheckoutLogisticsOptionsService $logisticsOptions): JsonResponse
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

        // Same Companies the web checkout offers: an active Station Hub network has to reach both
        // the Seller and this address. The Buyer's saved Company wins when it serves the route.
        $preferredCompanyId = (int) (BuyerDetail::query()->where('user_id', $request->user()->id)
            ->value('preferred_delivery_company_id') ?? 0);

        $quotes = [];
        $totalCents = 0;
        foreach ($items->groupBy(fn (CartItem $item) => $item->product->seller_id) as $sellerId => $sellerItems) {
            $seller = $sellerItems->first()->product->seller->sellerDetail;
            $options = $logisticsOptions->options(
                $seller?->province, $seller?->municipality, $location['province'], $location['city'],
            );
            if ($options === []) {
                throw ValidationException::withMessages([
                    'shipping_city_code' => 'No Logistics Company has an active Station Hub in both the Seller and delivery municipality yet.',
                ]);
            }
            $selected = collect($options)->firstWhere('company_id', $preferredCompanyId) ?? $options[0];
            $selected['shipping_fee_label'] = $this->presenter->money($selected['shipping_fee']);
            $selected['options'] = $options;
            $quotes[(string) $sellerId] = $selected;
            $totalCents += (int) round(((float) $selected['shipping_fee']) * 100);
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
            // Up to two stacked codes, exactly like the web checkout.
            'code' => ['nullable', 'string', 'regex:/^[A-Z0-9]{4,12}$/'],
            'codes' => ['nullable', 'array', 'max:'.SellerVoucherService::MAX_STACK],
            'codes.*' => ['required', 'string', 'regex:/^[A-Z0-9]{4,12}$/'],
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
        $codes = collect($data['codes'] ?? array_filter([$data['code'] ?? null]))
            ->map(fn ($code) => trim((string) $code))->filter()->unique()->values()->all();
        if ($codes === []) {
            throw ValidationException::withMessages(['code' => 'Enter a shop voucher code.']);
        }

        $variants = ProductVariant::query()->whereIn('id', $items->pluck('product_variant_id')->filter())->get()->keyBy('id');
        $found = [];
        foreach ($codes as $code) {
            $voucher = SellerVoucher::query()->where('seller_id', $data['seller_id'])->where('code', $code)->first();
            if (! $voucher) {
                throw ValidationException::withMessages(['code' => 'Shop voucher '.$code.' was not found.']);
            }
            $found[] = $voucher;
        }
        $stack = $vouchers->stackedDiscountCents($found, $items, $products, $variants, (int) $request->user()->id);

        return response()->json([
            'code' => $codes[0],
            'codes' => $codes,
            'name' => $found[0]->name,
            'discount' => number_format($stack['total'] / 100, 2, '.', ''),
            'discount_label' => $this->presenter->money($stack['total'] / 100),
            'lines' => collect($stack['lines'])->map(fn ($cents, $code) => [
                'code' => $code, 'discount' => number_format($cents / 100, 2, '.', ''),
            ])->values()->all(),
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
            'shipping_address_id' => ['nullable', 'integer'],
            'shipping_quotes' => ['sometimes', 'array'],
            'shipping_quotes.*' => ['required', 'numeric', 'min:45'],
            'logistics_company_ids' => ['sometimes', 'array'],
            'logistics_company_ids.*' => ['required', 'integer', 'exists:logistics_companies,id'],
            'seller_notes' => ['sometimes', 'array'],
            'seller_notes.*' => ['nullable', 'string', 'max:300'],
            // Each seller entry is either one code or a list of up to two stacked codes.
            'voucher_codes' => ['sometimes', 'array'],
            'voucher_codes.*' => ['nullable'],
            'voucher_codes.*.*' => ['nullable', 'string', 'regex:/^[A-Z0-9]{4,12}$/'],
            'shipping_latitude' => ['nullable', 'required_with:shipping_longitude', 'numeric', 'between:-90,90'],
            'shipping_longitude' => ['nullable', 'required_with:shipping_latitude', 'numeric', 'between:-180,180'],
        ]);

        // A saved address is authoritative, exactly like the web checkout: rebuild the shipping
        // fields from the Buyer's own row instead of trusting the posted text and codes.
        if (! empty($data['shipping_address_id'])) {
            $saved = BuyerAddress::query()
                ->where('user_id', $request->user()->id)
                ->find($data['shipping_address_id']);
            if (! $saved) {
                throw ValidationException::withMessages(['shipping_address_id' => 'Pick one of your saved addresses, or enter a new one.']);
            }
            $codes = $locations->codesForNames($saved->province, $saved->municipality);
            if (! $codes) {
                throw ValidationException::withMessages(['shipping_address_id' => 'This saved address is outside our delivery area. Update it in Account.']);
            }
            $data['shipping_address'] = $saved->fullLine();
            $data['shipping_province_code'] = (string) $codes[0];
            $data['shipping_city_code'] = (string) $codes[1];
            $data['shipping_latitude'] = $saved->latitude;
            $data['shipping_longitude'] = $saved->longitude;
        }

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
