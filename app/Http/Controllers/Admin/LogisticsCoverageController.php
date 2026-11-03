<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActionLog;
use App\Models\Ecommerce\Order;
use App\Models\Profiles\LogisticsFacility;
use App\Services\OrderRoutingService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LogisticsCoverageController extends Controller
{
    private const PER_PAGE = 15;

    /** Lower number = shown first. Rows that need an admin come before rows that are fine. */
    private const STATE_ORDER = ['inactive' => 0, 'needs' => 1, 'assigned' => 2, 'auto' => 3];

    public function index(Request $request): View
    {
        $this->requireSuperAdmin();

        $search = $request->string('q')->trim()->limit(100, '')->toString();
        $areas = $this->areas();

        $counts = [
            'needs' => $areas->whereIn('state', ['inactive', 'needs'])->count(),
            'assigned' => $areas->where('state', 'assigned')->count(),
            'auto' => $areas->where('state', 'auto')->count(),
            'all' => $areas->count(),
        ];

        // Open on the work that needs doing; fall back to everything when nothing is waiting.
        $status = $request->string('status')->toString();
        if (! in_array($status, ['needs', 'assigned', 'auto', 'all'], true)) {
            $status = $counts['needs'] > 0 ? 'needs' : 'all';
        }

        $rows = $areas->filter(fn ($a) => match ($status) {
            'needs' => in_array($a->state, ['inactive', 'needs'], true),
            'assigned' => $a->state === 'assigned',
            'auto' => $a->state === 'auto',
            default => true,
        });

        if ($search !== '') {
            $needle = Str::lower($search);
            $rows = $rows->filter(fn ($a) => str_contains($a->haystack, $needle));
        }

        $rows = $rows->sortBy([
            fn ($a, $b) => self::STATE_ORDER[$a->state] <=> self::STATE_ORDER[$b->state],
            fn ($a, $b) => strcmp($a->province, $b->province),
            fn ($a, $b) => strcmp($a->municipality, $b->municipality),
        ])->values();

        $last = max(1, (int) ceil($rows->count() / self::PER_PAGE));
        $page = min(max(1, (int) $request->query('page', 1)), $last);
        $paginator = (new LengthAwarePaginator(
            $rows->forPage($page, self::PER_PAGE)->values(),
            $rows->count(),
            self::PER_PAGE,
            $page,
            ['path' => $request->url()],
        ))->withQueryString();

        // Ready orders that have no route yet. Saving a primary retries them, so it shows how urgent the list is.
        $waiting = $counts['needs'] > 0
            ? Order::query()->where('status', 'ready_for_pickup')
                ->where(fn ($q) => $q->whereNull('origin_station_id')->orWhereNull('destination_station_id'))
                ->count()
            : 0;

        return view('admin.logistics-coverage.index', [
            'areas' => $paginator,
            'counts' => $counts,
            'status' => $status,
            'search' => $search,
            'waiting' => $waiting,
        ]);
    }

    public function update(Request $request, OrderRoutingService $routing): RedirectResponse
    {
        $this->requireSuperAdmin();
        $data = $request->validate([
            'province_code' => ['required', 'regex:/^[0-9]{9}$/'],
            'municipality_code' => ['required', 'regex:/^[0-9]{9}$/'],
            'station_hub_id' => ['required', 'integer', 'exists:logistics_facilities,id'],
        ], [
            'station_hub_id.required' => 'Choose a station first.',
            'station_hub_id.integer' => 'Choose a station from the list.',
            'station_hub_id.exists' => 'That station no longer exists. Reload the page and choose again.',
            'province_code.regex' => 'Reload the page and try again.',
            'municipality_code.regex' => 'Reload the page and try again.',
        ]);

        $station = $this->activeStationsQuery()->whereKey($data['station_hub_id'])
            ->where('province_code', $data['province_code'])
            ->where('municipality_code', $data['municipality_code'])
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

        return back()->with('success', $station->municipality.' now uses '.($station->company?->business_name ?? 'the company').' · '.$station->name.'. Orders waiting for a route were retried.');
    }

    // ------------------------------------------------------------------ data

    /** Stations that can take parcels today: active, under an active Province Hub, in an approved and active company. */
    private function activeStationsQuery()
    {
        return LogisticsFacility::query()->where('kind', 'station_hub')
            ->where('is_active', true)->whereHas('parent', fn ($query) => $query->where('is_active', true))
            ->whereHas('company', fn ($query) => $query->where('status', 'approved')
                ->whereHas('logisticsCenter.user', fn ($user) => $user->where('status', 'approved')
                    ->whereNull('archived_at')->where(fn ($active) => $active->whereNull('account_status')->orWhere('account_status', 'active'))));
    }

    /**
     * One row per municipality that has a working station or a saved primary.
     * state: needs (2+ stations, no primary), inactive (saved primary no longer works),
     *        assigned (working primary), auto (one station, no primary needed).
     */
    private function areas(): Collection
    {
        $stations = $this->activeStationsQuery()->with('company')->get()
            ->groupBy(fn ($s) => $s->province_code.':'.$s->municipality_code);

        $primary = DB::table('primary_logistics_coverage as p')
            ->leftJoin('users as u', 'u.id', '=', 'p.assigned_by')
            ->select('p.*', 'u.name as assigned_name')->get()
            ->keyBy(fn ($row) => $row->province_code.':'.$row->municipality_code);

        $primaryStations = LogisticsFacility::query()->with('company')
            ->whereIn('id', $primary->pluck('station_hub_id'))->get()->keyBy('id');
        $label = fn ($s) => trim(($s->company?->business_name ?? 'Company').' · '.$s->name);

        $rows = $stations->map(function ($group, $key) use ($primary, $primaryStations, $label) {
            $first = $group->first();
            $saved = $primary->get($key);
            $selected = $saved?->station_hub_id ? (int) $saved->station_hub_id : null;
            $working = $selected && $group->contains('id', $selected);

            $state = match (true) {
                (bool) $selected && ! $working => 'inactive',
                (bool) $selected => 'assigned',
                $group->count() > 1 => 'needs',
                default => 'auto',
            };

            return $this->row($key, $first->province, $first->municipality, $first->province_code, $first->municipality_code, $state, [
                'stations' => $group->map(fn ($s) => (object) ['id' => $s->id, 'label' => $label($s)])->values(),
                'companies' => $group->map(fn ($s) => $s->company?->business_name)->filter()->unique()->values(),
                'selected' => $working ? $selected : null,
                'inactive_label' => ($selected && ! $working && $primaryStations->has($selected)) ? $label($primaryStations[$selected]) : null,
                'saved' => $saved,
            ]);
        });

        // A saved primary whose municipality has no working station left would otherwise vanish from the page.
        $orphans = $primary->reject(fn ($row, $key) => $stations->has($key))->map(function ($saved, $key) use ($primaryStations, $label) {
            $facility = $primaryStations->get($saved->station_hub_id);
            if (! $facility) {
                return null;
            }

            return $this->row($key, $facility->province, $facility->municipality, $saved->province_code, $saved->municipality_code, 'inactive', [
                'stations' => collect(),
                'companies' => collect(),
                'selected' => null,
                'inactive_label' => $label($facility),
                'saved' => $saved,
            ]);
        })->filter();

        return $rows->concat($orphans)->values();
    }

    private function row(string $key, ?string $province, ?string $municipality, string $provinceCode, string $municipalityCode, string $state, array $extra): object
    {
        $saved = $extra['saved'] ?? null;

        return (object) [
            'key' => $key,
            'province' => (string) $province,
            'municipality' => (string) $municipality,
            'province_code' => $provinceCode,
            'municipality_code' => $municipalityCode,
            'state' => $state,
            'stations' => $extra['stations'],
            'companies' => $extra['companies'],
            'selected' => $extra['selected'],
            'inactive_label' => $extra['inactive_label'],
            'assigned_by' => $saved?->assigned_name,
            'assigned_at' => $saved?->updated_at ? Carbon::parse($saved->updated_at) : null,
            'haystack' => Str::lower($municipality.' '.$province.' '.$extra['stations']->pluck('label')->implode(' ').' '.$extra['inactive_label']),
        ];
    }

    private function requireSuperAdmin(): void
    {
        abort_unless(auth('admin')->user()?->is_super_admin, 403, 'Superuser access only.');
    }
}
