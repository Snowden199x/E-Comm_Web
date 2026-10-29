<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class DeliveryFeeSetting extends Model
{
    /** Keeps today's behavior until the Vendo Admin changes it: Vendo takes no delivery fee share. */
    public const DEFAULT_VENDO_DELIVERY_COMMISSION_RATE = 0.00;

    /** Matches the fixed 10% product commission already used by the Admin reports. */
    public const DEFAULT_SELLER_COMMISSION_RATE = 10.00;

    protected $fillable = ['vendo_delivery_commission_rate', 'seller_commission_rate', 'updated_by'];

    protected function casts(): array
    {
        return [
            'vendo_delivery_commission_rate' => 'decimal:2',
            'seller_commission_rate' => 'decimal:2',
        ];
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** The single settings row; created with defaults if it is somehow missing. */
    public static function current(): self
    {
        return static::query()->first() ?? static::query()->create([
            'vendo_delivery_commission_rate' => self::DEFAULT_VENDO_DELIVERY_COMMISSION_RATE,
            'seller_commission_rate' => self::DEFAULT_SELLER_COMMISSION_RATE,
        ]);
    }

    public static function vendoDeliveryCommissionRate(): float
    {
        return (float) static::current()->vendo_delivery_commission_rate;
    }

    public static function sellerCommissionRate(): float
    {
        return (float) static::current()->seller_commission_rate;
    }
}
