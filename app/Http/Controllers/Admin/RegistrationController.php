<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Mail\AccountApprovedMail;
use Illuminate\Support\Facades\Mail;
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
        $user->load(['sellerDetail', 'courierDetail', 'categories', 'buyerDetail']);

        return view('admin.registrations.show', compact('user'));
    }

    public function approve(User $user, OrderRoutingService $routing): RedirectResponse
{
    abort_unless(in_array($user->role, ['seller', 'buyer', 'logistics_center'], true) && $user->status === 'pending', 403);
    $user->update(['status' => 'approved']);

    if ($user->role === 'logistics_center') {
        $routing->routeUnresolvedReady();
    }

    Mail::to($user->email)->send(new AccountApprovedMail($user));

    return back()->with('confirmation', 'approved');
}

    public function disapprove(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'reason' => 'required|string',
            'additional_details' => 'nullable|string|max:500',
        ]);

        $user->update([
            'status' => 'disapproved',
            'rejection_reason' => $request->reason,
            'rejection_notes' => $request->additional_details,
        ]);

        return back()->with('confirmation', 'rejected');
    }
}
