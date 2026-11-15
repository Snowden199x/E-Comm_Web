<?php

namespace App\Support;

use Illuminate\Contracts\Database\Query\Builder;

/**
 * Shared search rules for the Seller pages, so every page finds an order the same way.
 */
class SellerSearch
{
    /** Tidy what the seller typed: trim, collapse spaces, drop a leading #. */
    public static function clean(?string $term): string
    {
        return ltrim(trim((string) preg_replace('/\s+/u', ' ', (string) $term)), '#');
    }

    /** A LIKE pattern where %, _ and \ in the term are matched as plain characters. */
    public static function like(string $term): string
    {
        return '%'.addcslashes($term, '\\%_').'%';
    }

    /**
     * Add "this order matches" conditions to an OR group on the orders table.
     * Matches the order number the seller sees (ORD-2026-0005), both tracking numbers,
     * and old VN-000005 numbers. Call it inside a where(function ($q) { ... }) group.
     */
    public static function orOrderColumns(Builder $group, string $term): void
    {
        $like = self::like($term);
        $group->orWhere('orders.order_number', 'like', $like)
            ->orWhere('orders.tracking_number', 'like', $like)
            ->orWhere('orders.carrier_tracking_number', 'like', $like);

        if (preg_match('/^VN-?0*(\d+)$/i', $term, $match)) {
            $group->orWhere('orders.id', (int) $match[1]);
        }
    }

    /** Same match, for a page that lists something belonging to an order (reviews, returns, complaints). */
    public static function orWhereOrder(Builder $group, string $term): void
    {
        $group->orWhereHas('order', fn ($order) => $order->where(fn ($inner) => self::orOrderColumns($inner, $term)));
    }
}
