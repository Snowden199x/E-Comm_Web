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
        $conversation = Conversation::where('user_id', auth()->id())->first();

        return view('buyer.messages', compact('conversation'));
    }

    public function start(Request $request)
    {
        $request->validate(['reason' => 'required|string']);

        $conversation = Conversation::updateOrCreate(
            ['user_id' => auth()->id()],
            ['status' => 'open']
        );

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => auth()->id(),
            'body' => $request->reason,
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => null,
            'body' => 'Thanks for reaching out! An admin will respond shortly.',
        ]);

        $conversation->update(['last_message_at' => now()]);

        return redirect()->route('buyer.messages.index');
    }

    public function close(Conversation $conversation)
    {
        abort_unless($conversation->user_id === auth()->id(), 403);

        $conversation->update(['status' => 'closed']);

        return redirect()->route('buyer.messages.index');
    }

    public function fetch(Conversation $conversation)
    {
        abort_unless($conversation->user_id === auth()->id(), 403);

        $conversation->load('messages.attachments');

        return response()->json([
            'status' => $conversation->status,
            'messages' => $conversation->messages->map(fn ($m) => [
                'id' => $m->id,
                'body' => $m->body,
                'is_mine' => $m->sender_id === auth()->id(),
                'is_system' => is_null($m->sender_id),
                'attachments' => $m->attachments->map(fn ($a) => [
                    'url' => asset('storage/' . $a->path),
                    'name' => $a->original_filename,
                ]),
            ]),
        ]);
    }

    public function store(Request $request, Conversation $conversation)
    {
        abort_unless($conversation->user_id === auth()->id(), 403);
        abort_if($conversation->status === 'closed', 400, 'This conversation is closed.');

        $request->validate([
            'body' => 'nullable|string',
            'attachment' => 'nullable|file|max:5120',
        ]);

        if (!$request->body && !$request->hasFile('attachment')) {
            return back()->withErrors(['body' => 'Type a message or attach a file.']);
        }

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