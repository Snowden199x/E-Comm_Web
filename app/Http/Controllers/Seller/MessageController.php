<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Communication\Conversation;
use App\Models\Communication\Message;
use App\Models\Communication\Notification;
use App\Models\Communication\MarketplaceConversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $conversation = Conversation::query()->where('user_id', $request->user()->id)
            ->whereNull('complaint_id')->latest('id')->first();
        $conversation?->messages()->whereNull('read_at')
            ->where('sender_id', '!=', $request->user()->id)->update(['read_at' => now()]);
        $customerConversations = $this->customerConversations($request->user()->id);

        return view('seller.messages.index', compact('conversation', 'customerConversations'));
    }

    public function customerList(Request $request)
    {
        return view('seller.messages.partials.customer-list', [
            'customerConversations' => $this->customerConversations($request->user()->id),
        ]);
    }

    private function customerConversations(int $sellerId)
    {
        return MarketplaceConversation::query()->where('seller_id', $sellerId)
            ->with(['buyer:id,name,profile_picture', 'order:id', 'latestMessage'])
            ->withCount(['messages as unread_count' => fn ($query) => $query
                ->where('sender_id', '!=', $sellerId)->whereNull('read_at')])
            ->orderByDesc('last_message_at')->get();
    }

    public function start(Request $request)
    {
        $validated = $request->validate(['reason' => ['required', 'in:General Inquiry,Raise a Concern,Other']]);
        $active = Conversation::query()->where('user_id', $request->user()->id)
            ->whereNull('complaint_id')->where('status', 'open')->latest('id')->first();
        if ($active) return redirect()->route('seller.messages.index');

        $conversation = Conversation::create(['user_id' => $request->user()->id, 'status' => 'open']);
        $conversation->messages()->create(['sender_id' => $request->user()->id, 'body' => $validated['reason']]);
        $conversation->messages()->create(['sender_id' => null, 'body' => 'Thanks for contacting Vendo Support. We received your '.$validated['reason'].' request. Our team will reply here.']);
        $conversation->update(['last_message_at' => now()]);
        Notification::create(['user_id' => null, 'type' => 'support_message', 'title' => 'New seller support message', 'message' => 'A seller sent a support message.', 'link' => route('admin.messages.index', ['conversation' => $conversation->id])]);

        return redirect()->route('seller.messages.index');
    }

    public function fetch(Request $request, Conversation $conversation)
    {
        $this->authorizeConversation($request, $conversation);
        $validated = $request->validate(['after' => ['nullable', 'integer', 'min:0']]);
        $conversation->messages()->whereNull('read_at')
            ->where('sender_id', '!=', $request->user()->id)->update(['read_at' => now()]);
        $messages = $conversation->messages()->with(['sender:id,name,role,profile_picture', 'attachments'])
            ->when($validated['after'] ?? null, fn ($query, $id) => $query->where('id', '>', $id))
            ->orderBy('id')->limit(100)->get();

        return response()->json([
            'status' => $conversation->fresh()->status,
            'active_ids' => $conversation->messages()->pluck('id'),
            'messages' => $messages->map(fn (Message $message) => [
                'id' => $message->id,
                'body' => $message->body,
                'mine' => $message->sender_id === $request->user()->id,
                'sender' => $message->sender_id === $request->user()->id ? 'You' : 'Vendo Support',
                'system' => $message->sender_id === null,
                'avatar' => $message->sender?->profile_picture ? Storage::disk('public')->url($message->sender->profile_picture) : null,
                'initial' => mb_strtoupper(mb_substr($message->sender?->name ?? 'V', 0, 1)),
                'time' => $message->created_at->format('M j, g:i A'),
                'attachments' => $message->attachments->map(fn ($attachment) => [
                    'name' => $attachment->original_filename,
                    'url' => route('messages.attachments.show', $attachment),
                ]),
            ]),
        ]);
    }

    public function store(Request $request, Conversation $conversation)
    {
        $this->authorizeConversation($request, $conversation);
        abort_if($conversation->status !== 'open', 409, 'Reopen the conversation before sending.');
        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);
        if (! $request->filled('body') && ! $request->hasFile('attachment')) {
            return response()->json(['message' => 'Type a message or attach a file.'], 422);
        }

        $message = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $validated['body'] ?? null,
        ]);
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $message->attachments()->create([
                'path' => $file->store('seller-support'),
                'original_filename' => $file->getClientOriginalName(),
            ]);
        }
        $conversation->update(['last_message_at' => now()]);
        Notification::create(['user_id' => null, 'type' => 'support_message', 'title' => 'New seller support message', 'message' => 'A seller sent a support message.', 'link' => route('admin.messages.index', ['conversation' => $conversation->id])]);

        return response()->json(['success' => true]);
    }

    public function close(Request $request, Conversation $conversation)
    {
        $this->authorizeConversation($request, $conversation);
        $conversation->update(['status' => 'closed']);

        return back()->with('success', 'Conversation closed.');
    }

    public function reopen(Request $request, Conversation $conversation)
    {
        $this->authorizeConversation($request, $conversation);
        $conversation->update(['status' => 'open']);

        return back()->with('success', 'Conversation reopened.');
    }

    private function authorizeConversation(Request $request, Conversation $conversation): void
    {
        abort_unless($conversation->user_id === $request->user()->id && $conversation->complaint_id === null, 404);
    }
}
