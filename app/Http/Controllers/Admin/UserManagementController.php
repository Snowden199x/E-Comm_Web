<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OrderRoutingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $showRejected = $request->boolean('rejected');
        $users = $this->filteredUsers($request, $showRejected);

        $stats = [
            'total_users' => User::whereIn('role', ['seller', 'buyer', 'logistics_center'])->count(),
            'sellers' => User::where('role', 'seller')->count(),
            'buyers' => User::where('role', 'buyer')->count(),
            'logistics_centers' => User::where('role', 'logistics_center')->count(),
        ];

        return view('admin.user-management.index', compact('users', 'stats', 'showRejected'));
    }

    public function table(Request $request): View
    {
        $showRejected = $request->boolean('rejected');
        $users = $this->filteredUsers($request, $showRejected);

        return view('admin.user-management.partials.users-table', compact('users'));
    }

    private function filteredUsers(Request $request, bool $showRejected)
    {
        $query = User::whereIn('role', ['seller', 'buyer', 'logistics_center']);

        if ($showRejected) {
            $query->where('status', 'disapproved');
        } else {
            $query->whereIn('status', ['approved', 'suspended', 'deactivated']);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        }

        if (in_array($request->input('user_type'), ['seller', 'buyer', 'logistics_center'], true)) {
            $query->where('role', $request->user_type);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        return $query->with(['sellerDetail', 'buyerDetail', 'logisticsCenterDetail', 'categories'])
            ->latest()
            ->paginate(8)
            ->withQueryString();
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        $this->authorizeManagedAccount($user, ['approved']);
        $request->validate([
            'reasons' => 'required|array|min:1',
            'reasons.*' => 'string',
            'additional_details' => 'nullable|string|max:500',
        ]);

        $user->update([
            'status' => 'suspended',
            'suspension_reason' => implode(', ', $request->reasons),
            'suspension_notes' => $request->additional_details,
            'suspended_at' => now(),
            'suspended_until' => now()->addDays(7),
        ]);

        return back()->with('confirmation', 'suspended');
    }

    public function deactivate(User $user): RedirectResponse
    {
        $this->authorizeManagedAccount($user, ['approved']);
        $user->update(['status' => 'deactivated']);

        return back()->with('confirmation', 'deactivated');
    }

    public function activate(User $user, OrderRoutingService $routing): RedirectResponse
    {
        $this->authorizeManagedAccount($user, ['suspended', 'deactivated']);
        $wasSuspended = $user->status === 'suspended';

        $user->update(['status' => 'approved']);
        if ($user->role === 'logistics_center') {
            $routing->routeUnresolvedReady();
        }

        return back()->with('confirmation', $wasSuspended ? 'suspension_lifted' : 'activated');
    }

    private function authorizeManagedAccount(User $user, array $statuses): void
    {
        abort_unless(in_array($user->role, ['seller', 'buyer', 'logistics_center'], true)
            && in_array($user->status, $statuses, true), 403);
    }
}
