<?php

namespace App\Models\Profiles;

use App\Models\Ecommerce\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class LogisticsTransportTrip extends Model
{
    protected $table = 'transport_trips';

    protected $fillable = [
        'logistics_company_id', 'trip_type', 'origin_facility_id', 'destination_facility_id',
        'driver_id', 'vehicle_id', 'status', 'scheduled_departure_at', 'expected_arrival_at',
        'departed_at', 'arrived_at', 'cancel_reason', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_departure_at' => 'datetime',
            'expected_arrival_at' => 'datetime',
            'departed_at' => 'datetime',
            'arrived_at' => 'datetime',
        ];
    }

    public function company()
    {
        return $this->belongsTo(LogisticsCompany::class, 'logistics_company_id');
    }

    public function origin()
    {
        return $this->belongsTo(LogisticsFacility::class, 'origin_facility_id');
    }

    public function destination()
    {
        return $this->belongsTo(LogisticsFacility::class, 'destination_facility_id');
    }

    public function driver()
    {
        return $this->belongsTo(TransportDriverDetail::class, 'driver_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(LogisticsCompanyVehicle::class, 'vehicle_id');
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'transport_trip_orders', 'trip_id', 'order_id')->withTimestamps();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
