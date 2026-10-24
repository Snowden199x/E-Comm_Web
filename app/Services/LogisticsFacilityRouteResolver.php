<?php

namespace App\Services;

use App\Models\Profiles\LogisticsFacility;

class LogisticsFacilityRouteResolver
{
    /** @return array<int, LogisticsFacility> */
    public function between(LogisticsFacility $origin, LogisticsFacility $destination): array
    {
        $originHub = $origin->parent;
        $destinationHub = $destination->parent;
        if (! $originHub || ! $destinationHub) {
            return [];
        }

        if ($origin->is($destination)) {
            return [$origin];
        }

        if ((int) $origin->logistics_company_id === (int) $destination->logistics_company_id
            && $originHub->is($destinationHub)) {
            return [$origin, $originHub, $destination];
        }

        return [$origin, $originHub, $destinationHub, $destination];
    }
}
