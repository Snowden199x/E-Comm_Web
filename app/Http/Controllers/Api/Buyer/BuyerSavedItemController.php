<?php

namespace App\Http\Controllers\Api\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\BuyerSavedItem;
use App\Models\Ecommerce\Product;
use App\Services\BuyerApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Buyer wishlist ("saved items"), the same 60-product list the web heart buttons write to. */
class BuyerSavedItemController extends Controller
{
    public function __construct(private BuyerApiPresenter $presenter) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->payload((int) $request->user()->id));
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        $buyerId = (int) $request->user()->id;
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

        return response()->json($this->payload($buyerId) + ['message' => 'Saved for later.']);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        BuyerSavedItem::query()->where('user_id', $request->user()->id)->where('product_id', $product->id)->delete();

        return response()->json($this->payload((int) $request->user()->id) + ['message' => 'Removed from saved items.']);
    }

    public function clear(Request $request): JsonResponse
    {
        BuyerSavedItem::query()->where('user_id', $request->user()->id)->delete();

        return response()->json(['items' => [], 'count' => 0, 'message' => 'Saved items cleared.']);
    }

    private function payload(int $buyerId): array
    {
        $savedIds = BuyerSavedItem::query()->where('user_id', $buyerId)->orderByDesc('id')->pluck('product_id');
        $products = Product::with(['images', 'seller.sellerDetail'])->availableToBuy()->withCardMetrics()
            ->whereIn('id', $savedIds)->get()->keyBy('id');
        $items = $savedIds->map(fn ($id) => $products->get($id))->filter()->values()
            ->map(fn (Product $product) => $this->presenter->productCard($product))->all();

        return ['items' => $items, 'count' => count($items)];
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
