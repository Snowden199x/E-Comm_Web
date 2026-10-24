<?php

namespace App\Models\Profiles;

use App\Models\Ecommerce\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class OrderDeliveryProof extends Model
{
    protected $fillable = [
        'order_id', 'rider_id', 'station_hub_id', 'scan_key', 'recipient_name',
        'photo_path', 'notes', 'captured_at',
    ];

    protected function casts(): array
    {
        return ['captured_at' => 'datetime'];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function rider()
    {
        return $this->belongsTo(User::class, 'rider_id');
    }
}
