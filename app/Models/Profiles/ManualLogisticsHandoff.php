<?php

namespace App\Models\Profiles;

use App\Models\Ecommerce\Order;
use Illuminate\Database\Eloquent\Model;

class ManualLogisticsHandoff extends Model
{
    protected $fillable = [
        'order_id', 'manifest_id', 'origin_company_id', 'destination_company_id',
        'origin_facility_id', 'destination_facility_id', 'status',
        'initiated_by', 'accepted_by', 'initiated_at', 'accepted_at',
    ];

    protected function casts(): array
    {
        return ['initiated_at' => 'datetime', 'accepted_at' => 'datetime'];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function manifest()
    {
        return $this->belongsTo(CrossCompanyManifest::class, 'manifest_id');
    }

    public function originCompany()
    {
        return $this->belongsTo(LogisticsCompany::class, 'origin_company_id');
    }

    public function destinationCompany()
    {
        return $this->belongsTo(LogisticsCompany::class, 'destination_company_id');
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
