<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SellerProfileController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->role === 'buyer', 403);
        $validated = $request->validate(['search' => ['nullable', 'string', 'max:100']]);
        $search = trim($validated['search'] ?? '');

        $sellers = User::query()->where('role', 'seller')->where('status', 'approved')
            ->whereNull('archived_at')->where(fn ($query) => $query->whereNull('account_status')->orWhere('account_status', 'active'))
            ->when($search !== '', fn ($query) => $query->where(fn ($match) => $match
                ->where('name', 'like', '%'.$search.'%')
                ->orWhereHas('sellerDetail', fn ($detail) => $detail->where('business_name', 'like', '%'.$search.'%'))))
            ->with('sellerDetail')
            ->withCount(['products as approved_products_count' => fn ($query) => $query->where('status', 'approved')])
            ->orderBy('name')->paginate(12)->withQueryString();

        return view('buyer.sellers.index', compact('sellers', 'search'));
    }

    public function show(Request $request, User $seller)
    {
        abort_unless($request->user()?->role === 'buyer', 403);
        abort_unless($seller->role === 'seller' && $seller->status === 'approved'
            && ! $seller->archived_at && (! $seller->account_status || $seller->account_status === 'active'), 404);

        $seller->load('sellerDetail');
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', Rule::in(['latest', 'price_asc', 'price_desc'])],
        ]);
        $search = trim($validated['search'] ?? '');
        $sort = $validated['sort'] ?? 'latest';

        $products = Product::query()->where('seller_id', $seller->id)->where('status', 'approved')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->with(['images', 'seller.sellerDetail'])
            ->when($sort === 'price_asc', fn ($query) => $query->orderBy('price')->orderBy('id'))
            ->when($sort === 'price_desc', fn ($query) => $query->orderByDesc('price')->orderBy('id'))
            ->when($sort === 'latest', fn ($query) => $query->latest())
            ->paginate(12)->withQueryString();
        $filters = compact('search', 'sort');
        return view('buyer.sellers.show', compact('seller', 'products', 'filters'));
    }
}
