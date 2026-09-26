<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Communication\Conversation;
use App\Models\Communication\Message;
use App\Models\Communication\Notification;
use App\Models\Communication\MarketplaceConversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MessageController extends Controller
{
    public function index()
    {
        $conversation = Conversation::where('user_id', auth()->id())->whereNull('complaint_id')->latest('id')->first();
        $sellerConversations = $this->sellerConversations(auth()->id());

        return view('buyer.messages', compact('conversation', 'sellerConversations'));
    }

    public function sellerList()
    {
        return view('buyer.messages.partials.seller-list', [
            'sellerConversations' => $this->sellerConversations(auth()->id()),
        ]);
    }

    private function sellerConversations(int $buyerId)
    {
        return MarketplaceConversation::query()->where('buyer_id', $buyerId)
            ->with(['seller:id,name,profile_picture', 'order:id', 'latestMessage'])
            ->withCount(['messages as unread_count' => fn ($query) => $query
                ->where('sender_id', '!=', $buyerId)->whereNull('read_at')])
            ->orderByDesc('last_message_at')->get();
    }

    public function start(Request $request)
    {
        abort_unless($request->user()?->role === 'buyer', 403);
        $validated = $request->validate(['reason' => ['required', 'in:General Inquiry,Raise a Concern,Other']]);
        $active = Conversation::query()->where('user_id', $request->user()->id)
            ->whereNull('complaint_id')->where('status', 'open')->latest('id')->first();
        if ($active) return redirect()->route('buyer.messages.index');

        $conversation = Conversation::create(['user_id' => $request->user()->id, 'status' => 'open']);
        $conversation->messages()->create(['sender_id' => $request->user()->id, 'body' => $validated['reason']]);
        $conversation->messages()->create(['sender_id' => null, 'body' => 'Thanks for contacting Vendo Support. We received your '.$validated['reason'].' request. Our team will reply here.']);
        $conversation->update(['last_message_at' => now()]);
        Notification::create(['user_id' => null, 'type' => 'support_message', 'title' => 'New buyer support message', 'message' => 'A buyer sent a support message.', 'link' => route('admin.messages.index', ['conversation' => $conversation->id])]);

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

        $conversation->load('messages.attachments', 'messages.sender');

        return response()->json([
            'status' => $conversation->status,
            'messages' => $conversation->messages->map(fn ($m) => [
                'id' => $m->id,
                'body' => $m->body,
                'is_mine' => $m->sender_id === auth()->id(),
                'is_system' => is_null($m->sender_id),
                'avatar' => $m->sender?->profile_picture ? Storage::disk('public')->url($m->sender->profile_picture) : null,
                'initial' => mb_strtoupper(mb_substr($m->sender?->name ?? 'V', 0, 1)),
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

        if (! $request->filled('body') && ! $request->hasFile('attachment')) {
            throw ValidationException::withMessages(['body' => 'Type a message or attach a file.']);
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
        Notification::create(['user_id' => null, 'type' => 'support_message', 'title' => 'New buyer support message', 'message' => 'A buyer sent a support message.', 'link' => route('admin.messages.index', ['conversation' => $conversation->id])]);

        return $request->expectsJson() ? response()->json(['success' => true]) : back();
    }
}
