<?php

namespace App\Models\Complaints;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['complaint_id', 'actor', 'action'])]
class ComplaintActivity extends Model
{
    public function complaint()
    {
        return $this->belongsTo(Complaint::class);
    }
}