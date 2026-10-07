<?php

namespace App\Models\Profiles;

use Illuminate\Database\Eloquent\Model;

class LogisticsRouteCheckpoint extends Model
{
    protected $fillable = [
        'code', 'name', 'province', 'municipality', 'province_code', 'municipality_code', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function planStops()
    {
        return $this->hasMany(LogisticsRoutePlanStop::class, 'checkpoint_id');
    }
}
