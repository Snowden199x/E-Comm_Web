<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminActionLog extends Model
{
    protected $fillable = ['actor_id', 'target_user_id', 'action', 'reason'];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function target()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    /**
     * Record an Admin decision. Pass the affected account when there is one; platform-wide changes
     * (rates, policies, announcements) keep the target empty and show as "Platform setting".
     */
    public static function record(?User $user, string $action, ?string $reason = null): void
    {
        self::create([
            'actor_id' => auth('admin')->id(),
            'target_user_id' => $user?->id,
            'action' => $action,
            'reason' => $reason,
        ]);
    }
}
