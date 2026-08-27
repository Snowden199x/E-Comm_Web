<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::whereIn('role', ['seller', 'buyer'])
            ->where('status', 'pending');

        // Search by name or email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by user type (role)
        if ($request->filled('user_type') && $request->user_type !== 'all') {
            $query->where('role', $request->user_type);
        }

        // Filter by date applied
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $registrations = $query->latest()->paginate(8)->withQueryString();

        $stats = [
            'pending_request' => User::whereIn('role', ['seller', 'buyer'])->where('status', 'pending')->count(),
            'pending_sellers' => User::where('role', 'seller')->where('status', 'pending')->count(),
            'pending_buyers' => User::where('role', 'buyer')->where('status', 'pending')->count(),
        ];

        return view('admin.registrations.index', compact('registrations', 'stats'));
    }

    public function show(User $user): View
    {
        $user->load(['sellerDetail', 'courierDetail', 'categories', 'buyerDetail']);

        return view('admin.registrations.show', compact('user'));
    }

    public function approve(User $user): RedirectResponse
    {
        $user->update(['status' => 'approved']);

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