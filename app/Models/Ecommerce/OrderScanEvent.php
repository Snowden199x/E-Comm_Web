<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Model;

class OrderScanEvent extends Model
{
    protected $fillable = [
        'order_id', 'actor_id', 'logistics_center_id', 'scan_type',
        'from_status', 'to_status', 'scan_key',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
