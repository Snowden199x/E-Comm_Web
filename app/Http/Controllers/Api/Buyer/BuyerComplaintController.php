<?php

namespace App\Http\Controllers\Api\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Complaints\Complaint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Complaints the signed-in Buyer filed, with the Open / In review / Resolved
 * counts the web Complaints page shows. Scoped to complainant_id.
 */
class BuyerComplaintController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $owned = Complaint::query()->where('complainant_id', $request->user()->id);

        $complaints = (clone $owned)
            ->with(['order', 'respondent'])
            ->withCount('evidences')
            ->latest()
            ->paginate(12);

        $counts = (clone $owned)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json([
            'data' => collect($complaints->items())->map(fn (Complaint $complaint) => [
                'id' => $complaint->id,
                'type' => $complaint->type,
                'kind' => $complaint->kind,
                'description' => $complaint->description,
                'status' => $complaint->status,
                'status_label' => match ($complaint->status) {
                    'open' => 'Open',
                    'in_review' => 'In review',
                    'resolved' => 'Resolved',
                    default => ucfirst((string) $complaint->status),
                },
                'decision' => $complaint->decision,
                'filed_at_label' => $complaint->created_at?->format('M j, Y g:i A'),
                'attachments' => (int) $complaint->evidences_count,
                'order' => $complaint->order
                    ? ['id' => $complaint->order->id, 'number' => $complaint->order->number]
                    : null,
                'respondent' => $complaint->respondent
                    ? ['id' => $complaint->respondent->id, 'name' => $complaint->respondent->name]
                    : null,
            ])->values()->all(),
            'counts' => [
                'open' => (int) ($counts['open'] ?? 0),
                'in_review' => (int) ($counts['in_review'] ?? 0),
                'resolved' => (int) ($counts['resolved'] ?? 0),
            ],
            'meta' => [
                'current_page' => $complaints->currentPage(),
                'last_page' => $complaints->lastPage(),
                'total' => $complaints->total(),
            ],
        ]);
    }
}
