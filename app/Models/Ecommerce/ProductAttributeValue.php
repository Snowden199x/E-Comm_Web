<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Model;

class ProductAttributeValue extends Model
{
    public $timestamps = false;

    protected $fillable = ['key', 'value'];

    protected $casts = ['value' => 'array'];
}
