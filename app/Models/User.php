<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'status', 'phone_number','rejection_reason'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
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

    public function buyerDetail()
    {
        return $this->hasOne(BuyerDetail::class);
    }
}