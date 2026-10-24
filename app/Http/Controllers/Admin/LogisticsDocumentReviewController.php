<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Communication\Notification;
use App\Models\Profiles\LogisticsDocumentRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LogisticsDocumentReviewController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->validate(['status' => ['nullable', 'in:pending,approved,rejected']])['status'] ?? 'pending';
        $requests = LogisticsDocumentRequest::query()->where('status', $status)
            ->with('center.user')->latest()->paginate(30)->withQueryString();

        return view('admin.logistics-documents.index', compact('requests', 'status'));
    }

    public function file(LogisticsDocumentRequest $documentRequest): StreamedResponse
    {
        $path = $documentRequest->path;
        abort_unless(str_starts_with($path, 'private/logistics-document-updates/'.$documentRequest->logistics_center_id.'/')
            && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, 'logistics-document.'.pathinfo($path, PATHINFO_EXTENSION), [
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function decide(Request $request, LogisticsDocumentRequest $documentRequest): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'review_note' => ['required_if:decision,rejected', 'nullable', 'string', 'max:1000'],
        ]);
        DB::transaction(function () use ($request, $documentRequest, $data) {
            $item = LogisticsDocumentRequest::query()->whereKey($documentRequest->id)
                ->lockForUpdate()->firstOrFail();
            abort_unless($item->status === 'pending', 409, 'This request was already reviewed.');
            $center = $item->center()->lockForUpdate()->firstOrFail();
            abort_unless(Storage::disk('local')->exists($item->path), 409,
                'The submitted document is missing from private storage.');
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
        }, 3);

        return back()->with('success', 'Document request '.$data['decision'].'.');
    }
}
