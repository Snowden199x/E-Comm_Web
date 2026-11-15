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

        $status = (string) $request->query('status', '');
        if (! array_key_exists($status, SupportTicket::STATUSES)) {
            $status = '';
        }
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) > 100) {
            return redirect()->route('buyer.support.index', array_filter(['status' => $status]))
                ->withErrors(['q' => 'Search is too long. Use 100 characters or fewer.']);
        }

        $tickets = SupportTicket::query()
            ->where('user_id', $buyer->id)
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($q !== '', function ($query) use ($q) {
                $number = ltrim($q, '#');
                $query->where(function ($inner) use ($q, $number) {
                    $inner->where('subject', 'like', '%'.$q.'%');
                    if (ctype_digit($number)) {
                        $inner->orWhere('id', (int) $number);
                    }
                });
            })
            ->with('order:id,order_number')
            ->withCount('messages')
            ->latest('last_reply_at')
            ->paginate(10)
            ->withQueryString();

        $counts = SupportTicket::query()
            ->where('user_id', $buyer->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('buyer.support.index', compact('tickets', 'counts', 'status', 'q'));
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
            'subject.required' => 'Subject is empty. Add a short subject, like "Wrong item delivered".',
            'subject.min' => 'Subject is too short. Use at least 3 characters.',
            'subject.max' => 'Subject is too long. Shorten it to 140 characters or fewer.',
            'message.required' => 'Message is empty. Describe the problem so Vendo can help.',
            'message.min' => 'Message is too short. Add at least 10 characters, such as what happened and when.',
            'message.max' => 'Message is too long. Shorten it to 5,000 characters or fewer.',
            'category.required' => 'No topic chosen. Pick the topic that fits best.',
            'category.in' => 'That topic is not available. Pick one from the list.',
            'order_id.exists' => 'That order was not found. Pick an order from the list, or choose "Not about one order".',
            'attachments.max' => 'Too many files. Attach 2 files or fewer.',
            'attachments.*.mimes' => 'One file is not a supported type. Attach a JPG, PNG, WebP or PDF.',
            'attachments.*.max' => 'One file is over 5 MB. Attach a smaller file.',
            'attachments.*.file' => 'One file could not be uploaded. Choose it again.',
        ]);

        $ticket = $support->create($request->user(), $data, $request->file('attachments', []));

        return redirect()->route('buyer.support.show', $ticket)
            ->with('success', 'Request sent. Vendo replies in this thread.');
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
        ], [
            'body.required' => 'Reply is empty. Write your message, then send it.',
            'body.min' => 'Reply is too short. Add at least 2 characters.',
            'body.max' => 'Reply is too long. Shorten it to 5,000 characters or fewer.',
            'attachments.max' => 'Too many files. Attach 2 files or fewer.',
            'attachments.*.mimes' => 'One file is not a supported type. Attach a JPG, PNG, WebP or PDF.',
            'attachments.*.max' => 'One file is over 5 MB. Attach a smaller file.',
            'attachments.*.file' => 'One file could not be uploaded. Choose it again.',
        ]);

        $record = SupportTicket::query()->where('user_id', $buyer->id)->findOrFail($ticket);
        $support->replyByBuyer($buyer, $record, $data['body'], $request->file('attachments', []));

        return redirect()->route('buyer.support.show', $record)->with('success', 'Reply sent.');
    }

    public function close(Request $request, int $ticket, SupportTicketService $support)
    {
        $buyer = $request->user();
        abort_unless($buyer->role === 'buyer', 403);

        $record = SupportTicket::query()->where('user_id', $buyer->id)->findOrFail($ticket);
        if ($record->status === SupportTicket::STATUS_RESOLVED) {
            return redirect()->route('buyer.support.show', $record)
                ->withErrors(['status' => 'This request is already resolved. Open a new request if you still need help.']);
        }

        $support->setStatus($record, SupportTicket::STATUS_RESOLVED, true);

        return redirect()->route('buyer.support.show', $record)->with('success', 'Request marked as resolved.');
    }
}
