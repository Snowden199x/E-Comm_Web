<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Model;

class SellerVoucherRedemption extends Model
{
    protected $fillable = ['voucher_id', 'order_id', 'buyer_id', 'discount', 'voided_at'];

    protected function casts(): array
    {
        return ['voided_at' => 'datetime', 'discount' => 'decimal:2'];
    }

    public function voucher()
    {
        return $this->belongsTo(SellerVoucher::class, 'voucher_id');
    }
}
