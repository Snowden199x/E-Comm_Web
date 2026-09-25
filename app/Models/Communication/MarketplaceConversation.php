<?php

namespace App\Models\Communication;

use App\Models\Ecommerce\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class MarketplaceConversation extends Model
{
    protected $fillable = ['order_id', 'buyer_id', 'seller_id', 'last_message_at'];

    protected $casts = ['last_message_at' => 'datetime'];

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

    public function messages()
    {
        return $this->hasMany(MarketplaceMessage::class)->orderBy('id');
    }

    public function latestMessage()
    {
        return $this->hasOne(MarketplaceMessage::class)->latestOfMany();
    }
}
