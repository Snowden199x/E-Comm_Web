<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Order;
use App\Models\Profiles\LogisticsFacility;
use App\Models\Profiles\OrderFacilityStop;
use App\Services\LocationCatalog;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FacilityController extends Controller
{
    public function index(Request $request, LocationCatalog $locations): View
    {
        $center = $request->user()->logisticsCenterDetail;
        $facilities = $center->facilities()->orderByRaw("CASE WHEN kind = 'main_hub' THEN 0 ELSE 1 END")
            ->orderBy('municipality')->get();
        $cities = $center->coverage_province_code
            ? ($locations->all()[$center->coverage_province_code]['cities'] ?? []) : [];

        return view('logistics.facilities.index', compact('center', 'facilities', 'cities'));
    }

    public function show(Request $request, LogisticsFacility $facility): View
    {
        $center = $request->user()->logisticsCenterDetail;
        abort_unless($facility->logistics_center_id === $center->id, 404);
        $orders = Order::query()->where(function ($query) use ($facility, $center) {
            $query->whereHas('facilityStops', fn ($stops) => $stops->where('facility_id', $facility->id));
            if ($facility->kind === 'main_hub') {
                $query->orWhere(fn ($origin) => $origin->where('logistics_center_id', $center->id)
                    ->whereIn('status', ['ready_for_pickup', 'picked_up', 'at_sorting_center', 'sorted']))
                    ->orWhere(fn ($destination) => $destination->where('destination_logistics_center_id', $center->id)
                        ->whereIn('status', ['in_transit_to_hub', 'at_destination_hub']));
            }
        })->whereNotIn('status', ['cancelled', 'delivered', 'completed', 'returned'])
            ->with(['seller:id,name', 'facilityStops.facility'])
            ->latest()->paginate(20);

        return view('logistics.facilities.show', compact('center', 'facility', 'orders'));
    }

    public function store(Request $request, LocationCatalog $locations): RedirectResponse
    {
        $center = $request->user()->logisticsCenterDetail;
        abort_unless($center->coverage_province_code, 409, 'This account needs a unique province claim before stations can be added.');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('logistics_facilities')->where('logistics_center_id', $center->id)],
            'municipality_code' => ['required', 'regex:/^[0-9]{9}$/'],
            'address' => ['required', 'string', 'max:255'],
        ]);
        $place = $locations->address($center->coverage_province_code, $data['municipality_code']);
        if (! $place) {
            throw ValidationException::withMessages(['municipality_code' => 'Select a municipality within your province.']);
        }

        try {
            $center->facilities()->create([
                'kind' => 'station', 'name' => $data['name'],
                'province_code' => $center->coverage_province_code,
                'province' => $center->coverage_province,
                'municipality_code' => $data['municipality_code'],
                'municipality' => $place['city'], 'address' => $data['address'], 'is_active' => true,
            ]);
        } catch (UniqueConstraintViolationException $exception) {
            throw ValidationException::withMessages(['name' => 'A facility with this name already exists in your province network.']);
        }

        return redirect()->route('logistics.facilities.index')->with('success', 'Station added to your province network.');
    }

    public function update(Request $request, LogisticsFacility $facility): RedirectResponse
    {
        $center = $request->user()->logisticsCenterDetail;
        abort_unless($facility->logistics_center_id === $center->id && $facility->kind === 'station', 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('logistics_facilities')
                ->where('logistics_center_id', $center->id)->ignore($facility->id)],
            'address' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ]);
        if (! $data['is_active'] && $facility->is_active) {
            $unfinished = OrderFacilityStop::query()->where('facility_id', $facility->id)
                ->whereNull('departed_at')->whereHas('order', fn ($query) => $query
                    ->whereNotIn('status', ['cancelled', 'delivered', 'completed', 'returned']))->exists();
            abort_if($unfinished, 409, 'Finish or reroute parcels assigned to this station before deactivating it.');
        }
        $facility->update($data);

        return back()->with('success', 'Station details saved.');
    }
}
