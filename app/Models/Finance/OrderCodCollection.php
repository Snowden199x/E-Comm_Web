<?php

namespace App\Models\Finance;

use App\Models\Ecommerce\Order;
use App\Models\Profiles\LogisticsFacility;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class OrderCodCollection extends Model
{
    protected $fillable = ['order_id', 'rider_user_id', 'station_hub_id', 'amount', 'scan_key', 'collected_at'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'collected_at' => 'datetime'];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function rider()
    {
        return $this->belongsTo(User::class, 'rider_user_id');
    }

    public function stationHub()
    {
        return $this->belongsTo(LogisticsFacility::class, 'station_hub_id');
    }
}
