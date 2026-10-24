<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActionLog;
use App\Models\Profiles\LogisticsFacility;
use App\Services\OrderRoutingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LogisticsCoverageController extends Controller
{
    public function index(): View
    {
        $this->requireSuperAdmin();
        $stations = LogisticsFacility::query()->where('kind', 'station_hub')
            ->where('is_active', true)->whereHas('parent', fn ($query) => $query->where('is_active', true))
            ->whereHas('company', fn ($query) => $query->where('status', 'approved')
                ->whereHas('logisticsCenter.user', fn ($user) => $user->where('status', 'approved')
                    ->whereNull('archived_at')->where(fn ($active) => $active->whereNull('account_status')->orWhere('account_status', 'active'))))
            ->with('company')->orderBy('province')->orderBy('municipality')->orderBy('name')->get();
        $primary = DB::table('primary_logistics_coverage')->get()
            ->keyBy(fn ($row) => $row->province_code.':'.$row->municipality_code);

        return view('admin.logistics-coverage.index', [
            'areas' => $stations->groupBy(fn ($station) => $station->province_code.':'.$station->municipality_code),
            'primary' => $primary,
        ]);
    }

    public function update(Request $request, OrderRoutingService $routing): RedirectResponse
    {
        $this->requireSuperAdmin();
        $data = $request->validate([
            'province_code' => ['required', 'regex:/^[0-9]{9}$/'],
            'municipality_code' => ['required', 'regex:/^[0-9]{9}$/'],
            'station_hub_id' => ['required', 'integer', 'exists:logistics_facilities,id'],
        ]);
        $station = LogisticsFacility::query()->whereKey($data['station_hub_id'])
            ->where('kind', 'station_hub')->where('is_active', true)
            ->where('province_code', $data['province_code'])
            ->where('municipality_code', $data['municipality_code'])
            ->whereHas('parent', fn ($query) => $query->where('is_active', true))
            ->whereHas('company', fn ($query) => $query->where('status', 'approved')
                ->whereHas('logisticsCenter.user', fn ($user) => $user->where('status', 'approved')
                    ->whereNull('archived_at')->where(fn ($active) => $active->whereNull('account_status')->orWhere('account_status', 'active'))))
            ->with('company.logisticsCenter.user')->first();
        if (! $station) {
            throw ValidationException::withMessages(['station_hub_id' => 'Choose an active station in this municipality.']);
        }

        DB::transaction(function () use ($data, $station) {
            DB::table('primary_logistics_coverage')->upsert([
                'province_code' => $data['province_code'], 'municipality_code' => $data['municipality_code'],
                'station_hub_id' => $station->id, 'assigned_by' => auth('admin')->id(),
                'created_at' => now(), 'updated_at' => now(),
            ], ['province_code', 'municipality_code'], ['station_hub_id', 'assigned_by', 'updated_at']);
            AdminActionLog::record($station->company->logisticsCenter->user, 'logistics_primary_set',
                $station->province.' / '.$station->municipality.' → '.$station->name);
        });

        $routing->routeUnresolvedReady();

        return back()->with('success', 'Primary Logistics station saved. Unresolved ready orders were retried; new Rider applications use this station.');
    }

    private function requireSuperAdmin(): void
    {
        abort_unless(auth('admin')->user()?->is_super_admin, 403, 'Superuser access only.');
    }
}
