<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ShCheckpointScanWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShScannerScanController extends Controller
{
    public function lookup(Request $request, ShCheckpointScanWorkflow $workflow): JsonResponse
    {
        $data = $request->validate(['tracking_number' => ['required', 'string', 'max:80']]);

        return response()->json($workflow->lookup(
            $request->attributes->get('sh_station'), trim($data['tracking_number'])
        ));
    }

    public function store(Request $request, ShCheckpointScanWorkflow $workflow): JsonResponse
    {
        $data = $request->validate([
            'tracking_number' => ['required', 'string', 'max:80'],
            'scan_key' => ['required', 'uuid'],
        ]);

        return response()->json($workflow->record(
            $request->attributes->get('sh_station'), trim($data['tracking_number']), $data['scan_key']
        ));
    }

    public function history(Request $request, ShCheckpointScanWorkflow $workflow): JsonResponse
    {
        return response()->json(['scans' => $workflow->history($request->attributes->get('sh_station'))]);
    }
}
