<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profiles\ShScannerStation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShScannerAuthController extends Controller
{
    public function pair(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'size:64']]);
        $result = DB::transaction(function () use ($data) {
            $station = ShScannerStation::query()
                ->where('pairing_code_hash', hash('sha256', $data['code']))
                ->lockForUpdate()->first();
            if (! $station || $station->revoked_at || ! $station->pairing_expires_at
                || $station->pairing_expires_at->isPast() || $station->paired_at
                || ! $station->checkpoint?->is_active) {
                return null;
            }
            $centerUser = $station->logisticsCenter?->user;
            if (! $centerUser || $centerUser->role !== 'logistics_center'
                || $centerUser->status !== 'approved' || $centerUser->archived_at
                || ($centerUser->account_status && $centerUser->account_status !== 'active')) {
                return null;
            }

            $token = bin2hex(random_bytes(32));
            $station->forceFill([
                'pairing_code_hash' => null,
                'pairing_expires_at' => null,
                'paired_at' => now(),
                'token_hash' => hash('sha256', $token),
                'token_expires_at' => now()->addDays(30),
            ])->save();

            return ['token' => $token, 'station' => $this->stationData($station)];
        }, 3);

        if (! $result) {
            return response()->json(['message' => 'Pairing QR is invalid, expired, or already used.'], 422);
        }

        return response()->json($result);
    }

    public function session(Request $request): JsonResponse
    {
        return response()->json(['station' => $this->stationData($request->attributes->get('sh_station'))]);
    }

    public function logout(Request $request): JsonResponse
    {
        $station = $request->attributes->get('sh_station');
        ShScannerStation::query()->whereKey($station->id)
            ->where('token_hash', $station->token_hash)
            ->update(['token_hash' => null, 'token_expires_at' => null,
                'revoked_at' => now(), 'updated_at' => now()]);

        return response()->json(['message' => 'Scanner signed out and token revoked.']);
    }

    private function stationData(ShScannerStation $station): array
    {
        return [
            'id' => $station->id,
            'name' => $station->name,
            'checkpoint' => [
                'code' => $station->checkpoint->code,
                'name' => $station->checkpoint->name,
                'municipality' => $station->checkpoint->municipality,
            ],
            'expires_at' => $station->token_expires_at?->toIso8601String(),
        ];
    }
}
