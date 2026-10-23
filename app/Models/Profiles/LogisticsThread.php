<?php

namespace App\Models\Profiles;

use Illuminate\Database\Eloquent\Model;

class LogisticsThread extends Model
{
    protected $fillable = ['logistics_company_id', 'facility_id', 'created_by', 'subject', 'topic', 'priority', 'status'];

    public function facility()
    {
        return $this->belongsTo(LogisticsFacility::class);
    }

    public function messages()
    {
        return $this->hasMany(LogisticsThreadMessage::class);
    }
}
