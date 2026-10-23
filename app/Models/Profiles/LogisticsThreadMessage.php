<?php

namespace App\Models\Profiles;

use Illuminate\Database\Eloquent\Model;

class LogisticsThreadMessage extends Model
{
    protected $fillable = ['logistics_thread_id', 'sender_id', 'body'];

    public function sender()
    {
        return $this->belongsTo(\App\Models\User::class, 'sender_id');
    }
}
