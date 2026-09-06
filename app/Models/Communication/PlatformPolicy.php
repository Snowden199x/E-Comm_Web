<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'version', 'content'])]
class PlatformPolicy extends Model
{
    public function getIconAttribute(): array
    {
        $map = [
            'Seller Policy' => ['icon' => 'seller-policy-icon.svg', 'bg' => '#F3E8FF'],
            'Buyer Policy' => ['icon' => 'buyer-policy-icon.svg', 'bg' => '#FCE7F3'],
            'Logistics Policy' => ['icon' => 'logistics-policy-icon.svg', 'bg' => '#FFEDD5'],
            'Prohibited Item Policy' => ['icon' => 'prohibited-policy-icon.svg', 'bg' => '#F3F4F6'],
        ];

        return $map[$this->name] ?? ['icon' => 'seller-policy-icon.svg', 'bg' => '#F3F4F6'];
    }
}