<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Profiles\LogisticsFacility;
use App\Services\FacilityScanWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FacilityScanController extends Controller
{
    public function store(Request $request, LogisticsFacility $facility, FacilityScanWorkflow $workflow): JsonResponse
    {
        abort_unless($facility->logistics_center_id === $request->user()->logisticsCenterDetail->id, 404);
        $data = $request->validate([
            'tracking_number' => ['required', 'string', 'max:100'],
            'action' => ['required', Rule::in(['auto', 'origin_arrival', 'origin_departure', 'arrival', 'sort', 'departure'])],
            'scan_key' => ['required', 'uuid'],
        ]);

        return response()->json($workflow->record(
            $request->user(), $facility, trim($data['tracking_number']), $data['action'], $data['scan_key']
        ));
    }
}
