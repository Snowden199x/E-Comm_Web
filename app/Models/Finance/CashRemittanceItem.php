<?php

namespace App\Models\Finance;

use App\Models\Ecommerce\Order;
use Illuminate\Database\Eloquent\Model;

class CashRemittanceItem extends Model
{
    protected $fillable = ['remittance_id', 'order_id', 'level', 'amount'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function remittance()
    {
        return $this->belongsTo(CashRemittance::class, 'remittance_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
