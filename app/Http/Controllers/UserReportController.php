<?php

namespace App\Http\Controllers;

use App\Models\Communication\MarketplaceConversation;
use App\Models\Communication\Notification;
use App\Models\Complaints\Complaint;
use App\Models\Ecommerce\Order;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserReportController extends Controller
{
    private const REASONS = [
        'buyer' => ['Misleading listing', 'Item not received', 'Seller harassment', 'Suspected fraud', 'Other'],
        'seller' => ['Bogus order', 'Abusive messages', 'Suspected fraud', 'Repeated cancellations', 'Other'],
    ];

    private const RIDER_REASONS = ['Rude or unprofessional', 'Late or no-show', 'Damaged parcel', 'Item missing or tampered', 'Asked for extra payment', 'Other'];

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $actor = $request->user();
        $role = $this->reporterRole($request);
        $request->merge(['description' => trim((string) $request->input('description'))]);
        $isRiderReport = str_starts_with((string) $request->input('context'), 'rider:');
        $validated = $request->validate([
            'context' => ['required', 'string', 'regex:/^(order|seller|buyer|rider):[1-9][0-9]*$/'],
            'reason' => ['required', Rule::in($isRiderReport ? self::RIDER_REASONS : self::REASONS[$role])],
            'description' => ['required', 'string', 'min:20', 'max:2000'],
            'evidence' => ['nullable', 'array', 'max:5'],
            'evidence.*' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);
        [$contextType, $contextId] = explode(':', $validated['context'], 2);
        $order = null;

        if ($contextType === 'order') {
            $order = Order::query()->whereKey((int) $contextId)
                ->where($role === 'buyer' ? 'buyer_id' : 'seller_id', $actor->id)->firstOrFail();
            $respondentId = $role === 'buyer' ? $order->seller_id : $order->buyer_id;
            if ($role === 'seller' && $validated['reason'] === 'Repeated cancellations') {
                $cancelledByBuyer = $order->status === 'cancelled'
                    && $order->statusEvents()->where('to_status', 'cancelled')->where('user_id', $order->buyer_id)->exists();
                abort_unless($cancelledByBuyer, 422, 'Only an order the buyer cancelled can be reported for repeated cancellations.');
            }
        } elseif ($contextType === 'rider') {
            $order = Order::query()->whereKey((int) $contextId)
                ->where($role === 'buyer' ? 'buyer_id' : 'seller_id', $actor->id)->firstOrFail();
            $respondentId = $role === 'buyer' ? ($order->delivery_courier_id ?: $order->courier_id) : $order->courier_id;
            abort_unless($respondentId, 422, 'No rider is assigned to this order yet.');
        } elseif ($contextType === 'seller') {
            abort_unless($role === 'buyer', 404);
            $seller = User::query()->whereKey((int) $contextId)->where('role', 'seller')
                ->where('status', 'approved')->whereNull('archived_at')
                ->where(function ($query) {
                    $query->whereNull('account_status')->orWhere('account_status', 'active');
                })->firstOrFail();
            $respondentId = $seller->id;
        } else {
            abort_unless($role === 'seller', 404);
            $chat = MarketplaceConversation::query()->where('seller_id', $actor->id)
                ->where('buyer_id', (int) $contextId)->firstOrFail();
            $respondentId = $chat->buyer_id;
        }

        abort_if($respondentId === $actor->id, 422, 'You cannot report your own account.');
        abort_if($order && Complaint::query()->where('kind', 'user_report')->where('order_id', $order->id)
            ->where('complainant_id', $actor->id)->where('respondent_id', $respondentId)
            ->where('status', '!=', 'resolved')->exists(), 422, 'You already reported this account for this order. Admin is reviewing it.');
        $targetLabel = $isRiderReport ? 'rider' : ($role === 'buyer' ? 'seller' : 'buyer');

        $storedEvidence = [];
        try {
            DB::transaction(function () use ($actor, $respondentId, $order, $validated, $request, $targetLabel, &$storedEvidence) {
                $report = Complaint::create([
                    'order_id' => $order?->id,
                    'complainant_id' => $actor->id,
                    'respondent_id' => $respondentId,
                    'kind' => 'user_report',
                    'type' => $validated['reason'],
                    'description' => trim($validated['description']),
                    'status' => 'open',
                ]);
                foreach ($request->file('evidence', []) as $file) {
                    $path = $file->store('complaint-evidence/'.$report->id, 'local');
                    $storedEvidence[] = $path;
                    $report->evidences()->create([
                        'path' => $path,
                        'original_filename' => $file->getClientOriginalName(),
                    ]);
                }
                $report->activities()->create(['actor' => $actor->role.' #'.$actor->id, 'action' => 'Report submitted']);
                Notification::create([
                    'user_id' => null,
                    'type' => 'complaint_submitted',
                    'title' => 'New account report',
                    'message' => 'A '.$actor->role.' reported a '.$targetLabel.': '.$validated['reason'].'.',
                    'link' => route('admin.complaints.show', $report),
                ]);
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($storedEvidence);
            throw $exception;
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Report sent to Admin for review.']);
        }

        return back()->with('success', 'Report sent to Admin for review.');
    }

    private function reporterRole(Request $request): string
    {
        $role = $request->user()?->role;
        abort_unless(in_array($role, ['buyer', 'seller'], true), 403);
        abort_unless($request->routeIs($role.'.user-reports.*'), 403);

        return $role;
    }
}
