<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Complaints\Complaint;
use Illuminate\Http\Request;
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

        $query = (clone $base)->with(['order', 'complainant', 'respondent', 'evidences', 'activities']);
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
}
