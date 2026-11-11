<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AccountDeletionRequest extends Model
{
    /** Reasons a buyer or seller can pick from. Kept server-side so the admin sees the same wording. */
    public const REASONS = [
        'No longer using Vendo',
        'Privacy concerns',
        'Too many emails or notifications',
        'Found another marketplace',
        'Created the account by mistake',
        'Other',
    ];

    public const STATUSES = ['pending', 'approved', 'rejected'];

    protected $fillable = [
        'user_id',
        'reasons',
        'comments',
        'status',
        'requested_at',
        'reviewed_by',
        'reviewed_at',
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'reasons' => 'array',
            'requested_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
