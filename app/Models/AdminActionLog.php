<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminActionLog extends Model
{
    protected $fillable = ['actor_id', 'target_user_id', 'action', 'reason'];

    public static function record(User $user, string $action, ?string $reason = null): void
    {
        self::create([
            'actor_id' => auth('admin')->id(),
            'target_user_id' => $user->id,
            'action' => $action,
            'reason' => $reason,
        ]);
    }
}
