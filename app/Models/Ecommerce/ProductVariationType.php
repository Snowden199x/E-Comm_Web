<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Model;

class ProductVariationType extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'sort_order'];

    public function options()
    {
        return $this->hasMany(ProductVariationOption::class)->orderBy('sort_order');
    }
}
