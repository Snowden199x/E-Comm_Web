<?php

namespace App\Services;

use App\Models\Ecommerce\Order;
use App\Models\Profiles\LogisticsCenter;

class OrderRoutingService
{
    public function __construct(private LocationCatalog $locations) {}

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
            $changes['logistics_center_id'] = $this->centerFor($seller->province, $seller->municipality)?->id;
        }
        if (! $order->destination_logistics_center_id && $order->shipping_province && $order->shipping_city) {
            $changes['destination_logistics_center_id'] = $this->centerFor($order->shipping_province, $order->shipping_city)?->id;
        }
        $order->fill(array_filter($changes, fn ($id) => $id !== null))->save();
    }

    private function centerFor(string $province, string $city): ?LogisticsCenter
    {
        $matches = LogisticsCenter::query()->whereHas('user', fn ($query) => $query
            ->where('role', 'logistics_center')->where('status', 'approved')
            ->whereNull('archived_at')
            ->where(fn ($active) => $active->whereNull('account_status')->orWhere('account_status', 'active')))
            ->get()->filter(fn ($center) => $this->locations->normalize($center->province) === $this->locations->normalize($province));
        $sameCity = $matches->filter(fn ($center) => $this->locations->normalize($center->municipality) === $this->locations->normalize($city));

        if ($sameCity->count() === 1) {
            return $sameCity->first();
        }

        return $matches->count() === 1 ? $matches->first() : null;
    }
}
