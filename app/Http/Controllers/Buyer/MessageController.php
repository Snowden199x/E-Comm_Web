<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Communication\Conversation;
use App\Models\Communication\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index()
    {
        $conversation = Conversation::firstOrCreate(['user_id' => auth()->id()]);
        $conversation->load('messages');

        return view('buyer.messages', compact('conversation'));
    }

    public function fetch()
    {
        $conversation = Conversation::firstOrCreate(['user_id' => auth()->id()]);
        $conversation->load('messages.attachments');

        return response()->json([
            'messages' => $conversation->messages->map(fn($m) => [
                'id' => $m->id,
                'body' => $m->body,
                'is_mine' => $m->sender_id === auth()->id(),
                'attachments' => $m->attachments->map(fn($a) => [
                    'url' => asset('storage/' . $a->path),
                    'name' => $a->original_filename,
                ]),
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'body' => 'nullable|string',
            'attachment' => 'nullable|file|max:5120',
        ]);

        if (!$request->body && !$request->hasFile('attachment')) {
            return back()->withErrors(['body' => 'Type a message or attach a file.']);
        }

        $conversation = Conversation::firstOrCreate(['user_id' => auth()->id()]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => auth()->id(),
            'body' => $request->body,
        ]);

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('chat-attachments', 'public');

            $message->attachments()->create([
                'path' => $path,
                'original_filename' => $request->file('attachment')->getClientOriginalName(),
            ]);
        }

        $conversation->update(['last_message_at' => now()]);

        return back();
    }
}
