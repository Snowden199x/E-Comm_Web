<?php

namespace App\Models\Profiles;

use Illuminate\Database\Eloquent\Model;

class LogisticsCompany extends Model
{
    /** Used when a Company has no rider pay set; matches the migration default. */
    public const DEFAULT_RIDER_PAY_PER_PARCEL = 10.00;

    protected $fillable = ['logistics_center_id', 'business_name', 'status', 'rider_pay_per_parcel'];

    protected function casts(): array
    {
        return ['rider_pay_per_parcel' => 'decimal:2'];
    }

    public function logisticsCenter()
    {
        return $this->belongsTo(LogisticsCenter::class);
    }

    public function user()
    {
        return $this->hasOneThrough(\App\Models\User::class, LogisticsCenter::class, 'id', 'id', 'logistics_center_id', 'user_id');
    }

    public function facilities()
    {
        return $this->hasMany(LogisticsFacility::class, 'logistics_company_id');
    }

    public function provinceHubs()
    {
        return $this->facilities()->whereIn('kind', ['province_hub', 'main_hub']);
    }

    public function stations()
    {
        return $this->facilities()->where('kind', 'station_hub');
    }

    public function vehicles()
    {
        return $this->hasMany(LogisticsCompanyVehicle::class, 'logistics_company_id');
    }

    public function trips()
    {
        return $this->hasMany(LogisticsTransportTrip::class, 'logistics_company_id');
    }

    public function transportDrivers()
    {
        return TransportDriverDetail::query()->whereIn('province_hub_id', $this->provinceHubs()->select('id'));
    }
}
