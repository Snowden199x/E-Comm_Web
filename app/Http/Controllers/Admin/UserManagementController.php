<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AdminActionLog;
use App\Services\OrderRoutingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $showRejected = $request->boolean('rejected');
        $users = $this->filteredUsers($request, $showRejected);

        $managed = User::query()->where('status', 'approved');
        $stats = [
            'total_users' => (clone $managed)->whereIn('role', ['seller', 'buyer', 'logistics_center'])->count(),
            'sellers' => (clone $managed)->where('role', 'seller')->count(),
            'buyers' => (clone $managed)->where('role', 'buyer')->count(),
            'logistics_centers' => (clone $managed)->where('role', 'logistics_center')->count(),
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
            $query->where('status', 'approved');
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
        $this->authorizeManagedAccount($user, ['active']);
        $data = $request->validate([
            'reasons' => 'required|array|min:1',
            'reasons.*' => ['string', Rule::in(['Policy Violation', 'Suspicious Activity', 'Repeated Violations', 'Security Concern', 'Other'])],
            'additional_details' => ['nullable', 'string', 'max:500', Rule::requiredIf(fn () => in_array('Other', $request->input('reasons', []), true))],
            'duration_days' => ['nullable', 'required_unless:permanent,1', 'integer', 'min:1', 'max:365'],
            'permanent' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($user, $data) {
            $user->update([
                'account_status' => 'suspended',
                'suspension_reason' => implode(', ', $data['reasons']),
                'suspension_notes' => $data['additional_details'] ?? null,
                'suspended_at' => now(),
                'suspended_until' => ($data['permanent'] ?? false) ? null : now()->addDays((int) $data['duration_days']),
            ]);
            AdminActionLog::record($user, 'user_suspended', implode(', ', $data['reasons']));
        }, 3);

        return back()->with('confirmation', 'suspended');
    }

    public function deactivate(User $user): RedirectResponse
    {
        $this->authorizeManagedAccount($user, ['active']);
        DB::transaction(function () use ($user) {
            $user->update(['account_status' => 'deactivated']);
            AdminActionLog::record($user, 'user_deactivated');
        }, 3);

        return back()->with('confirmation', 'deactivated');
    }

    public function activate(User $user, OrderRoutingService $routing): RedirectResponse
    {
        $this->authorizeManagedAccount($user, ['suspended', 'deactivated']);
        $wasSuspended = $user->account_status === 'suspended';

        DB::transaction(function () use ($user, $wasSuspended) {
            $user->update(['account_status' => 'active', 'suspended_until' => null]);
            AdminActionLog::record($user, $wasSuspended ? 'suspension_lifted' : 'user_activated');
        }, 3);
        if ($user->role === 'logistics_center') {
            $routing->routeUnresolvedReady();
        }

        return back()->with('confirmation', $wasSuspended ? 'suspension_lifted' : 'activated');
    }

    private function authorizeManagedAccount(User $user, array $accountStatuses): void
    {
        abort_unless(in_array($user->role, ['seller', 'buyer', 'logistics_center'], true)
            && $user->status === 'approved'
            && in_array($user->account_status ?? 'active', $accountStatuses, true), 403);
    }
}
