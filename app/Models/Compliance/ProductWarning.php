<?php

namespace App\Models\Compliance;

use App\Models\Ecommerce\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['product_id', 'seller_id', 'reason', 'details'])]
class ProductWarning extends Model
{
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}