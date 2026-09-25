<?php

namespace App\Models\Ecommerce;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ProductReview extends Model
{
    protected $fillable = [
        'order_item_id', 'order_id', 'product_id', 'seller_id', 'buyer_id',
        'rating', 'comment', 'visibility', 'moderated_by', 'moderated_at', 'moderation_reason',
    ];

    protected $casts = ['rating' => 'integer', 'moderated_at' => 'datetime'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function reply()
    {
        return $this->hasOne(ProductReviewReply::class);
    }

    public function report()
    {
        return $this->hasOne(ProductReviewReport::class);
    }

    public function moderationEvents()
    {
        return $this->hasMany(ProductReviewModerationEvent::class);
    }
}
