<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['title', 'message', 'audience', 'scheduled_at', 'status', 'is_active'])]
class Announcement extends Model
{
    protected $casts = ['scheduled_at' => 'datetime'];
}