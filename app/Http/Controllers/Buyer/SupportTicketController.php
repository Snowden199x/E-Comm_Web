<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Order;
use App\Models\Support\SupportTicket;
use App\Services\SupportTicketService;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $buyer = $request->user();
        abort_unless($buyer->role === 'buyer', 403);

        $tickets = SupportTicket::query()
            ->where('user_id', $buyer->id)
            ->with('order:id,order_number')
            ->withCount('messages')
            ->latest('last_reply_at')
            ->paginate(10);

        $counts = SupportTicket::query()
            ->where('user_id', $buyer->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('buyer.support.index', compact('tickets', 'counts'));
    }

    public function create(Request $request)
    {
        $buyer = $request->user();
        abort_unless($buyer->role === 'buyer', 403);

        $orders = Order::query()
            ->where('buyer_id', $buyer->id)
            ->latest()
            ->limit(40)
            ->get(['id', 'order_number', 'status', 'created_at']);

        return view('buyer.support.create', compact('orders'));
    }

    public function store(Request $request, SupportTicketService $support)
    {
        $data = $request->validate([
            'category' => ['required', 'string', 'in:'.implode(',', array_keys(SupportTicket::CATEGORIES))],
            'subject' => ['required', 'string', 'min:3', 'max:140'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'attachments' => ['nullable', 'array', 'max:2'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ], [
            'subject.required' => 'Add a short subject so Vendo knows what this is about.',
            'subject.min' => 'Use at least 3 characters for the subject.',
            'message.required' => 'Describe the problem so Vendo can help.',
            'message.min' => 'Add a little more detail (at least 10 characters).',
            'category.in' => 'Choose a valid topic.',
        ]);

        $ticket = $support->create($request->user(), $data, $request->file('attachments', []));

        return redirect()->route('buyer.support.show', $ticket)
            ->with('success', 'Your support request was sent. Vendo replies in this thread.');
    }

    public function show(Request $request, int $ticket)
    {
        $buyer = $request->user();
        abort_unless($buyer->role === 'buyer', 403);

        $record = SupportTicket::query()
            ->where('user_id', $buyer->id)
            ->with(['messages.sender:id,name', 'messages.attachments', 'order:id,order_number'])
            ->findOrFail($ticket);

        return view('buyer.support.show', ['ticket' => $record]);
    }

    public function reply(Request $request, int $ticket, SupportTicketService $support)
    {
        $buyer = $request->user();
        abort_unless($buyer->role === 'buyer', 403);

        $data = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:2'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ], ['body.required' => 'Write a message before sending.']);

        $record = SupportTicket::query()->where('user_id', $buyer->id)->findOrFail($ticket);
        $support->replyByBuyer($buyer, $record, $data['body'], $request->file('attachments', []));

        return redirect()->route('buyer.support.show', $record)->with('success', 'Reply sent to Vendo.');
    }

    public function close(Request $request, int $ticket, SupportTicketService $support)
    {
        $buyer = $request->user();
        abort_unless($buyer->role === 'buyer', 403);

        $record = SupportTicket::query()->where('user_id', $buyer->id)->findOrFail($ticket);
        abort_if($record->status === SupportTicket::STATUS_RESOLVED, 409, 'This request is already resolved.');

        $support->setStatus($record, SupportTicket::STATUS_RESOLVED);

        return redirect()->route('buyer.support.show', $record)->with('success', 'You marked this request resolved.');
    }
}
