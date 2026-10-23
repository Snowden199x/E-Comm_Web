<?php

namespace App\Http\Controllers;

use App\Models\Ecommerce\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderDeliveryProofController extends Controller
{
    public function show(Request $request, Order $order): StreamedResponse|BinaryFileResponse
    {
        $user = $request->user();
        abort_unless($user && $user->status === 'approved'
            && in_array($user->account_status, [null, 'active'], true) && ! $user->archived_at, 403);
        $allowed = match ($user->role) {
            'buyer' => (int) $order->buyer_id === (int) $user->id,
            'seller' => (int) $order->seller_id === (int) $user->id,
            'logistics_center' => $user->logisticsCenterDetail?->company?->status === 'approved'
                && $order->facilityStops()->whereHas('facility', fn ($query) => $query
                    ->where('logistics_company_id', $user->logisticsCenterDetail->company->id))->exists(),
            'province_hub', 'station_hub' => $user->facilityStaff?->facility?->is_active
                && $user->facilityStaff->facility->company?->status === 'approved'
                && $order->facilityStops()->whereHas('facility', fn ($query) => $query
                    ->where('logistics_company_id', $user->facilityStaff->facility->logistics_company_id)
                    ->where(fn ($scope) => $user->role === 'province_hub'
                        ? $scope->where('id', $user->facilityStaff->facility_id)
                            ->orWhere('parent_id', $user->facilityStaff->facility_id)
                        : $scope->where('id', $user->facilityStaff->facility_id)))->exists(),
            default => false,
        };
        abort_unless($allowed, 404);
        $proof = $order->deliveryProof;
        abort_unless($proof && str_starts_with($proof->photo_path, 'private/delivery-proofs/'.$order->id.'/')
            && Storage::disk('local')->exists($proof->photo_path), 404);

        return Storage::disk('local')->response($proof->photo_path, 'delivery-proof.'.pathinfo($proof->photo_path, PATHINFO_EXTENSION), [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
