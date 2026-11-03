<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActionLog;
use App\Models\Communication\Notification;
use App\Models\Profiles\SellerCategoryRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SellerCategoryRequestController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->validate(['status' => ['nullable', 'in:pending,approved,rejected']])['status'] ?? 'pending';
        // Pending is a queue, so the longest wait comes first. Reviewed tabs show the latest first.
        $categoryRequests = SellerCategoryRequest::query()->with(['seller.categories', 'categories', 'reviewer'])
            ->where('status', $status)
            ->orderBy('created_at', $status === 'pending' ? 'asc' : 'desc')
            ->orderBy('id', $status === 'pending' ? 'asc' : 'desc')
            ->paginate(12)->withQueryString();
        $counts = SellerCategoryRequest::query()->selectRaw('status, count(*) as total')->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.category-requests.index', compact('categoryRequests', 'status', 'counts'));
    }

    public function permit(SellerCategoryRequest $categoryRequest)
    {
        $path = $categoryRequest->permit_path;
        abort_unless(str_starts_with($path, 'private/seller-category-permits/'.$categoryRequest->seller_id.'/')
            && ! str_contains($path, '..') && ! str_contains($path, '\\')
            && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, 'category-permit.'.pathinfo($path, PATHINFO_EXTENSION), [
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function decide(Request $request, SellerCategoryRequest $categoryRequest): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'admin_note' => ['required_if:decision,rejected', 'nullable', 'string', 'max:1000'],
        ], [
            'decision.required' => 'Choose Approve or Reject.',
            'decision.in' => 'Choose Approve or Reject.',
            'admin_note.required_if' => 'Add a note so the seller knows what to fix.',
            'admin_note.max' => 'Shorten the note to 1,000 characters or fewer.',
        ]);

        $message = '';

        DB::transaction(function () use ($request, $categoryRequest, $data, &$message) {
            $record = SellerCategoryRequest::query()->whereKey($categoryRequest->id)->lockForUpdate()->firstOrFail();
            if ($record->status !== 'pending') {
                throw ValidationException::withMessages(['decision' => 'Another admin already reviewed this request. Open the Approved or Rejected tab to see the decision.']);
            }
            $seller = User::query()->whereKey($record->seller_id)->lockForUpdate()->firstOrFail();
            $categoryNames = $record->categories()->pluck('categories.name')->implode(', ');
            if ($data['decision'] === 'approved') {
                $seller->categories()->syncWithoutDetaching($record->categories()->pluck('categories.id')->all());
            }
            $message = $data['decision'] === 'approved'
                ? "Approved. {$seller->name} can now sell in {$categoryNames}."
                : "Rejected. {$seller->name} sees your note in Account Management.";
            $record->update([
                'status' => $data['decision'],
                'admin_note' => $data['admin_note'] ?? null,
                'reviewed_by' => $request->user('admin')->id,
                'reviewed_at' => now(),
            ]);
            AdminActionLog::record($seller, 'seller_category_request_'.$data['decision'], 'Request #'.$record->id);
            Notification::create([
                'user_id' => $seller->id,
                'type' => 'seller_category_request_reviewed',
                'title' => 'Category request '.($data['decision'] === 'approved' ? 'approved' : 'not approved'),
                'message' => $data['decision'] === 'approved'
                    ? 'Your new selling categories are available.' : 'Review the Admin note in Account Management.',
                'link' => route('seller.account.index').'#sellingCategories',
            ]);
        }, 3);

        return back()->with('success', $message);
    }
}
