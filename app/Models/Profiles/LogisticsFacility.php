<?php

namespace App\Models\Profiles;

use Illuminate\Database\Eloquent\Model;

class LogisticsFacility extends Model
{
    protected $fillable = [
        'logistics_center_id', 'logistics_company_id', 'parent_id', 'kind', 'main_hub_owner_id', 'name',
        'province_hub_key', 'station_municipality_key', 'province_code', 'province', 'municipality_code', 'municipality',
        'address', 'latitude', 'longitude', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function network()
    {
        return $this->belongsTo(LogisticsCenter::class, 'logistics_center_id');
    }

    public function company()
    {
        return $this->belongsTo(LogisticsCompany::class, 'logistics_company_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function staff()
    {
        return $this->hasOne(FacilityStaff::class, 'facility_id');
    }

    public function riders()
    {
        return $this->hasMany(CourierDetail::class, 'station_hub_id');
    }

    public function transportDrivers()
    {
        return $this->hasMany(TransportDriverDetail::class, 'province_hub_id');
    }

    public function isProvinceHub(): bool
    {
        return in_array($this->kind, ['province_hub', 'main_hub'], true);
    }

}
