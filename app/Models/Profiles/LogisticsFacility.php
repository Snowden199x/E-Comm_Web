<?php

namespace App\Models\Profiles;

use Illuminate\Database\Eloquent\Model;

class LogisticsFacility extends Model
{
    protected $fillable = [
        'logistics_center_id', 'kind', 'main_hub_owner_id', 'name',
        'province_code', 'province', 'municipality_code', 'municipality', 'address', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function network()
    {
        return $this->belongsTo(LogisticsCenter::class, 'logistics_center_id');
    }
}
