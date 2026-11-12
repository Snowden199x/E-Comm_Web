<?php

namespace App\Models\Ecommerce;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    /** Maximum number of distinct product/variant lines one buyer cart can hold. */
    public const MAX_LINES = 200;

    /**
     * Buyer-facing cart counts stop at "99+" even though a cart may hold 200 lines, so the
     * header badge and dashboard tile keep their width.
     */
    public const DISPLAY_CAP = 99;

    public static function displayCount(int $count): string
    {
        return $count > self::DISPLAY_CAP ? self::DISPLAY_CAP.'+' : (string) $count;
    }

    protected $fillable = ['user_id', 'product_id', 'product_variant_id', 'quantity', 'color', 'size'];

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function getUnitPriceAttribute(): float
    {
        $product = $this->product;
        if (! $product) {
            return 0;
        }

        return $this->variant
            ? $product->salePriceFor((float) $this->variant->price)
            : (float) $product->effective_price;
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
