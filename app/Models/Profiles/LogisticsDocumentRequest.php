<?php

namespace App\Models\Profiles;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class LogisticsDocumentRequest extends Model
{
    protected $fillable = [
        'logistics_center_id', 'submitted_by', 'document_type', 'path', 'status',
        'reviewed_by', 'review_note', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function center()
    {
        return $this->belongsTo(LogisticsCenter::class, 'logistics_center_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
