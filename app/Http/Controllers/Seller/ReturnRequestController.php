<?php

namespace App\Http\Controllers\Seller;

use App\Support\SellerSearch;
use App\Http\Controllers\Controller;
use App\Models\Ecommerce\OrderReturnRequest;
use App\Services\OrderReturnService;
use App\Services\ReturnTripService;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ReturnRequestController extends Controller
{
    /** Filter keys for the status chips. "declined" is stored as "rejected". */
    private const STATUS_FILTERS = [
        'pending' => OrderReturnRequest::STATUS_PENDING,
        'approved' => OrderReturnRequest::STATUS_APPROVED,
        'declined' => OrderReturnRequest::STATUS_REJECTED,
    ];

    public function index(Request $request)
    {
        $sellerId = $request->user()->id;

        $status = (string) $request->query('status', '');
        if (! array_key_exists($status, self::STATUS_FILTERS)) {
            $status = '';
        }
        $reason = (string) $request->query('reason', '');
        if (! array_key_exists($reason, OrderReturnRequest::REASONS)) {
            $reason = '';
        }
        $q = SellerSearch::clean((string) $request->query('q', ''));
        if (mb_strlen($q) > 100) {
            return redirect()->route('seller.returns.index', array_filter(['status' => $status, 'reason' => $reason]))
                ->withErrors(['q' => 'Search is too long. Use 100 characters or fewer.']);
        }

        $returns = OrderReturnRequest::query()
            ->where('seller_id', $sellerId)
            ->when($status !== '', fn ($query) => $query->where('status', self::STATUS_FILTERS[$status]))
            ->when($reason !== '', fn ($query) => $query->where('reason', $reason))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    SellerSearch::orWhereOrder($inner, $q);
                    $inner->orWhereHas('buyer', fn ($buyer) => $buyer->where('name', 'like', SellerSearch::like($q)))
                        ->orWhereHas('order.items.product', fn ($product) => $product->where('name', 'like', SellerSearch::like($q)));
                });
            })
            ->with(['order.items.product.images', 'buyer:id,name', 'pickupRider:id,name'])
            // Requests waiting for a decision come first, oldest first; handled ones follow, newest first.
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByRaw("CASE WHEN status = 'pending' THEN created_at END ASC")
            ->latest('decided_at')
            ->paginate(15)
            ->withQueryString();

        $counts = OrderReturnRequest::query()->where('seller_id', $sellerId)
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $onTheWay = OrderReturnRequest::query()->where('seller_id', $sellerId)
            ->where('status', OrderReturnRequest::STATUS_APPROVED)
            ->whereIn('return_status', [OrderReturnRequest::RETURN_STATUS_PENDING_PICKUP, OrderReturnRequest::RETURN_STATUS_PICKED_UP])
            ->count();

        $refundedCents = (int) OrderReturnRequest::query()->where('seller_id', $sellerId)
            ->where('status', OrderReturnRequest::STATUS_APPROVED)->sum('refund_cents');

        // Notification links arrive as ?return=ID. Open that return even when it is not on this page of the list.
        $focusId = (int) $request->query('return');
        $focus = $focusId > 0
            ? OrderReturnRequest::query()->where('seller_id', $sellerId)
                ->with(['order.items.product.images', 'buyer:id,name', 'pickupRider:id,name'])->find($focusId)
            : null;
        $focusMissing = $focusId > 0 && ! $focus;
        $focusOffPage = $focus && ! $returns->getCollection()->contains('id', $focus->id);

        return view('seller.returns.index', compact('returns', 'counts', 'onTheWay', 'refundedCents', 'status', 'reason', 'q', 'focus', 'focusMissing', 'focusOffPage'));
    }

    public function approve(Request $request, int $returnRequest, OrderReturnService $returns)
    {
        try {
            $record = $returns->approve($request->user(), $returnRequest);
        } catch (HttpException $e) {
            return $this->failed($e);
        }

        return redirect()->route('seller.returns.index')
            ->with('success', 'Return approved for '.$record->order->number.'. The stock is back and the refund is recorded.');
    }

    /** Confirms the returned parcel is back in the seller's hands when the Rider app could not scan it. */
    public function received(Request $request, int $returnRequest, ReturnTripService $trips)
    {
        $record = OrderReturnRequest::query()->where('seller_id', $request->user()->id)->findOrFail($returnRequest);

        try {
            $trips->confirmReceived($request->user(), $record);
        } catch (HttpException $e) {
            return $this->failed($e);
        }

        return redirect()->route('seller.returns.index')->with('success', 'Return marked as received. The buyer was notified.');
    }

    public function reject(Request $request, int $returnRequest, OrderReturnService $returns)
    {
        $data = $request->validate([
            'decision_note' => ['required', 'string', 'min:5', 'max:450'],
        ], [
            'decision_note.required' => 'Reason is empty. Tell the buyer why you are declining the return.',
            'decision_note.min' => 'Reason is too short. Use at least 5 characters, for example what you found on the item.',
            'decision_note.max' => 'Reason is too long. Shorten it to 450 characters or fewer.',
        ]);

        try {
            $returns->reject($request->user(), $returnRequest, $data['decision_note']);
        } catch (HttpException $e) {
            return $this->failed($e);
        }

        return redirect()->route('seller.returns.index')->with('success', 'Return declined. The buyer was notified.');
    }

    /**
     * Business-rule refusals (409 / 422) come back to the list as a banner with the fix.
     * Anything else (403, 404) keeps its normal error page.
     */
    private function failed(HttpException $e)
    {
        if (! in_array($e->getStatusCode(), [409, 422], true)) {
            throw $e;
        }

        $fixes = [
            'This return request was already handled.' => 'This return was already handled. Reload the list to see its latest status.',
            'This order can no longer be returned.' => 'This order can no longer be returned, so it cannot be approved. Check the order status.',
            'The rider has not collected the parcel yet.' => 'The rider has not collected the parcel yet. Mark it received after the rider picks it up.',
            'This return was not approved.' => 'This return was not approved. Approve it before marking it received.',
            'Add a reason for declining the return.' => 'Reason is empty. Tell the buyer why you are declining the return.',
        ];
        $message = $e->getMessage();

        return redirect()->route('seller.returns.index')
            ->withErrors(['return' => $fixes[$message] ?? rtrim($message, '.').'. Reload the list and try again.']);
    }
}
