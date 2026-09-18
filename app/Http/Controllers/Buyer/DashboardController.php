<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Ecommerce\Product;

class DashboardController extends Controller
{
    public function index()
    {
        $categories = Category::whereNull('parent_id')->get();

        $products = Product::with('images')
            ->where('status', 'approved')
            ->latest()
            ->take(12)
            ->get();

        return view('buyer.dashboard', compact('categories', 'products'));
    }
}
