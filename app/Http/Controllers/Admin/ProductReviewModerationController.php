<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Communication\Notification;
use App\Models\Ecommerce\ProductReview;
use App\Models\Ecommerce\ProductReviewModerationEvent;
use App\Models\Ecommerce\ProductReviewReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductReviewModerationController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['status' => ['nullable', 'in:open,resolved,dismissed']]);
        $reports = ProductReviewReport::query()->with([
            'seller:id,name', 'review.buyer:id,name', 'review.product:id,name', 'review.order:id',
        ])->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest('id')->paginate(15)->withQueryString();
        $counts = ProductReviewReport::query()->select('status')->selectRaw('COUNT(*) AS total')->groupBy('status')
            ->pluck('total', 'status')->all();

        return view('admin.review-reports.index', compact('reports', 'counts', 'filters'));
    }

    public function show(int $report)
    {
        $record = ProductReviewReport::query()->with([
            'seller:id,name,email', 'review.buyer:id,name,email', 'review.product.images',
            'review.order', 'review.orderItem', 'review.reply.seller:id,name', 'review.moderationEvents',
        ])->findOrFail($report);

        return view('admin.review-reports.show', ['report' => $record]);
    }

    public function visibility(Request $request, int $review)
    {
        $validated = $request->validate([
            'visibility' => ['required', 'in:published,hidden'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        DB::transaction(function () use ($request, $review, $validated) {
            $record = ProductReview::query()->lockForUpdate()->findOrFail($review);
            $from = $record->visibility;
            $record->update([
                'visibility' => $validated['visibility'], 'moderated_by' => Auth::guard('admin')->id(),
                'moderated_at' => now(), 'moderation_reason' => trim($validated['reason']),
            ]);
            ProductReviewModerationEvent::create([
                'product_review_id' => $record->id, 'admin_id' => Auth::guard('admin')->id(),
                'from_visibility' => $from, 'to_visibility' => $validated['visibility'],
                'reason' => trim($validated['reason']),
            ]);
            Notification::create([
                'user_id' => $record->buyer_id, 'type' => 'product_review_moderation',
                'title' => 'Review moderation update',
                'message' => $validated['visibility'] === 'hidden'
                    ? 'Your product review was hidden after moderation.'
                    : 'Your product review is visible again.',
                'link' => route('buyer.orders.show', $record->order_id),
            ]);
        });

        return back()->with('success', 'Review visibility updated.');
    }

    public function resolve(Request $request, int $report)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:resolved,dismissed'],
            'resolution_note' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        DB::transaction(function () use ($report, $validated) {
            $record = ProductReviewReport::query()->lockForUpdate()->findOrFail($report);
            abort_unless($record->status === 'open', 409, 'This report has already been resolved.');
            $record->update([
                'status' => $validated['status'], 'resolution_note' => trim($validated['resolution_note']),
                'resolved_by' => Auth::guard('admin')->id(), 'resolved_at' => now(),
            ]);
        });

        return back()->with('success', 'Report decision saved.');
    }
}
