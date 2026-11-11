<?php

namespace App\Services;

use App\Models\Communication\Notification;
use App\Models\Ecommerce\Order;
use App\Models\Support\SupportTicket;
use App\Models\Support\SupportTicketMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Buyer support tickets: a Buyer asks Vendo Admin for help, both sides reply, and Admin marks the
 * ticket resolved. Separate from complaints, which are about a Seller or a specific order problem.
 * Nothing here moves money or changes an order.
 */
class SupportTicketService
{
    public function create(User $buyer, array $data): SupportTicket
    {
        abort_unless($buyer->role === 'buyer', 403);
        $category = (string) ($data['category'] ?? 'other');
        abort_unless(isset(SupportTicket::CATEGORIES[$category]), 422, 'Choose a valid topic.');
        $subject = trim((string) ($data['subject'] ?? ''));
        $body = trim((string) ($data['message'] ?? ''));
        abort_if($subject === '', 422, 'Add a short subject so Vendo knows what this is about.');
        abort_if($body === '', 422, 'Describe the problem so Vendo can help.');

        $orderId = isset($data['order_id']) && $data['order_id'] !== '' ? (int) $data['order_id'] : null;

        return DB::transaction(function () use ($buyer, $category, $subject, $body, $orderId) {
            if ($orderId !== null) {
                abort_unless(
                    Order::query()->whereKey($orderId)->where('buyer_id', $buyer->id)->exists(),
                    422,
                    'Choose one of your own orders.',
                );
            }

            $ticket = SupportTicket::create([
                'user_id' => $buyer->id,
                'order_id' => $orderId,
                'category' => $category,
                'subject' => $subject,
                'status' => SupportTicket::STATUS_OPEN,
                'last_reply_at' => now(),
            ]);

            $ticket->messages()->create([
                'sender_id' => $buyer->id,
                'body' => $body,
                'is_staff' => false,
            ]);

            Notification::create([
                'user_id' => null,
                'type' => 'support_ticket',
                'title' => 'Support request: '.$subject,
                'message' => $buyer->name.' opened a support ticket ('.$ticket->category_label.').',
                'link' => route('admin.support-tickets.show', $ticket),
            ]);

            return $ticket->fresh(['messages']);
        }, 3);
    }

    public function replyByBuyer(User $buyer, SupportTicket $ticket, string $body): SupportTicketMessage
    {
        abort_unless((int) $ticket->user_id === (int) $buyer->id, 403);
        $body = trim($body);
        abort_if($body === '', 422, 'Write a message before sending.');
        abort_if($ticket->status === SupportTicket::STATUS_RESOLVED, 409, 'This request is resolved. Open a new one if you still need help.');

        return DB::transaction(function () use ($buyer, $ticket, $body) {
            $message = $ticket->messages()->create([
                'sender_id' => $buyer->id,
                'body' => $body,
                'is_staff' => false,
            ]);

            $ticket->update(['last_reply_at' => now()]);

            Notification::create([
                'user_id' => null,
                'type' => 'support_ticket',
                'title' => 'Support reply: '.$ticket->subject,
                'message' => $buyer->name.' replied on support ticket #'.$ticket->id.'.',
                'link' => route('admin.support-tickets.show', $ticket),
            ]);

            return $message;
        }, 3);
    }

    public function replyByAdmin(User $admin, SupportTicket $ticket, string $body): SupportTicketMessage
    {
        $body = trim($body);
        abort_if($body === '', 422, 'Write a reply before sending.');

        return DB::transaction(function () use ($admin, $ticket, $body) {
            $message = $ticket->messages()->create([
                'sender_id' => $admin->id,
                'body' => $body,
                'is_staff' => true,
            ]);

            $ticket->update([
                'last_reply_at' => now(),
                'status' => $ticket->status === SupportTicket::STATUS_OPEN
                    ? SupportTicket::STATUS_IN_REVIEW
                    : $ticket->status,
            ]);

            Notification::create([
                'user_id' => $ticket->user_id,
                'type' => 'support_reply',
                'title' => 'Vendo replied to "'.$ticket->subject.'"',
                'message' => 'Open the support ticket to read the reply and answer.',
                'link' => route('buyer.support.show', $ticket),
            ]);

            return $message;
        }, 3);
    }

    public function setStatus(SupportTicket $ticket, string $status): SupportTicket
    {
        abort_unless(isset(SupportTicket::STATUSES[$status]), 422, 'Choose a valid status.');

        $ticket->update([
            'status' => $status,
            'closed_at' => $status === SupportTicket::STATUS_RESOLVED ? now() : null,
        ]);

        if ($status === SupportTicket::STATUS_RESOLVED) {
            Notification::create([
                'user_id' => $ticket->user_id,
                'type' => 'support_reply',
                'title' => 'Support request resolved: '.$ticket->subject,
                'message' => 'Vendo marked this request resolved. Open it to read the last reply.',
                'link' => route('buyer.support.show', $ticket),
            ]);
        }

        return $ticket->fresh();
    }
}
