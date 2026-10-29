<?php

namespace App\Services;

use App\Models\Communication\Notification;
use App\Models\Ecommerce\CartItem;
use App\Models\Ecommerce\Order;
use App\Models\Ecommerce\Product;
use App\Models\Ecommerce\ProductVariant;
use App\Models\Ecommerce\SellerVoucher;
use App\Models\Finance\DeliveryFeeSetting;
use App\Models\Profiles\LogisticsCompany;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Places the selected cart lines as one order per Seller, in a single locked transaction.
 * Shared by the web checkout and the Buyer mobile API so stock, vouchers, COD amount and the
 * rate snapshots are computed once, in the same order.
 */
class BuyerCheckoutService
{
    public function __construct(
        private CheckoutShippingQuoteService $shippingQuotes,
        private CheckoutRevisionService $revisions,
        private SellerVoucherService $vouchers,
        private InventoryService $inventory,
    ) {}

    /**
     * @param  array{items:array<int,int>, shipping_address:string, shipping_province_code:string, shipping_city_code:string, payment_mode:string, checkout_revision:string, shipping_quotes?:array, seller_notes?:array, voucher_codes?:array, shipping_latitude?:?string, shipping_longitude?:?string}  $data
     * @param  array{province:string, city:string}  $location
     * @return array<int, Order>
     */
    public function place(User $buyer, array $data, array $location): array
    {
        return DB::transaction(function () use ($buyer, $data, $location) {
            $selectedIds = array_values(array_map('intval', $data['items']));
            $cartItems = CartItem::where('user_id', $buyer->id)->whereIn('id', $selectedIds)->orderBy('id')->lockForUpdate()->get();
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
            if (array_key_exists('shipping_quotes', $data) && $data['shipping_quotes'] !== null
                && collect(array_keys($data['shipping_quotes']))->map(fn ($id) => (string) $id)->sort()->values()->all()
                    !== $sellerIds->sort()->values()->all()) {
                throw ValidationException::withMessages(['shipping_quotes' => 'Refresh the shipping quote for every shop before placing your order.']);
            }
            if (collect(array_keys($data['logistics_company_ids'] ?? []))->map(fn ($id) => (string) $id)->diff($sellerIds)->isNotEmpty()) {
                throw ValidationException::withMessages(['logistics_company_ids' => 'A Logistics choice does not belong to this checkout. Refresh and try again.']);
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
            if (! hash_equals($this->revisions->for($cartItems, $products, $variants), (string) $data['checkout_revision'])) {
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
            // Rates in force right now; each order saves its own copy so later changes never alter it.
            $feeSettings = DeliveryFeeSetting::current();
            $orders = [];
            foreach ($cartItems->groupBy(fn ($item) => $products[$item->product_id]->seller_id) as $sellerId => $items) {
                $sellerDetail = $products[$items->first()->product_id]->seller?->sellerDetail;
                $companyId = isset($data['logistics_company_ids'][$sellerId])
                    ? (int) $data['logistics_company_ids'][$sellerId] : null;
                $shippingQuote = $this->shippingQuotes->quote(
                    $sellerDetail?->province,
                    $sellerDetail?->municipality,
                    $location['province'],
                    $location['city'],
                    $companyId,
                );
                if (isset($data['shipping_quotes'][$sellerId])
                    && number_format((float) $data['shipping_quotes'][$sellerId], 2, '.', '') !== $shippingQuote['shipping_fee']) {
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
                    $discountCents = $this->vouchers->discountCents($voucher, $items, $products, $variants, $buyer->id);
                }
                $itemsTotal = $items->sum(fn ($item) => $item->quantity * ($item->product_variant_id
                    ? $variants[$item->product_variant_id]->price : $products[$item->product_id]->price));
                // COD: the exact cash the Rider must collect (items - voucher + delivery fee), frozen now.
                $codCents = max(0, (int) round(((float) $itemsTotal) * 100) - $discountCents
                    + (int) round(((float) $shippingQuote['shipping_fee']) * 100));
                $riderPay = LogisticsCompany::query()->whereKey($shippingQuote['delivery_company_id'] ?? 0)
                    ->value('rider_pay_per_parcel') ?? LogisticsCompany::DEFAULT_RIDER_PAY_PER_PARCEL;
                $order = Order::create([
                    'buyer_id' => $buyer->id, 'seller_id' => $sellerId,
                    'buyer_note' => $buyerNote !== '' ? $buyerNote : null,
                    'total_amount' => $itemsTotal,
                    'shipping_fee' => $shippingQuote['shipping_fee'],
                    'cod_amount' => number_format($codCents / 100, 2, '.', ''),
                    'money_status' => 'not_collected',
                    'rider_pay_rate' => number_format((float) $riderPay, 2, '.', ''),
                    'vendo_delivery_commission_rate' => $feeSettings->vendo_delivery_commission_rate,
                    'seller_commission_rate' => $feeSettings->seller_commission_rate,
                    'delivery_company_id' => $shippingQuote['delivery_company_id'] ?? null,
                    'shipping_hub_count' => $shippingQuote['hub_count'],
                    'shipping_route_snapshot' => $shippingQuote['route'],
                    'status' => 'placed', 'payment_mode' => $data['payment_mode'],
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
                    $this->vouchers->redeemLocked($voucher, $order->id, $buyer->id, $discountCents);
                }
                $order->statusEvents()->create(['user_id' => $buyer->id, 'to_status' => 'placed']);
                Notification::create(['user_id' => $sellerId, 'type' => 'new_order', 'title' => 'New order '.$order->number, 'message' => 'A buyer placed an order. Review it in Orders.', 'link' => route('seller.orders.show', $order)]);
                foreach ($items as $item) {
                    $product = $products[$item->product_id];
                    $variant = $item->product_variant_id ? $variants[$item->product_variant_id] : null;
                    $order->items()->create(['product_id' => $product->id, 'product_variant_id' => $variant?->id,
                        'quantity' => $item->quantity, 'color' => $item->color, 'size' => $item->size,
                        'price' => $variant?->price ?? $product->price]);
                    $this->inventory->changeLocked($product, -$item->quantity, 'checkout', $buyer->id, $order->id, variant: $variant);
                }
                $orders[] = $order;
            }
            CartItem::whereIn('id', $cartItems->pluck('id'))->where('user_id', $buyer->id)->delete();

            return $orders;
        }, 3);
    }
}
