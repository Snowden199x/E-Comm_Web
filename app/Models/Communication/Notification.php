<?php

namespace App\Models\Communication;

use App\Jobs\SendPushNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
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
            'new_seller_registration', 'new_buyer_registration', 'new_logistics_registration' => 'new-register-notif.svg',
            'new_courier_registration' => 'new-courier-notif.svg',
            'complaint_submitted' => 'complaint-notif.svg',
            'seller_compliance_warning' => 'compliance-notif.svg',
            'platform_announcement' => 'announcement-notif.svg',
            default => 'notifications-icon.svg',
        };
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Rider notifications also go out as a push. The row stays the source of truth, and
     * when Firebase is not configured the job is skipped so polling still works.
     */
    protected static function booted(): void
    {
        static::created(function (Notification $notification) {
            if ($notification->user_id === null) {
                return;
            }

            $role = User::query()->whereKey($notification->user_id)->value('role');
            if ($role !== 'courier') {
                return;
            }

            // Without Firebase credentials every device would answer "disabled";
            // skipping the job keeps the queue quiet and polling unaffected.
            if (! app(\App\Services\FirebasePushService::class)->enabled()) {
                return;
            }

            SendPushNotification::dispatch($notification->id)->afterCommit();
        });
    }
}
