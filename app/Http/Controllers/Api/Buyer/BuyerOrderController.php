<?php

namespace App\Http\Controllers\Api\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Communication\Notification;
use App\Models\Ecommerce\Order;
use App\Models\Ecommerce\OrderItem;
use App\Models\Ecommerce\OrderReturnRequest;
use App\Services\BuyerApiPresenter;
use App\Services\OrderCancellationService;
use App\Services\OrderReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

/** The signed-in Buyer's own orders. Every lookup is scoped to buyer_id; nothing else is readable. */
class BuyerOrderController extends Controller
{
    public function __construct(private BuyerApiPresenter $presenter) {}

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tab' => ['nullable', Rule::in(array_keys(Order::BUYER_TABS))],
            'status' => ['nullable', Rule::in(array_keys(Order::STATUSES))],
        ]);

        $tab = $data['tab'] ?? 'all';

        $orders = Order::with(['items.product.images', 'seller.sellerDetail', 'statusEvents', 'latestReturnRequest'])
            ->where('buyer_id', $request->user()->id)
            ->when($data['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($tab === 'returns', fn ($query) => $query->where(fn ($query) => $query
                ->where('status', 'returned')->orWhereHas('latestReturnRequest')))
            ->when(
                in_array($tab, ['all', 'returns'], true) ? null : Order::buyerTabStatuses($tab),
                fn ($query, $statuses) => $query->whereIn('status', $statuses),
            )
            ->latest()->paginate(15);

        return response()->json([
            'data' => collect($orders->items())->map(fn (Order $order) => $this->presenter->orderSummary($order))->values()->all(),
            'tab' => $tab,
            'tabs' => $this->tabCounts($request->user()->id),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
            'statuses' => Order::STATUSES,
        ]);
    }

    /** Every tab with how many of the Buyer's orders it holds, like the counts on the web tabs. */
    private function tabCounts(int $buyerId): array
    {
        $all = Order::where('buyer_id', $buyerId)
            ->withExists('latestReturnRequest')
            ->get(['id', 'status']);

        $counts = [];
        foreach (array_keys(Order::BUYER_TABS) as $key) {
            $counts[$key] = match ($key) {
                'all' => $all->count(),
                'returns' => $all->filter(fn (Order $order) => $order->status === 'returned' || $order->latest_return_request_exists)->count(),
                default => $all->filter(fn (Order $order) => in_array($order->status, Order::buyerTabStatuses($key) ?? [], true))->count(),
            };
        }

        return collect(Order::BUYER_TABS)
            ->map(fn ($tab, $key) => ['key' => $key, 'label' => $tab['label'], 'count' => $counts[$key]])
            ->values()->all();
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        abort_unless((int) $order->buyer_id === (int) $request->user()->id, 404);

        return response()->json(['order' => $this->presenter->orderDetail($this->loadDetail($order))]);
    }

    /** The relations every block of the order page reads. */
    private function loadDetail(Order $order): Order
    {
        return $order->load(
            'items.product.images',
            'items.review.reply',
            'seller.sellerDetail',
            'statusEvents',
            'latestReturnRequest',
            'deliveryProof',
        );
    }

    /** The rider's delivery photo. Same file and checks as the web's orders.delivery-proof route. */
    public function deliveryProof(Request $request, Order $order): StreamedResponse|BinaryFileResponse
    {
        abort_unless((int) $order->buyer_id === (int) $request->user()->id, 404);
        $proof = $order->deliveryProof;
        abort_unless($proof && str_starts_with((string) $proof->photo_path, 'private/delivery-proofs/'.$order->id.'/')
            && Storage::disk('local')->exists($proof->photo_path), 404);

        return Storage::disk('local')->response($proof->photo_path, 'delivery-proof.'.pathinfo($proof->photo_path, PATHINFO_EXTENSION), [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** Ask the seller to take a delivered order back. Same service and rules as the web page. */
    public function requestReturn(Request $request, Order $order, OrderReturnService $returns): JsonResponse
    {
        abort_unless((int) $order->buyer_id === (int) $request->user()->id, 404);
        $data = $request->validate([
            'reason' => ['required', Rule::in(array_keys(OrderReturnRequest::REASONS))],
            'details' => ['required_if:reason,other', 'nullable', 'string', 'max:450'],
        ]);

        try {
            $returns->request($request->user(), $order->id, $data['reason'], $data['details'] ?? null);
        } catch (HttpException $e) {
            // 409: the order changed since the form opened. 422: the service rejected a field.
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        }

        return response()->json([
            'message' => 'Return request sent. The seller will review it.',
            'order' => $this->presenter->orderDetail($this->loadDetail($order)),
        ], 201);
    }

    public function cancel(Request $request, Order $order, OrderCancellationService $cancellations): JsonResponse
    {
        abort_unless((int) $order->buyer_id === (int) $request->user()->id, 404);
        $data = $request->validate([
            'expected_status' => ['required', Rule::in(OrderCancellationService::BUYER_CANCELLABLE_STATUSES)],
            'reason' => ['required', Rule::in(array_keys(OrderCancellationService::BUYER_REASONS))],
            'reason_details' => ['required_if:reason,other', 'nullable', 'string', 'max:450'],
        ]);

        $cancellations->cancelForBuyer(
            $request->user(),
            $order->id,
            $data['expected_status'],
            $data['reason'],
            $data['reason_details'] ?? null,
        );

        return response()->json([
            'message' => 'Your order has been cancelled. Reserved stock has been restored.',
            'order' => $this->presenter->orderDetail($this->loadDetail($order->refresh())),
        ]);
    }

    public function complete(Request $request, Order $order): JsonResponse
    {
        abort_unless((int) $order->buyer_id === (int) $request->user()->id, 404);

        DB::transaction(function () use ($request, $order) {
            $record = Order::where('buyer_id', $request->user()->id)->lockForUpdate()->findOrFail($order->id);
            abort_unless($record->status === 'delivered', 409, 'Only delivered orders can be confirmed as received.');
            $record->update(['status' => 'completed']);
            $record->statusEvents()->create(['user_id' => $request->user()->id, 'from_status' => 'delivered', 'to_status' => 'completed']);
        });

        return response()->json([
            'message' => 'Order received. Thank you!',
            'order' => $this->presenter->orderDetail($this->loadDetail($order->refresh())),
        ]);
    }

    public function review(Request $request, int $orderItem): JsonResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $orderItem, $validated) {
            $item = OrderItem::query()->whereKey($orderItem)->firstOrFail();
            $order = Order::query()->whereKey($item->order_id)
                ->where('buyer_id', $request->user()->id)
                ->lockForUpdate()->firstOrFail();

            abort_unless($order->status === 'completed', 409, 'Reviews are available after you confirm receipt.');
            abort_unless($item->product && (int) $item->product->seller_id === (int) $order->seller_id, 409);
            abort_if($item->review()->exists(), 409, 'You have already reviewed this item.');

            $review = $item->review()->create([
                'order_id' => $order->id,
                'product_id' => $item->product_id,
                'seller_id' => $order->seller_id,
                'buyer_id' => $request->user()->id,
                'rating' => $validated['rating'],
                'comment' => trim($validated['comment']),
                'visibility' => 'published',
            ]);

            Notification::create([
                'user_id' => $order->seller_id,
                'type' => 'product_review',
                'title' => 'New product review',
                'message' => $request->user()->name.' left a '.$review->rating.'-star review.',
                'link' => route('seller.feedback.show', $review),
            ]);
        });

        return response()->json(['message' => 'Your review was submitted. Thank you!'], 201);
    }
}
