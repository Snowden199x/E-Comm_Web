<?php

namespace App\Services;

use App\Models\Communication\Notification;
use App\Models\Ecommerce\CartItem;
use App\Models\Ecommerce\Order;
use App\Models\Ecommerce\OrderReturnRequest;
use App\Models\Ecommerce\Product;
use App\Models\Ecommerce\ProductVariant;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Shapes the Buyer mobile API payloads. The web Blade pages keep their own markup; this class
 * keeps every endpoint consistent (prices, images, shop names, order states) and in one place.
 */
class BuyerApiPresenter
{
    public function money(float|int|string|null $amount): string
    {
        $value = (float) $amount;

        return '₱'.number_format($value, fmod($value, 1) ? 2 : 0);
    }

    public function amount(float|int|string|null $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    public function imageUrl(?string $path): string
    {
        return $path ? asset('storage/'.$path) : asset('images/products/tote-bag.jpg');
    }

    public function productCard(Product $product): array
    {
        $image = $product->images->first();
        $seller = $product->seller;
        $price = (float) $product->effective_price;
        $compareAt = (float) ($product->compare_at_price ?? ($product->hasActiveSale() ? $product->price : 0));

        return [
            'id' => $product->id,
            'name' => $product->name,
            'price' => $this->money($price),
            'price_value' => $this->amount($price),
            'compare_at_price' => $compareAt > $price ? $this->money($compareAt) : null,
            'discount_percent' => $compareAt > $price ? (int) round((1 - ($price / $compareAt)) * 100) : 0,
            'image' => $this->imageUrl($image?->path),
            'shop' => $seller?->sellerDetail?->business_name ?: $seller?->name,
            'shop_id' => $seller?->id,
            'rating' => $product->reviews_avg_rating !== null ? round((float) $product->reviews_avg_rating, 1) : null,
            'sold' => (int) ($product->sold_count ?? 0),
            'stock' => (int) $product->stock,
            'has_variations' => (bool) $product->has_variations,
            'colors' => $this->options($product->colors),
            'sizes' => $this->options($product->sizes),
            'sold_out' => (int) $product->stock <= 0,
        ];
    }

    public function productDetail(Product $product): array
    {
        $seller = $product->seller;
        $images = $product->images->map(fn ($image) => $this->imageUrl($image->path))->values()->all();
        if ($images === []) {
            $images = [$this->imageUrl(null)];
        }

        // Same service the web product page renders, so both surfaces stay in sync.
        $content = app(ProductPageContent::class);
        $shippingFee = $content->shippingFee($product);

        return $this->productCard($product) + [
            'description' => $product->description,
            'category' => $product->category?->name,
            'images' => $images,
            'variants' => $product->variants->map(fn (ProductVariant $variant) => [
                'id' => $variant->id,
                'label' => $variant->label,
                'price' => $this->money($variant->price),
                'price_value' => $this->amount($variant->price),
                'stock' => (int) $variant->stock,
                'options' => $variant->options ?? [],
                'image' => $variant->image_path ? $this->imageUrl($variant->image_path) : null,
            ])->values()->all(),
            'specifications' => $content->specifications($product)->values()->all(),
            'delivery' => [
                'ships_from' => $content->shipsFrom($product),
                'shipping_fee' => '₱'.number_format($shippingFee, 2),
                'shipping_fee_value' => $this->amount($shippingFee),
                'shipping_note' => $content->shippingNote($shippingFee),
                'payment_method' => ProductPageContent::PAYMENT_METHOD,
            ],
            'ratings' => $content->ratings($product),
            'reviews' => $product->publishedReviews()
                ->with(['buyer:id,name', 'reply:id,product_review_id,body,seller_id,created_at', 'reply.seller:id,name'])
                ->latest('id')->take(8)->get()
                ->map(fn ($review) => [
                    'id' => $review->id,
                    'buyer' => $review->buyer?->name ?? 'Buyer',
                    'initial' => Str::upper(Str::substr($review->buyer?->name ?? 'B', 0, 1)),
                    'rating' => (int) $review->rating,
                    'date' => $review->created_at?->format('M j, Y'),
                    // Only confirmed orders can leave a review, so every published one is verified.
                    'verified' => true,
                    'comment' => $review->comment,
                    'reply' => $review->reply ? [
                        'body' => $review->reply->body,
                        'seller' => $review->reply->seller?->name,
                    ] : null,
                ])->values()->all(),
            'seller' => $this->sellerSummary($seller, withStats: true),
        ];
    }

    public function sellerSummary(?User $seller, bool $withStats = false): ?array
    {
        if (! $seller) {
            return null;
        }

        $detail = $seller->sellerDetail;
        // Only the product page's shop card shows the stats; the seller list would N+1 on them.
        $stats = $withStats ? (new ProductPageContent)->shopStats($seller) : null;

        return [
            'id' => $seller->id,
            'name' => $detail?->business_name ?: $seller->name,
            'owner' => $seller->name,
            'description' => $detail?->description,
            'province' => $detail?->province,
            'municipality' => $detail?->municipality,
            'rating' => $seller->reviews_avg_rating !== null ? round((float) $seller->reviews_avg_rating, 1) : null,
            'ratings_count' => $stats['ratings'] ?? null,
            'average_rating' => $stats && $stats['ratings'] ? round((float) $stats['average'], 1) : null,
            'products_count' => $stats['products']
                ?? (isset($seller->approved_products_count) ? (int) $seller->approved_products_count : null),
            'joined' => $stats['joined'] ?? null,
        ];
    }

    /**
     * The shop page: the seller summary plus what the web shop header renders (banner, photo,
     * description, product/rating counts, "Joined Oct 2026").
     */
    public function sellerShop(User $seller): array
    {
        $detail = $seller->sellerDetail;
        $stats = (new ProductPageContent)->shopStats($seller);

        return array_replace($this->sellerSummary($seller) ?? [], [
            'description' => $detail?->shop_description,
            'ratings_count' => $stats['ratings'],
            'average_rating' => $stats['ratings'] ? round((float) $stats['average'], 1) : null,
            'products_count' => $stats['products'],
            'joined' => $seller->created_at?->format('M Y'),
            'banner' => $detail?->shop_banner_path ? asset('storage/'.$detail->shop_banner_path) : null,
            'avatar' => $seller->profile_picture ? asset('storage/'.$seller->profile_picture) : null,
            'url' => route('buyer.sellers.show', $seller),
        ]);
    }

    /** One cart line, already joined with its product and variant. */
    public function cartLine(CartItem $item): array
    {
        $product = $item->product;
        $variant = $item->variant;
        $unit = (float) ($variant?->price ?? $product?->price ?? 0);
        $image = $product?->images->first();

        return [
            'id' => $item->id,
            'product_id' => $item->product_id,
            'name' => $product?->name,
            'image' => $this->imageUrl($image?->path),
            'unit_price' => $this->money($unit),
            'unit_price_value' => $this->amount($unit),
            'quantity' => (int) $item->quantity,
            'stock' => (int) ($variant?->stock ?? $product?->stock ?? 0),
            'color' => $item->color,
            'size' => $item->size,
            'variant_id' => $item->product_variant_id,
            'variant_label' => $variant?->label,
            'subtotal' => $this->money($unit * $item->quantity),
            'subtotal_value' => $this->amount($unit * $item->quantity),
            'shop_id' => $product?->seller_id,
            'shop' => $product?->seller?->sellerDetail?->business_name ?: $product?->seller?->name,
        ];
    }

    /**
     * Cart lines grouped per shop, with the running totals the mobile cart shows.
     *
     * @param  Collection<int, CartItem>  $items
     */
    public function cartPayload(Collection $items): array
    {
        $lines = $items->map(fn (CartItem $item) => $this->cartLine($item))->values();
        $groups = $lines->groupBy('shop_id')->map(function (Collection $shopLines, $shopId) {
            $subtotal = $shopLines->sum(fn ($line) => (float) $line['subtotal_value']);

            return [
                'shop_id' => (int) $shopId,
                'shop' => $shopLines->first()['shop'],
                'items' => $shopLines->values()->all(),
                'subtotal' => $this->money($subtotal),
                'subtotal_value' => $this->amount($subtotal),
            ];
        })->values()->all();
        $itemCount = $lines->sum('quantity');
        $subtotal = $lines->sum(fn ($line) => (float) $line['subtotal_value']);

        return [
            'items' => $lines->all(),
            'groups' => $groups,
            'totals' => [
                'items' => (int) $itemCount,
                'subtotal' => $this->money($subtotal),
                'subtotal_value' => $this->amount($subtotal),
            ],
        ];
    }

    public function orderSummary(Order $order): array
    {
        $firstItem = $order->items->first();
        $image = $firstItem?->product?->images->first();
        // The cancellation service records who cancelled and why on the status event.
        $cancelNote = (string) $order->statusEvents->where('to_status', 'cancelled')->last()?->note;
        $cancelledBy = $order->status !== 'cancelled' ? null : match (true) {
            str_starts_with($cancelNote, 'Seller') => 'the seller',
            str_starts_with($cancelNote, 'Buyer') => 'you',
            default => null,
        };
        $cancelReason = $cancelledBy !== null && Str::contains($cancelNote, 'cancellation reason:')
            ? trim(Str::after($cancelNote, 'cancellation reason:'))
            : null;

        return [
            'id' => $order->id,
            'number' => $order->number,
            'status' => $order->status,
            'status_label' => $order->status_label,
            'shop' => $order->seller?->sellerDetail?->business_name ?: $order->seller?->name,
            'items_count' => (int) $order->items->sum('quantity'),
            'total' => $this->money($order->total_amount),
            'total_value' => $this->amount($order->total_amount),
            'shipping_fee' => $this->money($order->shipping_fee),
            'cod_amount' => $this->money($order->cod_amount ?? $order->buyer_payable),
            'cod_amount_value' => $this->amount($order->cod_amount ?? $order->buyer_payable),
            'image' => $this->imageUrl($image?->path),
            'placed_at' => $order->created_at?->toIso8601String(),
            'placed_at_label' => $order->created_at?->format('M j, Y g:i A'),
            'can_cancel' => in_array($order->status, \App\Services\OrderCancellationService::BUYER_CANCELLABLE_STATUSES, true),
            'cancelled_by' => $cancelledBy,
            'cancel_reason' => $cancelReason,
            'return_pill' => $this->returnPill($order),
            'action_label' => match ($order->status) {
                'delivered' => 'Confirm receipt',
                'completed' => 'View and rate',
                default => 'View details',
            },
        ];
    }

    /** The small pill the Returns list puts next to the status: waiting, approved, declined, handed back. */
    private function returnPill(Order $order): ?array
    {
        $request = $order->latestReturnRequest;
        if ($request === null) {
            return $order->status === 'returned' ? ['label' => 'Returned', 'tone' => 'done'] : null;
        }

        return match (true) {
            $request->status === OrderReturnRequest::STATUS_PENDING => ['label' => 'Return: waiting for the seller', 'tone' => 'pending'],
            $request->status === 'rejected' => ['label' => 'Return declined', 'tone' => 'rejected'],
            $request->return_status === 'delivered_to_seller' => ['label' => 'Return: handed back', 'tone' => 'done'],
            default => ['label' => 'Return approved', 'tone' => 'approved'],
        };
    }

    /**
     * One order, carrying every block the web order page shows so the app can draw the
     * same page: progress rail, items with their reviews, order updates, summary, seller,
     * tracking and the return window.
     */
    public function orderDetail(Order $order): array
    {
        $itemsTotal = $order->items->sum(fn ($item) => $item->quantity * $item->price);
        $returns = app(OrderReturnService::class);
        $returnRequest = $order->latestReturnRequest;
        $returnPending = $returnRequest?->status === OrderReturnRequest::STATUS_PENDING;
        $seller = $order->seller;
        $steps = $order->buyerProgressSteps();
        $currentIndex = array_search($order->status, $steps, true);
        $terminalIssue = in_array($order->status, ['cancelled', 'returned', 'delivery_failed'], true);
        $maxDeliveryAttempts = DeliveryCutoff::MAX_DELIVERY_ATTEMPTS + (int) $order->delivery_extra_attempts;

        return $this->orderSummary($order) + [
            'items' => $order->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'name' => $item->product?->name,
                'image' => $this->imageUrl($item->product?->images->first()?->path),
                'quantity' => (int) $item->quantity,
                'price' => $this->money($item->price),
                'price_value' => $this->amount($item->price),
                'line_total' => $this->money($item->quantity * $item->price),
                'color' => $item->color,
                'size' => $item->size,
                'reviewed' => $item->review !== null,
                'review' => $item->review === null ? null : [
                    'rating' => (int) $item->review->rating,
                    'comment' => $item->review->comment,
                    'hidden' => $item->review->visibility === 'hidden',
                    'reply' => $item->review->reply?->body,
                ],
            ])->values()->all(),
            'items_total' => $this->money($itemsTotal),
            'items_total_value' => $this->amount($itemsTotal),
            'payment_label' => 'Cash on delivery',
            'shipping_address' => $order->shipping_address,
            'shipping_city' => $order->shipping_city,
            'shipping_province' => $order->shipping_province,
            'payment_mode' => $order->payment_mode,
            'money_status' => $order->money_status,
            'money_status_label' => $order->money_status ? (\App\Models\Ecommerce\Order::MONEY_STATUSES[$order->money_status] ?? $order->money_status) : null,
            'voucher_code' => $order->voucher_code,
            'voucher_codes' => $order->voucher_codes,
            'voucher_discount' => $this->money($order->voucher_discount ?? 0),
            'buyer_note' => $order->buyer_note,
            'tracking_number' => $order->tracking_number,
            'can_complete' => $order->status === 'delivered',
            'cancel_locked' => ! in_array($order->status, OrderCancellationService::BUYER_CANCELLABLE_STATUSES, true)
                && ! in_array($order->status, ['completed', 'cancelled', 'returned', 'delivery_failed', 'delivered'], true),
            'progress' => [
                'terminal_issue' => $terminalIssue,
                'status_label' => $order->status_label,
                'steps' => collect($steps)->map(fn ($step, $index) => [
                    'status' => $step,
                    'label' => $step === 'completed'
                        ? 'Rate products'
                        : (\App\Models\Ecommerce\Order::STATUSES[$step] ?? Str::headline($step)),
                    'done' => $currentIndex !== false && $index <= $currentIndex,
                    'current' => $currentIndex !== false && $index === $currentIndex,
                ])->values()->all(),
            ],
            'timeline' => $order->statusEvents->sortByDesc('created_at')->values()->map(fn ($event) => [
                'status' => $event->to_status,
                'label' => \App\Models\Ecommerce\Order::STATUSES[$event->to_status] ?? $event->to_status,
                'at' => $event->created_at?->toIso8601String(),
                'at_label' => $event->created_at?->format('M j, Y g:i A'),
                'note' => $event->note ?? null,
            ])->values()->all(),
            'seller' => $seller === null ? null : [
                'id' => $seller->id,
                'name' => $seller->sellerDetail?->business_name ?: $seller->name,
            ],
            'tracking' => [
                'number' => $order->tracking_number,
                'carrier' => $order->carrier_name,
                'carrier_tracking' => $order->carrier_tracking_number,
                'estimated_from_label' => $order->estimated_delivery_from?->format('M j, Y'),
                'estimated_to_label' => $order->estimated_delivery_to?->format('M j, Y'),
                'attempts' => (int) $order->delivery_attempts,
                'max_attempts' => $maxDeliveryAttempts,
                'next_attempt_label' => $order->delivery_scheduled_for?->format('M j, Y'),
                'has_delivery_proof' => $order->deliveryProof !== null,
            ],
            // The Seller's packed-item photo the web order page shows below the tracking.
            'has_pickup_photo' => filled($order->pickup_photo_path),
            'cancel_reasons' => collect(OrderCancellationService::BUYER_REASONS)
                ->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values()->all(),
            'can_request_return' => ! $returnPending && $returns->canRequest($order),
            'return_window_closes_at_label' => $returns->windowClosesAt($order)?->format('M j, Y'),
            'return_days_left' => $returns->daysLeft($order),
            'return_reasons' => collect(OrderReturnRequest::REASONS)
                ->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values()->all(),
            'return_request' => $returnRequest === null ? null : [
                'status' => $returnRequest->status,
                'status_label' => $returnRequest->status_label,
                'reason_label' => $returnRequest->reason_label,
                'decision_note' => $returnRequest->decision_note,
                'refund' => $returnRequest->status === 'approved'
                    ? $this->money(((int) $returnRequest->refund_cents) / 100)
                    : null,
                'return_status_label' => $returnRequest->return_status_label,
                'picked_up_at_label' => $returnRequest->picked_up_at?->format('M j, Y'),
                'time_ago' => $returnRequest->created_at?->diffForHumans(),
                'is_pending' => $returnPending,
            ],
        ];
    }

    public function notification(Notification $notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'title' => $notification->title,
            'message' => $notification->message,
            'read' => $notification->read_at !== null,
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
            'time_ago' => $notification->created_at?->diffForHumans(),
            'product' => $this->notificationProduct($notification),
            'order' => $this->notificationOrder($notification),
        ];
    }

    /** @var array<string, array{id: int, number: string, status: string, status_label: string}|null> */
    private array $notificationOrderCache = [];

    /**
     * The order a notification is about, so the app opens that order when the card is
     * tapped and labels the card from the order's real status. Matching the wording
     * could not tell "VND-00026: Completed" apart from an order that was only placed.
     *
     * @return array{id: int, number: string, status: string, status_label: string}|null
     */
    private function notificationOrder(Notification $notification): ?array
    {
        $path = parse_url((string) $notification->link, PHP_URL_PATH);
        if (! is_string($path) || ! preg_match('~^/buyer/orders/(\d+)(?:/|$)~', $path, $matches)) {
            return null;
        }

        // Every update of one parcel shares a link, so one lookup serves the group.
        $key = ((int) $notification->user_id).'|'.$path;
        if (! array_key_exists($key, $this->notificationOrderCache)) {
            $order = Order::query()
                ->where('buyer_id', (int) $notification->user_id)
                ->find((int) $matches[1]);

            $this->notificationOrderCache[$key] = $order ? [
                'id' => $order->id,
                'number' => $order->number,
                'status' => $order->status,
                'status_label' => $order->status_label,
            ] : null;
        }

        return $this->notificationOrderCache[$key];
    }

    /** @var array<string, array{name: string, image: string|null}|null> */
    private array $notificationProductCache = [];

    /**
     * The product a notification is about, so the app can show the picture of what the
     * parcel holds instead of an empty frame. Resolved from the notification's link, the
     * same page the web bubble opens: an order page gives the order's first item, a
     * product page gives that product. Announcements and account notices point at nothing
     * on sale, so they get null and the app keeps its kind icon.
     *
     * @return array{name: string, image: string|null}|null
     */
    private function notificationProduct(Notification $notification): ?array
    {
        $path = parse_url((string) $notification->link, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            return null;
        }

        // A parcel's notifications all share one link, so one lookup serves the group.
        $key = ((int) $notification->user_id).'|'.$path;
        if (! array_key_exists($key, $this->notificationProductCache)) {
            $this->notificationProductCache[$key] = $this->resolveNotificationProduct($path, (int) $notification->user_id);
        }

        return $this->notificationProductCache[$key];
    }

    /** @return array{name: string, image: string|null}|null */
    private function resolveNotificationProduct(string $path, int $userId): ?array
    {
        $product = null;

        if (preg_match('~^/buyer/orders/(\d+)(?:/|$)~', $path, $matches)) {
            // Buyers only ever receive their own order links; the scope keeps it that way
            // even if a link is malformed.
            $product = Order::query()
                ->where('buyer_id', $userId)
                ->with('items.product.images')
                ->find((int) $matches[1])
                ?->items->first()?->product;
        } elseif (preg_match('~^/buyer/products/(\d+)(?:/|$)~', $path, $matches)) {
            $product = Product::query()->with('images')->find((int) $matches[1]);
        }

        $name = $product?->name;
        if (! is_string($name) || $name === '') {
            return null;
        }

        $image = $product->images->first();

        return [
            'name' => $name,
            'image' => $image?->path ? $this->imageUrl($image->path) : null,
        ];
    }

    /** @return array<int, string> */
    private function options(?string $value): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $value))));
    }
}
