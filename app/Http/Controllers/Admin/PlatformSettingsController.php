<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Communication\Announcement;
use App\Models\Communication\ChatSetting;
use App\Models\Communication\Notification;
use App\Models\Communication\PlatformPolicy;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PlatformSettingsController extends Controller
{
    /** Policy types in the order the page lists them. */
    private const POLICY_TYPES = ['Seller Policy', 'Buyer Policy', 'Logistics Policy', 'Prohibited Item Policy'];

    /** Who is notified when a policy changes. Shown in the editor so the admin knows before saving. */
    private const POLICY_AUDIENCE = [
        'Seller Policy' => 'Sellers',
        'Buyer Policy' => 'Buyers',
        'Logistics Policy' => 'Couriers',
        'Prohibited Item Policy' => 'Sellers, Buyers and Couriers',
    ];

    /** Stored as-is and matched by the Buyer and Seller dashboards: do not rename. */
    private const AUDIENCES = ['All Users', 'Buyers & Sellers', 'Buyers Only', 'Sellers Only', 'Couriers Only'];

    private const STATUSES = ['draft', 'scheduled', 'published'];

    public function index(Request $request): View
    {
        $announcements = $this->filteredAnnouncements($request);

        $policies = PlatformPolicy::all()
            ->sortBy(fn (PlatformPolicy $policy) => array_search($policy->name, self::POLICY_TYPES, true) === false
                ? count(self::POLICY_TYPES)
                : array_search($policy->name, self::POLICY_TYPES, true))
            ->values();

        $byStatus = Announcement::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $stats = [
            'all' => (int) $byStatus->sum(),
            'published' => (int) ($byStatus['published'] ?? 0),
            'scheduled' => (int) ($byStatus['scheduled'] ?? 0),
            'draft' => (int) ($byStatus['draft'] ?? 0),
        ];

        return view('admin.platform-settings.index', [
            'announcements' => $announcements,
            'policies' => $policies,
            'policyTypes' => self::POLICY_TYPES,
            'policyAudience' => self::POLICY_AUDIENCE,
            'audiences' => self::AUDIENCES,
            'stats' => $stats,
            'greeting' => ChatSetting::currentWelcomeMessage(),
            'search' => $request->string('search')->trim()->toString(),
            'date' => $this->validDate($request->string('date')->toString()),
            'status' => $this->validStatus($request->string('status')->toString()),
        ]);
    }

    public function announcementsTable(Request $request): View
    {
        return view('admin.platform-settings.partials.announcements-table', [
            'announcements' => $this->filteredAnnouncements($request),
        ]);
    }

    public function updateChatWelcome(Request $request): RedirectResponse
    {
        $request->validate(
            ['welcome_message' => ['required', 'string', 'max:1000']],
            [
                'welcome_message.required' => 'Write a greeting for new chats.',
                'welcome_message.max' => 'Shorten the greeting to 1,000 characters or fewer.',
            ],
        );

        $setting = ChatSetting::first();

        if ($setting) {
            $setting->update(['welcome_message' => $request->welcome_message]);
        } else {
            ChatSetting::create(['welcome_message' => $request->welcome_message]);
        }

        return back()->with('success', 'Greeting saved. New chats use it.');
    }

    public function storeAnnouncement(Request $request): RedirectResponse
    {
        $data = $this->validatedAnnouncement($request);

        $announcement = Announcement::create($data);

        return back()->with('success', $this->savedMessage($announcement));
    }

    public function updateAnnouncement(Request $request, Announcement $announcement): RedirectResponse
    {
        $announcement->update($this->validatedAnnouncement($request));

        return back()->with('success', $this->savedMessage($announcement));
    }

    public function destroyAnnouncement(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return back()->with('success', 'Announcement deleted');
    }

    public function storePolicy(Request $request): RedirectResponse
    {
        $request->validate(
            ['name' => ['required', Rule::in(self::POLICY_TYPES)]],
            ['name.*' => 'Pick one of the four policy types.'],
        );

        $policy = PlatformPolicy::firstOrCreate(
            ['name' => $request->name],
            ['version' => '1.0', 'content' => '']
        );

        // The editor opens straight away: an empty policy is hidden from users.
        return back()
            ->with('success', $policy->wasRecentlyCreated
                ? "{$policy->name} added. Write its content to publish it."
                : "{$policy->name} is already added.")
            ->with('edit_policy', $policy->id);
    }

    public function updatePolicy(Request $request, PlatformPolicy $policy): RedirectResponse
    {
        $request->validate(
            [
                'content' => ['nullable', 'string'],
                'version' => ['required', 'string', 'max:20'],
            ],
            [
                'version.required' => 'Enter a version, like 1.1.',
                'version.max' => 'Shorten the version to 20 characters or fewer.',
            ],
        );

        $policy->update([
            'content' => $request->content,
            'version' => $request->version,
        ]);

        $this->notifyPolicyUpdate($policy);

        $who = self::POLICY_AUDIENCE[$policy->name] ?? 'Users';

        return back()->with('success', "{$policy->name} saved. {$who} were notified.");
    }

    public function destroyPolicy(PlatformPolicy $policy): RedirectResponse
    {
        $policy->delete();

        return back()->with('success', "{$policy->name} deleted");
    }

    private function validatedAnnouncement(Request $request): array
    {
        $data = $request->validate(
            [
                'title' => ['required', 'string', 'max:255'],
                'message' => ['required', 'string', 'max:2000'],
                'audience' => ['required', Rule::in(self::AUDIENCES)],
                'status' => ['required', Rule::in(self::STATUSES)],
                'scheduled_at' => ['nullable', 'required_if:status,scheduled', 'date', 'after:now'],
            ],
            [
                'title.required' => 'Add a title.',
                'title.max' => 'Shorten the title to 255 characters or fewer.',
                'message.required' => 'Write the message people will read.',
                'message.max' => 'Shorten the message to 2,000 characters or fewer.',
                'audience.*' => 'Pick who should see this announcement.',
                'status.*' => 'Pick Draft, Scheduled or Published.',
                'scheduled_at.required_if' => 'Pick the date and time to publish.',
                'scheduled_at.date' => 'Pick the date and time to publish.',
                'scheduled_at.after' => 'Pick a date and time in the future, or choose Published to go live now.',
            ],
        );

        return [
            'title' => $data['title'],
            'message' => $data['message'],
            'audience' => $data['audience'],
            'status' => $data['status'],
            // A date only means something while the announcement is scheduled.
            'scheduled_at' => $data['status'] === 'scheduled' ? $data['scheduled_at'] : null,
            'is_active' => $data['status'] === 'published',
        ];
    }

    private function savedMessage(Announcement $announcement): string
    {
        return match ($announcement->status) {
            'published' => 'Announcement published',
            'scheduled' => 'Announcement scheduled for '.$announcement->scheduled_at->format('M j, g:i A'),
            default => 'Draft saved',
        };
    }

    private function filteredAnnouncements(Request $request)
    {
        $search = $request->string('search')->trim()->toString();
        $status = $this->validStatus($request->string('status')->toString());
        $date = $this->validDate($request->string('date')->toString());

        $query = Announcement::query();

        if ($search !== '') {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $query->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('message', 'like', $like));
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // The table shows the go-live date for scheduled items and the created date for the rest.
        if ($date !== '') {
            $query->where(fn ($q) => $q
                ->where(fn ($scheduled) => $scheduled->where('status', 'scheduled')->whereDate('scheduled_at', $date))
                ->orWhere(fn ($other) => $other->where(fn ($not) => $not->where('status', '!=', 'scheduled')->orWhereNull('scheduled_at'))->whereDate('created_at', $date)));
        }

        $query->latest()->orderByDesc('id');

        $page = $query->paginate(8)->withQueryString();

        // A delete can leave the current page empty: step back to the last page that has rows.
        if ($page->isEmpty() && $page->currentPage() > 1) {
            $page = $query->paginate(8, ['*'], 'page', $page->lastPage())->withQueryString();
        }

        return $page;
    }

    private function validStatus(string $status): string
    {
        return in_array($status, self::STATUSES, true) ? $status : 'all';
    }

    private function validDate(string $date): string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1 && strtotime($date) !== false ? $date : '';
    }

    private function notifyPolicyUpdate(PlatformPolicy $policy): void
    {
        $roleMap = [
            'Seller Policy' => 'seller',
            'Buyer Policy' => 'buyer',
            'Logistics Policy' => 'courier',
        ];

        $role = $roleMap[$policy->name] ?? null;

        $userIds = $role
            ? User::where('role', $role)->pluck('id')
            : User::whereIn('role', ['seller', 'buyer', 'courier'])->pluck('id');

        foreach ($userIds as $userId) {
            Notification::create([
                'user_id' => $userId,
                'type' => 'platform_announcement',
                'title' => 'Policy updated',
                'message' => "{$policy->name} is now version {$policy->version}. Review what changed.",
            ]);
        }
    }
}
