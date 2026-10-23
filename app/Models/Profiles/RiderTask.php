<?php

namespace App\Models\Profiles;

use App\Models\Ecommerce\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RiderTask extends Model
{
    protected $fillable = [
        'rider_id', 'station_hub_id', 'order_id', 'type', 'status', 'sequence',
        'assigned_by', 'assigned_at', 'started_at', 'completed_at', 'failure_reason',
    ];

    protected function casts(): array
    {
        return ['assigned_at' => 'datetime', 'started_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function rider()
    {
        return $this->belongsTo(CourierDetail::class, 'rider_id');
    }

    public function station()
    {
        return $this->belongsTo(LogisticsFacility::class, 'station_hub_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
