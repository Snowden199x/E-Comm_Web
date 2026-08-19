<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'type',
        'title',
        'message',
        'link',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function timeAgo(): string
    {
        return $this->created_at->diffForHumans();
    }
        public function icon(): string
    {
        return match ($this->type) {
            'new_seller_registration', 'new_buyer_registration' => 'new-register-notif.svg',
            'new_courier_registration' => 'new-courier-notif.svg',
            'complaint_submitted' => 'complaint-notif.svg',
            'seller_compliance_warning' => 'compliance-notif.svg',
            'platform_announcement' => 'announcement-notif.svg',
            default => 'notifications-icon.svg',
        };
    }
}