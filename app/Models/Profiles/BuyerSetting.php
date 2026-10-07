<?php

namespace App\Models\Profiles;

use Illuminate\Database\Eloquent\Model;

class BuyerSetting extends Model
{
    protected $fillable = ['user_id', 'text_size', 'reduce_motion', 'notification_sound'];

    protected $casts = ['reduce_motion' => 'boolean', 'notification_sound' => 'boolean'];
}
