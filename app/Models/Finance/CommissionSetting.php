<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['rate'])]
class CommissionSetting extends Model
{
    public static function currentRate(): float
    {
        return static::first()?->rate ?? 10.00;
    }
}