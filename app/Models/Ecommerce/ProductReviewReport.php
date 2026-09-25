<?php

namespace App\Models\Ecommerce;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ProductReviewReport extends Model
{
    protected $fillable = [
        'product_review_id', 'seller_id', 'reason', 'status', 'resolution_note', 'resolved_by', 'resolved_at',
    ];

    protected $casts = ['resolved_at' => 'datetime'];

    public function review()
    {
        return $this->belongsTo(ProductReview::class, 'product_review_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}
