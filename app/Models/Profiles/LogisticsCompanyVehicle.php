<?php

namespace App\Models\Profiles;

use Illuminate\Database\Eloquent\Model;

class LogisticsCompanyVehicle extends Model
{
    protected $fillable = [
        'logistics_company_id', 'province_hub_id', 'vehicle_code', 'plate_number', 'vehicle_type', 'capacity_kg', 'status',
        'maintenance_note', 'maintenance_at',
    ];

    protected function casts(): array
    {
        return ['maintenance_at' => 'datetime'];
    }

    public function company()
    {
        return $this->belongsTo(LogisticsCompany::class, 'logistics_company_id');
    }

    public function provinceHub()
    {
        return $this->belongsTo(LogisticsFacility::class, 'province_hub_id');
    }

    public function trips()
    {
        return $this->hasMany(LogisticsTransportTrip::class, 'vehicle_id');
    }
}
