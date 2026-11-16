<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Complaints\Complaint;
use App\Services\BuyerComplianceService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Lets a buyer see their strikes and suspension status. Reachable while suspended. */
class StandingController extends Controller
{
    /** One approved report about this buyer: what it was for, which order, and Admin's note. */
    public function report(Request $request, int $complaint): View
    {
        $buyer = $request->user();
        abort_unless($buyer->role === 'buyer', 403);
        $report = Complaint::query()->with('order')->where('kind', 'user_report')
            ->where('respondent_id', $buyer->id)->where('decision', 'approved')->findOrFail($complaint);
        $activity = $report->activities()->where('action', 'like', 'Report approved:%')->latest('id')->first();
        $note = $activity ? trim(substr($activity->action, strlen('Report approved:'))) : null;
        $reporterRole = $report->complainant?->role === 'seller' ? 'A seller' : ($report->complainant?->role === 'buyer' ? 'A buyer' : 'Another user');

        return view('buyer.standing-report', compact('report', 'note', 'reporterRole'));
    }

    public function show(Request $request): View
    {
        $buyer = $request->user();
        abort_unless($buyer->role === 'buyer', 403);
        BuyerComplianceService::liftIfExpired($buyer);
        $buyer->refresh();

        $strikes = BuyerComplianceService::strikes($buyer);
        $reports = Complaint::query()->where('kind', 'user_report')
            ->where('respondent_id', $buyer->id)->where('decision', 'approved')
            ->latest('reviewed_at')->limit(20)->get(['id', 'type', 'reviewed_at', 'created_at']);

        return view('buyer.standing', [
            'strikes' => $strikes,
            'untilNext' => BuyerComplianceService::strikesUntilNextStep($strikes),
            'reports' => $reports,
            'ladder' => BuyerComplianceService::LADDER,
            'suspended' => $buyer->account_status === 'suspended',
            'buyer' => $buyer,
        ]);
    }
}
