<?php

namespace App\Models\Communication;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class MarketplaceMessage extends Model
{
    protected $fillable = ['sender_id', 'order_item_id', 'shared_order_id', 'body', 'attachment_path', 'attachment_name', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];

    public function conversation()
    {
        return $this->belongsTo(MarketplaceConversation::class, 'marketplace_conversation_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function item()
    {
        return $this->belongsTo(\App\Models\Ecommerce\OrderItem::class, 'order_item_id');
    }

    public function sharedOrder()
    {
        return $this->belongsTo(\App\Models\Ecommerce\Order::class, 'shared_order_id');
    }
}
