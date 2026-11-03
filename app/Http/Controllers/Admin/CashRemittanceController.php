<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Finance\CashRemittance;
use App\Services\CashRemittanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;

/** Vendo Admin: confirm the cash each Logistics Company sends Vendo (item price plus Vendo's delivery commission). */
class CashRemittanceController extends Controller
{
    /** Filter keys the page accepts. "confirmed" covers both received and resolved batches. */
    private const STATUS_FILTERS = [
        'pending' => [CashRemittance::STATUS_PENDING],
        'discrepancy' => [CashRemittance::STATUS_DISCREPANCY],
        'confirmed' => [CashRemittance::STATUS_RECEIVED, CashRemittance::STATUS_RESOLVED],
    ];

    public function __construct(private CashRemittanceService $remittances) {}

    public function index(Request $request): View
    {
        $base = CashRemittance::query()->where('level', CashRemittance::LEVEL_COMPANY_TO_VENDO);

        $status = (string) $request->query('status', 'all');
        $status = array_key_exists($status, self::STATUS_FILTERS) ? $status : 'all';
        $search = trim((string) $request->query('search', ''));

        $batches = (clone $base)->with(['company', 'verifiedBy', 'resolvedBy'])->withCount('items')
            ->when($status !== 'all', fn ($q) => $q->whereIn('status', self::STATUS_FILTERS[$status]))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('reference', 'like', "%{$search}%")
                ->orWhereHas('company', fn ($c) => $c->where('business_name', 'like', "%{$search}%"))))
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'discrepancy' THEN 1 ELSE 2 END")->latest()
            ->paginate(15)->withQueryString();

        // One query for all three cards: batches and expected amount per status.
        $byStatus = (clone $base)->selectRaw('status, COUNT(*) as batches, COALESCE(SUM(expected_amount), 0) as amount')
            ->groupBy('status')->get()->keyBy('status');
        $summary = collect(self::STATUS_FILTERS)->map(fn (array $statuses) => [
            'batches' => (int) collect($statuses)->sum(fn ($s) => $byStatus[$s]->batches ?? 0),
            'amount' => (float) collect($statuses)->sum(fn ($s) => $byStatus[$s]->amount ?? 0),
        ])->all();

        return view('admin.remittances.index', [
            'batches' => $batches,
            'summary' => $summary,
            'status' => $status,
            'search' => $search,
        ]);
    }

    public function receive(Request $request, CashRemittance $remittance): RedirectResponse
    {
        abort_unless($remittance->level === CashRemittance::LEVEL_COMPANY_TO_VENDO, 404);

        // Each message says the fix and shows under its field.
        $data = $request->validate([
            'received_amount' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'received_amount.required' => 'Enter the amount received, like 1250.50.',
            'received_amount.numeric' => 'Use numbers only, like 1250.50.',
            'received_amount.min' => 'Enter 0 or more.',
            'received_amount.max' => 'This amount is too large. Check for extra digits.',
            'received_amount.decimal' => 'Use at most 2 decimals, like 1250.50.',
            'note.max' => 'Keep the note under 500 characters.',
        ]);

        try {
            [$matches, $expected, $received] = $this->remittances->receive(
                $remittance, (int) round(((float) $data['received_amount']) * 100), $data['note'] ?? null, auth('admin')->user());
        } catch (HttpException $e) {
            return $this->alreadyHandled($e);
        }

        return back()->with('success', $matches
            ? 'Received ₱'.number_format($received / 100, 2).'. Sellers on this batch are credited.'
            : 'Recorded ₱'.number_format(abs($expected - $received) / 100, 2).' '.($received < $expected ? 'short' : 'over').'. Resolve it to credit the Sellers.');
    }

    public function resolve(Request $request, CashRemittance $remittance): RedirectResponse
    {
        abort_unless($remittance->level === CashRemittance::LEVEL_COMPANY_TO_VENDO, 404);

        $data = $request->validate(['resolution_note' => ['required', 'string', 'min:5', 'max:1000']], [
            'resolution_note.required' => 'Write how the difference was settled.',
            'resolution_note.min' => 'Write at least 5 characters.',
            'resolution_note.max' => 'Keep the note under 1000 characters.',
        ]);

        try {
            $this->remittances->resolve($remittance, $data['resolution_note'], auth('admin')->user());
        } catch (HttpException $e) {
            return $this->alreadyHandled($e);
        }

        return back()->with('success', 'Discrepancy resolved. Sellers on this batch are credited.');
    }

    /** Another admin got there first (409): show the reason and the fix on the page instead of an error screen. */
    private function alreadyHandled(HttpException $e): RedirectResponse
    {
        if ($e->getStatusCode() !== 409) {
            throw $e;
        }

        return back()->with('error', $e->getMessage().' Reload the page to see its current status.');
    }
}
