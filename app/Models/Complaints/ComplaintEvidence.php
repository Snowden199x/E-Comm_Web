<?php

namespace App\Models\Complaints;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['complaint_id', 'path', 'original_filename'])]
class ComplaintEvidence extends Model
{
    protected $table = 'complaint_evidences';

    public function complaint()
    {
        return $this->belongsTo(Complaint::class);
    }
}