<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Model;

class ProductSpecification extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'value', 'sort_order'];
}
