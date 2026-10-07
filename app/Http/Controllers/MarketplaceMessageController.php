<?php

namespace App\Http\Controllers;

use App\Models\Communication\MarketplaceConversation;
use App\Models\Communication\MarketplaceMessage;
use App\Models\Communication\Notification;
use App\Models\Ecommerce\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MarketplaceMessageController extends Controller
{
    public function buyerShow(Request $request, int $order)
    {
        $this->authorizeBuyer($request);
        $record = Order::query()->where('buyer_id', $request->user()->id)
            ->with(['seller.sellerDetail', 'items.product:id,name', 'items.product.images'])->findOrFail($order);
        $conversation = $record->marketplaceConversation;
        $selectedItem = $record->items->firstWhere('id', (int) $request->query('item'));
        $availableOrders = $this->buyerOrders($request->user()->id, $record->seller_id);
        if ($conversation) $this->markIncomingRead($conversation, $request->user()->id);

        return view('buyer.marketplace-chat', $this->threadData($record, $conversation, 'buyer', $record->seller, $availableOrders, $selectedItem));
    }

    public function buyerSellerShow(Request $request, User $seller)
    {
        $this->authorizeBuyer($request);
        $this->authorizePublicSeller($seller);
        $availableOrders = $this->buyerOrders($request->user()->id, $seller->id);
        $conversation = MarketplaceConversation::query()->where('buyer_id', $request->user()->id)
            ->where('seller_id', $seller->id)->whereNull('order_id')->first();
        if (! $conversation && $availableOrders->isNotEmpty()) {
            return redirect()->route('buyer.marketplace-messages.show', $availableOrders->first());
        }
        if ($conversation) $this->markIncomingRead($conversation, $request->user()->id);

        return view('buyer.marketplace-chat', $this->threadData(null, $conversation, 'buyer', $seller, $availableOrders));
    }

    public function buyerStore(Request $request, int $order)
    {
        $this->authorizeBuyer($request);
        $validated = $this->validateMessage($request);
        $record = Order::query()->where('buyer_id', $request->user()->id)->findOrFail($order);
        $this->validateOrderReferences($request, $record->seller_id, $validated, $record);
        $path = $this->storeAttachment($request);
        try {
            DB::transaction(function () use ($request, $record, $validated, $path) {
                Order::query()->whereKey($record->id)->lockForUpdate()->firstOrFail();
                $conversation = MarketplaceConversation::firstOrCreate(
                    ['order_id' => $record->id],
                    ['buyer_id' => $record->buyer_id, 'seller_id' => $record->seller_id]
                );
                abort_unless($conversation->buyer_id === $request->user()->id && $conversation->seller_id === $record->seller_id, 403);
                $this->saveMessage($request, $conversation, $validated, $path, $record->seller_id);
            });
        } catch (\Throwable $exception) {
            if ($path) Storage::disk('local')->delete($path);
            throw $exception;
        }

        return $this->sendResponse($request, route('buyer.marketplace-messages.show', $record),
            route('buyer.marketplace-messages.fetch', $record));
    }

    public function buyerSellerStore(Request $request, User $seller)
    {
        $this->authorizeBuyer($request);
        $this->authorizePublicSeller($seller);
        $validated = $this->validateMessage($request);
        $this->validateOrderReferences($request, $seller->id, $validated);
        $path = $this->storeAttachment($request);
        try {
            DB::transaction(function () use ($request, $seller, $validated, $path) {
                User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
                $conversation = MarketplaceConversation::firstOrCreate(
                    ['buyer_id' => $request->user()->id, 'seller_id' => $seller->id, 'order_id' => null]
                );
                $this->saveMessage($request, $conversation, $validated, $path, $seller->id);
            });
        } catch (\Throwable $exception) {
            if ($path) Storage::disk('local')->delete($path);
            throw $exception;
        }

        return $this->sendResponse($request, route('buyer.marketplace-messages.seller.show', $seller),
            route('buyer.marketplace-messages.seller.fetch', $seller));
    }

    public function buyerFetch(Request $request, int $order)
    {
        $this->authorizeBuyer($request);
        $record = Order::query()->where('buyer_id', $request->user()->id)->findOrFail($order);
        $conversation = $record->marketplaceConversation;
        abort_unless($conversation && $conversation->buyer_id === $request->user()->id, 404);

        return $this->fetch($request, $conversation);
    }

    public function buyerSellerFetch(Request $request, User $seller)
    {
        $this->authorizeBuyer($request);
        $conversation = MarketplaceConversation::query()->where('buyer_id', $request->user()->id)
            ->where('seller_id', $seller->id)->whereNull('order_id')->firstOrFail();

        return $this->fetch($request, $conversation);
    }

    public function sellerShow(Request $request, MarketplaceConversation $conversation)
    {
        $this->authorizeSeller($request, $conversation);
        $conversation->load(['order.items.product:id,name', 'order.items.product.images', 'buyer:id,name,profile_picture']);
        $this->markIncomingRead($conversation, $request->user()->id);

        return view('seller.messages.order', $this->threadData($conversation->order, $conversation, 'seller', $request->user()));
    }

    public function sellerStore(Request $request, MarketplaceConversation $conversation)
    {
        $this->authorizeSeller($request, $conversation);
        $validated = $this->validateMessage($request);
        $this->validateOrderReferences($request, $conversation->seller_id, $validated, $conversation->order, $conversation->buyer_id);
        $path = $this->storeAttachment($request);
        try {
            DB::transaction(fn () => $this->saveMessage($request, $conversation, $validated, $path, $conversation->buyer_id));
        } catch (\Throwable $exception) {
            if ($path) Storage::disk('local')->delete($path);
            throw $exception;
        }

        return $this->sendResponse($request, route('seller.marketplace-messages.show', $conversation),
            route('seller.marketplace-messages.fetch', $conversation));
    }

    public function sellerFetch(Request $request, MarketplaceConversation $conversation)
    {
        $this->authorizeSeller($request, $conversation);
        return $this->fetch($request, $conversation);
    }

    public function attachment(Request $request, MarketplaceMessage $message)
    {
        $conversation = $message->conversation;
        abort_unless($conversation && in_array($request->user()?->id, [$conversation->buyer_id, $conversation->seller_id], true), 404);
        abort_unless($message->attachment_path && Storage::disk('local')->exists($message->attachment_path), 404);

        return Storage::disk('local')->response($message->attachment_path, $message->attachment_name);
    }

    private function authorizeBuyer(Request $request): void
    {
        abort_unless($request->user()?->role === 'buyer', 403);
    }

    private function authorizePublicSeller(User $seller): void
    {
        abort_unless($seller->role === 'seller' && $seller->status === 'approved'
            && ! $seller->archived_at && (! $seller->account_status || $seller->account_status === 'active'), 404);
    }

    private function authorizeSeller(Request $request, MarketplaceConversation $conversation): void
    {
        abort_unless($request->user()?->role === 'seller' && $conversation->seller_id === $request->user()->id, 404);
    }

    private function buyerOrders(int $buyerId, int $sellerId)
    {
        return Order::query()->where('buyer_id', $buyerId)->where('seller_id', $sellerId)
            ->with('items.product.images')->latest('id')->limit(20)->get(['id', 'seller_id', 'status', 'total_amount', 'created_at']);
    }

    private function validateMessage(Request $request): array
    {
        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'order_item_id' => ['nullable', 'integer', 'min:1'],
            'shared_order_id' => ['nullable', 'integer', 'min:1'],
        ]);
        if (trim((string) ($validated['body'] ?? '')) === '' && ! $request->hasFile('attachment') && empty($validated['shared_order_id'])) {
            throw ValidationException::withMessages(['body' => 'Write a message, attach a photo, or share an order.']);
        }
        return $validated;
    }

    private function validateOrderReferences(Request $request, int $sellerId, array $validated, ?Order $threadOrder = null, ?int $buyerId = null): void
    {
        $buyerId = $request->user()->role === 'buyer' ? $request->user()->id : ($buyerId ?? $threadOrder?->buyer_id);
        if (! empty($validated['shared_order_id'])) {
            abort_unless($threadOrder && (int) $validated['shared_order_id'] === $threadOrder->id, 422, 'Open that order’s own chat to share it.');
            abort_unless(Order::query()->whereKey($validated['shared_order_id'])
                ->where('seller_id', $sellerId)->where('buyer_id', $buyerId)->exists(), 422, 'That order does not belong to this conversation.');
        }
        if (! empty($validated['order_item_id'])) {
            abort_unless($threadOrder && $threadOrder->items()->whereKey($validated['order_item_id'])->exists(), 422, 'This item is not in the order.');
        }
    }

    private function storeAttachment(Request $request): ?string
    {
        return $request->hasFile('attachment') ? $request->file('attachment')->store('marketplace-chat', 'local') : null;
    }

    private function saveMessage(Request $request, MarketplaceConversation $conversation, array $validated, ?string $path, int $recipientId): void
    {
        $body = trim((string) ($validated['body'] ?? ''));
        $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'order_item_id' => $validated['order_item_id'] ?? null,
            'shared_order_id' => $validated['shared_order_id'] ?? null,
            'body' => $body !== '' ? $body : null,
            'attachment_path' => $path,
            'attachment_name' => $path ? Str::limit($request->file('attachment')->getClientOriginalName(), 240, '') : null,
        ]);
        $recipientIsSeller = $recipientId === $conversation->seller_id;
        if ($recipientIsSeller && $conversation->messages()->count() === 1) {
            $conversation->messages()->create([
                'sender_id' => null,
                'body' => $conversation->order_id
                    ? (! empty($validated['shared_order_id'])
                        ? 'Order #'.$conversation->order->number.' was shared with the seller. Please wait for their reply here.'
                        : 'Your message about order #'.$conversation->order->number.' has reached the seller. Please wait for their reply here.')
                    : 'Your message has reached the seller. Please wait for their reply here.',
            ]);
        } elseif ($recipientIsSeller && ! empty($validated['shared_order_id'])) {
            $conversation->messages()->create([
                'sender_id' => null,
                'body' => 'Order #'.$conversation->order->number.' was shared with the seller. Please wait for their reply here.',
            ]);
        }
        $conversation->update(['last_message_at' => now()]);
        Notification::create([
            'user_id' => $recipientId,
            'type' => 'marketplace_message',
            'title' => 'New message from '.($recipientIsSeller ? 'a buyer' : 'a seller'),
            'message' => $request->user()->name.' sent a message'.($conversation->order_id ? ' about order '.$conversation->order?->number : '').'.',
            'link' => $recipientIsSeller
                ? route('seller.marketplace-messages.show', $conversation)
                : ($conversation->order_id
                    ? route('buyer.marketplace-messages.show', $conversation->order_id)
                    : route('buyer.marketplace-messages.seller.show', $conversation->seller_id)),
        ]);
    }

    private function sendResponse(Request $request, string $showUrl, string $fetchUrl)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'fetch_url' => $fetchUrl, 'show_url' => $showUrl]);
        }
        return redirect()->to($showUrl)->with('success', 'Message sent.');
    }

    private function fetch(Request $request, MarketplaceConversation $conversation)
    {
        $validated = $request->validate(['after' => ['nullable', 'integer', 'min:0']]);
        $this->markIncomingRead($conversation, $request->user()->id);
        $messages = $conversation->messages()->reorder()->where('id', '>', $validated['after'] ?? 0)
            ->with(['sender:id,name,profile_picture', 'item.product:id,name', 'sharedOrder.items.product.images'])->orderBy('id')->limit(100)->get();

        return response()->json(['active_ids' => $conversation->messages()->pluck('id'), 'messages' => $messages->map(fn (MarketplaceMessage $message) => [
            'id' => $message->id,
            'mine' => $message->sender_id === $request->user()->id,
            'system' => $message->sender_id === null,
            'avatar' => $message->sender?->profile_picture ? Storage::disk('public')->url($message->sender->profile_picture) : null,
            'initial' => mb_strtoupper(mb_substr($message->sender?->name ?? 'V', 0, 1)),
            'body' => $message->body,
            'item' => $message->item?->product?->name,
            'time' => $message->created_at->format('M j, g:i A'),
            'attachment_url' => $message->attachment_path ? route('marketplace-messages.attachment', $message) : null,
            'shared_order' => $message->sharedOrder ? [
                'number' => $message->sharedOrder->number,
                'image_url' => $this->orderImage($message->sharedOrder),
                'product_name' => $message->sharedOrder->items->first()?->product?->name ?? 'Order items',
                'item_count' => $message->sharedOrder->items->sum('quantity'),
                'carrier' => $message->sharedOrder->carrier_name,
                'tracking' => $message->sharedOrder->carrier_tracking_number,
                'status' => $message->sharedOrder->status_label,
                'total' => '₱'.number_format($message->sharedOrder->total_amount, 2),
                'url' => $request->user()->role === 'buyer'
                    ? route('buyer.orders.show', $message->sharedOrder)
                    : route('seller.orders.index', ['order' => $message->sharedOrder->id]),
            ] : null,
        ])]);
    }

    private function orderImage(Order $order): ?string
    {
        $path = $order->items->first()?->product?->images->first()?->path;

        return $path ? Storage::disk('public')->url($path) : null;
    }

    private function markIncomingRead(MarketplaceConversation $conversation, int $userId): void
    {
        $conversation->messages()->where('sender_id', '!=', $userId)
            ->whereNull('read_at')->update(['read_at' => now()]);
    }

    private function threadData(?Order $order, ?MarketplaceConversation $conversation, string $side, User $seller, $availableOrders = null, $selectedItem = null): array
    {
        $messages = $conversation
            ? $conversation->messages()->reorder()->with(['sender:id,name,profile_picture', 'item.product:id,name', 'sharedOrder.items.product.images'])->latest('id')->limit(50)->get()->reverse()->values()
            : collect();
        $availableOrders ??= collect();

        return compact('order', 'conversation', 'messages', 'side', 'seller', 'availableOrders', 'selectedItem');
    }
}
