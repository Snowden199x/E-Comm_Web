<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profiles\LogisticsTransportTrip;
use App\Models\Profiles\OrderFacilityStop;
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
        $passwordIsValid = $user && Hash::check($data['password'], $user->password);
        $driver = $passwordIsValid && $user->role === 'transport_driver'
            ? app(\App\Services\TransportDriverAccessService::class)->approvedDriver($user)
            : null;

        if (! $driver) {
            // A valid applicant learns where the application stands; a wrong
            // password or an unrelated account only gets the generic answer.
            if ($passwordIsValid && $user->role === 'transport_driver') {
                $detail = TransportDriverDetail::query()->where('user_id', $user->id)
                    ->with(['stationHub:id,name', 'provinceHub:id,name'])->first();
                $pending = $user->status === 'pending';
                $reviewing = $detail?->reviewingHub()?->name;

                return response()->json([
                    'message' => $pending
                        ? 'Your application is waiting for '.($reviewing ?? 'hub').' approval.'
                        : ($user->status === 'disapproved'
                            ? 'Your application was declined.'.($user->rejection_reason ? ' '.$user->rejection_reason : '')
                            : 'Your account is not active. Contact your hub.'),
                    'status' => $pending ? 'pending' : ($user->status === 'disapproved' ? 'rejected' : 'inactive'),
                    'apply_to' => $detail?->apply_to,
                    'reviewing_hub' => $reviewing,
                    'rejection_reason' => $user->status === 'disapproved' ? $user->rejection_reason : null,
                ], 403);
            }

            return response()->json(['message' => 'Invalid email or password.'], 401);
        }

        $token = $user->createToken($data['device_name'], ['transport-driver:trip'], now()->addDays(7));

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
        ] + $this->profilePayload($user, $driver));
    }

    /**
     * The signed-in driver. The app calls this on start-up to restore a saved
     * session, so the profile it shows is the one the hub approved.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var TransportDriverDetail $driver */
        $driver = $request->attributes->get('transport_driver_detail');

        return response()->json($this->profilePayload($request->user(), $driver));
    }

    /**
     * One shape for login and /me, so the app maps a driver the same way after
     * signing in and after restoring a stored token.
     */
    private function profilePayload(User $user, TransportDriverDetail $driver): array
    {
        $driver->loadMissing([
            'provinceHub:id,name,province,municipality,logistics_company_id',
            'provinceHub.company:id,business_name',
            'stationHub:id,name,province,municipality',
        ]);
        $reviewing = $driver->reviewingHub();
        $hub = fn ($facility) => $facility ? [
            'id' => $facility->id, 'name' => $facility->name,
            'province' => $facility->province, 'municipality' => $facility->municipality,
        ] : null;

        return [
            'driver' => [
                'id' => $driver->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone_number,
                'apply_to' => $driver->apply_to,
                'apply_label' => $driver->applyLabel(),
                'status' => $driver->status,
                'license_number' => $driver->license_number,
                'license_category' => $driver->license_category,
                'license_expires_at' => $driver->license_expires_at?->toDateString(),
                'vehicle_type' => $driver->vehicle_type,
                'plate_number' => $driver->plate_number,
                'works_station_to_station' => $driver->worksStationToStation(),
            ],
            'province_hub' => $hub($driver->provinceHub),
            'station_hub' => $hub($driver->stationHub),
            'reviewing_hub' => $reviewing ? [
                'kind' => $driver->worksStationToStation() ? 'station_hub' : 'province_hub',
                'name' => $reviewing->name,
            ] : null,
            'company' => ['name' => $driver->provinceHub?->company?->business_name],
        ];
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

    /**
     * The driver's load scan. One label may name a single parcel or a whole
     * container, so the request carries either `tracking_number` or a
     * `tracking_numbers` list, and every parcel in the list is recorded at once.
     */
    public function scan(Request $request, LogisticsTransportTrip $trip, TransportTripWorkflow $workflow): JsonResponse
    {
        $data = $request->validate([
            'tracking_number' => ['nullable', 'string', 'max:100', 'required_without:tracking_numbers'],
            'tracking_numbers' => ['nullable', 'array', 'min:1', 'max:100', 'required_without:tracking_number'],
            'tracking_numbers.*' => ['string', 'max:100'],
            'scan_key' => ['required', 'uuid'],
        ]);
        $trackingNumbers = collect($data['tracking_numbers'] ?? [])
            ->push($data['tracking_number'] ?? null)
            ->filter(fn ($tracking) => is_string($tracking) && trim($tracking) !== '')
            ->map(fn ($tracking) => trim($tracking))
            ->unique()->take(100)->values()->all();
        abort_if($trackingNumbers === [], 422, 'Send at least one tracking number.');

        /** @var TransportDriverDetail $driver */
        $driver = $request->attributes->get('transport_driver_detail');
        abort_unless((int) $trip->driver_id === (int) $driver->id
            && (int) $trip->logistics_company_id === (int) $driver->provinceHub->logistics_company_id, 404);

        $result = $workflow->scanLoad($trip, $request->user(), $trackingNumbers, $data['scan_key']);
        $scanned = count($this->scannedOrderIds($result['trip'], $result['trip']->origin_facility_id, 'departed_at'));
        $total = $result['trip']->orders->count();
        $count = count($result['recorded']);
        $duplicates = count($result['duplicates']);

        $message = match (true) {
            $count > 0 => $count.' '.($count === 1 ? 'parcel' : 'parcels').' scanned. '
                .$scanned.' of '.$total.' on board.',
            $duplicates === 1 => 'That parcel was already scanned on this trip.',
            $duplicates > 1 => 'Those parcels were already scanned on this trip.',
            default => 'No parcel from that label is on this trip manifest.',
        };

        return response()->json([
            'message' => $message,
            'duplicate' => $count === 0 && $duplicates > 0,
            'recorded' => $result['recorded'],
            'duplicates' => $result['duplicates'],
            'unknown' => $result['unknown'],
            'not_routed' => $result['not_routed'],
            'trip' => $this->tripPayload($result['trip']),
        ]);
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
        $originScanIds = $this->scannedOrderIds($trip, $trip->origin_facility_id, 'departed_at');
        $destinationScanIds = $this->scannedOrderIds($trip, $trip->destination_facility_id, 'arrived_at');

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
                // Per-parcel flags, so the driver app ticks the exact parcels
                // it has scanned instead of guessing from a count.
                'origin_scanned' => in_array((int) $order->id, $originScanIds, true),
                'destination_scanned' => in_array((int) $order->id, $destinationScanIds, true),
            ])->values(),
            // The same facility scans the workflow checks before it lets a
            // trip depart or close, so the app can show real progress instead
            // of guessing from the order status.
            'origin_scanned' => count($originScanIds),
            'destination_scanned' => count($destinationScanIds),
        ];
    }

    /**
     * The manifest order ids that carry the given facility scan.
     *
     * @return array<int, int>
     */
    private function scannedOrderIds(LogisticsTransportTrip $trip, ?int $facilityId, string $column): array
    {
        $orderIds = $trip->orders->pluck('id');
        if (! $facilityId || $orderIds->isEmpty()) {
            return [];
        }

        return OrderFacilityStop::query()
            ->whereIn('order_id', $orderIds)
            ->where('facility_id', $facilityId)
            ->whereNotNull($column)
            ->distinct()
            ->pluck('order_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Signed out.']);
    }
}
