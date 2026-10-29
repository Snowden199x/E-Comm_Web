<?php

namespace App\Services;

use App\Models\Communication\Notification;
use App\Models\Ecommerce\CartItem;
use App\Models\Ecommerce\Order;
use App\Models\Ecommerce\Product;
use App\Models\Ecommerce\ProductVariant;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Shapes the Buyer mobile API payloads. The web Blade pages keep their own markup; this class
 * keeps every endpoint consistent (prices, images, shop names, order states) and in one place.
 */
class BuyerApiPresenter
{
    public function money(float|int|string|null $amount): string
    {
        $value = (float) $amount;

        return '₱'.number_format($value, fmod($value, 1) ? 2 : 0);
    }

    public function amount(float|int|string|null $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    public function imageUrl(?string $path): string
    {
        return $path ? asset('storage/'.$path) : asset('images/products/tote-bag.jpg');
    }

    public function productCard(Product $product): array
    {
        $image = $product->images->first();
        $seller = $product->seller;
        $price = (float) $product->price;
        $compareAt = (float) ($product->compare_at_price ?? 0);

        return [
            'id' => $product->id,
            'name' => $product->name,
            'price' => $this->money($price),
            'price_value' => $this->amount($price),
            'compare_at_price' => $compareAt > $price ? $this->money($compareAt) : null,
            'discount_percent' => $compareAt > $price ? (int) round((1 - ($price / $compareAt)) * 100) : 0,
            'image' => $this->imageUrl($image?->path),
            'shop' => $seller?->sellerDetail?->business_name ?: $seller?->name,
            'shop_id' => $seller?->id,
            'rating' => $product->reviews_avg_rating !== null ? round((float) $product->reviews_avg_rating, 1) : null,
            'sold' => (int) ($product->sold_count ?? 0),
            'stock' => (int) $product->stock,
            'has_variations' => (bool) $product->has_variations,
            'colors' => $this->options($product->colors),
            'sizes' => $this->options($product->sizes),
            'sold_out' => (int) $product->stock <= 0,
        ];
    }

    public function productDetail(Product $product): array
    {
        $seller = $product->seller;
        $images = $product->images->map(fn ($image) => $this->imageUrl($image->path))->values()->all();
        if ($images === []) {
            $images = [$this->imageUrl(null)];
        }

        return $this->productCard($product) + [
            'description' => $product->description,
            'category' => $product->category?->name,
            'images' => $images,
            'variants' => $product->variants->map(fn (ProductVariant $variant) => [
                'id' => $variant->id,
                'label' => $variant->label,
                'price' => $this->money($variant->price),
                'price_value' => $this->amount($variant->price),
                'stock' => (int) $variant->stock,
                'options' => $variant->options ?? [],
                'image' => $variant->image_path ? $this->imageUrl($variant->image_path) : null,
            ])->values()->all(),
            'specifications' => $product->specifications->map(fn ($spec) => [
                'name' => $spec->name, 'value' => $spec->value,
            ])->values()->all(),
            'seller' => $this->sellerSummary($seller),
        ];
    }

    public function sellerSummary(?User $seller): ?array
    {
        if (! $seller) {
            return null;
        }

        $detail = $seller->sellerDetail;

        return [
            'id' => $seller->id,
            'name' => $detail?->business_name ?: $seller->name,
            'owner' => $seller->name,
            'description' => $detail?->description,
            'province' => $detail?->province,
            'municipality' => $detail?->municipality,
            'rating' => $seller->reviews_avg_rating !== null ? round((float) $seller->reviews_avg_rating, 1) : null,
            'products_count' => isset($seller->approved_products_count) ? (int) $seller->approved_products_count : null,
        ];
    }

    /** One cart line, already joined with its product and variant. */
    public function cartLine(CartItem $item): array
    {
        $product = $item->product;
        $variant = $item->variant;
        $unit = (float) ($variant?->price ?? $product?->price ?? 0);
        $image = $product?->images->first();

        return [
            'id' => $item->id,
            'product_id' => $item->product_id,
            'name' => $product?->name,
            'image' => $this->imageUrl($image?->path),
            'unit_price' => $this->money($unit),
            'unit_price_value' => $this->amount($unit),
            'quantity' => (int) $item->quantity,
            'stock' => (int) ($variant?->stock ?? $product?->stock ?? 0),
            'color' => $item->color,
            'size' => $item->size,
            'variant_id' => $item->product_variant_id,
            'variant_label' => $variant?->label,
            'subtotal' => $this->money($unit * $item->quantity),
            'subtotal_value' => $this->amount($unit * $item->quantity),
            'shop_id' => $product?->seller_id,
            'shop' => $product?->seller?->sellerDetail?->business_name ?: $product?->seller?->name,
        ];
    }

    /**
     * Cart lines grouped per shop, with the running totals the mobile cart shows.
     *
     * @param  Collection<int, CartItem>  $items
     */
    public function cartPayload(Collection $items): array
    {
        $lines = $items->map(fn (CartItem $item) => $this->cartLine($item))->values();
        $groups = $lines->groupBy('shop_id')->map(function (Collection $shopLines, $shopId) {
            $subtotal = $shopLines->sum(fn ($line) => (float) $line['subtotal_value']);

            return [
                'shop_id' => (int) $shopId,
                'shop' => $shopLines->first()['shop'],
                'items' => $shopLines->values()->all(),
                'subtotal' => $this->money($subtotal),
                'subtotal_value' => $this->amount($subtotal),
            ];
        })->values()->all();
        $itemCount = $lines->sum('quantity');
        $subtotal = $lines->sum(fn ($line) => (float) $line['subtotal_value']);

        return [
            'items' => $lines->all(),
            'groups' => $groups,
            'totals' => [
                'items' => (int) $itemCount,
                'subtotal' => $this->money($subtotal),
                'subtotal_value' => $this->amount($subtotal),
            ],
        ];
    }

    public function orderSummary(Order $order): array
    {
        $firstItem = $order->items->first();
        $image = $firstItem?->product?->images->first();

        return [
            'id' => $order->id,
            'number' => $order->number,
            'status' => $order->status,
            'status_label' => $order->status_label,
            'shop' => $order->seller?->sellerDetail?->business_name ?: $order->seller?->name,
            'items_count' => (int) $order->items->sum('quantity'),
            'total' => $this->money($order->total_amount),
            'total_value' => $this->amount($order->total_amount),
            'shipping_fee' => $this->money($order->shipping_fee),
            'cod_amount' => $this->money($order->cod_amount ?? $order->buyer_payable),
            'cod_amount_value' => $this->amount($order->cod_amount ?? $order->buyer_payable),
            'image' => $this->imageUrl($image?->path),
            'placed_at' => $order->created_at?->toIso8601String(),
            'placed_at_label' => $order->created_at?->format('M j, Y g:i A'),
            'can_cancel' => in_array($order->status, \App\Services\OrderCancellationService::BUYER_CANCELLABLE_STATUSES, true),
        ];
    }

    public function orderDetail(Order $order): array
    {
        return $this->orderSummary($order) + [
            'items' => $order->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'name' => $item->product?->name,
                'image' => $this->imageUrl($item->product?->images->first()?->path),
                'quantity' => (int) $item->quantity,
                'price' => $this->money($item->price),
                'price_value' => $this->amount($item->price),
                'color' => $item->color,
                'size' => $item->size,
                'reviewed' => $item->review()->exists(),
            ])->values()->all(),
            'shipping_address' => $order->shipping_address,
            'shipping_city' => $order->shipping_city,
            'shipping_province' => $order->shipping_province,
            'payment_mode' => $order->payment_mode,
            'money_status' => $order->money_status,
            'money_status_label' => $order->money_status ? (\App\Models\Ecommerce\Order::MONEY_STATUSES[$order->money_status] ?? $order->money_status) : null,
            'voucher_code' => $order->voucher_code,
            'voucher_discount' => $this->money($order->voucher_discount ?? 0),
            'buyer_note' => $order->buyer_note,
            'tracking_number' => $order->tracking_number,
            'timeline' => $order->statusEvents->map(fn ($event) => [
                'status' => $event->to_status,
                'label' => \App\Models\Ecommerce\Order::STATUSES[$event->to_status] ?? $event->to_status,
                'at' => $event->created_at?->toIso8601String(),
                'at_label' => $event->created_at?->format('M j, Y g:i A'),
                'note' => $event->note ?? null,
            ])->values()->all(),
        ];
    }

    public function notification(Notification $notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'title' => $notification->title,
            'message' => $notification->message,
            'read' => $notification->read_at !== null,
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
            'time_ago' => $notification->created_at?->diffForHumans(),
        ];
    }

    /** @return array<int, string> */
    private function options(?string $value): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $value))));
    }
}
