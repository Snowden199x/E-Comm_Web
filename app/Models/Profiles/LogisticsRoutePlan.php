<?php

namespace App\Models\Profiles;

use Illuminate\Database\Eloquent\Model;

class LogisticsRoutePlan extends Model
{
    protected $fillable = [
        'from_logistics_center_id', 'to_logistics_center_id', 'priority', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function fromLogisticsCenter()
    {
        return $this->belongsTo(LogisticsCenter::class, 'from_logistics_center_id');
    }

    public function toLogisticsCenter()
    {
        return $this->belongsTo(LogisticsCenter::class, 'to_logistics_center_id');
    }

    public function stops()
    {
        return $this->hasMany(LogisticsRoutePlanStop::class, 'route_plan_id')->orderBy('position');
    }
}
