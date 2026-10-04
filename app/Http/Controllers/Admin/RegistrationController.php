<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AdminActionLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Mail\AccountApprovedMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Services\OrderRoutingService;

class RegistrationController extends Controller
{
    public function index(Request $request): View
    {
        $registrations = $this->filteredRegistrations($request);

         $stats = [
            'pending_request' => User::whereIn('role', ['seller', 'buyer', 'logistics_center'])->where('status', 'pending')->count(),
            'pending_sellers' => User::where('role', 'seller')->where('status', 'pending')->count(),
            'pending_buyers' => User::where('role', 'buyer')->where('status', 'pending')->count(),
            'pending_logistics_centers' => User::where('role', 'logistics_center')->where('status', 'pending')->count(),
        ];

        return view('admin.registrations.index', compact('registrations', 'stats'));
    }

    public function table(Request $request): View
    {
        $registrations = $this->filteredRegistrations($request);

        return view('admin.registrations.partials.registrations-table', compact('registrations'));
    }

    private function filteredRegistrations(Request $request)
    {
        $query = User::whereIn('role', ['seller', 'buyer', 'logistics_center'])->where('status', 'pending');

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

        return $query->latest()->paginate(8)->withQueryString();
    }

    public function show(User $user): View
    {
        abort_unless(in_array($user->role, ['seller', 'buyer', 'logistics_center'], true), 403);
        $user->load(['sellerDetail', 'courierDetail', 'categories', 'buyerDetail']);

        return view('admin.registrations.show', compact('user'));
    }

    public function approve(User $user, OrderRoutingService $routing): RedirectResponse
{
    abort_unless(in_array($user->role, ['seller', 'buyer', 'logistics_center'], true) && $user->status === 'pending', 403);
    DB::transaction(function () use ($user) {
        $user->update(['status' => 'approved']);
        AdminActionLog::record($user, 'registration_approved');
    }, 3);

    if ($user->role === 'logistics_center') {
        $routing->routeUnresolvedReady();
    }

    try {
        Mail::to($user->email)->send(new AccountApprovedMail($user));
    } catch (\Throwable $e) {
        Log::error('Account approval email failed', ['user_id' => $user->id, 'exception' => $e]);

        return back()->with('confirmation', 'approved')->with('warning', 'Account approved, but the approval email could not be sent.');
    }

    return back()->with('confirmation', 'approved');
}

    public function disapprove(Request $request, User $user): RedirectResponse
    {
        abort_unless(in_array($user->role, ['seller', 'buyer', 'logistics_center'], true) && $user->status === 'pending', 403);
        $request->validate([
            'reason' => 'required|string',
            'additional_details' => ['nullable', 'string', 'max:500', Rule::requiredIf(fn () => $request->input('reason') === 'Other (please specify)')],
        ]);

        DB::transaction(function () use ($request, $user) {
            $user->update([
                'status' => 'disapproved',
                'rejection_reason' => $request->reason,
                'rejection_notes' => $request->additional_details,
            ]);
            AdminActionLog::record($user, 'registration_rejected', $request->reason);
        }, 3);

        return back()->with('confirmation', 'rejected');
    }
}
