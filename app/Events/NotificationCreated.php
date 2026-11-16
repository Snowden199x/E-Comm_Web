<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class NotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly int $notificationId,
        public readonly ?int $recipientId,
        public readonly string $type,
    ) {}

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        if ($this->recipientId !== null) {
            $channels = [new PrivateChannel('users.'.$this->recipientId)];

            // Publishing a product also changes the public buyer catalog.
            if ($this->type === 'product_approved') {
                $channels[] = new Channel('catalog');
            }

            return $channels;
        }

        // A legacy `new_order` row is deliberately excluded from the Admin feed.
        return $this->type === 'new_order' ? [] : [new PrivateChannel('admin.notifications')];
    }

    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    public function broadcastWith(): array
    {
        // The client fetches the authorized notification feed; no account or order
        // details are exposed through the websocket payload.
        return [
            'id' => $this->notificationId,
            'type' => $this->type,
        ];
    }

    public function broadcastQueue(): string
    {
        return 'default';
    }
}
