<?php

namespace App\Models\Ecommerce;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    public const STATUSES = [
        'placed' => 'Placed', 'confirmed' => 'Confirmed', 'preparing' => 'Preparing',
        'ready_for_pickup' => 'Ready for Pickup', 'picked_up' => 'Picked Up',
        'at_sorting_center' => 'At Sorting Center', 'sorted' => 'Sorted',
        'assigned_to_rider' => 'Assigned to Rider', 'out_for_delivery' => 'Out for Delivery',
        'delivered' => 'Delivered', 'completed' => 'Completed', 'delivery_failed' => 'Delivery Failed',
        'returned' => 'Returned', 'cancelled' => 'Cancelled',
    ];

    public const SELLER_GROUPS = [
        'new' => ['placed'], 'pack' => ['confirmed', 'preparing'], 'pickup' => ['ready_for_pickup'],
        'pending' => ['picked_up', 'at_sorting_center', 'sorted', 'assigned_to_rider', 'out_for_delivery', 'delivery_failed'],
        'completed' => ['delivered', 'completed'], 'cancelled' => ['cancelled'], 'returned' => ['returned'],
    ];

    public const SHIPMENT_GROUPS = [
        'to_ship' => ['confirmed', 'preparing', 'ready_for_pickup'],
        'in_transit' => ['picked_up', 'at_sorting_center', 'sorted', 'assigned_to_rider', 'out_for_delivery', 'delivery_failed'],
        'delivered' => ['delivered', 'completed'], 'cancelled' => ['cancelled'], 'returned' => ['returned'],
    ];

    public const SHIPMENT_LABELS = ['to_ship' => 'To Ship', 'in_transit' => 'In Transit', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled', 'returned' => 'Returned'];

    protected $casts = ['shipping_fee' => 'decimal:2', 'estimated_delivery_from' => 'date', 'estimated_delivery_to' => 'date'];

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            $order->tracking_number ??= 'VND-'.Str::ulid();
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

    protected $fillable = [
        'buyer_id',
        'seller_id',
        'courier_id',
        'total_amount',
        'status',
        'payment_mode',
        'shipping_address',
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

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
