<?php

namespace App\Models\Profiles;

use App\Models\Ecommerce\Order;
use Illuminate\Database\Eloquent\Model;

class LogisticsParcelException extends Model
{
    protected $fillable = [
        'order_id', 'facility_id', 'reported_by', 'reason', 'details', 'status',
        'resolved_by', 'resolution_note', 'resolved_at',
    ];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function facility()
    {
        return $this->belongsTo(LogisticsFacility::class, 'facility_id');
    }
}
