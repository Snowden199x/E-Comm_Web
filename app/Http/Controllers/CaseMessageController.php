<?php

namespace App\Http\Controllers;

use App\Models\Complaints\Complaint;
use App\Models\Communication\Conversation;
use App\Models\Communication\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CaseMessageController extends Controller
{
    public function adminStore(Request $request, Complaint $complaint): RedirectResponse
    {
        $validated = $request->validate([
            'recipient_id' => ['required', 'integer'],
            'body' => ['required', 'string', 'min:1', 'max:2000'],
        ]);
        abort_if($complaint->status === 'resolved', 409, 'This case is resolved.');
        $recipient = collect([$complaint->complainant, $complaint->respondent])
            ->first(fn ($person) => $person && $person->id === (int) $validated['recipient_id']);
        abort_unless($recipient && in_array($recipient->role, ['buyer', 'seller'], true), 403);

        DB::transaction(function () use ($complaint, $recipient, $validated) {
            $lockedCase = Complaint::query()->whereKey($complaint->id)->lockForUpdate()->firstOrFail();
            abort_if($lockedCase->status === 'resolved', 409, 'This case is resolved.');
            $conversation = Conversation::query()->firstOrCreate(
                ['complaint_id' => $complaint->id, 'user_id' => $recipient->id],
                ['status' => 'open']
            );
            $conversation->messages()->create([
                'sender_id' => Auth::guard('admin')->id(),
                'body' => trim($validated['body']),
            ]);
            $conversation->update(['last_message_at' => now()]);
            Notification::create([
                'user_id' => $recipient->id,
                'type' => 'case_message',
                'title' => 'New message about your case',
                'message' => 'Vendo sent an update about case #'.$complaint->id.'.',
                'link' => route($recipient->role.'.case-messages.show', $complaint),
            ]);
        });

        return back()->with('case_message_sent', true);
    }

    public function show(Request $request, Complaint $complaint): View
    {
        $conversation = $this->participantConversation($request, $complaint);
        $conversation->messages()->whereNull('read_at')->where('sender_id', '!=', $request->user()->id)->update(['read_at' => now()]);
        $conversation->load(['messages.sender']);

        return view('shared.case-messages', compact('complaint', 'conversation'));
    }

    public function store(Request $request, Complaint $complaint): RedirectResponse
    {
        $conversation = $this->participantConversation($request, $complaint);
        abort_if($complaint->status === 'resolved' || $conversation->status !== 'open', 409, 'This case conversation is closed.');
        $validated = $request->validate(['body' => ['required', 'string', 'min:1', 'max:2000']]);

        DB::transaction(function () use ($conversation, $request, $validated, $complaint) {
            $lockedCase = Complaint::query()->whereKey($complaint->id)->lockForUpdate()->firstOrFail();
            abort_if($lockedCase->status === 'resolved', 409, 'This case is resolved.');
            $lockedConversation = Conversation::query()->whereKey($conversation->id)->lockForUpdate()->firstOrFail();
            abort_if($lockedConversation->status !== 'open', 409, 'This case conversation is closed.');
            $lockedConversation->messages()->create(['sender_id' => $request->user()->id, 'body' => trim($validated['body'])]);
            $lockedConversation->update(['last_message_at' => now()]);
            Notification::create([
                'user_id' => null,
                'type' => 'case_message',
                'title' => 'New case message',
                'message' => 'A participant replied to case #'.$complaint->id.'.',
                'link' => route('admin.complaints.show', $complaint).'#messages-title',
            ]);
        });

        return back()->with('case_message_sent', true);
    }

    private function participantConversation(Request $request, Complaint $complaint): Conversation
    {
        $user = $request->user();
        abort_unless($user && in_array($user->role, ['buyer', 'seller'], true), 403);
        abort_unless(in_array((int) $user->id, [(int) $complaint->complainant_id, (int) $complaint->respondent_id], true), 404);

        return Conversation::query()->where('complaint_id', $complaint->id)
            ->where('user_id', $user->id)->firstOrFail();
    }
}
