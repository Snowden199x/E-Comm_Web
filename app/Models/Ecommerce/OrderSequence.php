<?php

namespace App\Models\Ecommerce;

use Illuminate\Support\Facades\DB;

/**
 * Yearly counter behind the Seller-visible `ORD-YYYY-NNNN` order numbers. The row is locked while a
 * new order is created, so two checkouts in the same second cannot take the same number.
 */
class OrderSequence
{
    public static function next(): string
    {
        $year = (int) now()->year;

        DB::table('order_sequences')->insertOrIgnore([
            'year' => $year, 'last_number' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $row = DB::table('order_sequences')->where('year', $year)->lockForUpdate()->first();
        $next = (int) ($row->last_number ?? 0) + 1;

        DB::table('order_sequences')->where('year', $year)->update([
            'last_number' => $next, 'updated_at' => now(),
        ]);

        return sprintf('ORD-%d-%04d', $year, $next);
    }
}
