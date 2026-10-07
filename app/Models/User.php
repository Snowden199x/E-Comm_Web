<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\AdminLoginSession;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Models\Ecommerce\Product;
use App\Models\Compliance\ProductWarning;
use App\Models\Compliance\ProductViolation;
use App\Models\Profiles\SellerDetail;
use App\Models\Profiles\BuyerDetail;
use App\Models\Profiles\CourierDetail;

#[Fillable(['name', 'first_name', 'last_name', 'middle_initial', 'email', 'recovery_email', 'password', 'role', 'status', 'phone_number', 'rejection_reason', 'rejection_notes', 'suspension_reason', 'suspension_notes', 'suspended_at', 'suspended_until', 'is_super_admin', 'must_change_password', 'account_status', 'profile_picture', 'last_login_at', 'temp_password_plain', 'archived_at'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'suspended_at' => 'datetime',
            'suspended_until' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function fullName(): Attribute
    {
        return Attribute::make(
            get: fn() => trim($this->first_name . ' ' . ($this->middle_initial ? $this->middle_initial . '. ' : '') . $this->last_name),
        );
    }

    public function daysRemaining(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->suspended_until && $this->suspended_until->isFuture()
                ? (int) ceil(now()->floatDiffInDays($this->suspended_until))
                : 0,
        );
    }

    public function routeNotificationForMail($notification = null)
    {
        return $this->recovery_email ?: $this->email;
    }

    public function sellerDetail()
    {
        return $this->hasOne(SellerDetail::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'seller_categories');
    }

    public function courierDetail()
    {
        return $this->hasOne(CourierDetail::class);
    }

    public function logisticsCenterDetail()
    {
        return $this->hasOne(\App\Models\Profiles\LogisticsCenter::class);
    }

    public function buyerDetail()
    {
        return $this->hasOne(BuyerDetail::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'seller_id');
    }

    public function productWarnings()
    {
        return $this->hasMany(ProductWarning::class, 'seller_id');
    }

    public function productViolations()
    {
        return $this->hasMany(ProductViolation::class, 'seller_id');
    }

    public function complianceScore(): Attribute
    {
        return Attribute::make(
            get: fn() => max(0, 100 - ($this->productViolations()->count() * 10)),
        );
    }

    public function orders()
    {
        return $this->hasMany(\App\Models\Ecommerce\Order::class, 'seller_id');
    }

    public function conversations()
    {
        return $this->hasMany(\App\Models\Communication\Conversation::class);
    }

    public function loginSessions()
    {
        return $this->hasMany(AdminLoginSession::class)
            ->latest('login_at');
    }

    public function isOnline(): bool
    {
        return $this->loginSessions()
            ->whereNull('logged_out_at')
            ->exists();
    }
}
