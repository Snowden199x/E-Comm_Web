<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Profiles\LogisticsRouteCheckpoint;
use App\Models\Profiles\ShScannerStation;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ShScannerStationController extends Controller
{
    public function index(Request $request)
    {
        $centerId = $request->user()->logisticsCenterDetail->id;
        $checkpoints = LogisticsRouteCheckpoint::query()
            ->where('is_active', true)
            ->whereHas('planStops.routePlan', fn ($query) => $query->where('is_active', true)
                ->where(fn ($pair) => $pair->where('from_logistics_center_id', $centerId)
                    ->orWhere('to_logistics_center_id', $centerId)))
            ->orderBy('code')->get();
        $stations = ShScannerStation::query()->where('logistics_center_id', $centerId)
            ->with('checkpoint')->latest()->get();

        return view('logistics.scanners.index', compact('checkpoints', 'stations'));
    }

    public function store(Request $request): RedirectResponse
    {
        $centerId = $request->user()->logisticsCenterDetail->id;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'checkpoint_id' => ['required', 'integer', Rule::exists('logistics_route_checkpoints', 'id')],
        ]);
        $checkpoint = LogisticsRouteCheckpoint::query()->where('is_active', true)
            ->whereHas('planStops.routePlan', fn ($query) => $query->where('is_active', true)
                ->where(fn ($pair) => $pair->where('from_logistics_center_id', $centerId)
                    ->orWhere('to_logistics_center_id', $centerId)))
            ->findOrFail($data['checkpoint_id']);
        $code = Str::random(64);
        $station = ShScannerStation::query()->create([
            'logistics_center_id' => $centerId,
            'checkpoint_id' => $checkpoint->id,
            'name' => $data['name'],
        ]);
        $station->forceFill([
            'pairing_code_hash' => hash('sha256', $code),
            'pairing_expires_at' => now()->addMinutes(5),
        ])->save();

        $qr = (new SvgWriter())->write(new QrCode(data: 'vendo-scanner-pair:v1:'.$code))->getDataUri();

        return redirect()->route('logistics.scanners.index')
            ->with('pairing', ['station_id' => $station->id, 'code' => $code, 'qr' => $qr]);
    }

    public function revoke(Request $request, ShScannerStation $station): RedirectResponse
    {
        abort_unless($station->logistics_center_id === $request->user()->logisticsCenterDetail->id, 404);
        DB::transaction(function () use ($station) {
            $locked = ShScannerStation::query()->lockForUpdate()->findOrFail($station->id);
            $locked->forceFill([
                'pairing_code_hash' => null,
                'pairing_expires_at' => null,
                'token_hash' => null,
                'token_expires_at' => null,
                'revoked_at' => now(),
            ])->save();
        });

        return back()->with('success', 'Scanner station revoked. It can no longer submit scans.');
    }

    public function refreshPairing(Request $request, ShScannerStation $station): RedirectResponse
    {
        abort_unless($station->logistics_center_id === $request->user()->logisticsCenterDetail->id, 404);
        $code = Str::random(64);
        DB::transaction(function () use ($station, $code) {
            $locked = ShScannerStation::query()->lockForUpdate()->findOrFail($station->id);
            abort_unless(! $locked->revoked_at && ! $locked->paired_at, 409,
                'A paired or revoked station cannot receive another pairing QR.');
            $locked->forceFill([
                'pairing_code_hash' => hash('sha256', $code),
                'pairing_expires_at' => now()->addMinutes(5),
            ])->save();
        });
        $qr = (new SvgWriter())->write(new QrCode(data: 'vendo-scanner-pair:v1:'.$code))->getDataUri();

        return redirect()->route('logistics.scanners.index')
            ->with('pairing', ['station_id' => $station->id, 'code' => $code, 'qr' => $qr]);
    }
}
