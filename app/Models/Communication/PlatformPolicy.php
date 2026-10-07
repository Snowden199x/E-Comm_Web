<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'version', 'content'])]
class PlatformPolicy extends Model
{
    public static function availableForRole(string $role): \Illuminate\Database\Eloquent\Collection
    {
        $name = match ($role) {
            'seller' => 'Seller Policy',
            'buyer' => 'Buyer Policy',
            'logistics_center', 'courier' => 'Logistics Policy',
            default => null,
        };

        return static::query()->whereIn('name', $name ? [$name, 'Prohibited Item Policy'] : [])
            ->orderByRaw('CASE WHEN name = ? THEN 0 ELSE 1 END', [$name ?? ''])
            ->get()->filter(function (self $policy) {
                $text = html_entity_decode(strip_tags(\App\Support\PolicyContent::render($policy->content)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

                return trim(str_replace("\u{00A0}", ' ', $text)) !== '';
            })->values();
    }

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