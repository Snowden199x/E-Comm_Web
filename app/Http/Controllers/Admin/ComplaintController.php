<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaints\Complaint;
use App\Models\Complaints\ComplaintEvidence;
use App\Models\Communication\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
        $types = Complaint::query()->whereNotNull('type')->distinct()->orderBy('type')->pluck('type');

        return view('admin.complaints.index', compact('complaints', 'stats', 'types'));
    }

    public function table(Request $request): View
    {
        $complaints = $this->filteredComplaints($request);

        return view('admin.complaints.partials.complaints-table', compact('complaints'));
    }

    public function show(Complaint $complaint): View
    {
        $complaint->load(['order.items.product.images', 'complainant', 'respondent', 'evidences', 'activities']);

        return view('admin.complaints.show', compact('complaint'));
    }

    public function evidence(Complaint $complaint, ComplaintEvidence $evidence)
    {
        abort_unless($evidence->complaint_id === $complaint->id, 404);
        $disk = Storage::disk('local')->exists($evidence->path) ? 'local' : 'public';
        abort_unless(Storage::disk($disk)->exists($evidence->path), 404);

        return Storage::disk($disk)->response($evidence->path, $evidence->original_filename);
    }

    private function filteredComplaints(Request $request)
    {
        $query = Complaint::with(['order', 'complainant', 'respondent']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn($q) => $q->whereHas('complainant', fn($sq) => $sq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                ->orWhereHas('respondent', fn($sq) => $sq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")));
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

        if ($complaint->kind === 'user_report') {
            abort_if($request->status === 'resolved', 422, 'Approve or reject account reports through the review action.');
            $changed = Complaint::query()->whereKey($complaint->id)->whereNull('decision')
                ->where('status', 'open')->update(['status' => 'in_review']);
            abort_unless($changed, 409, 'This report has already moved forward.');
        } else {
            $complaint->update(['status' => $request->status]);
        }

        ComplaintActivity::create([
            'complaint_id' => $complaint->id,
            'actor' => 'admin',
            'action' => $request->status === 'resolved'
                ? 'Status changed to Resolved'
                : 'Status changed to In Progress',
        ]);

        return back()->with('confirmation', 'status_updated');
    }

    public function decide(Request $request, Complaint $complaint): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'note' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        DB::transaction(function () use ($complaint, $validated) {
            $report = Complaint::query()->whereKey($complaint->id)->lockForUpdate()->firstOrFail();
            abort_unless($report->kind === 'user_report', 404);
            abort_if($report->decision !== null || $report->status === 'resolved', 409, 'This report has already been reviewed.');

            $report->update([
                'status' => 'resolved',
                'decision' => $validated['decision'],
                'reviewed_by' => Auth::guard('admin')->id(),
                'reviewed_at' => now(),
            ]);
            $report->activities()->create([
                'actor' => 'admin #'.Auth::guard('admin')->id(),
                'action' => 'Report '.($validated['decision'] === 'approved' ? 'approved' : 'rejected').': '.trim($validated['note']),
            ]);

            if ($validated['decision'] === 'approved') {
                $respondent = $report->respondent;
                Notification::create([
                    'user_id' => $respondent->id,
                    'type' => 'account_warning',
                    'title' => 'Account warning',
                    'message' => 'Admin reviewed an account report and issued a warning for: '.$report->type.'. Please follow Vendo policies.',
                    'link' => $respondent->role === 'buyer'
                        ? route('buyer.notifications.index')
                        : route('seller.notifications.index'),
                ]);
            }
        });

        return back()->with('confirmation', 'report_reviewed');
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