<?php

namespace App\Models\Finance;

use App\Models\Profiles\LogisticsCompany;
use App\Models\Profiles\LogisticsFacility;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CashRemittance extends Model
{
    public const LEVEL_RIDER_TO_STATION = 'rider_to_station';
    public const LEVEL_STATION_TO_PROVINCE = 'station_to_province';
    public const LEVEL_PROVINCE_TO_COMPANY = 'province_to_company';
    public const LEVEL_COMPANY_TO_VENDO = 'company_to_vendo';

    public const LEVELS = [
        self::LEVEL_RIDER_TO_STATION => 'Rider to Station Hub',
        self::LEVEL_STATION_TO_PROVINCE => 'Station Hub to Province Hub',
        self::LEVEL_PROVINCE_TO_COMPANY => 'Province Hub to Company',
        self::LEVEL_COMPANY_TO_VENDO => 'Company to Vendo',
    ];

    /** The order money_status while a batch at this level is in flight (sent, not yet confirmed). */
    public const SENT_MONEY_STATUS = [
        self::LEVEL_STATION_TO_PROVINCE => 'remitted_to_station',
        self::LEVEL_PROVINCE_TO_COMPANY => 'remitted_to_province',
        self::LEVEL_COMPANY_TO_VENDO => 'received_by_company',
    ];

    /**
     * The order money_status once the receiver at this level confirms the batch. When Vendo confirms
     * a Company's batch the Seller is credited automatically (no real payout), so there is no
     * separate resting state between that confirmation and credited_to_seller.
     */
    public const RECEIVED_MONEY_STATUS = [
        self::LEVEL_RIDER_TO_STATION => 'remitted_to_station',
        self::LEVEL_STATION_TO_PROVINCE => 'remitted_to_province',
        self::LEVEL_PROVINCE_TO_COMPANY => 'received_by_company',
        self::LEVEL_COMPANY_TO_VENDO => 'credited_to_seller',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_DISCREPANCY = 'discrepancy';
    public const STATUS_RESOLVED = 'resolved';

    public const STATUSES = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_RECEIVED => 'Received',
        self::STATUS_DISCREPANCY => 'Discrepancy',
        self::STATUS_RESOLVED => 'Resolved',
    ];

    protected $fillable = [
        'reference', 'level', 'from_rider_user_id', 'from_facility_id', 'to_facility_id', 'logistics_company_id',
        'expected_amount', 'received_amount', 'status', 'submitted_by', 'verified_by',
        'submitted_at', 'verified_at', 'note', 'resolution_note', 'resolved_at', 'resolved_by',
    ];

    protected function casts(): array
    {
        return [
            'expected_amount' => 'decimal:2',
            'received_amount' => 'decimal:2',
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function items()
    {
        return $this->hasMany(CashRemittanceItem::class, 'remittance_id');
    }

    public function fromRider()
    {
        return $this->belongsTo(User::class, 'from_rider_user_id');
    }

    public function fromFacility()
    {
        return $this->belongsTo(LogisticsFacility::class, 'from_facility_id');
    }

    public function toFacility()
    {
        return $this->belongsTo(LogisticsFacility::class, 'to_facility_id');
    }

    public function company()
    {
        return $this->belongsTo(LogisticsCompany::class, 'logistics_company_id');
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
