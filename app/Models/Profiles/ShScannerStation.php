<?php

namespace App\Models\Profiles;

use Illuminate\Database\Eloquent\Model;

class ShScannerStation extends Model
{
    protected $fillable = ['logistics_center_id', 'checkpoint_id', 'name'];

    protected $casts = [
        'pairing_expires_at' => 'datetime',
        'token_expires_at' => 'datetime',
        'paired_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function checkpoint()
    {
        return $this->belongsTo(LogisticsRouteCheckpoint::class, 'checkpoint_id');
    }

    public function logisticsCenter()
    {
        return $this->belongsTo(LogisticsCenter::class);
    }
}
