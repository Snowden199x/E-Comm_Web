<?php

namespace App\Http\Controllers;

use App\Models\Ecommerce\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LiveRevisionController extends Controller
{
    public function buyer(Request $request, string $scope): JsonResponse
    {
        abort_unless($request->user()?->role === 'buyer', 403);
        abort_unless(in_array($scope, ['orders', 'cart', 'catalog', 'notifications'], true), 404);
        $id = $request->user()->id;
        $rows = match ($scope) {
            'orders' => [$this->orders(Order::where('buyer_id', $id)),
                DB::table('product_reviews')->where('buyer_id', $id)->orderBy('id')->get(['id', 'visibility', 'rating', 'updated_at']),
                DB::table('product_review_replies')->whereIn('product_review_id',
                    DB::table('product_reviews')->where('buyer_id', $id)->select('id'))
                    ->orderBy('id')->get(['id', 'product_review_id', 'updated_at'])],
            'cart' => [
                DB::table('cart_items')->where('user_id', $id)->orderBy('id')->get(['id', 'product_id', 'quantity', 'color', 'size']),
                DB::table('products')->whereIn('id', DB::table('cart_items')->where('user_id', $id)->select('product_id'))
                    ->orderBy('id')->get(['id', 'status', 'stock', 'price', 'colors', 'sizes', 'updated_at']),
            ],
            'catalog' => [
                DB::table('products')->where('status', 'approved')->orderBy('id')
                    ->get(['id', 'status', 'stock', 'price', 'updated_at']),
                DB::table('product_images')->whereIn('product_id',
                    DB::table('products')->where('status', 'approved')->select('id'))
                    ->orderBy('id')->get(['id', 'product_id', 'path', 'sort_order']),
                DB::table('product_reviews')->where('visibility', 'published')->orderBy('id')
                    ->get(['id', 'product_id', 'rating', 'updated_at']),
                DB::table('product_review_replies')->orderBy('id')
                    ->get(['id', 'product_review_id', 'updated_at']),
            ],
            'notifications' => DB::table('notifications')->where('user_id', $id)->orderBy('id')->get(['id', 'read_at']),
        };
        return $this->respond($rows);
    }

    public function seller(Request $request, string $scope): JsonResponse
    {
        abort_unless($request->user()?->role === 'seller', 403);
        abort_unless(in_array($scope, ['dashboard', 'shipments', 'products', 'feedback', 'notifications'], true), 404);
        $id = $request->user()->id;
        $orders = fn () => $this->orders(Order::where('seller_id', $id));
        $products = fn () => DB::table('products')->where('seller_id', $id)->orderBy('id')
            ->get(['id', 'status', 'stock', 'price', 'updated_at']);
        $reviews = fn () => [
            DB::table('product_reviews')->where('seller_id', $id)->orderBy('id')
                ->get(['id', 'rating', 'visibility', 'updated_at']),
            DB::table('product_review_replies')->where('seller_id', $id)->orderBy('id')
                ->get(['id', 'product_review_id', 'updated_at']),
        ];
        $images = fn () => DB::table('product_images')->whereIn('product_id',
            DB::table('products')->where('seller_id', $id)->select('id'))
            ->orderBy('id')->get(['id', 'product_id', 'path', 'sort_order']);
        $rows = match ($scope) {
            'dashboard' => [$orders(), $products(), $images(), $reviews()],
            'shipments' => $orders(),
            'products' => [$products(), $images()],
            'feedback' => $reviews(),
            'notifications' => DB::table('notifications')->where('user_id', $id)->orderBy('id')->get(['id', 'read_at']),
        };
        return $this->respond($rows);
    }

    public function admin(Request $request, string $scope): JsonResponse
    {
        abort_unless($request->user('admin')?->role === 'admin', 403);
        abort_unless(in_array($scope, ['dashboard', 'cases', 'products', 'accounts', 'notifications'], true), 404);
        $orders = fn () => $this->orders(Order::query());
        $products = fn () => DB::table('products')->orderBy('id')->get(['id', 'status', 'stock', 'price', 'updated_at']);
        $cases = fn () => DB::table('complaints')->orderBy('id')->get(['id', 'status', 'updated_at']);
        $rows = match ($scope) {
            'dashboard' => [$orders(), $products(), $cases(),
                DB::table('users')->whereIn('role', ['buyer', 'seller'])->orderBy('id')->get(['id', 'role', 'status', 'updated_at']),
                DB::table('notifications')->whereNull('user_id')->where('type', '!=', 'new_order')->orderBy('id')->get(['id', 'read_at']),
                DB::table('announcements')->orderBy('id')->get(['id', 'status', 'updated_at'])],
            'cases' => $cases(),
            'accounts' => DB::table('users')->whereIn('role', ['buyer', 'seller'])->orderBy('id')
                ->get(['id', 'role', 'status', 'account_status', 'updated_at']),
            'products' => [$products(), DB::table('product_images')->orderBy('id')->get(['id', 'product_id', 'path', 'sort_order'])],
            'notifications' => DB::table('notifications')->whereNull('user_id')->where('type', '!=', 'new_order')->orderBy('id')->get(['id', 'read_at']),
        };
        return $this->respond($rows);
    }

    private function orders($query)
    {
        return $query->orderBy('id')->get(['id', 'status', 'seller_id', 'buyer_id', 'total_amount',
            'carrier_name', 'carrier_tracking_number', 'courier_id', 'updated_at']);
    }

    private function respond($rows): JsonResponse
    {
        return response()->json(['revision' => hash('sha256', json_encode($rows))])
            ->header('Cache-Control', 'no-store, private');
    }
}
