<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use App\Models\User;
use App\Models\Category;
use App\Models\Compliance\ProductWarning;
use App\Models\Compliance\ProductViolation;

#[Fillable([
    'product_code', 'seller_id', 'category_id', 'name', 'description', 'price', 'stock',
    'brand', 'material', 'sizes', 'colors', 'weight', 'country_of_origin', 'status',
    'rejection_reason', 'rejection_details',
])]
class Product extends Model
{
    protected $casts = ['price' => 'decimal:2'];

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