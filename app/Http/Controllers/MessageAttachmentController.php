<?php

namespace App\Http\Controllers;

use App\Models\Communication\MessageAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MessageAttachmentController extends Controller
{
    public function show(Request $request, MessageAttachment $attachment)
    {
        $user = $request->user();
        $attachment->loadMissing('message.conversation');
        $conversation = $attachment->message?->conversation;
        abort_unless($user && $conversation && (
            $user->role === 'admin' || $conversation->user_id === $user->id
        ), 404);

        $disk = str_starts_with($attachment->path, 'seller-support/') ? 'local' : 'public';
        abort_unless(Storage::disk($disk)->exists($attachment->path), 404);

        return Storage::disk($disk)->response($attachment->path, $attachment->original_filename);
    }
}
