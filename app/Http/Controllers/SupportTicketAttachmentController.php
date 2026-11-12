<?php

namespace App\Http\Controllers;

use App\Models\Support\SupportTicketAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Support attachments stay on the private disk. The Buyer reads only files on their own
 * tickets; a Vendo Admin (admin guard) may read any ticket attachment.
 */
class SupportTicketAttachmentController extends Controller
{
    public function buyer(Request $request, SupportTicketAttachment $attachment)
    {
        $buyer = $request->user();
        abort_unless($buyer && $buyer->role === 'buyer', 403);

        $ticket = $attachment->message?->ticket;
        abort_unless($ticket && (int) $ticket->user_id === (int) $buyer->id, 403);

        return $this->download($attachment);
    }

    public function admin(SupportTicketAttachment $attachment)
    {
        return $this->download($attachment);
    }

    private function download(SupportTicketAttachment $attachment)
    {
        $disk = Storage::disk('local');
        abort_unless($disk->exists($attachment->path), 404);

        return $disk->download($attachment->path, $attachment->original_name);
    }
}
