<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['rate'])]
class CommissionSetting extends Model
{
    /** The rate used for orders placed before each order saved its own seller commission rate. */
    public const FIXED_RATE = 10.00;

    /** The seller commission Vendo currently applies to new orders (editable on the Admin Delivery Fees page). */
    public static function currentRate(): float
    {
        return DeliveryFeeSetting::sellerCommissionRate();
    }

    /**
     * SQL for an order's seller commission rate: the rate saved on the order, or the fixed rate for older
     * orders. Amounts are calculated per order so a rate change never alters past commissions.
     */
    public static function orderRateSql(string $ordersTable = 'orders'): string
    {
        return 'COALESCE('.$ordersTable.'.seller_commission_rate, '.self::FIXED_RATE.')';
    }
}
