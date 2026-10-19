<?php

namespace App\Models\Profiles;

use App\Models\Ecommerce\Order;
use Illuminate\Database\Eloquent\Model;

class OrderFacilityStop extends Model
{
    protected $fillable = ['facility_id', 'position', 'arrived_at', 'sorted_at', 'departed_at'];

    protected $casts = ['arrived_at' => 'datetime', 'sorted_at' => 'datetime', 'departed_at' => 'datetime'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function facility()
    {
        return $this->belongsTo(LogisticsFacility::class);
    }
}
