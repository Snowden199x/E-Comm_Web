<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Finance\CashRemittance;
use App\Services\CashRemittanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Vendo Admin: confirm the cash each Logistics Company sends Vendo (item price plus Vendo's delivery commission). */
class CashRemittanceController extends Controller
{
    public function __construct(private CashRemittanceService $remittances) {}

    public function index(Request $request): View
    {
        $base = CashRemittance::query()->where('level', CashRemittance::LEVEL_COMPANY_TO_VENDO);
        $batches = (clone $base)->with(['company', 'verifiedBy', 'resolvedBy'])->withCount('items')
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'discrepancy' THEN 1 ELSE 2 END")->latest()->paginate(15);

        return view('admin.remittances.index', [
            'batches' => $batches,
            'awaiting' => (float) (clone $base)->where('status', CashRemittance::STATUS_PENDING)->sum('expected_amount'),
            'openDiscrepancies' => (clone $base)->where('status', CashRemittance::STATUS_DISCREPANCY)->count(),
            'confirmed' => (float) (clone $base)->whereIn('status', [CashRemittance::STATUS_RECEIVED, CashRemittance::STATUS_RESOLVED])->sum('expected_amount'),
            'confirmedBatches' => (clone $base)->whereIn('status', [CashRemittance::STATUS_RECEIVED, CashRemittance::STATUS_RESOLVED])->count(),
        ]);
    }

    public function receive(Request $request, CashRemittance $remittance): RedirectResponse
    {
        abort_unless($remittance->level === CashRemittance::LEVEL_COMPANY_TO_VENDO, 404);
        $data = $request->validate([
            'received_amount' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        [$matches, $expected, $received] = $this->remittances->receive(
            $remittance, (int) round(((float) $data['received_amount']) * 100), $data['note'] ?? null, auth('admin')->user());

        return back()->with('success', $matches
            ? 'Received ₱'.number_format($received / 100, 2).' from the Logistics Company. The Sellers on this batch are credited automatically.'
            : 'Recorded with a discrepancy of ₱'.number_format(abs($expected - $received) / 100, 2).' ('.($received < $expected ? 'short' : 'over').'). Resolve it below.');
    }

    public function resolve(Request $request, CashRemittance $remittance): RedirectResponse
    {
        abort_unless($remittance->level === CashRemittance::LEVEL_COMPANY_TO_VENDO, 404);
        $data = $request->validate(['resolution_note' => ['required', 'string', 'min:5', 'max:1000']]);
        $this->remittances->resolve($remittance, $data['resolution_note'], auth('admin')->user());

        return back()->with('success', 'Discrepancy resolved. The batch now counts in full.');
    }
}
