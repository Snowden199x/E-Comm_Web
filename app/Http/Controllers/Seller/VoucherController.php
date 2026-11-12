<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Product;
use App\Models\Ecommerce\SellerVoucher;
use App\Models\User;
use App\Services\SellerVoucherNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VoucherController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:active,scheduled,expired,paused'],
            'search' => ['nullable', 'string', 'max:60'],
            'product' => ['nullable', 'integer'],
        ], [
            'status.in' => 'Choose All, Active, Scheduled, Expired, or Paused.',
            'search.max' => 'Search is too long. Use 60 characters or fewer.',
            'product.integer' => 'Product filter is not valid. Clear it and try again.',
        ]);
        $filters = ['status' => $filters['status'] ?? '', 'search' => trim($filters['search'] ?? ''),
            'product' => $filters['product'] ?? ''];
        $sellerId = $request->user()->id;
        $base = SellerVoucher::query()->where('seller_id', $sellerId)->whereNull('deleted_at');
        $active = fn ($q) => $q->where('status', 'active')->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->where(fn ($q) => $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'));
        $counts = [
            'all' => (clone $base)->count(),
            'active' => $active(clone $base)->count(),
            'scheduled' => (clone $base)->where('status', 'active')->where('starts_at', '>', now())->count(),
            'expired' => (clone $base)->where('status', 'active')->where(fn ($q) => $q->where('ends_at', '<=', now())
                ->orWhere(fn ($q) => $q->whereNotNull('usage_limit')->whereColumn('used_count', '>=', 'usage_limit')))->count(),
            'paused' => (clone $base)->where('status', 'paused')->count(),
        ];
        $redemptions = DB::table('seller_voucher_redemptions')->join('seller_vouchers', 'seller_vouchers.id', '=', 'seller_voucher_redemptions.voucher_id')
            ->where('seller_vouchers.seller_id', $sellerId)->whereNull('seller_voucher_redemptions.voided_at');
        $stats = ['redeemed' => (clone $redemptions)->count(),
            'discount_given' => (float) (clone $redemptions)->sum('seller_voucher_redemptions.discount'),
            'orders' => (clone $redemptions)->distinct()->count('seller_voucher_redemptions.order_id')];

        $query = (clone $base)->with(['products:id,name', 'categories:id,name'])
            ->withCount(['redemptions as orders_count' => fn ($q) => $q->whereNull('voided_at')])
            ->withSum(['redemptions as discount_given' => fn ($q) => $q->whereNull('voided_at')], 'discount');
        if ($filters['status'] === 'active') $active($query);
        if ($filters['status'] === 'scheduled') $query->where('status', 'active')->where('starts_at', '>', now());
        if ($filters['status'] === 'expired') $query->where('status', 'active')->where(fn ($q) => $q->where('ends_at', '<=', now())
            ->orWhere(fn ($q) => $q->whereNotNull('usage_limit')->whereColumn('used_count', '>=', 'usage_limit')));
        if ($filters['status'] === 'paused') $query->where('status', 'paused');
        if ($filters['search'] !== '') {
            $term = $filters['search'];
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$term.'%')->orWhere('code', 'like', '%'.$term.'%'));
        }
        $products = Product::query()->with('category')->where('seller_id', $sellerId)->orderBy('name')->get(['id', 'name', 'product_code', 'price', 'category_id']);
        if ($filters['product'] !== '') {
            $product = $products->firstWhere('id', (int) $filters['product']);
            abort_unless($product, 404);
            $parentId = $product->category?->parent_id ?: $product->category_id;
            $query->where(fn ($q) => $q->where('scope', 'all')
                ->orWhereHas('products', fn ($p) => $p->whereKey($product->id))
                ->orWhereHas('categories', fn ($c) => $c->whereKey($parentId)));
        }
        $categories = $request->user()->categories()->orderBy('name')->get();
        $vouchers = $query->latest()->paginate(8)->withQueryString();

        return view('seller.vouchers.index', compact('vouchers', 'counts', 'stats', 'filters', 'products', 'categories'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $voucher = DB::transaction(function () use ($request, $data) {
            User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $this->checkCode($request->user()->id, $data['code']);
            $voucher = SellerVoucher::create($this->fields($data, $request->user()->id));
            $this->syncScope($voucher, $data);
            if ($voucher->status === 'active') app(SellerVoucherNotificationService::class)->announceFirstActivation($voucher);

            return $voucher;
        }, 3);

        return response()->json(['message' => 'Voucher created.', 'id' => $voucher->id], 201);
    }

    public function update(Request $request, SellerVoucher $voucher): JsonResponse
    {
        $this->owned($request, $voucher);
        $data = $this->validated($request);
        DB::transaction(function () use ($request, $voucher, $data) {
            User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $record = SellerVoucher::query()->whereKey($voucher->id)->lockForUpdate()->firstOrFail();
            $this->owned($request, $record);
            $firstActivation = $record->status !== 'active' && $data['status'] === 'active';
            $this->checkCode($request->user()->id, $data['code'], $record->id);
            $record->update($this->fields($data, $request->user()->id));
            $this->syncScope($record, $data);
            if ($firstActivation) app(SellerVoucherNotificationService::class)->announceFirstActivation($record);
        }, 3);

        return response()->json(['message' => 'Voucher updated.']);
    }

    public function toggle(Request $request, SellerVoucher $voucher): JsonResponse
    {
        $this->owned($request, $voucher);
        $status = $request->validate(['status' => ['required', 'in:active,paused']])['status'];
        DB::transaction(function () use ($request, $voucher, $status) {
            $record = SellerVoucher::query()->whereKey($voucher->id)->lockForUpdate()->firstOrFail();
            $this->owned($request, $record);
            $firstActivation = $record->status !== 'active' && $status === 'active';
            $record->update(['status' => $status]);
            if ($firstActivation) app(SellerVoucherNotificationService::class)->announceFirstActivation($record);
        }, 3);

        return response()->json(['message' => $status === 'active' ? 'Voucher resumed.' : 'Voucher paused.']);
    }

    public function destroy(Request $request, SellerVoucher $voucher): JsonResponse
    {
        $this->owned($request, $voucher);
        $voucher->update(['deleted_at' => now(), 'status' => 'paused']);

        return response()->json(['message' => 'Voucher deleted.']);
    }

    private function owned(Request $request, SellerVoucher $voucher): void
    {
        abort_unless($voucher->seller_id === $request->user()->id && $voucher->deleted_at === null, 404);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'code' => ['required', 'string', 'regex:/^[A-Z0-9]{4,12}$/'],
            'type' => ['required', 'in:fixed,percent'],
            'value' => ['required', 'numeric', 'gt:0', 'max:99999999', 'regex:/^\d+(\.\d{1,2})?$/'],
            'max_discount' => ['nullable', 'numeric', 'gt:0', 'max:99999999', 'regex:/^\d+(\.\d{1,2})?$/'],
            'min_spend' => ['nullable', 'numeric', 'min:0', 'max:99999999', 'regex:/^\d+(\.\d{1,2})?$/'],
            'usage_limit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'per_buyer_limit' => ['required', 'integer', 'min:1', 'max:1000000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:now'],
            'scope' => ['required', 'in:all,products,categories'],
            'product_ids' => ['array', 'max:1000'], 'product_ids.*' => ['integer', 'distinct'],
            'category_ids' => ['array', 'max:100'], 'category_ids.*' => ['integer', 'distinct'],
            'status' => ['required', 'in:active,paused'],
        ], [
            'name.required' => 'Give the voucher a name.',
            'code.required' => 'Enter a voucher code, or use Generate.',
            'code.regex' => 'Code must be 4–12 capital letters or numbers. Use Generate to make one.',
            'value.required' => 'Enter a discount above zero.',
            'value.gt' => 'Discount must be above zero. Enter a higher amount.',
            'value.regex' => 'Discount can have up to 2 decimal places. Round it and try again.',
            'max_discount.gt' => 'Max discount must be above zero, or leave it empty.',
            'min_spend.min' => 'Minimum spend cannot be negative. Enter 0 for no minimum.',
            'usage_limit.min' => 'Total uses must be at least 1, or leave it empty for unlimited.',
            'per_buyer_limit.required' => 'Enter how many times one buyer can use the voucher.',
            'per_buyer_limit.min' => 'Uses per buyer must be at least 1.',
            'starts_at.date' => 'Start time is not valid. Pick it from the calendar.',
            'ends_at.date' => 'End time is not valid. Pick it from the calendar.',
            'ends_at.after' => 'End time must be in the future. Pick a later date.',
        ]);
        $fail = fn (string $field, string $message) => throw ValidationException::withMessages([$field => $message]);
        if ($data['type'] === 'percent' && $data['value'] > 90) {
            $fail('value', 'Percentage vouchers give at most 90% off. Lower the discount.');
        }
        if ($data['type'] === 'fixed' && ($data['min_spend'] ?? 0) > 0 && $data['value'] > $data['min_spend']) {
            $fail('value', 'Discount is bigger than the minimum spend. Lower the discount or raise the minimum spend.');
        }
        if (($data['usage_limit'] ?? null) !== null && $data['per_buyer_limit'] > $data['usage_limit']) {
            $fail('per_buyer_limit', 'Uses per buyer cannot exceed total uses. Lower it or raise total uses.');
        }
        if (! empty($data['starts_at']) && ! empty($data['ends_at']) && $data['ends_at'] <= $data['starts_at']) {
            $fail('ends_at', 'End time is before the start time. Pick an end after the start.');
        }
        $sellerId = $request->user()->id;
        $ids = $data['scope'] === 'products' ? ($data['product_ids'] ?? []) : ($data['category_ids'] ?? []);
        if ($data['scope'] !== 'all' && ! $ids) {
            throw ValidationException::withMessages(['scope' => 'Choose at least one product or category, or apply the voucher to all products.']);
        }
        if ($data['scope'] === 'products' && Product::query()->where('seller_id', $sellerId)->whereIn('id', $ids)->count() !== count($ids)) {
            throw ValidationException::withMessages(['product_ids' => 'One product is not in your shop. Remove it and try again.']);
        }
        if ($data['scope'] === 'categories' && $request->user()->categories()->whereIn('categories.id', $ids)->count() !== count($ids)) {
            throw ValidationException::withMessages(['category_ids' => 'One category is not approved for your shop. Remove it and try again.']);
        }

        return $data;
    }

    private function fields(array $data, int $sellerId): array
    {
        return ['seller_id' => $sellerId, 'name' => $data['name'], 'code' => $data['code'], 'type' => $data['type'],
            'value' => $data['value'], 'max_discount' => $data['type'] === 'percent' ? ($data['max_discount'] ?? null) : null,
            'min_spend' => $data['min_spend'] ?? 0, 'usage_limit' => $data['usage_limit'] ?? null,
            'per_buyer_limit' => $data['per_buyer_limit'], 'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null, 'scope' => $data['scope'], 'status' => $data['status']];
    }

    private function syncScope(SellerVoucher $voucher, array $data): void
    {
        $voucher->products()->sync($data['scope'] === 'products' ? ($data['product_ids'] ?? []) : []);
        $voucher->categories()->sync($data['scope'] === 'categories' ? ($data['category_ids'] ?? []) : []);
    }

    private function checkCode(int $sellerId, string $code, ?int $exceptId = null): void
    {
        if (SellerVoucher::query()->where('seller_id', $sellerId)->where('code', $code)
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))->exists()) {
            throw ValidationException::withMessages(['code' => 'You already use this code. Pick another, or use Generate.']);
        }
    }
}
