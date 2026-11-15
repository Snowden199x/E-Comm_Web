<?php

namespace App\Models\Profiles;

use App\Models\Ecommerce\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * A Rider accepting an assignment from the Station Hub. The answer is kept apart
 * from `rider_tasks` because a hub can reassign a parcel and expire the task
 * while the Rider's answer should still stand.
 */
class RiderAssignmentAcceptance extends Model
{
    protected $fillable = ['rider_id', 'order_id', 'assignment', 'accepted_at'];

    protected function casts(): array
    {
        return ['accepted_at' => 'datetime'];
    }

    public function rider()
    {
        return $this->belongsTo(User::class, 'rider_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
