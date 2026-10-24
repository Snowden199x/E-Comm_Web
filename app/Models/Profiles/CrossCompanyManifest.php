<?php

namespace App\Models\Profiles;

use Illuminate\Database\Eloquent\Model;

class CrossCompanyManifest extends Model
{
    protected $fillable = [
        'origin_company_id', 'destination_company_id', 'origin_facility_id', 'destination_facility_id',
        'driver_id', 'vehicle_id', 'status', 'expected_arrival_at', 'departed_at', 'completed_at', 'created_by',
    ];

    protected function casts(): array
    {
        return ['expected_arrival_at' => 'datetime', 'departed_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function handoffs()
    {
        return $this->hasMany(ManualLogisticsHandoff::class, 'manifest_id');
    }

    public function driver()
    {
        return $this->belongsTo(TransportDriverDetail::class, 'driver_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(LogisticsCompanyVehicle::class, 'vehicle_id');
    }

    public function originFacility()
    {
        return $this->belongsTo(LogisticsFacility::class, 'origin_facility_id');
    }

    public function destinationFacility()
    {
        return $this->belongsTo(LogisticsFacility::class, 'destination_facility_id');
    }
}
