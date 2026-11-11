<?php

namespace App\Models\Ecommerce;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class OrderReturnRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING => 'Waiting for the seller',
        self::STATUS_APPROVED => 'Approved',
        self::STATUS_REJECTED => 'Declined',
    ];

    public const RETURN_STATUS_NOT_STARTED = 'not_started';
    public const RETURN_STATUS_PENDING_PICKUP = 'pending_pickup';
    public const RETURN_STATUS_PICKED_UP = 'picked_up';
    public const RETURN_STATUS_DELIVERED_TO_SELLER = 'delivered_to_seller';

    public const RETURN_STATUSES = [
        self::RETURN_STATUS_NOT_STARTED => 'Not scheduled',
        self::RETURN_STATUS_PENDING_PICKUP => 'Waiting for rider pickup',
        self::RETURN_STATUS_PICKED_UP => 'On the way back to the seller',
        self::RETURN_STATUS_DELIVERED_TO_SELLER => 'Handed back to the seller',
    ];

    public const REASONS = [
        'damaged' => 'Item arrived damaged',
        'wrong_item' => 'Wrong item was delivered',
        'missing_item' => 'Item is missing from the parcel',
        'not_as_described' => 'Item is not as described',
        'other' => 'Other',
    ];

    protected $fillable = [
        'order_id', 'buyer_id', 'seller_id', 'reason', 'details', 'status',
        'decided_by', 'decided_at', 'decision_note', 'refund_cents',
        'return_status', 'pickup_rider_id', 'picked_up_at', 'delivered_to_seller_at',
    ];

    protected function casts(): array
    {
        return [
            'decided_at' => 'datetime', 'refund_cents' => 'integer',
            'picked_up_at' => 'datetime', 'delivered_to_seller_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function decidedBy()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function getReasonLabelAttribute(): string
    {
        return self::REASONS[$this->reason] ?? ucfirst(str_replace('_', ' ', (string) $this->reason));
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function pickupRider()
    {
        return $this->belongsTo(User::class, 'pickup_rider_id');
    }

    public function getReturnStatusLabelAttribute(): string
    {
        return self::RETURN_STATUSES[$this->return_status] ?? ucfirst(str_replace('_', ' ', (string) $this->return_status));
    }
}
