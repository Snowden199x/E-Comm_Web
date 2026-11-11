<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuyerAddress extends Model
{
    protected $fillable = [
        'user_id',
        'label',
        'house_no',
        'street',
        'barangay',
        'municipality',
        'province',
        'zip_code',
        'latitude',
        'longitude',
        'is_default',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** One-line address used on the account page and at checkout. */
    public function fullLine(): string
    {
        return trim(implode(', ', array_filter([
            $this->house_no,
            $this->street,
            $this->barangay,
            $this->municipality,
            $this->province,
            $this->zip_code,
        ])));
    }
}
