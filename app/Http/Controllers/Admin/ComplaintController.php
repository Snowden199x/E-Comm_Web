<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaints\Complaint;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Complaints\ComplaintActivity;
use Illuminate\Http\RedirectResponse;

class ComplaintController extends Controller
{
    public function index(Request $request): View
    {
        $complaints = $this->filteredComplaints($request);
        $stats = $this->complaintStats();

        return view('admin.complaints.index', compact('complaints', 'stats'));
    }

    public function table(Request $request): View
    {
        $complaints = $this->filteredComplaints($request);

        return view('admin.complaints.partials.complaints-table', compact('complaints'));
    }

    public function show(Complaint $complaint): View
    {
        $complaint->load(['order.items.product', 'complainant', 'respondent', 'evidences', 'activities']);

        return view('admin.complaints.show', compact('complaint'));
    }

    private function filteredComplaints(Request $request)
    {
        $query = Complaint::with(['order', 'complainant', 'respondent']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn ($q) => $q->whereHas('complainant', fn ($sq) => $sq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                ->orWhereHas('respondent', fn ($sq) => $sq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
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

    public function updateStatus(Request $request, Complaint $complaint): RedirectResponse
    {
        $request->validate(['status' => 'required|in:in_review,resolved']);

        $complaint->update(['status' => $request->status]);

        ComplaintActivity::create([
            'complaint_id' => $complaint->id,
            'actor' => 'admin',
            'action' => $request->status === 'resolved'
                ? 'Status changed to Resolved'
                : 'Status changed to In Progress',
        ]);

        return back()->with('confirmation', 'status_updated');
    }

    private function complaintStats(): array
    {
        return [
            'total' => Complaint::count(),
            'open' => Complaint::where('status', 'open')->count(),
            'in_progress' => Complaint::where('status', 'in_review')->count(),
            'resolved' => Complaint::where('status', 'resolved')->count(),
        ];
    }
}