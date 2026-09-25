<?php

namespace App\Http\Controllers;

use App\Models\Communication\Conversation;
use App\Models\Communication\MarketplaceMessage;
use App\Models\Communication\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MessageDeletionController extends Controller
{
    public function support(Request $request, Conversation $conversation, Message $message): JsonResponse
    {
        $user = $request->user('admin') ?? $request->user();
        abort_unless($user && $message->conversation_id === $conversation->id
            && $message->sender_id === $user->id
            && ($user->role === 'admin' || $conversation->user_id === $user->id), 404);

        $paths = $message->attachments()->pluck('path');
        $message->attachments()->delete();
        $message->delete();
        foreach ($paths as $path) {
            Storage::disk(str_starts_with($path, 'seller-support/') ? 'local' : 'public')->delete($path);
        }
        $conversation->update(['last_message_at' => $conversation->messages()->max('created_at')]);

        return response()->json(['success' => true]);
    }

    public function marketplace(Request $request, MarketplaceMessage $message): JsonResponse
    {
        $conversation = $message->conversation;
        $user = $request->user();
        abort_unless($user && $conversation && $message->sender_id === $user->id
            && (($user->role === 'buyer' && $conversation->buyer_id === $user->id)
                || ($user->role === 'seller' && $conversation->seller_id === $user->id)), 404);

        $path = $message->attachment_path;
        $message->delete();
        if ($path) Storage::disk('local')->delete($path);
        $conversation->update(['last_message_at' => $conversation->messages()->max('created_at')]);

        return response()->json(['success' => true]);
    }
}
