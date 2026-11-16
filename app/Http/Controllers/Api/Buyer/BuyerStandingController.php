<?php

namespace App\Http\Controllers\Api\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Complaints\Complaint;
use App\Services\BuyerComplianceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Account standing for the Buyer app: the same strikes, suspension state and
 * approved reports the web account-standing page shows. Reachable while the
 * account is suspended, so a Buyer can always see why and until when.
 */
class BuyerStandingController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $buyer = $request->user();
        abort_unless($buyer && $buyer->role === 'buyer', 403, 'This endpoint is for buyer accounts only.');

        BuyerComplianceService::liftIfExpired($buyer);
        $buyer->refresh();

        $strikes = BuyerComplianceService::strikes($buyer);
        $reports = Complaint::query()
            ->with(['order', 'complainant'])
            ->where('kind', 'user_report')
            ->where('respondent_id', $buyer->id)
            ->where('decision', 'approved')
            ->latest('reviewed_at')
            ->limit(20)
            ->get();

        return response()->json([
            'strikes' => $strikes,
            'until_next' => BuyerComplianceService::strikesUntilNextStep($strikes),
            'suspended' => $buyer->account_status === 'suspended',
            'account' => [
                'status' => $buyer->account_status,
                'suspension_reason' => $buyer->suspension_reason,
                'suspension_notes' => $buyer->suspension_notes,
                'suspended_at' => $buyer->suspended_at?->toIso8601String(),
                'suspended_until' => $buyer->suspended_until?->toIso8601String(),
                'suspended_until_label' => $buyer->suspended_until?->format('M j, Y g:i A'),
            ],
            'ladder' => collect(BuyerComplianceService::LADDER)
                ->map(fn (?int $days, int $count) => [
                    'strikes' => $count,
                    'days' => $days,
                    'label' => $days ? $days.'-day suspension' : 'permanent suspension',
                ])
                ->values()
                ->all(),
            'reports' => $reports->map(function (Complaint $report) {
                $activity = $report->activities()
                    ->where('action', 'like', 'Report approved:%')
                    ->latest('id')
                    ->first();

                return [
                    'id' => $report->id,
                    'type' => $report->type,
                    'reported_on' => $report->created_at?->format('M j, Y'),
                    'approved_on' => ($report->reviewed_at ?? $report->updated_at)?->format('M j, Y'),
                    'reporter' => $report->complainant?->role === 'seller'
                        ? 'A seller'
                        : ($report->complainant?->role === 'buyer' ? 'A buyer' : 'Another user'),
                    'note' => $activity ? trim(substr($activity->action, strlen('Report approved:'))) : null,
                    'order' => $report->order
                        ? ['id' => $report->order->id, 'number' => $report->order->number]
                        : null,
                ];
            })->values()->all(),
        ]);
    }
}
