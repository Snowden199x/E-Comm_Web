<?php

namespace App\Services;

use App\Models\Ecommerce\Order;

class OrderRoutingService
{
    public function __construct(private LogisticsCenterMatcher $centers) {}

    public function routeUnresolvedReady(): int
    {
        $count = 0;
        Order::query()->where('status', 'ready_for_pickup')
            ->where(fn ($query) => $query->whereNull('logistics_center_id')->orWhereNull('destination_logistics_center_id'))
            ->with('seller.sellerDetail')
            ->chunkById(100, function ($orders) use (&$count) {
                foreach ($orders as $order) {
                    $this->route($order);
                    $count++;
                }
            });

        return $count;
    }

    public function route(Order $order): void
    {
        $order->loadMissing('seller.sellerDetail');
        $seller = $order->seller?->sellerDetail;
        $changes = [];
        if (! $order->logistics_center_id && $seller) {
            $changes['logistics_center_id'] = $this->centers->forAddress($seller->province, $seller->municipality)?->id;
        }
        if (! $order->destination_logistics_center_id && $order->shipping_province && $order->shipping_city) {
            $changes['destination_logistics_center_id'] = $this->centers->forAddress($order->shipping_province, $order->shipping_city)?->id;
        }
        $order->fill(array_filter($changes, fn ($id) => $id !== null))->save();
    }

}
