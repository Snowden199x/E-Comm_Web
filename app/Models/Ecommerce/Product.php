<?php

namespace App\Models\Ecommerce;

use App\Models\Category;
use App\Models\Compliance\ProductViolation;
use App\Models\Compliance\ProductWarning;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'product_code', 'seller_id', 'category_id', 'name', 'description', 'price', 'stock',
    'brand', 'material', 'sizes', 'colors', 'weight', 'country_of_origin', 'status',
    'rejection_reason', 'rejection_details',
])]
class Product extends Model
{
    public const LOW_STOCK_THRESHOLD = 10;

    public const STOCK_LABELS = ['in_stock' => 'In Stock', 'low_stock' => 'Low Stock', 'out_of_stock' => 'Out of Stock'];

    protected $casts = ['price' => 'decimal:2', 'stock' => 'integer'];

    public function getStockStatusAttribute(): string
    {
        return $this->stock === 0 ? 'out_of_stock' : ($this->stock <= self::LOW_STOCK_THRESHOLD ? 'low_stock' : 'in_stock');
    }

    public function getRevisionAttribute(): string
    {
        $photos = $this->images()->reorder()->orderBy('sort_order')->orderBy('id')
            ->get(['id', 'path', 'sort_order'])->toArray();

        return hash('sha256', json_encode([$this->getRawOriginal(), $photos]));
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

    public function warnings()
    {
        return $this->hasMany(ProductWarning::class);
    }

    public function violations()
    {
        return $this->hasMany(ProductViolation::class);
    }
}
