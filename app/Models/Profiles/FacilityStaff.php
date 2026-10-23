<?php

namespace App\Models\Profiles;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FacilityStaff extends Model
{
    protected $fillable = ['facility_id', 'user_id', 'staff_title'];

    public function facility()
    {
        return $this->belongsTo(LogisticsFacility::class, 'facility_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
