<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaints\Complaint;
use App\Models\User;
use App\Services\BuyerComplianceService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Buyers that other users reported. Approved reports are strikes, and strikes lead to suspension. */
class BuyerComplianceController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'standing' => ['nullable', 'in:all,flagged,suspended'],
        ]);
        $standing = $filters['standing'] ?? 'all';
        $reports = fn () => Complaint::query()->where('kind', 'user_report')->whereColumn('respondent_id', 'users.id');

        $query = User::query()->select('users.*')
            ->addSelect([
                'approved_reports' => $reports()->selectRaw('count(*)')->where('decision', 'approved'),
                'pending_reports' => $reports()->selectRaw('count(*)')->whereNull('decision'),
            ])
            ->where('role', 'buyer')
            ->where(fn ($q) => $q->whereExists($reports()->select('complaints.id')->toBase())->orWhere('account_status', 'suspended'));

        if (filled($filters['search'] ?? null)) {
            $search = trim($filters['search']);
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        }
        if ($standing === 'suspended') {
            $query->where('account_status', 'suspended');
        } elseif ($standing === 'flagged') {
            $query->whereExists($reports()->select('complaints.id')->where('decision', 'approved')->toBase());
        }

        $buyers = $query->orderByDesc('approved_reports')->orderByDesc('pending_reports')->orderBy('name')
            ->paginate(10)->withQueryString();

        $buyerReports = fn () => Complaint::query()->where('kind', 'user_report')
            ->whereHas('respondent', fn ($q) => $q->where('role', 'buyer'));
        $stats = [
            'reported' => $buyerReports()->distinct()->count('respondent_id'),
            'pending' => $buyerReports()->whereNull('decision')->count(),
            'flagged' => $buyerReports()->where('decision', 'approved')->distinct()->count('respondent_id'),
            'suspended' => User::where('role', 'buyer')->where('account_status', 'suspended')->count(),
        ];
        $ladder = BuyerComplianceService::LADDER;

        return view('admin.buyer-compliance.index', compact('buyers', 'stats', 'ladder', 'filters', 'standing'));
    }
}
