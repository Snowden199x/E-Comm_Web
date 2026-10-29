<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profiles\LogisticsTransportTrip;
use App\Models\Profiles\TransportDriverDetail;
use App\Models\User;
use App\Services\TransportTripWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TransportDriverApiController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);
        $user = User::query()->where('email', strtolower(trim($data['email'])))->first();
        $driver = $user && Hash::check($data['password'], $user->password)
            ? app(\App\Services\TransportDriverAccessService::class)->approvedDriver($user)
            : null;

        if (! $driver) {
            return response()->json(['message' => 'Invalid credentials or Transport Driver account is not approved.'], 401);
        }

        $token = $user->createToken($data['device_name'], ['transport-driver:trip'], now()->addDays(7));

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
            'driver' => ['id' => $driver->id, 'name' => $user->name],
            'province_hub' => ['id' => $driver->provinceHub->id, 'name' => $driver->provinceHub->name],
            'company' => ['name' => $driver->provinceHub->company->business_name],
        ]);
    }

    public function trips(Request $request): JsonResponse
    {
        /** @var TransportDriverDetail $driver */
        $driver = $request->attributes->get('transport_driver_detail');
        $trips = LogisticsTransportTrip::query()->where('driver_id', $driver->id)
            ->whereIn('status', ['scheduled', 'in_transit'])
            ->with(['origin:id,name,municipality,province,address',
                'destination:id,name,municipality,province,address',
                'vehicle:id,vehicle_code,plate_number,vehicle_type',
                'orders:id,tracking_number,status'])
            ->orderByRaw("CASE WHEN status = 'in_transit' THEN 0 ELSE 1 END")
            ->orderBy('scheduled_departure_at')->limit(50)->get()
            ->map(fn (LogisticsTransportTrip $trip) => $this->tripPayload($trip));

        return response()->json(['trips' => $trips]);
    }

    public function depart(Request $request, LogisticsTransportTrip $trip, TransportTripWorkflow $workflow): JsonResponse
    {
        /** @var TransportDriverDetail $driver */
        $driver = $request->attributes->get('transport_driver_detail');
        abort_unless((int) $trip->driver_id === (int) $driver->id
            && (int) $trip->logistics_company_id === (int) $driver->provinceHub->logistics_company_id, 404);
        $trip = $workflow->depart($trip, $request->user());

        return response()->json(['message' => 'Trip departed.', 'trip' => $this->tripPayload($trip)]);
    }

    public function arrive(Request $request, LogisticsTransportTrip $trip, TransportTripWorkflow $workflow): JsonResponse
    {
        /** @var TransportDriverDetail $driver */
        $driver = $request->attributes->get('transport_driver_detail');
        abort_unless((int) $trip->driver_id === (int) $driver->id
            && (int) $trip->logistics_company_id === (int) $driver->provinceHub->logistics_company_id, 404);
        $trip = $workflow->arrive($trip, $request->user());

        return response()->json(['message' => 'Trip arrived. Destination scans are complete.', 'trip' => $this->tripPayload($trip)]);
    }

    private function tripPayload(LogisticsTransportTrip $trip): array
    {
        return [
            'id' => $trip->id,
            'code' => 'TRP-'.str_pad((string) $trip->id, 5, '0', STR_PAD_LEFT),
            'type' => $trip->trip_type,
            'status' => $trip->status,
            'scheduled_departure_at' => $trip->scheduled_departure_at?->toIso8601String(),
            'expected_arrival_at' => $trip->expected_arrival_at?->toIso8601String(),
            'departed_at' => $trip->departed_at?->toIso8601String(),
            'arrived_at' => $trip->arrived_at?->toIso8601String(),
            'origin' => $trip->origin ? ['name' => $trip->origin->name, 'municipality' => $trip->origin->municipality, 'province' => $trip->origin->province] : null,
            'destination' => $trip->destination ? ['name' => $trip->destination->name, 'municipality' => $trip->destination->municipality, 'province' => $trip->destination->province] : null,
            'vehicle' => $trip->vehicle ? ['code' => $trip->vehicle->vehicle_code, 'plate_number' => $trip->vehicle->plate_number, 'type' => $trip->vehicle->vehicle_type] : null,
            'manifest' => $trip->orders->map(fn ($order) => [
                'order_number' => $order->number,
                'tracking_number' => $order->tracking_number,
                'status' => $order->status,
            ])->values(),
        ];
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Signed out.']);
    }
}
