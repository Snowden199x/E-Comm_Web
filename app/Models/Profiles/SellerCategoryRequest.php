<?php

namespace App\Models\Profiles;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SellerCategoryRequest extends Model
{
    protected $fillable = ['seller_id', 'status', 'permit_path', 'note', 'admin_note', 'reviewed_by', 'reviewed_at'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'seller_category_request_categories', 'request_id', 'category_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
