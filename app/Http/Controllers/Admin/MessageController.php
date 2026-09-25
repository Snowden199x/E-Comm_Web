<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Communication\Conversation;
use App\Models\Communication\Message;
use App\Models\Communication\MessageAttachment;
use App\Models\Communication\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function index(Request $request): View
    {
        $conversations = $this->filteredConversations($request);
        $activeId = $request->get('conversation');

        return view('admin.messages.index', compact('conversations', 'activeId'));
    }

    public function conversationsList(Request $request): View
    {
        $conversations = $this->filteredConversations($request);
        $activeId = $request->get('conversation');

        return view('admin.messages.partials.conversation-list', compact('conversations', 'activeId'));
    }

        public function thread(Conversation $conversation): View
    {
        $conversation->load(['user', 'complaint', 'messages.sender', 'messages.attachments']);

        $conversation->messages()
            ->whereNull('read_at')
            ->where('sender_id', '!=', Auth::guard('admin')->id())
            ->update(['read_at' => now()]);

        return view('admin.messages.partials.thread', compact('conversation'));
    }

    public function fetchMessages(Conversation $conversation)
    {
        $conversation->messages()
            ->whereNull('read_at')
            ->where('sender_id', '!=', Auth::guard('admin')->id())
            ->update(['read_at' => now()]);

        $messages = $conversation->messages()->with('attachments')->get();

        return response()->json([
            'messages' => $messages->map(fn ($m) => [
                'id' => $m->id,
                'body' => $m->body,
                'is_mine' => $m->sender_id === Auth::guard('admin')->id(),
                'created_at' => $m->created_at->format('g:i A'),
                'attachments' => $m->attachments->map(fn ($a) => [
                    'url' => route('messages.attachments.show', $a),
                    'name' => $a->original_filename,
                ]),
            ]),
        ]);
    }

    public function send(Request $request, Conversation $conversation)
    {
        $request->validate([
            'body' => 'nullable|string|max:2000',
            'attachment' => 'nullable|file|max:5120',
        ]);

        if (! $request->filled('body') && ! $request->hasFile('attachment')) {
            return back()->withErrors(['body' => 'Message or attachment is required.']);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => Auth::guard('admin')->id(),
            'body' => $request->body,
        ]);

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('chat-attachments', 'public');

            MessageAttachment::create([
                'message_id' => $message->id,
                'path' => $path,
                'original_filename' => $request->file('attachment')->getClientOriginalName(),
            ]);
        }

        $conversation->update(['last_message_at' => now()]);

        if (in_array($conversation->user?->role, ['seller', 'buyer'], true)) {
            Notification::create([
                'user_id' => $conversation->user_id,
                'type' => 'support_reply',
                'title' => 'New reply from Vendo Support',
                'message' => 'Your support conversation has a new reply.',
                'link' => $conversation->user->role === 'seller'
                    ? route('seller.messages.index') : route('buyer.messages.index'),
            ]);
        }

        return response()->json(['success' => true]);
    }

    private function filteredConversations(Request $request)
    {
        $query = Conversation::with(['user', 'latestMessage'])
            ->withCount(['messages as unread_count' => function ($q) {
                $q->whereNull('read_at')->where('sender_id', '!=', Auth::guard('admin')->id());
            }]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        }

        return $query->orderByDesc('last_message_at')->get();
    }
}
