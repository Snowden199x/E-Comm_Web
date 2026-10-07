<?php

namespace App\Models\Profiles;

use Illuminate\Database\Eloquent\Model;

class LogisticsRoutePlanStop extends Model
{
    protected $fillable = ['route_plan_id', 'checkpoint_id', 'position'];

    public function routePlan()
    {
        return $this->belongsTo(LogisticsRoutePlan::class, 'route_plan_id');
    }

    public function checkpoint()
    {
        return $this->belongsTo(LogisticsRouteCheckpoint::class, 'checkpoint_id');
    }
}
