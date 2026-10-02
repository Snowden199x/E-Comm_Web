<?php

namespace App\Models\Ecommerce;

use App\Models\User;
use App\Models\Profiles\LogisticsCenter;
use App\Models\Communication\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    public const STATUSES = [
        'placed' => 'Placed', 'confirmed' => 'Confirmed', 'preparing' => 'Preparing',
        'ready_for_pickup' => 'Ready for Pickup', 'picked_up' => 'Picked Up',
        'at_sorting_center' => 'At Sorting Center', 'sorted' => 'Sorted',
        'to_soc5' => 'On Route to SOC5', 'to_soc6' => 'On Route to SOC6',
        'in_transit_to_hub' => 'In Transit to Hub', 'at_destination_hub' => 'At Destination Hub',
        'assigned_to_rider' => 'Assigned to Rider', 'out_for_delivery' => 'Out for Delivery',
        'delivered' => 'Delivered', 'completed' => 'Completed', 'delivery_failed' => 'Delivery Failed',
        'returned' => 'Returned', 'cancelled' => 'Cancelled',
    ];

    public const SELLER_GROUPS = [
        'new' => ['placed'], 'pack' => ['confirmed', 'preparing'], 'pickup' => ['ready_for_pickup'],
        'pending' => ['picked_up', 'at_sorting_center', 'sorted', 'to_soc5', 'to_soc6', 'in_transit_to_hub', 'at_destination_hub', 'assigned_to_rider', 'out_for_delivery', 'delivery_failed'],
        'completed' => ['delivered', 'completed'], 'cancelled' => ['cancelled'], 'returned' => ['returned'],
    ];

    public const SHIPMENT_GROUPS = [
        'to_ship' => ['confirmed', 'preparing', 'ready_for_pickup'],
        'in_transit' => ['picked_up', 'at_sorting_center', 'sorted', 'to_soc5', 'to_soc6', 'in_transit_to_hub', 'at_destination_hub', 'assigned_to_rider', 'out_for_delivery', 'delivery_failed'],
        'delivered' => ['delivered', 'completed'], 'cancelled' => ['cancelled'], 'returned' => ['returned'],
    ];

    public const SHIPMENT_LABELS = ['to_ship' => 'To Ship', 'in_transit' => 'In Transit', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled', 'returned' => 'Returned'];

    protected $casts = ['shipping_fee' => 'decimal:2', 'estimated_delivery_from' => 'date', 'estimated_delivery_to' => 'date', 'delivered_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            $order->tracking_number ??= 'VND-'.Str::ulid();
        });

        static::saving(function (Order $order) {
            if ($order->isDirty('status') && $order->status === 'delivered') {
                $order->delivered_at = now();
            }
        });
        static::updated(function (Order $order) {
            if (! $order->wasChanged('status') || ! in_array($order->status, [
                'picked_up', 'at_sorting_center', 'sorted', 'to_soc5', 'to_soc6', 'in_transit_to_hub', 'at_destination_hub', 'assigned_to_rider',
                'out_for_delivery', 'delivered', 'completed', 'delivery_failed', 'returned',
            ], true)) {
                return;
            }

            Notification::create([
                'user_id' => $order->seller_id,
                'type' => 'shipment_update',
                'title' => 'Order '.$order->number.' updated',
                'message' => $order->shipmentUpdateMessage(),
                'link' => route('seller.orders.show', $order),
            ]);
            if (in_array($order->status, [
                'picked_up', 'at_sorting_center', 'sorted', 'to_soc5', 'to_soc6', 'in_transit_to_hub', 'at_destination_hub', 'assigned_to_rider', 'out_for_delivery',
                'delivered', 'completed', 'delivery_failed', 'returned',
            ], true)) {
                Notification::create([
                    'user_id' => $order->buyer_id,
                    'type' => 'order_update',
                    'title' => $order->number.': '.$order->status_label,
                    'message' => $order->shipmentUpdateMessage(),
                    'link' => route('buyer.orders.show', $order),
                ]);
            }
        });

        static::updated(function (Order $order) {
            if (! $order->wasChanged('status')) {
                return;
            }

            $conversation = $order->marketplaceConversation;
            if (! $conversation) {
                return;
            }

            $messages = [
                'confirmed' => 'The seller confirmed your order.',
                'preparing' => 'The seller is preparing your order.',
                'ready_for_pickup' => 'Your order is ready for courier pickup.',
                'picked_up' => 'The courier picked up your order.',
                'at_sorting_center' => 'Your order arrived at a sorting center.',
                'sorted' => 'Your order has been sorted for the next delivery step.',
                'to_soc5' => 'Your parcel was scanned after sorting. SOC5 is the next virtual route checkpoint.',
                'to_soc6' => 'The SOC5 route checkpoint was scanned. SOC6 is next.',
                'in_transit_to_hub' => 'Your order is on its way to the destination hub.',
                'at_destination_hub' => 'Your order arrived at the destination hub.',
                'assigned_to_rider' => 'A rider has been assigned to your order.',
                'out_for_delivery' => 'Your package is out for delivery.',
                'delivered' => 'Your order was marked as delivered. Check the order details if you need help.',
                'completed' => 'Your order is complete.',
                'delivery_failed' => 'The delivery attempt was unsuccessful. Check the order details for updates.',
                'returned' => 'Your order was returned.',
                'cancelled' => 'Your order was cancelled.',
            ];

            $conversation->messages()->create([
                'sender_id' => null,
                'shared_order_id' => $order->id,
                'body' => $messages[$order->status] ?? 'Order #'.$order->number.' is now '.$order->status_label.'.',
            ]);
            $conversation->update(['last_message_at' => now()]);
        });
    }

    public function getShipmentGroupAttribute(): string
    {
        foreach (self::SHIPMENT_GROUPS as $group => $statuses) {
            if (in_array($this->status, $statuses, true)) {
                return $group;
            }
        }

        return 'to_ship';
    }

    public function getRevisionAttribute(): string
    {
        return hash('sha256', json_encode($this->getRawOriginal()));
    }

    public const SALES_STATUSES = ['delivered', 'completed'];

    public function getNumberAttribute(): string
    {
        return 'VN-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    public function canPrintShippingLabel(): bool
    {
        return ! in_array($this->status, ['placed', 'confirmed', 'preparing', 'cancelled', 'returned'], true)
            && $this->logistics_center_id !== null
            && $this->tracking_number !== null;
    }

    public function shipmentUpdateMessage(): string
    {
        $origin = $this->logisticsCenter;
        $destination = $this->destinationLogisticsCenter;
        $place = fn (?LogisticsCenter $center) => $center
            ? $center->business_name.' ('.implode(', ', array_filter([$center->municipality, $center->province])).')'
            : 'the assigned logistics hub';

        return match ($this->status) {
            'picked_up' => 'Picked up by the rider assigned to '.$place($origin).'.',
            'at_sorting_center' => 'Arrived at '.$place($origin).' for sorting.',
            'sorted' => 'Sorted at '.$place($origin).'.',
            'to_soc5' => 'Scanned after sorting at '.$place($origin).'; next virtual route checkpoint: SOC5.',
            'to_soc6' => 'SOC5 virtual route checkpoint scanned; next: SOC6.',
            'in_transit_to_hub' => 'En route to '.$place($destination).'. Hub receipt is pending.',
            'at_destination_hub' => 'Received at '.$place($destination).'.',
            'assigned_to_rider' => 'A delivery rider from '.$place($destination).' has been assigned.',
            'out_for_delivery' => 'Out for delivery from '.$place($destination).'.',
            'delivered' => 'The assigned rider scanned this parcel as delivered from '.$place($destination).'. Buyer receipt confirmation is pending.',
            default => 'Order status changed to '.$this->status_label.'.',
        };
    }

    public function getSellerGroupAttribute(): string
    {
        foreach (self::SELLER_GROUPS as $group => $statuses) {
            if (in_array($this->status, $statuses, true)) {
                return $group;
            }
        }

        return 'pending';
    }

    public function statusEvents()
    {
        return $this->hasMany(OrderStatusEvent::class)->orderBy('id');
    }

    public function scanEvents()
    {
        return $this->hasMany(OrderScanEvent::class)->orderBy('id');
    }

    protected $fillable = [
        'buyer_id',
        'seller_id',
        'courier_id',
        'total_amount',
        'status',
        'payment_mode',
        'shipping_address',
        'shipping_province_code', 'shipping_city_code', 'shipping_province', 'shipping_city',
        'logistics_center_id', 'destination_logistics_center_id', 'route_plan_id', 'next_route_checkpoint_id', 'route_step',
        'tracking_number', 'carrier_name', 'carrier_tracking_number', 'estimated_delivery_from', 'estimated_delivery_to', 'shipping_fee',
    ];

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function courier()
    {
        return $this->belongsTo(User::class, 'courier_id');
    }

    public function linehaulRider()
    {
        return $this->belongsTo(User::class, 'linehaul_rider_id');
    }

    public function deliveryCourier()
    {
        return $this->belongsTo(User::class, 'delivery_courier_id');
    }

    public function logisticsCenter()
    {
        return $this->belongsTo(LogisticsCenter::class);
    }

    public function destinationLogisticsCenter()
    {
        return $this->belongsTo(LogisticsCenter::class, 'destination_logistics_center_id');
    }

    public function routePlan()
    {
        return $this->belongsTo(\App\Models\Profiles\LogisticsRoutePlan::class, 'route_plan_id');
    }

    public function nextRouteCheckpoint()
    {
        return $this->belongsTo(\App\Models\Profiles\LogisticsRouteCheckpoint::class, 'next_route_checkpoint_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function marketplaceConversation()
    {
        return $this->hasOne(\App\Models\Communication\MarketplaceConversation::class);
    }
}
