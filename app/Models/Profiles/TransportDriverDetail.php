<?php

namespace App\Models\Profiles;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TransportDriverDetail extends Model
{
    /** The driver applied to a Station Hub; that Station Hub approves. */
    public const APPLY_STATION = 'station';

    /** The driver applied to a Province Hub; that Province Hub approves. */
    public const APPLY_PROVINCE = 'province';

    /** The driver applied to both; only the Province Hub approves. */
    public const APPLY_BOTH = 'both';

    public const APPLY_SCOPES = [self::APPLY_STATION, self::APPLY_PROVINCE, self::APPLY_BOTH];

    protected $fillable = [
        'user_id', 'province_hub_id', 'apply_to', 'station_hub_id',
        'license_number', 'license_category', 'license_expires_at',
        'drivers_license_path', 'valid_id_path', 'status',
        'approved_by', 'approved_at',
        'birthday', 'sex', 'home_address', 'vehicle_type', 'plate_number',
    ];

    protected function casts(): array
    {
        return [
            'license_expires_at' => 'date',
            'birthday' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function provinceHub()
    {
        return $this->belongsTo(LogisticsFacility::class, 'province_hub_id');
    }

    public function stationHub()
    {
        return $this->belongsTo(LogisticsFacility::class, 'station_hub_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function trips()
    {
        return $this->hasMany(LogisticsTransportTrip::class, 'driver_id');
    }

    /** A station-only driver never leaves its Station Hub, so it cannot take trips. */
    public function worksStationToStation(): bool
    {
        return $this->apply_to === self::APPLY_STATION;
    }

    /** The hub whose staff approves this application. */
    public function reviewingHub(): ?LogisticsFacility
    {
        return $this->worksStationToStation() ? $this->stationHub : $this->provinceHub;
    }

    public function applyLabel(): string
    {
        return match ($this->apply_to) {
            self::APPLY_STATION => 'Station Hub only',
            self::APPLY_BOTH => 'Station Hub and Province Hub',
            default => 'Province Hub only',
        };
    }
}
