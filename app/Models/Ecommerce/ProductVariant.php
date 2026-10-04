<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = ['label', 'sku', 'price', 'stock', 'image_path', 'options', 'sort_order'];

    protected $casts = ['price' => 'decimal:2', 'stock' => 'integer', 'options' => 'array'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
