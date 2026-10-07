<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Model;

class BuyerSavedItem extends Model
{
    protected $fillable = ['user_id', 'product_id'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
