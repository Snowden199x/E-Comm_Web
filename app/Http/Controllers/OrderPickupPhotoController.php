<?php

namespace App\Http\Controllers;

use App\Models\Ecommerce\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Serves the seller's packed-item photo to the seller and the buyer of that order only. */
class OrderPickupPhotoController extends Controller
{
    public function show(Request $request, Order $order): StreamedResponse|BinaryFileResponse
    {
        $user = $request->user();
        abort_unless($user && $user->status === 'approved'
            && in_array($user->account_status, [null, 'active'], true) && ! $user->archived_at, 403);
        abort_unless(match ($user->role) {
            'buyer' => (int) $order->buyer_id === (int) $user->id,
            'seller' => (int) $order->seller_id === (int) $user->id,
            default => false,
        }, 404);
        $path = (string) $order->pickup_photo_path;
        abort_unless($path !== '' && str_starts_with($path, 'private/pickup-photos/'.$order->id.'/')
            && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, 'packed-item.'.pathinfo($path, PATHINFO_EXTENSION), [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
