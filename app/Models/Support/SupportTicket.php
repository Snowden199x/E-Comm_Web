<?php

namespace App\Models\Support;

use App\Models\Ecommerce\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_IN_REVIEW = 'in_review';
    public const STATUS_RESOLVED = 'resolved';

    public const STATUSES = [
        self::STATUS_OPEN => 'Open',
        self::STATUS_IN_REVIEW => 'In review',
        self::STATUS_RESOLVED => 'Resolved',
    ];

    public const CATEGORIES = [
        'order' => 'Order or delivery',
        'payment' => 'Payment or refund',
        'account' => 'Account and login',
        'product' => 'Product or shop question',
        'other' => 'Something else',
    ];

    protected $fillable = [
        'user_id', 'order_id', 'category', 'subject', 'status',
        'last_reply_at', 'closed_at',
    ];

    protected function casts(): array
    {
        return ['last_reply_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function messages()
    {
        return $this->hasMany(SupportTicketMessage::class)->orderBy('id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? 'Something else';
    }

    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }
}
