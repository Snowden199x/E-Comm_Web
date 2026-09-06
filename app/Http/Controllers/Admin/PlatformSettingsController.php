<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Communication\Announcement;
use App\Models\Communication\PlatformPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformSettingsController extends Controller
{
    public function index(Request $request): View
    {
        $announcements = $this->filteredAnnouncements($request);
        $policies = PlatformPolicy::latest()->paginate(8, ['*'], 'policies_page');

        $stats = [
            'total' => Announcement::count(),
            'published' => Announcement::where('status', 'published')->count(),
            'scheduled' => Announcement::where('status', 'scheduled')->count(),
            'drafts' => Announcement::where('status', 'draft')->count(),
        ];

        return view('admin.platform-settings.index', compact('announcements', 'policies', 'stats'));
    }

    public function announcementsTable(Request $request): View
    {
        $announcements = $this->filteredAnnouncements($request);

        return view('admin.platform-settings.partials.announcements-table', compact('announcements'));
    }

    private function filteredAnnouncements(Request $request)
    {
        $query = Announcement::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('message', 'like', "%{$search}%"));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $filter = $request->get('date_filter', 'all');
        match ($filter) {
            'today' => $query->whereDate('created_at', now()->toDateString()),
            'week' => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
            'month' => $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]),
            'custom' => $request->filled('custom_date') ? $query->whereDate('created_at', $request->custom_date) : null,
            default => null,
        };

        return $query->latest()->paginate(8)->withQueryString();
    }

    public function storeAnnouncement(Request $request): RedirectResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'audience' => 'required|string',
            'status' => 'required|in:draft,scheduled,published',
            'scheduled_at' => 'nullable|date',
        ]);

        Announcement::create([
            'title' => $request->title,
            'message' => $request->message,
            'audience' => $request->audience,
            'status' => $request->status,
            'scheduled_at' => $request->scheduled_at,
            'is_active' => $request->status === 'published',
        ]);

        return back()->with('confirmation', 'announcement_created');
    }

    public function storePolicy(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|in:Seller Policy,Buyer Policy,Logistics Policy,Prohibited Item Policy',
        ]);

        PlatformPolicy::firstOrCreate(
            ['name' => $request->name],
            ['version' => '1.0', 'content' => '']
        );

        return back()->with('confirmation', 'policy_created');
    }

    public function updatePolicy(Request $request, PlatformPolicy $policy): RedirectResponse
    {
        $request->validate([
            'content' => 'nullable|string',
            'version' => 'required|string|max:20',
        ]);

        $policy->update([
            'content' => $request->content,
            'version' => $request->version,
        ]);

        $this->notifyPolicyUpdate($policy);

        return back()->with('confirmation', 'policy_updated');
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
            ? \App\Models\User::where('role', $role)->pluck('id')
            : \App\Models\User::whereIn('role', ['seller', 'buyer', 'courier'])->pluck('id');

        foreach ($userIds as $userId) {
            \App\Models\Communication\Notification::create([
                'user_id' => $userId,
                'type' => 'platform_announcement',
                'title' => 'Policy Updated',
                'message' => "{$policy->name} has been updated to version {$policy->version}. Please review the changes.",
            ]);
        }
    }

    public function updateAnnouncement(Request $request, Announcement $announcement): RedirectResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'audience' => 'required|string',
            'status' => 'required|in:draft,scheduled,published',
            'scheduled_at' => 'nullable|date',
        ]);

        $announcement->update([
            'title' => $request->title,
            'message' => $request->message,
            'audience' => $request->audience,
            'status' => $request->status,
            'scheduled_at' => $request->scheduled_at,
            'is_active' => $request->status === 'published',
        ]);

        return back()->with('confirmation', 'announcement_updated');
    }

    public function destroyAnnouncement(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return back()->with('confirmation', 'announcement_deleted');
    }

    public function destroyPolicy(PlatformPolicy $policy): RedirectResponse
    {
        $policy->delete();

        return back()->with('confirmation', 'policy_deleted');
    }
}