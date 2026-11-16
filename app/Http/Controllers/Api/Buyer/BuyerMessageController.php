<?php

namespace App\Http\Controllers\Api\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Communication\MarketplaceConversation;
use App\Models\Communication\MarketplaceMessage;
use App\Models\Communication\Notification;
use App\Models\Ecommerce\Order;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Buyer chats with a shop, the same threads the web chat page reads and writes
 * (MarketplaceMessageController). A seller chat has no order; an order chat is
 * pinned to that order and is the only place an order can be shared.
 */
class BuyerMessageController extends Controller
{
    /** The Buyer's shop chats, newest first — the list on the web Messages page. */
    public function index(Request $request): JsonResponse
    {
        $buyerId = (int) $request->user()->id;
        $chats = MarketplaceConversation::query()
            ->where('buyer_id', $buyerId)
            ->with(['seller:id,name,profile_picture', 'order:id', 'latestMessage'])
            ->withCount(['messages as unread_count' => fn ($query) => $query
                ->where('sender_id', '!=', $buyerId)->whereNull('read_at')])
            ->orderByDesc('last_message_at')
            ->get();

        return response()->json([
            'data' => $chats->map(function (MarketplaceConversation $chat) {
                $latest = $chat->latestMessage;

                return [
                    'id' => $chat->id,
                    'seller' => [
                        'id' => $chat->seller_id,
                        'name' => $chat->seller?->name ?? 'Seller unavailable',
                        'avatar' => $chat->seller?->profile_picture
                            ? asset('storage/'.$chat->seller->profile_picture)
                            : null,
                    ],
                    'order' => $chat->order
                        ? ['id' => $chat->order->id, 'number' => $chat->order->number]
                        : null,
                    'preview' => $latest?->body
                        ?: ($latest?->shared_order_id ? 'Order shared' : ($latest ? 'Photo' : 'No messages yet')),
                    'time' => $latest?->created_at?->diffForHumans(null, true, true),
                    'unread' => (int) $chat->unread_count,
                ];
            })->values()->all(),
            'unread_total' => (int) $chats->sum('unread_count'),
        ]);
    }

    public function seller(Request $request, User $seller): JsonResponse
    {
        $this->ensurePublicSeller($seller);
        $buyerId = (int) $request->user()->id;
        $orders = $this->buyerOrders($buyerId, $seller->id);
        $conversation = MarketplaceConversation::query()
            ->where('buyer_id', $buyerId)->where('seller_id', $seller->id)->whereNull('order_id')->first();
        if ($conversation) {
            $this->markIncomingRead($conversation, $buyerId);
        }

        return response()->json($this->threadPayload($conversation, $seller, null, $orders, $buyerId)
            + ['redirect_order_id' => ! $conversation && $orders !== [] ? $orders[0]['id'] : null]);
    }

    public function order(Request $request, Order $order): JsonResponse
    {
        abort_unless((int) $order->buyer_id === (int) $request->user()->id, 404);
        $order->load('seller.sellerDetail');
        $buyerId = (int) $request->user()->id;
        $conversation = MarketplaceConversation::query()->where('order_id', $order->id)
            ->where('buyer_id', $buyerId)->first();
        if ($conversation) {
            $this->markIncomingRead($conversation, $buyerId);
        }

        // The order page can only share the order it belongs to, like the web form.
        return response()->json($this->threadPayload(
            $conversation,
            $order->seller,
            $order,
            [$this->orderLink($order)],
            $buyerId,
        ));
    }

    public function storeSeller(Request $request, User $seller): JsonResponse
    {
        $this->ensurePublicSeller($seller);
        $validated = $this->validateMessage($request);
        abort_unless(empty($validated['shared_order_id']), 422, 'Open that order’s own chat to share it.');
        $buyerId = (int) $request->user()->id;
        $path = $this->storeAttachment($request);
        try {
            DB::transaction(function () use ($request, $seller, $validated, $path, $buyerId) {
                User::query()->whereKey($buyerId)->lockForUpdate()->firstOrFail();
                $conversation = MarketplaceConversation::firstOrCreate(
                    ['buyer_id' => $buyerId, 'seller_id' => $seller->id, 'order_id' => null],
                );
                $this->saveMessage($request, $conversation, $validated, $path, (int) $seller->id, null);
            });
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }

        return response()->json([
            'message' => 'Message sent.',
            'thread_url' => route('api.buyer.messages.seller', $seller),
        ], 201);
    }

    public function storeOrder(Request $request, Order $order): JsonResponse
    {
        abort_unless((int) $order->buyer_id === (int) $request->user()->id, 404);
        $validated = $this->validateMessage($request);
        if (! empty($validated['shared_order_id'])) {
            abort_unless((int) $validated['shared_order_id'] === (int) $order->id, 422,
                'That order does not belong to this conversation.');
        }
        if (! empty($validated['order_item_id'])) {
            abort_unless($order->items()->whereKey($validated['order_item_id'])->exists(), 422,
                'This item is not in the order.');
        }

        $path = $this->storeAttachment($request);
        try {
            DB::transaction(function () use ($request, $order, $validated, $path) {
                Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
                $conversation = MarketplaceConversation::firstOrCreate(
                    ['order_id' => $order->id],
                    ['buyer_id' => $order->buyer_id, 'seller_id' => $order->seller_id],
                );
                $this->saveMessage($request, $conversation, $validated, $path, (int) $order->seller_id, $order);
            });
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }

        return response()->json([
            'message' => 'Message sent.',
            'thread_url' => route('api.buyer.messages.order', $order),
        ], 201);
    }

    /** New messages after the app's last one, for the chat's short poll. */
    public function fetchSeller(Request $request, User $seller): JsonResponse
    {
        $this->ensurePublicSeller($seller);
        $conversation = MarketplaceConversation::query()
            ->where('buyer_id', $request->user()->id)->where('seller_id', $seller->id)
            ->whereNull('order_id')->first();

        return response()->json($this->newMessages($request, $conversation));
    }

    public function fetchOrder(Request $request, Order $order): JsonResponse
    {
        abort_unless((int) $order->buyer_id === (int) $request->user()->id, 404);
        $conversation = MarketplaceConversation::query()->where('order_id', $order->id)
            ->where('buyer_id', $request->user()->id)->first();

        return response()->json($this->newMessages($request, $conversation));
    }

    /** Chat photos live on the private disk; this is the buyer's own copy of one. */
    public function attachment(Request $request, MarketplaceMessage $message)
    {
        $conversation = $message->conversation;
        abort_unless($conversation && (int) $conversation->buyer_id === (int) $request->user()->id, 404);
        abort_unless($message->attachment_path && Storage::disk('local')->exists($message->attachment_path), 404);

        return Storage::disk('local')->response($message->attachment_path, $message->attachment_name);
    }

    private function threadPayload(
        ?MarketplaceConversation $conversation,
        ?User $seller,
        ?Order $order,
        array $shareOrders,
        int $buyerId,
    ): array {
        return [
            'seller' => $this->sellerPayload($seller),
            'order' => $order ? $this->orderLink($order) : null,
            'share_orders' => $shareOrders,
            'messages' => $conversation
                ? $this->messages($conversation, $buyerId, 0, true)
                : [],
        ];
    }

    private function newMessages(Request $request, ?MarketplaceConversation $conversation): array
    {
        $data = $request->validate(['after' => ['nullable', 'integer', 'min:0']]);
        if (! $conversation) {
            return ['messages' => []];
        }
        $this->markIncomingRead($conversation, (int) $request->user()->id);

        return [
            'messages' => $this->messages($conversation, (int) $request->user()->id, (int) ($data['after'] ?? 0), false),
        ];
    }

    /** The thread's last 50 messages, or everything newer than [after] for polling. */
    private function messages(MarketplaceConversation $conversation, int $buyerId, int $after, bool $latest): array
    {
        $query = $conversation->messages()->reorder()
            ->with(['sender:id,name,profile_picture', 'item.product:id,name', 'sharedOrder.items.product.images']);
        if ($after > 0) {
            $query->where('id', '>', $after);
        }
        $rows = $latest
            ? $query->latest('id')->limit(50)->get()->reverse()->values()
            : $query->orderBy('id')->limit(100)->get();

        return $rows->map(fn (MarketplaceMessage $message) => $this->messagePayload($message, $buyerId))->values()->all();
    }

    private function messagePayload(MarketplaceMessage $message, int $buyerId): array
    {
        $mine = $message->sender_id !== null && (int) $message->sender_id === $buyerId;

        return [
            'id' => $message->id,
            'mine' => $mine,
            'system' => $message->sender_id === null,
            'sender' => $message->sender_id === null ? 'Vendo' : ($mine ? 'You' : ($message->sender?->name ?? 'Seller')),
            'avatar' => $message->sender?->profile_picture
                ? asset('storage/'.$message->sender->profile_picture)
                : null,
            'initial' => Str::upper(Str::substr($message->sender?->name ?? 'V', 0, 1)),
            'body' => $message->body,
            'item' => $message->item?->product?->name,
            'time' => $message->created_at?->format('M j, g:i A'),
            'attachment_url' => $message->attachment_path
                ? route('api.buyer.messages.attachment', $message)
                : null,
            'shared_order' => $message->sharedOrder ? $this->sharedOrderPayload($message->sharedOrder) : null,
        ];
    }

    private function sharedOrderPayload(Order $order): array
    {
        $item = $order->items->first();
        $image = $item?->product?->images->first()?->path;

        return [
            'order_id' => $order->id,
            'number' => $order->number,
            'image_url' => $image ? asset('storage/'.$image) : null,
            'product_name' => $item?->product?->name ?? 'Order items',
            'item_count' => (int) $order->items->sum('quantity'),
            'total' => '₱'.number_format((float) $order->payable_total, 2),
            'carrier' => $order->carrier_name,
            'tracking' => $order->carrier_tracking_number,
            'status' => $order->status_label,
        ];
    }

    private function orderLink(Order $order): array
    {
        return [
            'id' => $order->id,
            'number' => $order->number,
            'label' => '#'.$order->number.' · '.$order->status_label,
            'status' => $order->status,
        ];
    }

    private function sellerPayload(?User $seller): ?array
    {
        if (! $seller) {
            return null;
        }
        $detail = $seller->sellerDetail;

        return [
            'id' => $seller->id,
            'name' => $detail?->business_name ?: $seller->name,
            'owner' => $seller->name,
            'avatar' => $seller->profile_picture ? asset('storage/'.$seller->profile_picture) : null,
            'url' => route('buyer.sellers.show', $seller),
        ];
    }

    /** The orders this Buyer has with this shop, newest first, as the web share list shows them. */
    private function buyerOrders(int $buyerId, int $sellerId): array
    {
        return Order::query()->where('buyer_id', $buyerId)->where('seller_id', $sellerId)
            ->latest('id')->limit(20)->get()
            ->map(fn (Order $order) => $this->orderLink($order))->values()->all();
    }

    private function ensurePublicSeller(User $seller): void
    {
        abort_unless($seller->role === 'seller' && $seller->status === 'approved'
            && ! $seller->archived_at && (! $seller->account_status || $seller->account_status === 'active'), 404);
    }

    private function validateMessage(Request $request): array
    {
        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'order_item_id' => ['nullable', 'integer', 'min:1'],
            'shared_order_id' => ['nullable', 'integer', 'min:1'],
        ]);
        if (trim((string) ($validated['body'] ?? '')) === '' && ! $request->hasFile('attachment')
            && empty($validated['shared_order_id'])) {
            throw ValidationException::withMessages(['body' => 'Write a message, attach a photo, or share an order.']);
        }

        return $validated;
    }

    private function storeAttachment(Request $request): ?string
    {
        return $request->hasFile('attachment')
            ? $request->file('attachment')->store('marketplace-chat', 'local')
            : null;
    }

    /** Same rows the web saves, including the "Vendo" notice that greets a new seller chat. */
    private function saveMessage(
        Request $request,
        MarketplaceConversation $conversation,
        array $validated,
        ?string $path,
        int $recipientId,
        ?Order $order,
    ): void {
        $body = trim((string) ($validated['body'] ?? ''));
        $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'order_item_id' => $validated['order_item_id'] ?? null,
            'shared_order_id' => $validated['shared_order_id'] ?? null,
            'body' => $body !== '' ? $body : null,
            'attachment_path' => $path,
            'attachment_name' => $path
                ? Str::limit($request->file('attachment')->getClientOriginalName(), 240, '')
                : null,
        ]);
        $conversation->loadMissing('order');
        $order ??= $conversation->order;

        if ($conversation->messages()->count() === 1) {
            $conversation->messages()->create([
                'sender_id' => null,
                'body' => $order
                    ? (! empty($validated['shared_order_id'])
                        ? 'Order #'.$order->number.' was shared with the seller. Please wait for their reply here.'
                        : 'Your message about order #'.$order->number.' has reached the seller. Please wait for their reply here.')
                    : 'Your message has reached the seller. Please wait for their reply here.',
            ]);
        } elseif (! empty($validated['shared_order_id']) && $order) {
            $conversation->messages()->create([
                'sender_id' => null,
                'body' => 'Order #'.$order->number.' was shared with the seller. Please wait for their reply here.',
            ]);
        }

        $conversation->update(['last_message_at' => now()]);
        Notification::create([
            'user_id' => $recipientId,
            'type' => 'marketplace_message',
            'title' => 'New message from a buyer',
            'message' => $request->user()->name.' sent a message'.($order ? ' about order '.$order->number : '').'.',
            'link' => route('seller.marketplace-messages.show', $conversation),
        ]);
    }

    private function markIncomingRead(MarketplaceConversation $conversation, int $buyerId): void
    {
        $conversation->messages()->where('sender_id', '!=', $buyerId)
            ->whereNull('read_at')->update(['read_at' => now()]);
    }
}
