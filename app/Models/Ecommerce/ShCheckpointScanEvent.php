<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Model;

class ShCheckpointScanEvent extends Model
{
    protected $fillable = [
        'order_id', 'station_id', 'checkpoint_id', 'route_plan_id', 'route_step', 'scan_key',
    ];

    public function checkpoint()
    {
        return $this->belongsTo(\App\Models\Profiles\LogisticsRouteCheckpoint::class, 'checkpoint_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
