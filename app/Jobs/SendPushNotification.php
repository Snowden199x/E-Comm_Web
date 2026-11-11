<?php

namespace App\Jobs;

use App\Models\Communication\Notification;
use App\Models\Communication\PushDevice;
use App\Services\FirebasePushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Pushes one in-app notification to every device that user registered. The
 * notification row stays the source of truth, so a failed push only means the
 * Rider sees it on the next poll instead.
 */
class SendPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $notificationId) {}

    public function handle(FirebasePushService $push): void
    {
        $notification = Notification::query()->find($this->notificationId);
        if (! $notification || $notification->user_id === null) {
            return;
        }

        $devices = PushDevice::query()->where('user_id', $notification->user_id)->get();
        foreach ($devices as $device) {
            $result = $push->sendToToken($device->token, $notification->title, $notification->message, [
                'notification_id' => (string) $notification->id,
                'type' => (string) $notification->type,
                'link' => (string) $notification->link,
            ]);

            if ($result === FirebasePushService::INVALID) {
                $device->delete();
            }
        }
    }
}
