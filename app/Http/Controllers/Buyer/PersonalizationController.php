<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\BuyerSavedItem;
use App\Models\Ecommerce\Product;
use App\Models\Profiles\BuyerSetting;
use App\Services\BuyerPersonalizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PersonalizationController extends Controller
{
    public function savedItems(Request $request, BuyerPersonalizationService $personalization): JsonResponse
    {
        $this->buyerId($request);
        return response()->json(['items' => $personalization->savedItemsFor($request->user()->id)]);
    }

    public function save(Request $request, Product $product, BuyerPersonalizationService $personalization): JsonResponse
    {
        $buyerId = $this->buyerId($request);
        abort_unless($product->status === 'approved' && $product->seller?->role === 'seller'
            && $product->seller->status === 'approved' && ! $product->seller->archived_at
            && (! $product->seller->account_status || $product->seller->account_status === 'active'), 404);

        DB::transaction(function () use ($buyerId, $product) {
            DB::table('users')->where('id', $buyerId)->lockForUpdate()->first();
            $this->pruneUnavailable($buyerId);
            $count = BuyerSavedItem::query()->where('user_id', $buyerId)->count();
            abort_if($count >= 60 && ! BuyerSavedItem::query()->where('user_id', $buyerId)->where('product_id', $product->id)->exists(), 422, 'You can save up to 60 products.');
            BuyerSavedItem::query()->firstOrCreate(['user_id' => $buyerId, 'product_id' => $product->id]);
        });

        return response()->json(['items' => $personalization->savedItemsFor($buyerId)]);
    }

    public function remove(Request $request, Product $product, BuyerPersonalizationService $personalization): JsonResponse
    {
        $buyerId = $this->buyerId($request);
        BuyerSavedItem::query()->where('user_id', $buyerId)->where('product_id', $product->id)->delete();
        return response()->json(['items' => $personalization->savedItemsFor($buyerId)]);
    }

    public function clear(Request $request): JsonResponse
    {
        BuyerSavedItem::query()->where('user_id', $this->buyerId($request))->delete();
        return response()->json(['items' => []]);
    }

    public function import(Request $request, BuyerPersonalizationService $personalization): JsonResponse
    {
        $buyerId = $this->buyerId($request);
        $validated = $request->validate(['ids' => ['required', 'array', 'max:60'], 'ids.*' => ['integer', 'distinct', 'min:1']]);
        $ids = Product::query()->whereIn('id', $validated['ids'])->where('status', 'approved')
            ->whereHas('seller', fn ($seller) => $seller->where('role', 'seller')->where('status', 'approved')
                ->whereNull('archived_at')->where(fn ($active) => $active->whereNull('account_status')->orWhere('account_status', 'active')))
            ->pluck('id');

        DB::transaction(function () use ($buyerId, $ids) {
            DB::table('users')->where('id', $buyerId)->lockForUpdate()->first();
            $this->pruneUnavailable($buyerId);
            foreach ($ids as $id) {
                if (BuyerSavedItem::query()->where('user_id', $buyerId)->count() >= 60) break;
                BuyerSavedItem::query()->firstOrCreate(['user_id' => $buyerId, 'product_id' => $id]);
            }
        });

        return response()->json(['items' => $personalization->savedItemsFor($buyerId)]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $buyerId = $this->buyerId($request);
        $validated = $request->validate([
            'text' => ['required', Rule::in(['default', 'lg', 'xl'])],
            'calm' => ['required', 'boolean'],
            'sound' => ['required', 'boolean'],
        ]);
        BuyerSetting::query()->updateOrCreate(['user_id' => $buyerId], [
            'text_size' => $validated['text'],
            'reduce_motion' => $validated['calm'],
            'notification_sound' => $validated['sound'],
        ]);
        return response()->json(['text' => $validated['text'], 'calm' => (bool) $validated['calm'], 'sound' => (bool) $validated['sound']]);
    }

    public function resetSettings(Request $request): JsonResponse
    {
        BuyerSetting::query()->updateOrCreate(['user_id' => $this->buyerId($request)], [
            'text_size' => 'default',
            'reduce_motion' => false,
            'notification_sound' => true,
        ]);
        return response()->json(['text' => 'default', 'calm' => false, 'sound' => true]);
    }

    private function buyerId(Request $request): int
    {
        abort_unless($request->user()?->role === 'buyer', 403);
        return (int) $request->user()->id;
    }

    private function pruneUnavailable(int $buyerId): void
    {
        BuyerSavedItem::query()->where('user_id', $buyerId)
            ->whereDoesntHave('product', fn ($query) => $query->where('status', 'approved')
                ->whereHas('seller', fn ($seller) => $seller->where('role', 'seller')
                    ->where('status', 'approved')->whereNull('archived_at')
                    ->where(fn ($active) => $active->whereNull('account_status')->orWhere('account_status', 'active'))))
            ->delete();
    }
}
