<?php

namespace App\Models\Profiles;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'user_id', 'last_name', 'first_name', 'middle_name', 'sex', 'birthday', 'valid_id_path',
    'province', 'municipality', 'barangay', 'street', 'house_no', 'zip_code',
    'business_name', 'business_permit_path',
])]
class SellerDetail extends Model
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

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }
}