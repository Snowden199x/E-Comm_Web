<?php

namespace App\Http\Controllers;

use App\Models\Communication\Conversation;
use App\Models\Communication\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class MessageDeletionController extends Controller
{
    public function supportConversation(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user('admin') ?? $request->user();
        abort_unless($user && $user->role === 'admin', 404);

        $paths = DB::transaction(function () use ($conversation) {
            $paths = $conversation->messages()->with('attachments')->get()
                ->flatMap(fn (Message $message) => $message->attachments->pluck('path'))->all();
            $conversation->delete();

            return $paths;
        });
        foreach ($paths as $path) {
            Storage::disk(str_starts_with($path, 'seller-support/') ? 'local' : 'public')->delete($path);
        }

        return response()->json(['success' => true]);
    }
}
