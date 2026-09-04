<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Ecommerce\Product;
use App\Models\Compliance\ProductViolation;
use App\Models\Compliance\ProductWarning;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Database\Eloquent\Builder;

class SellerComplianceController extends Controller
{
    public function overview(Request $request): View
    {
        $sellers = $this->filteredSellers($request);
        $stats = $this->complianceStats();

        return view('admin.seller-compliance.overview', compact('sellers', 'stats'));
    }

    public function sellersTable(Request $request): View
    {
        $sellers = $this->filteredSellers($request);

        return view('admin.seller-compliance.partials.sellers-table', compact('sellers'));
    }

    private function filteredSellers(Request $request)
    {
        $query = User::where('role', 'seller')->whereIn('status', ['approved', 'suspended']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        }

        return $query->withCount(['productWarnings', 'productViolations'])
            ->with(['sellerDetail', 'categories'])
            ->latest()
            ->paginate(8)
            ->withQueryString();
    }

    public function productsForReview(Request $request): View
    {
        $products = $this->filteredProducts($request);

        $stats = [
            'for_review' => Product::where('status', 'for_review')->count(),
            'warnings_issued' => ProductWarning::count(),
            'violations' => ProductViolation::count(),
            'suspended_sellers' => User::where('role', 'seller')->where('status', 'suspended')->count(),
        ];

        $categories = Category::all();

        return view('admin.seller-compliance.products-for-review', compact('products', 'stats', 'categories'));
    }

    public function productsTable(Request $request): View
    {
        $products = $this->filteredProducts($request);

        return view('admin.seller-compliance.partials.products-table', compact('products'));
    }

    private function filteredProducts(Request $request)
    {
        $query = Product::where('status', 'for_review');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhereHas('seller', fn ($sq) => $sq->where('name', 'like', "%{$search}%")));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        return $query->with(['seller.sellerDetail', 'category', 'images'])
            ->latest()
            ->paginate(8)
            ->withQueryString();
    }

    public function approve(Product $product): RedirectResponse
    {
        $product->update(['status' => 'approved']);

        return back()->with('confirmation', 'product_approved');
    }

    public function reject(Request $request, Product $product): RedirectResponse
    {
        $request->validate([
            'reason' => 'required|string',
            'details' => 'required|string|max:500',
        ]);

        $product->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason,
            'rejection_details' => $request->details,
        ]);

        ProductViolation::create([
            'product_id' => $product->id,
            'seller_id' => $product->seller_id,
            'reason' => $request->reason,
            'details' => $request->details,
        ]);

        $this->escalateSuspension($product->seller);

        return back()->with('confirmation', 'product_rejected');
    }

    public function warn(Request $request, Product $product): RedirectResponse
    {
        $request->validate([
            'reason' => 'required|string',
            'details' => 'required|string|max:500',
        ]);

        ProductWarning::create([
            'product_id' => $product->id,
            'seller_id' => $product->seller_id,
            'reason' => $request->reason,
            'details' => $request->details,
        ]);

        $product->update(['status' => 'warned']);

        $seller = $product->seller;

        if ($seller->productWarnings()->count() % 3 === 0) {
            ProductViolation::create([
                'product_id' => $product->id,
                'seller_id' => $seller->id,
                'reason' => 'Accumulated 3 Warnings',
                'details' => 'Automatically recorded after the seller received 3 warnings.',
            ]);

            $this->escalateSuspension($seller);
        }

        return back()->with('confirmation', 'warning_issued');
    }

    private function escalateSuspension(User $seller): void
    {
        $count = $seller->productViolations()->count();

        if ($count >= 9) {
            $seller->update([
                'status' => 'suspended',
                'suspension_reason' => 'Severe/repeated violations',
                'suspension_notes' => 'Permanent suspension due to repeated policy violations.',
                'suspended_at' => now(),
                'suspended_until' => null,
            ]);
        } elseif ($count === 6) {
            $seller->update([
                'status' => 'suspended',
                'suspension_reason' => 'Repeated Violations',
                'suspension_notes' => 'Suspended for 30 days after 6 recorded violations.',
                'suspended_at' => now(),
                'suspended_until' => now()->addDays(30),
            ]);
        } elseif ($count === 3) {
            $seller->update([
                'status' => 'suspended',
                'suspension_reason' => 'Repeated Violations',
                'suspension_notes' => 'Suspended for 7 days after 3 recorded violations.',
                'suspended_at' => now(),
                'suspended_until' => now()->addDays(7),
            ]);
        }
    }
    
    private function complianceStats(): array
    {
        return [
            'compliant' => User::where('role', 'seller')->doesntHave('productViolations')->count(),
            'with_warnings' => User::where('role', 'seller')->has('productWarnings')->count(),
            'with_violations' => User::where('role', 'seller')->has('productViolations')->count(),
            'suspended' => User::where('role', 'seller')->where('status', 'suspended')->count(),
        ];
    }

    public function warnings(Request $request): View
    {
        $warnings = $this->filteredWarnings($request);
        $stats = $this->complianceStats();

        return view('admin.seller-compliance.warnings', compact('warnings', 'stats'));
    }

    public function warningsTable(Request $request): View
    {
        $warnings = $this->filteredWarnings($request);

        return view('admin.seller-compliance.partials.warnings-table', compact('warnings'));
    }

    private function filteredWarnings(Request $request)
    {
        $query = ProductWarning::with(['product', 'seller']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('seller', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        }

        $this->applyDateFilter($query, $request);

        return $query->latest()->paginate(8)->withQueryString();
    }

    private function filteredViolations(Request $request)
    {
        $query = ProductViolation::with(['product', 'seller']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('seller', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        }

        $this->applyDateFilter($query, $request);

        return $query->latest()->paginate(8)->withQueryString();
    }

    private function applyDateFilter(Builder $query, Request $request): void
    {
        $filter = $request->get('date_filter', 'all');

        match ($filter) {
            'today' => $query->whereDate('created_at', now()->toDateString()),
            'week' => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
            'month' => $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]),
            'custom' => $request->filled('custom_date') ? $query->whereDate('created_at', $request->custom_date) : null,
            default => null,
        };
    }

    public function violationsTable(Request $request): View
    {
        $violations = $this->filteredViolations($request);

        return view('admin.seller-compliance.partials.violations-table', compact('violations'));
    }

    public function violations(Request $request): View
    {
        $violations = $this->filteredViolations($request);
        $stats = $this->complianceStats();

        return view('admin.seller-compliance.violations', compact('violations', 'stats'));
    }
    
    public function suspendedSellers(Request $request): View
    {
        $sellers = $this->filteredSuspendedSellers($request);
        $stats = $this->complianceStats();

        return view('admin.seller-compliance.suspended-sellers', compact('sellers', 'stats'));
    }

    public function suspendedSellersTable(Request $request): View
    {
        $sellers = $this->filteredSuspendedSellers($request);

        return view('admin.seller-compliance.partials.suspended-sellers-table', compact('sellers'));
    }

    private function filteredSuspendedSellers(Request $request)
    {
        $query = User::where('role', 'seller')->where('status', 'suspended');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        }

        return $query->latest()->paginate(8)->withQueryString();
    }

    public function searchSellers(Request $request)
    {
        $search = trim((string) $request->get('q'));

        if ($search === '') {
            return response()->json([]);
        }

        $sellers = User::where('role', 'seller')
            ->whereIn('status', ['approved', 'suspended'])
            ->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
            ->limit(5)
            ->get(['id', 'name', 'email']);

        return response()->json($sellers);
    }
}