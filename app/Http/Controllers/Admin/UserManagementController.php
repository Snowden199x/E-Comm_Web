<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
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
            'total_users' => User::whereIn('role', ['seller', 'buyer'])->count(),
            'sellers' => User::where('role', 'seller')->count(),
            'buyers' => User::where('role', 'buyer')->count(),
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
        $query = User::whereIn('role', ['seller', 'buyer']);

        if ($showRejected) {
            $query->where('status', 'disapproved');
        } else {
            $query->whereIn('status', ['approved', 'suspended', 'deactivated']);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        }

        if ($request->filled('user_type') && $request->user_type !== 'all') {
            $query->where('role', $request->user_type);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        return $query->with(['sellerDetail', 'buyerDetail', 'categories'])
            ->latest()
            ->paginate(8)
            ->withQueryString();
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
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
        $user->update(['status' => 'deactivated']);

        return back()->with('confirmation', 'deactivated');
    }

    public function activate(User $user): RedirectResponse
    {
        $wasSuspended = $user->status === 'suspended';

        $user->update(['status' => 'approved']);

        return back()->with('confirmation', $wasSuspended ? 'suspension_lifted' : 'activated');
    }
}