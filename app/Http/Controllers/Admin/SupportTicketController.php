<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActionLog;
use App\Models\Support\SupportTicket;
use App\Services\SupportTicketService;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $status = (string) $request->query('status', '');
        $category = (string) $request->query('category', '');
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) > 100) {
            return redirect()->route('admin.support-tickets.index', array_filter(['status' => $status, 'category' => $category]))
                ->withErrors(['q' => 'Search is too long. Use 100 characters or fewer.']);
        }

        $tickets = SupportTicket::query()
            ->with(['user:id,name,email', 'order:id,order_number'])
            ->withCount('messages')
            ->when(isset(SupportTicket::STATUSES[$status]), fn ($query) => $query->where('status', $status))
            ->when(isset(SupportTicket::CATEGORIES[$category]), fn ($query) => $query->where('category', $category))
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.$q.'%';
                $number = '%'.ltrim($q, '#').'%'; // "#12" finds ticket 12
                $query->where(function ($inner) use ($like, $number) {
                    $inner->where('subject', 'like', $like)
                        ->orWhere('id', 'like', $number)
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', $like)->orWhere('email', 'like', $like));
                });
            })
            ->latest('last_reply_at')
            ->paginate(15)
            ->withQueryString();

        $counts = SupportTicket::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.support-tickets.index', [
            'tickets' => $tickets,
            'counts' => $counts,
            'status' => $status,
            'category' => $category,
            'q' => $q,
        ]);
    }

    public function show(int $ticket)
    {
        $record = SupportTicket::query()
            ->with(['user:id,name,email,phone_number', 'order:id,order_number', 'messages.sender:id,name,role', 'messages.attachments'])
            ->findOrFail($ticket);

        return view('admin.support-tickets.show', ['ticket' => $record]);
    }

    public function reply(Request $request, int $ticket, SupportTicketService $support)
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:2'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ], [
            'body.required' => 'Reply is empty. Write your reply, then send it.',
            'body.min' => 'Reply is too short. Add at least 2 characters.',
            'body.max' => 'Reply is too long. Shorten it to 5,000 characters or fewer.',
            'attachments.max' => 'Too many files. Attach 2 files or fewer.',
            'attachments.*.mimes' => 'One file is not a supported type. Attach a JPG, PNG, WebP or PDF.',
            'attachments.*.max' => 'One file is over 5 MB. Attach a smaller file.',
            'attachments.*.file' => 'One file could not be uploaded. Choose it again.',
        ]);

        $record = SupportTicket::query()->findOrFail($ticket);
        if ($record->status === SupportTicket::STATUS_RESOLVED) {
            return back()->with('error', 'This ticket is already resolved. Reopen it, then send your reply.');
        }

        $support->replyByAdmin(auth('admin')->user(), $record, $data['body'], $request->file('attachments', []));

        return redirect()->route('admin.support-tickets.show', $record)->with('success', 'Reply sent. The buyer is notified.');
    }

    public function status(Request $request, int $ticket, SupportTicketService $support)
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', array_keys(SupportTicket::STATUSES))],
        ], [
            'status.required' => 'No status chosen. Pick Open, In review or Resolved.',
            'status.in' => 'That status is not available. Pick Open, In review or Resolved.',
        ]);

        $record = SupportTicket::query()->with('user')->findOrFail($ticket);
        $support->setStatus($record, $data['status']);

        AdminActionLog::record($record->user, 'support_ticket_status',
            'Ticket #'.$record->id.' "'.$record->subject.'" set to '.strtolower(SupportTicket::STATUSES[$data['status']]));

        return redirect()->route('admin.support-tickets.show', $record)
            ->with('success', 'Ticket marked '.strtolower(SupportTicket::STATUSES[$data['status']]).'.'
                .($data['status'] === SupportTicket::STATUS_RESOLVED ? ' The buyer is notified.' : ''));
    }
}
