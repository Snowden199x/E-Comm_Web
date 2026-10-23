<?php

namespace App\Models\Profiles;

use Illuminate\Database\Eloquent\Model;

class RiderServiceArea extends Model
{
    protected $fillable = ['courier_detail_id', 'station_hub_id', 'barangay_code', 'barangay'];

    public function rider()
    {
        return $this->belongsTo(CourierDetail::class, 'courier_detail_id');
    }

    public function station()
    {
        return $this->belongsTo(LogisticsFacility::class, 'station_hub_id');
    }
}
