<?php

namespace App\Models\Ecommerce;

use App\Models\Category;
use App\Models\Compliance\ProductViolation;
use App\Models\Compliance\ProductWarning;
use App\Models\Communication\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'product_code', 'seller_id', 'category_id', 'name', 'description', 'price', 'stock',
    'brand', 'material', 'sizes', 'colors', 'weight', 'country_of_origin', 'status',
    'rejection_reason', 'rejection_details',
    'condition', 'video_path', 'weight_kg', 'package_length', 'package_width', 'package_height',
    'is_fragile', 'has_variations', 'compare_at_price',
    'sale_type', 'sale_value', 'sale_starts_at', 'sale_ends_at',
])]
class Product extends Model
{
    use SoftDeletes;

    public const LOW_STOCK_THRESHOLD = 10;

    public const STOCK_LABELS = ['in_stock' => 'In Stock', 'low_stock' => 'Low Stock', 'out_of_stock' => 'Out of Stock'];

    protected $casts = ['price' => 'decimal:2', 'stock' => 'integer', 'weight_kg' => 'decimal:3',
        'is_fragile' => 'boolean', 'has_variations' => 'boolean', 'compare_at_price' => 'decimal:2',
        'sale_value' => 'decimal:2', 'sale_starts_at' => 'datetime', 'sale_ends_at' => 'datetime'];

    protected static function booted(): void
    {
        static::updated(function (Product $product) {
            if (! $product->wasChanged('stock')) {
                return;
            }
            $before = (int) $product->getOriginal('stock');
            $after = (int) $product->stock;
            if ($after === 0 && $before > 0) {
                $label = 'Out of stock';
            } elseif ($after > 0 && $after <= self::LOW_STOCK_THRESHOLD && $before > self::LOW_STOCK_THRESHOLD) {
                $label = 'Low stock';
            } else {
                return;
            }

            Notification::create([
                'user_id' => $product->seller_id,
                'type' => 'inventory_alert',
                'title' => $label.': '.$product->name,
                'message' => $after.' units remain. Check Products & Inventory.',
                'link' => route('seller.products.show', $product),
            ]);
        });
    }

    public function getStockStatusAttribute(): string
    {
        return $this->stock === 0 ? 'out_of_stock' : ($this->stock <= self::LOW_STOCK_THRESHOLD ? 'low_stock' : 'in_stock');
    }

    /** A sale is live only inside its own date window. */
    public function hasActiveSale(): bool
    {
        if (! in_array($this->sale_type, ['percent', 'fixed'], true) || $this->sale_value === null) {
            return false;
        }
        $now = now();
        if ($this->sale_starts_at && $now->lt($this->sale_starts_at)) {
            return false;
        }
        if ($this->sale_ends_at && $now->gt($this->sale_ends_at)) {
            return false;
        }

        return (float) $this->sale_value > 0;
    }

    /** Apply the live sale to any unit price (base product or a variant). */
    public function salePriceFor(float $base): float
    {
        if (! $this->hasActiveSale()) {
            return $base;
        }

        $value = (float) $this->sale_value;
        $price = $this->sale_type === 'percent' ? $base * (1 - $value / 100) : $base - $value;

        return max(0.01, round($price, 2));
    }

    public function getEffectivePriceAttribute(): float
    {
        return $this->salePriceFor((float) $this->price);
    }

    public function getSaleAmountAttribute(): ?float
    {
        return $this->hasActiveSale() ? round((float) $this->price - $this->salePriceFor((float) $this->price), 2) : null;
    }

    /** True when nothing can be added: simple stock is zero, or every variant is zero. */
    public function getIsSoldOutAttribute(): bool
    {
        if (! $this->has_variations) {
            return (int) $this->stock <= 0;
        }

        return ! $this->variants()->where('stock', '>', 0)->exists();
    }

    public function getRevisionAttribute(): string
    {
        $photos = $this->images()->reorder()->orderBy('sort_order')->orderBy('id')
            ->get(['id', 'path', 'sort_order'])->toArray();

        $variants = $this->variants()->reorder()->orderBy('id')->get(['id', 'sku', 'price', 'stock', 'options'])->toArray();
        $attributes = $this->attributeValues()->orderBy('key')->get(['key', 'value'])->toArray();
        $specifications = $this->specifications()->reorder()->orderBy('id')->get(['name', 'value', 'sort_order'])->toArray();

        return hash('sha256', json_encode([$this->getRawOriginal(), $photos, $variants, $attributes, $specifications]));
    }

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function attributeValues()
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    public function specifications()
    {
        return $this->hasMany(ProductSpecification::class)->orderBy('sort_order');
    }

    public function variationTypes()
    {
        return $this->hasMany(ProductVariationType::class)->orderBy('sort_order');
    }

    public function warnings()
    {
        return $this->hasMany(ProductWarning::class);
    }

    public function violations()
    {
        return $this->hasMany(ProductViolation::class);
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function publishedReviews()
    {
        return $this->reviews()->where('visibility', 'published');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeAvailableToBuy($query)
    {
        return $query->where('status', 'approved')
            ->whereHas('seller', fn ($seller) => $seller->where('status', 'approved')
                ->whereNull('archived_at')
                ->where(fn ($active) => $active->whereNull('account_status')->orWhere('account_status', 'active')));
    }

    /** Listings a buyer can still add to a cart: simple stock above zero, or at least one sellable variant. */
    public function scopeInStock($query)
    {
        return $query->where(function ($outer) {
            $outer->where(function ($simple) {
                $simple->where('has_variations', false)->where('stock', '>', 0);
            })->orWhereHas('variants', fn ($variants) => $variants->where('stock', '>', 0));
        });
    }

    public function scopeWithCardMetrics($query)
    {
        return $query->withAvg('publishedReviews as reviews_avg_rating', 'rating')
            ->withSum(['orderItems as sold_count' => fn ($items) => $items->whereHas('order',
                fn ($orders) => $orders->whereIn('status', Order::SALES_STATUSES))], 'quantity');
    }
}
