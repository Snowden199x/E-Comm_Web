<?php

namespace App\Http\Middleware;

use App\Models\Profiles\LogisticsCompany;
use App\Models\Profiles\LogisticsFacility;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLogisticsPortalRole
{
    public function handle(Request $request, Closure $next, string $portal): Response
    {
        $user = $request->user();
        abort_unless($user && $user->status === 'approved' && ! $user->archived_at
            && (! $user->account_status || $user->account_status === 'active'), 403,
            'Your Logistics account is not active.');

        if ($portal === 'company') {
            abort_unless($user->role === 'logistics_center'
                && $user->logisticsCenterDetail?->company?->status === 'approved', 403,
                'Company Admin access only.');
            return $next($request);
        }

        $role = $portal === 'province' ? 'province_hub' : 'station_hub';
        abort_unless($user->role === $role, 403, ucfirst($portal).' Hub access only.');
        $membership = $user->facilityStaff()->with('facility.company.logisticsCenter.user', 'facility.parent.company.logisticsCenter.user')->first();
        abort_unless($membership && $membership->facility?->is_active, 403, 'An active facility assignment is required.');

        $company = $membership->facility->company;
        if (! $company && $membership->facility->parent) {
            $company = $membership->facility->parent->company;
        }
        abort_unless($company instanceof LogisticsCompany
            && $company->status === 'approved'
            && $company->logisticsCenter?->user?->status === 'approved'
            && in_array($company->logisticsCenter?->user?->account_status, [null, 'active'], true)
            && ! $company->logisticsCenter?->user?->archived_at, 403,
            'The parent Logistics company is not active.');

        $expectedKind = $portal === 'province' ? ['province_hub', 'main_hub'] : ['station_hub'];
        abort_unless(in_array($membership->facility->kind, $expectedKind, true), 403, 'Facility role does not match this portal.');
        $request->attributes->set('logistics_facility', $membership->facility);
        $request->attributes->set('logistics_company', $company);

        return $next($request);
    }
}
