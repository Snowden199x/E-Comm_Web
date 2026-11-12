<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Communication\Notification;
use App\Models\Profiles\LogisticsDocumentRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LogisticsDocumentReviewController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->validate(['status' => ['nullable', 'in:pending,approved,rejected']])['status'] ?? 'pending';
        // Pending is a queue, so the longest wait comes first. Reviewed tabs show the latest first.
        $requests = LogisticsDocumentRequest::query()->where('status', $status)
            ->with(['center.user', 'reviewer'])
            ->orderBy('created_at', $status === 'pending' ? 'asc' : 'desc')
            ->orderBy('id', $status === 'pending' ? 'asc' : 'desc')
            ->paginate(15)->withQueryString();
        $counts = LogisticsDocumentRequest::query()->selectRaw('status, count(*) as total')->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.logistics-documents.index', compact('requests', 'status', 'counts'));
    }

    public function file(LogisticsDocumentRequest $documentRequest): StreamedResponse
    {
        $path = $documentRequest->path;
        abort_unless(str_starts_with($path, 'private/logistics-document-updates/'.$documentRequest->logistics_center_id.'/')
            && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, 'logistics-document.'.pathinfo($path, PATHINFO_EXTENSION), [
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function decide(Request $request, LogisticsDocumentRequest $documentRequest): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'review_note' => ['required_if:decision,rejected', 'nullable', 'string', 'max:1000'],
        ], [
            'decision.required' => 'Choose Approve or Reject.',
            'decision.in' => 'Choose Approve or Reject.',
            'review_note.required_if' => 'Add a note so the company knows what to fix.',
            'review_note.max' => 'Shorten the note to 1,000 characters or fewer.',
        ]);
        $message = '';

        DB::transaction(function () use ($request, $documentRequest, $data, &$message) {
            $item = LogisticsDocumentRequest::query()->whereKey($documentRequest->id)
                ->lockForUpdate()->firstOrFail();
            if ($item->status !== 'pending') {
                throw ValidationException::withMessages(['decision' => 'Another admin already reviewed this request. Open the Approved or Rejected tab to see the decision.']);
            }
            $center = $item->center()->lockForUpdate()->firstOrFail();
            if (! Storage::disk('local')->exists($item->path)) {
                throw ValidationException::withMessages(['decision' => 'The submitted file is missing from storage. Ask the company to upload it again, then reject this request.']);
            }
            $documentLabel = $item->document_type === 'valid_id' ? 'valid ID' : 'business permit';
            $message = $data['decision'] === 'approved'
                ? "Approved. {$center->business_name} now uses the new {$documentLabel}."
                : "Rejected. {$center->business_name} sees your note in their account.";
            if ($data['decision'] === 'approved') {
                $field = $item->document_type === 'valid_id' ? 'valid_id_path' : 'business_permit_path';
                $center->update([$field => $item->path]);
            }
            $item->update(['status' => $data['decision'], 'reviewed_by' => auth('admin')->id(),
                'review_note' => $data['review_note'] ?? null, 'reviewed_at' => now()]);
            Notification::create(['user_id' => $center->user_id, 'type' => 'logistics_document_review',
                'title' => 'Document replacement '.($data['decision'] === 'approved' ? 'approved' : 'rejected'),
                'message' => $data['decision'] === 'rejected'
                    ? ($data['review_note'] ?? 'Please submit a new document.')
                    : 'Your updated '.str_replace('_', ' ', $item->document_type).' is now on file.',
                'link' => route('lgp.company.account')]);

            \App\Models\AdminActionLog::record(
                $center->user,
                'logistics_document_'.$data['decision'],
                $center->business_name.' '.$documentLabel.($data['review_note'] ? ': '.$data['review_note'] : ''),
            );
        }, 3);

        return back()->with('success', $message);
    }
}
