<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Communication\Conversation;
use App\Models\Communication\Message;
use App\Models\Communication\MessageAttachment;
use App\Models\Communication\Notification;
use App\Models\Complaints\Complaint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Admin inbox: complaint cases (one row per case, up to two people) and direct support conversations.
 * Rows are keyed "cs-{complaint id}" and "dm-{conversation id}".
 */
class MessageController extends Controller
{
    private const LIMIT = 100;

    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        $inbox = $this->inbox($filters);

        $activeKey = null;
        $activeUrl = null;
        if ($request->filled('conversation')) {
            $id = (int) $request->query('conversation');
            $activeKey = 'dm-'.$id;
            $activeUrl = route('admin.messages.thread', $id);
        } elseif ($request->filled('case')) {
            $id = (int) $request->query('case');
            $activeKey = 'cs-'.$id;
            $activeUrl = route('admin.messages.case.thread', $id);
        }

        return view('admin.messages.index', $inbox + compact('filters', 'activeKey', 'activeUrl'));
    }

    public function conversationsList(Request $request)
    {
        $filters = $this->filters($request);
        $inbox = $this->inbox($filters);

        return response()
            ->view('admin.messages.partials.conversation-list', $inbox + ['filters' => $filters])
            ->header('X-Counts', json_encode($inbox['counts']));
    }

    public function thread(Conversation $conversation): View
    {
        abort_unless($conversation->complaint_id === null, 404);
        $conversation->load(['user']);

        $this->markRead($conversation);

        return view('admin.messages.partials.thread', compact('conversation'));
    }

    public function fetchMessages(Conversation $conversation): JsonResponse
    {
        abort_unless($conversation->complaint_id === null, 404);
        $this->markRead($conversation);

        return response()->json([
            'messages' => $this->payload($conversation->messages()->with('attachments', 'sender')->get()),
            'can_reply' => true,
        ]);
    }

    public function caseThread(Complaint $complaint): View
    {
        $parties = $this->caseParties($complaint);
        abort_if($parties->isEmpty(), 404);

        $unread = $this->caseUnread($complaint);
        $defaultWho = ($parties->first(fn ($p) => ($unread[$p->id] ?? 0) > 0) ?? $parties->first())->id;

        return view('admin.messages.partials.case-thread', compact('complaint', 'parties', 'defaultWho'));
    }

    public function caseFetch(Request $request, Complaint $complaint): JsonResponse
    {
        $person = $this->caseParties($complaint)->firstWhere('id', (int) $request->query('recipient'));
        abort_unless($person, 404);

        $conversation = Conversation::where(['complaint_id' => $complaint->id, 'user_id' => $person->id])->first();
        $canReply = $complaint->status !== 'resolved';

        if (! $conversation) {
            return response()->json(['messages' => [], 'can_reply' => $canReply]);
        }

        $this->markRead($conversation);

        return response()->json([
            'messages' => $this->payload($conversation->messages()->with('attachments', 'sender')->get()),
            'can_reply' => $canReply,
        ]);
    }

    public function send(Request $request, Conversation $conversation): JsonResponse
    {
        abort_unless($conversation->complaint_id === null, 404);
        $request->validate([
            'body' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'max:5120'],
        ], [
            'body.max' => 'Shorten the message to 2,000 characters or fewer.',
            'attachment.max' => 'The file is over 5 MB. Choose a smaller one.',
            'attachment.file' => 'Choose a file to attach.',
        ]);

        if (! $request->filled('body') && ! $request->hasFile('attachment')) {
            return response()->json(['message' => 'Write a message or attach a file.'], 422);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => Auth::guard('admin')->id(),
            'body' => $request->body,
        ]);

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('chat-attachments', 'public');

            MessageAttachment::create([
                'message_id' => $message->id,
                'path' => $path,
                'original_filename' => $request->file('attachment')->getClientOriginalName(),
            ]);
        }

        $conversation->update(['last_message_at' => now()]);

        if (in_array($conversation->user?->role, ['seller', 'buyer'], true)) {
            Notification::create([
                'user_id' => $conversation->user_id,
                'type' => 'support_reply',
                'title' => 'New reply from Vendo Support',
                'message' => 'Your support conversation has a new reply.',
                'link' => $conversation->user->role === 'seller'
                    ? route('seller.messages.index') : route('buyer.messages.index'),
            ]);
        }

        return response()->json(['success' => true]);
    }

    // ------------------------------------------------------------------ inbox

    /** Read filters defensively: query values can be arrays or junk. */
    private function filters(Request $request): array
    {
        $date = $request->string('date')->toString();
        $status = $request->string('status', 'all')->toString();
        $tab = $request->string('tab', 'all')->toString();

        return [
            'search' => $request->string('search')->trim()->limit(100, '')->toString(),
            'date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : '',
            'status' => in_array($status, ['all', 'unread', 'open', 'closed'], true) ? $status : 'all',
            'tab' => in_array($tab, ['all', 'complaints', 'direct'], true) ? $tab : 'all',
        ];
    }

    private function inbox(array $filters): array
    {
        $needle = Str::lower($filters['search']);

        $items = $this->allItems()->filter(function ($i) use ($filters, $needle) {
            if ($needle !== '' && ! str_contains($i->haystack, $needle)) {
                return false;
            }
            if ($filters['date'] !== '' && $i->last_at?->toDateString() !== $filters['date']) {
                return false;
            }
            if ($filters['status'] === 'unread' && $i->unread < 1) {
                return false;
            }
            if (in_array($filters['status'], ['open', 'closed'], true) && $i->status !== $filters['status']) {
                return false;
            }

            return true;
        });

        $counts = [
            'all' => $items->count(),
            'complaints' => $items->where('kind', 'complaint')->count(),
            'direct' => $items->where('kind', 'direct')->count(),
        ];

        if ($filters['tab'] !== 'all') {
            $items = $items->where('kind', $filters['tab'] === 'complaints' ? 'complaint' : 'direct');
        }

        $total = $items->count();
        $items = $items->sortByDesc(fn ($i) => $i->last_at?->timestamp ?? 0)->take(self::LIMIT)->values();

        return ['items' => $items, 'counts' => $counts, 'total' => $total, 'limit' => self::LIMIT];
    }

    private function allItems(): Collection
    {
        $adminId = Auth::guard('admin')->id();
        $unread = fn ($q) => $q->whereNull('read_at')->where('sender_id', '!=', $adminId);
        $roleLabel = ['seller' => 'Seller', 'buyer' => 'Buyer', 'logistics_center' => 'Courier'];
        $code = fn (string $prefix, $model) => $prefix.'-'.($model->created_at?->format('Y') ?? now()->format('Y')).'-'.str_pad((string) $model->id, 4, '0', STR_PAD_LEFT);

        $direct = Conversation::whereNull('complaint_id')
            ->with(['user:id,name,email,role', 'latestMessage'])
            ->withCount(['messages as unread_count' => $unread])
            ->get()
            ->filter(fn ($c) => $c->user)
            ->map(function ($c) use ($adminId, $roleLabel, $code) {
                $id = $code('ADM', $c);
                $last = $c->latestMessage;
                $preview = $last
                    ? ($last->sender_id === $adminId ? 'You: ' : '').Str::limit($last->body ?: 'Attachment', 40)
                    : 'No messages yet';

                return (object) [
                    'key' => 'dm-'.$c->id,
                    'kind' => 'direct',
                    'code' => $id,
                    'subject' => $preview,
                    'detail' => null,
                    'people' => [['name' => $c->user->name, 'role' => $roleLabel[$c->user->role] ?? ucfirst($c->user->role)]],
                    'unread' => (int) $c->unread_count,
                    'last_at' => $c->last_message_at ?? $c->created_at,
                    'status' => $c->status === 'closed' ? 'closed' : 'open',
                    'url' => route('admin.messages.thread', $c),
                    'haystack' => Str::lower($id.' '.$c->user->name.' '.$c->user->email.' '.$preview),
                ];
            });

        $cases = Conversation::whereNotNull('complaint_id')
            ->with(['latestMessage', 'complaint.order:id', 'complaint.complainant:id,name,email,role', 'complaint.respondent:id,name,email,role'])
            ->withCount(['messages as unread_count' => $unread])
            ->get()
            ->filter(fn ($c) => $c->complaint)
            ->groupBy('complaint_id')
            ->map(function ($group) use ($roleLabel, $code) {
                $complaint = $group->first()->complaint;
                $id = $code('CMP', $complaint);
                $order = $complaint->order ? 'ORD-'.str_pad((string) $complaint->order->id, 4, '0', STR_PAD_LEFT) : null;
                $people = collect([$complaint->complainant, $complaint->respondent])->filter()->unique('id')->values();
                $lastAt = $group->map(fn ($c) => $c->last_message_at ?? $c->created_at)->filter()->max();

                return (object) [
                    'key' => 'cs-'.$complaint->id,
                    'kind' => 'complaint',
                    'code' => $id,
                    'subject' => $complaint->type ?? 'Other',
                    'detail' => $order ? 'Order #'.$order : null,
                    'people' => $people->map(fn ($p) => ['name' => $p->name, 'role' => $roleLabel[$p->role] ?? ucfirst($p->role)])->all(),
                    'unread' => (int) $group->sum('unread_count'),
                    'last_at' => $lastAt,
                    'status' => $complaint->status === 'resolved' ? 'closed' : 'open',
                    'url' => route('admin.messages.case.thread', $complaint),
                    'haystack' => Str::lower($id.' '.($complaint->type ?? '').' '.$order.' '.$people->map(fn ($p) => $p->name.' '.$p->email)->implode(' ')),
                ];
            });

        return $direct->concat($cases->values())->values();
    }

    // ---------------------------------------------------------------- helpers

    /** Buyer and seller on a case. Only they can be messaged (same rule as CaseMessageController). */
    private function caseParties(Complaint $complaint): Collection
    {
        $complaint->loadMissing(['order:id', 'complainant:id,name,email,role,profile_picture', 'respondent:id,name,email,role,profile_picture']);

        return collect([$complaint->complainant, $complaint->respondent])
            ->filter(fn ($u) => $u && in_array($u->role, ['buyer', 'seller'], true))
            ->unique('id')
            ->values();
    }

    private function caseUnread(Complaint $complaint): Collection
    {
        $adminId = Auth::guard('admin')->id();

        return Conversation::where('complaint_id', $complaint->id)
            ->withCount(['messages as unread_count' => fn ($q) => $q->whereNull('read_at')->where('sender_id', '!=', $adminId)])
            ->pluck('unread_count', 'user_id');
    }

    private function markRead(Conversation $conversation): void
    {
        $conversation->messages()
            ->whereNull('read_at')
            ->where('sender_id', '!=', Auth::guard('admin')->id())
            ->update(['read_at' => now()]);
    }

    private function payload(Collection $messages): array
    {
        $adminId = Auth::guard('admin')->id();

        return $messages->map(fn ($m) => [
            'id' => $m->id,
            'body' => $m->body,
            'is_mine' => $m->sender_id === $adminId,
            'avatar' => $m->sender?->profile_picture ? Storage::disk('public')->url($m->sender->profile_picture) : null,
            'initial' => mb_strtoupper(mb_substr($m->sender?->name ?? 'V', 0, 1)),
            'created_at' => $m->created_at->isToday() ? $m->created_at->format('g:i A') : $m->created_at->format('M j, g:i A'),
            'attachments' => $m->attachments->map(function ($a) {
                $ext = strtolower(pathinfo((string) $a->original_filename, PATHINFO_EXTENSION));

                return [
                    'url' => route('messages.attachments.show', $a),
                    'name' => $a->original_filename,
                    'kind' => in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) ? 'image' : ($ext === 'pdf' ? 'pdf' : 'file'),
                ];
            })->values(),
        ])->values()->all();
    }
}
