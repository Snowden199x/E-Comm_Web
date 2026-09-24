<?php
namespace App\Models\Ecommerce;
use Illuminate\Database\Eloquent\Model;
class OrderStatusEvent extends Model
{
    protected $fillable = ['user_id', 'from_status', 'to_status', 'note'];
}
