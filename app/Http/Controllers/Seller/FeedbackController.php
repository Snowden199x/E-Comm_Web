<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Communication\Notification;
use App\Models\Ecommerce\ProductReview;
use App\Models\Ecommerce\ProductReviewReply;
use App\Models\Ecommerce\ProductReviewReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeedbackController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'reply' => ['nullable', 'in:replied,pending'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'order' => ['nullable', 'integer'],
        ]);

        $scope = $this->scopedReviews($request->user()->id, $filters, false);
        $total = (clone $scope)->count();
        $answered = (clone $scope)->whereHas('reply')->count();
        $stats = [
            'average' => $total ? round((float) (clone $scope)->avg('rating'), 1) : null,
            'reviews' => $total,
            'customers' => (clone $scope)->distinct('buyer_id')->count('buyer_id'),
            'response_rate' => $total ? round($answered / $total * 100) : 0,
        ];
        $ratings = [];
        for ($rating = 5; $rating >= 1; $rating--) {
            $count = (clone $scope)->where('rating', $rating)->count();
            $ratings[$rating] = ['count' => $count, 'percent' => $total ? round($count / $total * 100) : 0];
        }

        $rows = $this->scopedReviews($request->user()->id, $filters, true)
            ->with(['buyer:id,name', 'product:id,name', 'order:id', 'orderItem:id,product_id'])
            ->latest('id')->paginate(8)->withQueryString();
        $recent = $this->scopedReviews($request->user()->id, $filters, true)
            ->with(['buyer:id,name', 'product:id,name'])->latest('id')->limit(4)->get();

        return view('seller.feedback.index', compact('stats', 'ratings', 'rows', 'recent', 'filters'));
    }

    public function show(Request $request, int $review)
    {
        $record = ProductReview::query()->where('seller_id', $request->user()->id)
            ->with(['buyer:id,name', 'product.images', 'order', 'orderItem', 'reply.seller:id,name', 'report', 'moderationEvents'])
            ->findOrFail($review);

        return view('seller.feedback.detail', ['review' => $record]);
    }

    public function reply(Request $request, int $review)
    {
        $validated = $request->validate(['body' => ['required', 'string', 'min:2', 'max:2000']]);
        DB::transaction(function () use ($request, $review, $validated) {
            $record = ProductReview::query()->where('seller_id', $request->user()->id)->lockForUpdate()->findOrFail($review);
            abort_unless($record->visibility === 'published', 409, 'This review is unavailable for replies.');
            abort_if($record->reply()->exists(), 409, 'A reply has already been added.');
            ProductReviewReply::create(['product_review_id' => $record->id, 'seller_id' => $request->user()->id, 'body' => trim($validated['body'])]);
            Notification::create([
                'user_id' => $record->buyer_id, 'type' => 'product_review_reply', 'title' => 'Seller replied to your review',
                'message' => 'The seller replied to your review of '.$record->product->name.'.',
                'link' => route('buyer.products.show', $record->product_id),
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Reply saved.']);
    }

    public function report(Request $request, int $review)
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:500']]);
        DB::transaction(function () use ($request, $review, $validated) {
            $record = ProductReview::query()->where('seller_id', $request->user()->id)->lockForUpdate()->findOrFail($review);
            abort_if($record->report()->exists(), 409, 'This review has already been reported.');
            ProductReviewReport::create([
                'product_review_id' => $record->id, 'seller_id' => $request->user()->id,
                'reason' => trim($validated['reason']), 'status' => 'open',
            ]);
            Notification::create([
                'user_id' => null, 'type' => 'product_review_report', 'title' => 'Product review reported',
                'message' => 'A seller reported a product review for moderation.',
                'link' => route('admin.review-reports.index'),
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Report sent to the review team.']);
    }

    private function scopedReviews(int $sellerId, array $filters, bool $tableFilters)
    {
        return ProductReview::query()->where('seller_id', $sellerId)->where('visibility', 'published')
            ->when($filters['order'] ?? null, fn ($query, $id) => $query->where('order_id', $id))
            ->when($filters['search'] ?? null, function ($query, string $term) {
                $term = trim($term);
                $id = preg_replace('/^VN-0*/i', '', $term);
                $query->where(function ($query) use ($term, $id) {
                    if ($id !== '' && ctype_digit($id)) $query->orWhere('order_id', (int) $id);
                    $query->orWhere('comment', 'like', "%{$term}%")
                        ->orWhereHas('buyer', fn ($buyer) => $buyer->where('name', 'like', "%{$term}%"))
                        ->orWhereHas('product', fn ($product) => $product->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('product_code', 'like', "%{$term}%")));
                });
            })
            ->when($filters['from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->when($tableFilters && ($filters['rating'] ?? null), fn ($query, $rating) => $query->where('rating', $rating))
            ->when($tableFilters && ($filters['reply'] ?? null) === 'replied', fn ($query) => $query->whereHas('reply'))
            ->when($tableFilters && ($filters['reply'] ?? null) === 'pending', fn ($query) => $query->whereDoesntHave('reply'));
    }
}
