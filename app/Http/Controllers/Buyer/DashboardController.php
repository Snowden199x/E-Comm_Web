<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Communication\Announcement;
use App\Models\Ecommerce\Product;

class DashboardController extends Controller
{
    public function index()
    {
        $categories = Category::whereNull('parent_id')->get();

        $announcements = Announcement::query()
            ->whereIn('audience', ['All Users', 'Buyers & Sellers', 'Buyers Only'])
            ->where(fn ($query) => $query->where('status', 'published')
                ->orWhere(fn ($scheduled) => $scheduled->where('status', 'scheduled')
                    ->whereNotNull('scheduled_at')->where('scheduled_at', '<=', now())))
            ->latest()
            ->take(5)
            ->get();

        $products = Product::with(['images', 'seller.sellerDetail'])
            ->availableToBuy()
            ->withCardMetrics()
            ->latest()
            ->take(18)
            ->get();

        return view('buyer.dashboard', compact('categories', 'products', 'announcements'));
    }
}
