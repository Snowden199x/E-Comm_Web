<?php

namespace App\Services;

use App\Models\AdminActionLog;
use App\Models\Communication\Notification;
use App\Models\Complaints\Complaint;
use App\Models\User;

/**
 * Buyer strikes. Every account report that Admin approves against a buyer is one strike.
 * Strikes lead to a timed or permanent suspension, the same ladder sellers get for violations.
 */
class BuyerComplianceService
{
    /** Strikes => suspension days (null = permanent). */
    public const LADDER = [3 => 7, 6 => 30, 9 => null];

    public static function strikes(User $user): int
    {
        return Complaint::query()->where('kind', 'user_report')
            ->where('respondent_id', $user->id)->where('decision', 'approved')->count();
    }

    /** Strikes left before the next suspension, or null when the buyer is already at the top step. */
    public static function strikesUntilNextStep(int $strikes): ?int
    {
        foreach (array_keys(self::LADDER) as $step) {
            if ($strikes < $step) {
                return $step - $strikes;
            }
        }

        return null;
    }

    /** Call after an approved report. Suspends the buyer when the count reaches a step. */
    public function enforce(User $buyer): ?string
    {
        if ($buyer->role !== 'buyer') {
            return null;
        }
        $strikes = self::strikes($buyer);
        $days = match (true) {
            $strikes >= 9 => 'permanent',
            $strikes === 6 => 30,
            $strikes === 3 => 7,
            default => null,
        };
        if ($days === null || ! in_array($buyer->account_status, [null, 'active'], true)) {
            return null;
        }

        $permanent = $days === 'permanent';
        $notes = $permanent
            ? 'Permanent suspension after '.$strikes.' approved account reports.'
            : 'Suspended for '.$days.' days after '.$strikes.' approved account reports.';
        $buyer->update([
            'account_status' => 'suspended',
            'suspension_reason' => 'Repeated account reports',
            'suspension_notes' => $notes,
            'suspended_at' => now(),
            'suspended_until' => $permanent ? null : now()->addDays($days),
        ]);
        Notification::create([
            'user_id' => $buyer->id,
            'type' => 'account_suspended',
            'title' => 'Account suspended',
            'message' => $notes.' Contact support if you think this is a mistake.',
            'link' => route('buyer.standing'),
        ]);
        AdminActionLog::record($buyer, 'buyer_auto_suspended', $notes);

        return $notes;
    }

    /** Lift a timed suspension once its end date has passed. Returns true when it was lifted. */
    public static function liftIfExpired(User $user): bool
    {
        if ($user->account_status !== 'suspended' || ! $user->suspended_until || $user->suspended_until->isFuture()) {
            return false;
        }
        $user->update(['account_status' => 'active', 'suspended_until' => null]);
        AdminActionLog::record($user, 'suspension_lifted', 'Suspension period ended.');

        return true;
    }
}
