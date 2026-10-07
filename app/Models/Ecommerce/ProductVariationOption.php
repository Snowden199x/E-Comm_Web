<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Model;

class ProductVariationOption extends Model
{
    public $timestamps = false;

    protected $fillable = ['value', 'sort_order'];
}
