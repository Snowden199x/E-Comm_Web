<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['rate'])]
class CommissionSetting extends Model
{
    public const FIXED_RATE = 10.00;

    public static function currentRate(): float
    {
        return self::FIXED_RATE;
    }
}
