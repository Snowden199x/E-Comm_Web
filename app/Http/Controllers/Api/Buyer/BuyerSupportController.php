<?php

namespace App\Http\Controllers\Api\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Communication\Conversation;
use App\Models\Communication\Message;
use App\Models\Communication\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Vendo Support chat for the Buyer app. Reads and writes the same
 * Conversation/Message rows the web Messages page uses, so a message sent on
 * either side appears on both.
 */
class BuyerSupportController extends Controller
{
    /** The three topics the web page offers, with the copy it shows. */
    private const REASONS = [
        'General Inquiry' => 'Questions about how Vendo works, your account or an order.',
        'Raise a Concern' => 'Report a problem with a seller, product or delivery.',
        'Other' => 'Anything else you would like help with.',
    ];

    /** The buyer's latest support conversation and the topics to start one. */
    public function show(Request $request): JsonResponse
    {
        $buyer = $request->user();
        abort_unless($buyer && $buyer->role === 'buyer', 403);

        $conversation = $this->conversationQuery((int) $buyer->id)->latest('id')->first();

        return response()->json([
            'conversation' => $conversation ? $this->payload($conversation, (int) $buyer->id) : null,
            'reasons' => collect(self::REASONS)->map(fn (string $description, string $name) => [
                'name' => $name,
                'description' => $description,
            ])->values()->all(),
        ]);
    }

    /** Starts a support conversation, or returns the one that is already open. */
    public function start(Request $request): JsonResponse
    {
        $buyer = $request->user();
        abort_unless($buyer && $buyer->role === 'buyer', 403);

        $data = $request->validate([
            'reason' => ['required', Rule::in(array_keys(self::REASONS))],
        ]);

        $existing = $this->conversationQuery((int) $buyer->id)
            ->where('status', 'open')->latest('id')->first();
        if ($existing) {
            return response()->json([
                'conversation' => $this->payload($existing, (int) $buyer->id),
                'created' => false,
            ]);
        }

        $conversation = Conversation::create(['user_id' => $buyer->id, 'status' => 'open']);
        $conversation->messages()->create(['sender_id' => $buyer->id, 'body' => $data['reason']]);
        $conversation->messages()->create([
            'sender_id' => null,
            'body' => 'Thanks for contacting Vendo Support. We received your '.$data['reason'].' request. Our team will reply here.',
        ]);
        $conversation->update(['last_message_at' => now()]);
        Notification::create([
            'user_id' => null,
            'type' => 'support_message',
            'title' => 'New buyer support message',
            'message' => 'A buyer sent a support message.',
            'link' => route('admin.messages.index', ['conversation' => $conversation->id]),
        ]);

        return response()->json([
            'conversation' => $this->payload($conversation->fresh(), (int) $buyer->id),
            'created' => true,
        ], 201);
    }

    /** The full thread, including messages Admin sent from the web. */
    public function thread(Request $request, int $conversation): JsonResponse
    {
        $record = $this->owned($request, $conversation);

        return response()->json([
            'conversation' => $this->payload($record, (int) $request->user()->id),
        ]);
    }

    public function store(Request $request, int $conversation): JsonResponse
    {
        $record = $this->owned($request, $conversation);
        abort_if($record->status === 'closed', 400, 'This conversation has ended. Start a new one.');

        $request->validate([
            'body' => ['nullable', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'max:5120'],
        ]);
        if (trim((string) $request->input('body')) === '' && ! $request->hasFile('attachment')) {
            throw ValidationException::withMessages(['body' => 'Type a message or attach a file.']);
        }

        $body = trim((string) $request->input('body'));
        $message = Message::create([
            'conversation_id' => $record->id,
            'sender_id' => $request->user()->id,
            'body' => $body !== '' ? $body : null,
        ]);

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('chat-attachments', 'public');
            $message->attachments()->create([
                'path' => $path,
                'original_filename' => mb_substr($file->getClientOriginalName(), 0, 255),
            ]);
        }

        $record->update(['last_message_at' => now()]);
        Notification::create([
            'user_id' => null,
            'type' => 'support_message',
            'title' => 'New buyer support message',
            'message' => 'A buyer sent a support message.',
            'link' => route('admin.messages.index', ['conversation' => $record->id]),
        ]);

        return response()->json([
            'conversation' => $this->payload($record->fresh(), (int) $request->user()->id),
        ], 201);
    }

    public function close(Request $request, int $conversation): JsonResponse
    {
        $record = $this->owned($request, $conversation);
        $record->update(['status' => 'closed']);

        return response()->json([
            'conversation' => $this->payload($record->fresh(), (int) $request->user()->id),
            'message' => 'Conversation ended.',
        ]);
    }

    private function conversationQuery(int $buyerId)
    {
        return Conversation::query()
            ->where('user_id', $buyerId)
            ->whereNull('complaint_id');
    }

    private function owned(Request $request, int $conversation): Conversation
    {
        abort_unless($request->user()?->role === 'buyer', 403);

        return $this->conversationQuery((int) $request->user()->id)->findOrFail($conversation);
    }

    /** Same message shape the web fetch returns, plus the time the app prints. */
    private function payload(Conversation $conversation, int $buyerId): array
    {
        $conversation->loadMissing([
            'messages.sender:id,name,profile_picture',
            'messages.attachments',
        ]);

        return [
            'id' => $conversation->id,
            'status' => $conversation->status,
            'started_at' => $conversation->created_at?->toIso8601String(),
            'messages' => $conversation->messages->map(fn (Message $message) => [
                'id' => $message->id,
                'body' => $message->body,
                'is_mine' => $message->sender_id !== null && (int) $message->sender_id === $buyerId,
                'is_system' => $message->sender_id === null,
                'avatar' => $message->sender?->profile_picture
                    ? asset('storage/'.$message->sender->profile_picture)
                    : null,
                'initial' => Str::upper(Str::substr($message->sender?->name ?? 'V', 0, 1)),
                'time' => $message->created_at?->format('M j, g:i A'),
                'attachments' => $message->attachments->map(fn ($attachment) => [
                    'url' => asset('storage/'.$attachment->path),
                    'name' => $attachment->original_filename,
                ])->values()->all(),
            ])->values()->all(),
        ];
    }
}
