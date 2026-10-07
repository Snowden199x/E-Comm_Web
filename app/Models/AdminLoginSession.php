<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminLoginSession extends Model
{
    protected $fillable = [
        'user_id',
        'login_at',
        'ip_address',
        'user_agent',
        'last_activity_at',
        'logged_out_at',
    ];

    protected function casts(): array
    {
        return [
            'login_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'logged_out_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getBrowserAttribute(): string
    {
        $userAgent = $this->user_agent ?? '';

        if (str_contains($userAgent, 'Edg/')) {
            return 'Microsoft Edge';
        }

        if (str_contains($userAgent, 'Chrome/')) {
            return 'Google Chrome';
        }

        if (str_contains($userAgent, 'Firefox/')) {
            return 'Mozilla Firefox';
        }

        if (str_contains($userAgent, 'Safari/')) {
            return 'Safari';
        }

        return 'Unknown Browser';
    }

    public function getDeviceAttribute(): string
    {
        $userAgent = $this->user_agent ?? '';

        if (preg_match('/Mobile|Android|iPhone|iPad/i', $userAgent)) {
            return 'Mobile Device';
        }

        return 'Desktop';
    }
}