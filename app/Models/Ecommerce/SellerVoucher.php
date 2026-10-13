<?php

namespace App\Models\Ecommerce;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SellerVoucher extends Model
{
    protected $fillable = ['seller_id', 'name', 'code', 'type', 'value', 'max_discount', 'min_spend',
        'usage_limit', 'per_buyer_limit', 'starts_at', 'ends_at', 'scope', 'status', 'deleted_at',
        'new_voucher_notified_at', 'expired_notified_at'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'deleted_at' => 'datetime',
            'new_voucher_notified_at' => 'datetime', 'expired_notified_at' => 'datetime',
            'value' => 'decimal:2', 'max_discount' => 'decimal:2', 'min_spend' => 'decimal:2'];
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'seller_voucher_products', 'voucher_id', 'product_id');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'seller_voucher_categories', 'voucher_id', 'category_id');
    }

    public function redemptions()
    {
        return $this->hasMany(SellerVoucherRedemption::class, 'voucher_id');
    }
}
