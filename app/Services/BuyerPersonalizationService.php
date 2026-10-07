<?php

namespace App\Services;

use App\Models\Ecommerce\BuyerSavedItem;
use App\Models\Profiles\BuyerSetting;

class BuyerPersonalizationService
{
    public function savedItemsFor(int $buyerId): array
    {
        return BuyerSavedItem::query()->where('user_id', $buyerId)
            ->whereHas('product', fn ($query) => $query->where('status', 'approved')
                ->whereHas('seller', fn ($seller) => $seller->where('role', 'seller')
                    ->where('status', 'approved')->whereNull('archived_at')
                    ->where(fn ($active) => $active->whereNull('account_status')->orWhere('account_status', 'active'))))
            ->with(['product.images', 'product.seller.sellerDetail'])
            ->latest()->limit(60)->get()
            ->map(function (BuyerSavedItem $saved) {
                $product = $saved->product;
                $image = $product->images->first();

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => '₱'.number_format((float) $product->price, fmod((float) $product->price, 1) ? 2 : 0),
                    'image' => $image ? asset('storage/'.$image->path) : asset('images/products/tote-bag.jpg'),
                    'url' => route('buyer.products.show', $product, false),
                    'shop' => $product->seller?->sellerDetail?->business_name ?: $product->seller?->name,
                ];
            })->all();
    }

    public function preferencesFor(int $buyerId): ?array
    {
        $setting = BuyerSetting::query()->where('user_id', $buyerId)->first();

        return $setting ? [
            'text' => $setting->text_size,
            'calm' => $setting->reduce_motion,
            'sound' => $setting->notification_sound,
        ] : null;
    }
}
