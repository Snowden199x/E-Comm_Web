<?php

namespace App\Models\Profiles;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'user_id', 'last_name', 'first_name', 'middle_name', 'sex', 'birthday', 'valid_id_path',
    'province', 'municipality', 'barangay', 'street', 'house_no', 'zip_code',
    'vehicle_type', 'plate_number', 'drivers_license_path', 'or_cr_path',
])]
class CourierDetail extends Model
{
    protected $casts = ['birthday' => 'date'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getAgeAttribute(): int
    {
        return $this->birthday->age;
    }
}