<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Complaints\Complaint;
use App\Models\Complaints\ComplaintEvidence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ComplaintController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'scope' => ['nullable', 'in:against,filed'],
            'status' => ['nullable', 'in:open,in_review,resolved'],
            'type' => ['nullable', 'string', 'max:100'],
            'search' => ['nullable', 'string', 'max:100'],
        ], [
            'scope.in' => 'Choose "Against my shop" or "My reports".',
            'status.in' => 'Choose All cases, Open, In progress, or Resolved.',
            'type.max' => 'Type is too long. Pick one from the list.',
            'search.max' => 'Search is too long. Use 100 characters or fewer.',
        ]);
        $filters = [
            'scope' => $filters['scope'] ?? 'against',
            'status' => $filters['status'] ?? '',
            'type' => $filters['type'] ?? '',
            'search' => trim($filters['search'] ?? ''),
        ];

        $sellerId = $request->user()->id;
        $scopeColumn = $filters['scope'] === 'filed' ? 'complainant_id' : 'respondent_id';
        $base = Complaint::query()->where($scopeColumn, $sellerId);
        $scopeCounts = [
            'against' => Complaint::query()->where('respondent_id', $sellerId)->count(),
            'filed' => Complaint::query()->where('complainant_id', $sellerId)->count(),
        ];
        $counts = ['all' => (clone $base)->count()];
        foreach (['open', 'in_review', 'resolved'] as $status) {
            $counts[$status] = (clone $base)->where('status', $status)->count();
        }
        $types = (clone $base)->whereNotNull('type')->distinct()->orderBy('type')->pluck('type');

        $query = (clone $base)->with([
            'order.items.product.images', 'complainant', 'respondent', 'evidences', 'activities',
            // Only this seller's own thread with Vendo, plus how many of its messages are still unread.
            'conversations' => fn ($threads) => $threads->where('user_id', $sellerId)->withCount([
                'messages',
                'messages as unread_count' => fn ($messages) => $messages->whereNull('read_at')->where('sender_id', '!=', $sellerId),
            ]),
        ]);
        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }
        if ($filters['type'] !== '') {
            $query->where('type', $filters['type']);
        }
        if ($filters['search'] !== '') {
            $term = $filters['search'];
            $caseId = preg_match('/^CMP-0*(\d+)$/i', $term, $matches) ? (int) $matches[1] : null;
            $orderId = preg_match('/^VN-0*(\d+)$/i', $term, $orderMatches) ? (int) $orderMatches[1] : null;
            $query->where(function ($cases) use ($term, $caseId, $orderId) {
                $cases->where('type', 'like', '%'.$term.'%')
                    ->orWhereHas('order', function ($orders) use ($term, $orderId) {
                        $orders->where('tracking_number', 'like', '%'.$term.'%');
                        if ($orderId) $orders->orWhere('orders.id', $orderId);
                    });
                if ($caseId) {
                    $cases->orWhere('complaints.id', $caseId);
                }
            });
        }

        $complaints = $query->latest()->paginate(8)->withQueryString();

        return view('seller.complaints.index', compact('complaints', 'counts', 'scopeCounts', 'filters', 'types'));
    }

    /** Streams one evidence file. Only the two people in the case can open it. */
    public function evidence(Request $request, int $complaint, int $evidence)
    {
        $sellerId = (int) $request->user()->id;
        $case = Complaint::query()->whereKey($complaint)
            ->where(fn ($q) => $q->where('respondent_id', $sellerId)->orWhere('complainant_id', $sellerId))
            ->firstOrFail();
        $file = ComplaintEvidence::query()->where('complaint_id', $case->id)->findOrFail($evidence);

        $disk = Storage::disk('local')->exists($file->path) ? 'local' : 'public';
        abort_unless(Storage::disk($disk)->exists($file->path), 404);

        // A buyer who reported the shop stays anonymous, so the original file name is never sent to the seller.
        $anonymous = $case->kind === 'user_report' && (int) $case->respondent_id === $sellerId;
        $extension = pathinfo($file->path, PATHINFO_EXTENSION);
        $name = $anonymous
            ? 'evidence-'.$file->id.($extension ? '.'.$extension : '')
            : ($file->original_filename ?: basename($file->path));

        $response = Storage::disk($disk)->response($file->path, $name);
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
