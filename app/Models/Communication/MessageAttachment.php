<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['message_id', 'path', 'original_filename'])]
class MessageAttachment extends Model
{
    public function message()
    {
        return $this->belongsTo(Message::class);
    }
}