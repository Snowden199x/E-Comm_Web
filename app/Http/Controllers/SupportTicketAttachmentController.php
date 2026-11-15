<?php

namespace App\Http\Controllers;

use App\Models\Support\SupportTicketAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * Support attachments stay on the private disk. The Buyer reads only files on their own
 * tickets; a Vendo Admin (admin guard) may read any ticket attachment.
 */
class SupportTicketAttachmentController extends Controller
{
    /** Only these types open in the browser. Anything else is sent as a download. */
    private const PREVIEWABLE = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

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

    /** Opens the file in the browser (for the Admin file viewer). The download route stays as it is. */
    public function adminPreview(SupportTicketAttachment $attachment)
    {
        $disk = Storage::disk('local');
        abort_unless($disk->exists($attachment->path), 404);

        // Read the type from the file itself, not from what the uploader claimed.
        $mime = (string) $disk->mimeType($attachment->path);
        if (! in_array($mime, self::PREVIEWABLE, true)) {
            return $this->download($attachment);
        }

        $fallback = preg_replace('/[^A-Za-z0-9._ -]/', '_', Str::ascii((string) $attachment->original_name)) ?: 'file';

        return response()->file($disk->path($attachment->path), [
            'Content-Type' => $mime,
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_INLINE, (string) $attachment->original_name, $fallback),
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    private function download(SupportTicketAttachment $attachment)
    {
        $disk = Storage::disk('local');
        abort_unless($disk->exists($attachment->path), 404);

        return $disk->download($attachment->path, $attachment->original_name);
    }
}
