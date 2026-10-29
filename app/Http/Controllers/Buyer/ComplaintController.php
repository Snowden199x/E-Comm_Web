<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Complaints\Complaint;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->role === 'buyer', 403);

        $complaints = Complaint::query()
            ->with(['order', 'respondent'])
            ->withCount('evidences')
            ->where('complainant_id', $request->user()->id)
            ->latest()
            ->paginate(12);

        $counts = Complaint::query()
            ->where('complainant_id', $request->user()->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('buyer.complaints.index', compact('complaints', 'counts'));
    }
}
