<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Model;

class ProductReviewModerationEvent extends Model
{
    protected $fillable = ['product_review_id', 'admin_id', 'from_visibility', 'to_visibility', 'reason'];

    public function review()
    {
        return $this->belongsTo(ProductReview::class, 'product_review_id');
    }
}
