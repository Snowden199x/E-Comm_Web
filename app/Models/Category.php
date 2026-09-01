<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['parent_id', 'name'];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function getColorsAttribute(): array
    {
        $map = [
            'Pet Supplies' => ['border' => '#6B4F2A', 'bg' => '#F1E6D2'],
            'Electronics and Gadgets' => ['border' => '#315A8A', 'bg' => '#DCE8F5'],
            "Women's Apparel" => ['border' => '#A34A6F', 'bg' => '#F4DDE7'],
            "Men's Apparel" => ['border' => '#3F5D75', 'bg' => '#DDE7EF'],
            'Kids and Baby' => ['border' => '#C56A35', 'bg' => '#F7E1D1'],
            'Home and Garden' => ['border' => '#4E7650', 'bg' => '#DDEBDD'],
            'Sports and Outdoors' => ['border' => '#237A6A', 'bg' => '#D8ECE7'],
            'Health and Beauty' => ['border' => '#7A5685', 'bg' => '#E9DDEC'],
            'Makeup & Cosmetics' => ['border' => '#B14F68', 'bg' => '#F3DCE2'],
            'Books and Media' => ['border' => '#705A3A', 'bg' => '#EEE5D5'],
            'Food and Gourmet' => ['border' => '#A65C2B', 'bg' => '#F4E0CF'],
            'Automotive & Motorcycle' => ['border' => '#7A3E3E', 'bg' => '#F0DADA'],
            'Furniture and Office Equipment' => ['border' => '#665A50', 'bg' => '#E7E1DC'],
            'Jewelry and Watches' => ['border' => '#8A6A25', 'bg' => '#F3E8C8'],
            'Office and School Supplies' => ['border' => '#486B7A', 'bg' => '#DDE9ED'],
        ];

        return $map[$this->name] ?? ['border' => '#6B7280', 'bg' => '#F3F4F6'];
    }
}