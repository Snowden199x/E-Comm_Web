<?php

namespace App\Http\Controllers;

use App\Models\Profiles\CourierDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VerificationDocumentController extends Controller
{
    public function admin(User $user, string $document): StreamedResponse
    {
        [$path, $prefix] = match ($user->role) {
            'buyer' => match ($document) {
                'valid-id' => [$user->buyerDetail?->valid_id_path, 'valid-ids/'],
                'second-id' => [$user->buyerDetail?->valid_id_path_2, 'valid-ids/'],
                default => [null, null],
            },
            'seller' => match ($document) {
                'valid-id' => [$user->sellerDetail?->valid_id_path, 'valid-ids/seller/'],
                'business-permit' => [$user->sellerDetail?->business_permit_path, 'business-permits/'],
                default => [null, null],
            },
            'logistics_center' => match ($document) {
                'valid-id' => [$user->logisticsCenterDetail?->valid_id_path, 'valid-ids/logistics-center/'],
                'business-permit' => [$user->logisticsCenterDetail?->business_permit_path, 'business-permits/'],
                default => [null, null],
            },
            default => [null, null],
        };

        return $this->serve($path, $prefix);
    }

    public function logistics(Request $request, CourierDetail $courierDetail, string $document): StreamedResponse
    {
        $center = $request->user()->logisticsCenterDetail;
        abort_unless($center && $courierDetail->logistics_center_id === $center->id
            && $courierDetail->user?->role === 'courier', 404);

        $path = match ($document) {
            'valid-id' => $courierDetail->valid_id_path,
            'drivers-license' => $courierDetail->drivers_license_path,
            'or-cr' => $courierDetail->or_cr_path,
            default => null,
        };

        return $this->serve($path, 'private/rider-verification/');
    }

    private function serve(?string $path, ?string $prefix): StreamedResponse
    {
        abort_unless($path && $prefix && str_starts_with($path, $prefix)
            && ! str_contains($path, '..') && ! str_contains($path, '\\'), 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404);

        return $disk->response($path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
