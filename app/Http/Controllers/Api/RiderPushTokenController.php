<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Communication\PushDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The Rider app registers its FCM token after login and removes it on logout.
 * A token belongs to one account at a time: signing in on the same phone moves
 * it to the newest Rider.
 */
class RiderPushTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'in:android,ios'],
        ]);

        $rider = $request->user();

        PushDevice::query()->where('token', $data['token'])
            ->where('user_id', '!=', $rider->id)->delete();

        PushDevice::query()->updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id' => $rider->id,
                'platform' => $data['platform'] ?? 'android',
                'last_seen_at' => now(),
            ],
        );

        return response()->json([
            'message' => 'Push notifications enabled for this device.',
            'push_enabled' => app(\App\Services\FirebasePushService::class)->enabled(),
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => ['required', 'string', 'max:255']]);

        PushDevice::query()->where('user_id', $request->user()->id)
            ->where('token', $data['token'])->delete();

        return response()->json(['message' => 'Push notifications disabled for this device.']);
    }
}
