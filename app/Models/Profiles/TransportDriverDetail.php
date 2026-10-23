<?php

namespace App\Models\Profiles;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TransportDriverDetail extends Model
{
    protected $fillable = [
        'user_id', 'province_hub_id', 'license_number', 'license_category', 'license_expires_at',
        'drivers_license_path', 'valid_id_path', 'status',
        'birthday', 'sex', 'home_address',
    ];

    protected function casts(): array
    {
        return ['license_expires_at' => 'date', 'birthday' => 'date'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function provinceHub()
    {
        return $this->belongsTo(LogisticsFacility::class, 'province_hub_id');
    }

    public function trips()
    {
        return $this->hasMany(LogisticsTransportTrip::class, 'driver_id');
    }
}
