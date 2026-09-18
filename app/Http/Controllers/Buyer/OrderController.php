<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Order;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('items.product')
            ->where('buyer_id', auth()->id())
            ->latest()
            ->get();

        return view('buyer.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        abort_unless($order->buyer_id === auth()->id(), 403);

        $order->load('items.product', 'seller');

        return view('buyer.orders.show', compact('order'));
    }
}
