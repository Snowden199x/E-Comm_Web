<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\OrderReturnRequest;
use App\Services\OrderReturnService;
use Illuminate\Http\Request;

class ReturnRequestController extends Controller
{
    public function index(Request $request)
    {
        $sellerId = $request->user()->id;
        $pending = OrderReturnRequest::query()->where('seller_id', $sellerId)
            ->where('status', OrderReturnRequest::STATUS_PENDING)
            ->with(['order', 'buyer:id,name'])->oldest()->get();
        $decided = OrderReturnRequest::query()->where('seller_id', $sellerId)
            ->whereIn('status', [OrderReturnRequest::STATUS_APPROVED, OrderReturnRequest::STATUS_REJECTED])
            ->with(['order', 'buyer:id,name'])->latest('decided_at')->limit(30)->get();

        return view('seller.returns.index', compact('pending', 'decided'));
    }

    public function approve(Request $request, int $returnRequest, OrderReturnService $returns)
    {
        $record = $returns->approve($request->user(), $returnRequest);

        return redirect()->route('seller.returns.index')
            ->with('success', 'Return approved for '.$record->order->number.'. The stock is back and the refund is recorded.');
    }

    public function reject(Request $request, int $returnRequest, OrderReturnService $returns)
    {
        $data = $request->validate([
            'decision_note' => ['required', 'string', 'min:5', 'max:450'],
        ], [
            'decision_note.required' => 'Add a reason for declining the return.',
            'decision_note.min' => 'Add a little more detail so the buyer understands why.',
        ]);

        $returns->reject($request->user(), $returnRequest, $data['decision_note']);

        return redirect()->route('seller.returns.index')->with('success', 'Return declined. The buyer was notified.');
    }
}
