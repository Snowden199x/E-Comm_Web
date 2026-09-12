<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['welcome_message'])]
class ChatSetting extends Model
{
    public static function currentWelcomeMessage(): string
    {
        return static::first()?->welcome_message ?? 'Hi! How can we help you today?';
    }
}