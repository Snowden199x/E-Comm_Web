<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\CartItem;
use App\Models\Ecommerce\Order;
use App\Models\Ecommerce\Product;
use App\Models\Ecommerce\ProductVariant;
use App\Models\Ecommerce\SellerVoucher;
use App\Models\Profiles\BuyerDetail;
use App\Services\BuyerCheckoutService;
use App\Services\CheckoutLogisticsOptionsService;
use App\Services\CheckoutRevisionService;
use App\Services\CheckoutShippingQuoteService;
use App\Services\LocationCatalog;
use App\Services\SellerVoucherService;
use Illuminate\Http\Request;
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
        $checkoutRevision = app(CheckoutRevisionService::class)->forLoadedCart($cartItems);
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

        return view('buyer.checkout', compact('cartItems', 'buyerDetail', 'defaultAddress', 'checkoutRevision', 'locationOptions', 'defaultProvinceCode', 'defaultCityCode', 'sellerNotesEnabled', 'voucherOptions'));
    }

    public function store(Request $request, LocationCatalog $locations, BuyerCheckoutService $checkout)
    {
        $data = $request->validate(['shipping_address' => 'required|string|max:1800', 'shipping_province_code' => 'required|string|size:9', 'shipping_city_code' => 'required|string|size:9', 'payment_mode' => 'required|in:cod', 'checkout_revision' => ['required', 'regex:/^[a-f0-9]{64}$/'], 'items' => ['required', 'array', 'min:1'], 'items.*' => ['required', 'integer', 'distinct'], 'shipping_quotes' => ['required', 'array'], 'shipping_quotes.*' => ['required', 'numeric', 'min:45'], 'logistics_company_ids' => ['required', 'array'], 'logistics_company_ids.*' => ['required', 'integer', 'exists:logistics_companies,id'], 'seller_notes' => ['sometimes', 'array'], 'seller_notes.*' => ['nullable', 'string', 'max:300'], 'voucher_codes' => ['sometimes', 'array'], 'voucher_codes.*' => ['nullable', 'string', 'regex:/^[A-Z0-9]{4,12}$/']]);
        $coordinates = $request->validate([
            'shipping_latitude' => ['nullable', 'required_with:shipping_longitude', 'numeric', 'between:-90,90'],
            'shipping_longitude' => ['nullable', 'required_with:shipping_latitude', 'numeric', 'between:-180,180'],
        ]);
        $data = array_merge($data, $coordinates);
        $location = $locations->address($data['shipping_province_code'], $data['shipping_city_code']);
        if (! $location) {
            throw ValidationException::withMessages(['shipping_city_code' => 'Select a city or municipality within the chosen province.']);
        }

        $checkout->place($request->user(), $data, $location);

        return redirect()->route('buyer.orders.index')->with('success', 'Order placed.');
    }

    public function shippingQuote(
        Request $request,
        LocationCatalog $locations,
        CheckoutLogisticsOptionsService $logisticsOptions,
        CheckoutShippingQuoteService $shippingQuotes,
    ) {
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

        $preferredCompanyId = (int) (BuyerDetail::query()->where('user_id', $request->user()->id)
            ->value('preferred_delivery_company_id') ?? 0);
        $quotes = [];
        foreach ($items->groupBy(fn ($item) => $item->product->seller_id) as $sellerId => $sellerItems) {
            $seller = $sellerItems->first()->product->seller->sellerDetail;
            $options = $logisticsOptions->options(
                $seller?->province, $seller?->municipality, $location['province'], $location['city'],
            );
            if ($options === []) {
                throw ValidationException::withMessages([
                    'shipping_city_code' => 'No Logistics Company has an active Station Hub in both the Seller and delivery municipality yet.',
                ]);
            }
            // Prefer the Buyer's saved Company when it serves this route, otherwise the cheapest.
            $selected = collect($options)->firstWhere('company_id', $preferredCompanyId) ?? $options[0];
            $quotes[$sellerId] = array_merge($selected, [
                'options' => $options,
                'delivery_company_id' => $selected['company_id'],
                'route' => $selected['route'],
            ]);
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
}
