<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Communication\Notification;
use App\Models\Profiles\LogisticsThread;
use App\Models\Profiles\LogisticsFacility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PortalMessageController extends Controller
{
    public function index(Request $request): View
    {
        $role = (string) $request->route('portal_role');
        $company = $this->company($request, $role);
        $facility = $request->attributes->get('logistics_facility');
        $threads = LogisticsThread::query()->where('logistics_company_id', $company->id)
            ->when($facility, fn ($query) => $query->where('facility_id', $facility->id))
            ->with(['facility', 'messages.sender'])->latest('updated_at')->paginate(30);

        return view('logistics.shared.live-messages', [
            'role' => $role, 'threads' => $threads,
            'facilities' => $role === 'company'
                ? $company->facilities()->where('is_active', true)->whereHas('staff')->whereNotNull('parent_id')->orderBy('name')->get()
                    ->merge($company->provinceHubs()->where('is_active', true)->whereHas('staff')->orderBy('name')->get())
                : collect(),
            'facility' => $facility,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $role = (string) $request->route('portal_role');
        $company = $this->company($request, $role);
        $ownFacility = $request->attributes->get('logistics_facility');
        $data = $request->validate([
            'facility_id' => [$role === 'company' ? 'required' : 'prohibited', 'integer'],
            'subject' => ['required', 'string', 'min:5', 'max:160'],
            'topic' => ['required', Rule::in(['Parcel', 'Trip', 'Rider', 'Driver', 'Account', 'Other'])],
            'priority' => ['required', Rule::in(['normal', 'urgent'])],
            'body' => ['required', 'string', 'min:10', 'max:4000'],
        ]);
        $facility = $ownFacility ?: $company->facilities()->whereKey($data['facility_id'])
            ->where('is_active', true)->whereHas('staff')->firstOrFail();
        DB::transaction(function () use ($request, $company, $facility, $data) {
            $thread = LogisticsThread::create(['logistics_company_id' => $company->id,
                'facility_id' => $facility->id, 'created_by' => $request->user()->id,
                'subject' => $data['subject'], 'topic' => $data['topic'], 'priority' => $data['priority']]);
            $thread->messages()->create(['sender_id' => $request->user()->id, 'body' => $data['body']]);
            $this->notifyOtherSide($thread, $request->user()->id);
        });

        return back()->with('success', 'Message sent.');
    }

    public function reply(Request $request, LogisticsThread $thread): RedirectResponse
    {
        $this->authorizeThread($request, $thread);
        $data = $request->validate(['body' => ['required', 'string', 'min:2', 'max:4000']]);
        DB::transaction(function () use ($request, $thread, $data) {
            $thread = LogisticsThread::query()->lockForUpdate()->findOrFail($thread->id);
            abort_unless($thread->status === 'open', 409, 'Reopen this conversation before replying.');
            $thread->messages()->create(['sender_id' => $request->user()->id, 'body' => $data['body']]);
            $thread->touch();
            $this->notifyOtherSide($thread, $request->user()->id);
        });

        return back()->with('success', 'Reply sent.');
    }

    public function status(Request $request, LogisticsThread $thread): RedirectResponse
    {
        $this->authorizeThread($request, $thread);
        $data = $request->validate(['status' => ['required', Rule::in(['open', 'resolved'])]]);
        $thread->update(['status' => $data['status']]);

        return back()->with('success', 'Conversation updated.');
    }

    private function authorizeThread(Request $request, LogisticsThread $thread): void
    {
        $role = (string) $request->route('portal_role');
        $company = $this->company($request, $role);
        abort_unless((int) $thread->logistics_company_id === (int) $company->id, 404);
        $facility = $request->attributes->get('logistics_facility');
        abort_if($facility && (int) $thread->facility_id !== (int) $facility->id, 404);
    }

    private function company(Request $request, string $role)
    {
        return $role === 'company' ? $request->user()->logisticsCenterDetail->company
            : $request->attributes->get('logistics_company');
    }

    private function notifyOtherSide(LogisticsThread $thread, int $senderId): void
    {
        $thread->loadMissing('facility.staff', 'facility.company.logisticsCenter');
        $companyUserId = $thread->facility->company?->logisticsCenter?->user_id;
        $facilityUserId = $thread->facility->staff?->user_id;
        $targetId = (int) $senderId === (int) $companyUserId ? $facilityUserId : $companyUserId;
        if (! $targetId || (int) $targetId === (int) $senderId) return;
        $route = (int) $targetId === (int) $companyUserId ? 'lgp.company.messages'
            : ($thread->facility->isProvinceHub() ? 'lgp.province.messages' : 'lgp.station.messages');
        Notification::create(['user_id' => $targetId, 'type' => 'logistics_message',
            'title' => 'Logistics message: '.$thread->subject,
            'message' => 'A new message is available in your Logistics inbox.', 'link' => route($route)]);
    }
}
