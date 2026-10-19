<x-logistics.layout title="Hubs & Stations">
    <div class="lg-page">
        <div class="lg-page-head">
            <div>
                <h1>Hubs & Stations</h1>
                <p>{{ $center->coverage_province ?: $center->province }} network · one account for the Main Hub and every station.</p>
            </div>
            <a href="{{ route('logistics.dashboard') }}" class="lg-btn lg-btn--outline">Back to dashboard</a>
        </div>

        @unless ($center->coverage_province_code)
            <div class="lg-network-note" role="status">This legacy account has no unique province claim yet. Ask an Admin to resolve coverage before adding stations or receiving new province assignments.</div>
        @endunless

        <section aria-labelledby="networkFacilitiesTitle">
            <h2 id="networkFacilitiesTitle" class="lg-section-title">Your facilities</h2>
            <div class="lg-facilities">
                @foreach ($facilities as $facility)
                    <a href="{{ route('logistics.facilities.show', $facility) }}" class="lg-facility" aria-label="Open {{ $facility->name }}">
                        <div class="lg-facility__top">
                            <span class="lg-facility__kind">{{ $facility->kind === 'main_hub' ? 'Main Hub' : 'Station' }}</span>
                            <span class="lg-facility__state">{{ $facility->is_active ? 'Active' : 'Inactive' }}</span>
                        </div>
                        <h3>{{ $facility->name }}</h3>
                        <p>{{ $facility->municipality }}, {{ $facility->province }}</p>
                        <span class="lg-facility__open">Open facility <span aria-hidden="true">→</span></span>
                    </a>
                @endforeach
            </div>
        </section>

        @if ($center->coverage_province_code)
            <section class="lg-card lg-facility-create" aria-labelledby="addStationTitle">
                <div class="lg-card__head">
                    <div>
                        <h2 id="addStationTitle" class="lg-section-title">Add a station</h2>
                        <p class="lg-section-sub">Add only a real operating stop that receives, sorts, or hands off parcels.</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logistics.facilities.store') }}" class="lg-facility-form">
                    @csrf
                    <div class="lg-field"><label for="stationName">Station name</label><input id="stationName" name="name" class="lg-input" maxlength="255" value="{{ old('name') }}" required placeholder="Pagsanjan Station"></div>
                    <div class="lg-field"><label for="stationCity">Municipality / city</label><select id="stationCity" name="municipality_code" class="lg-select" required><option value="">Select a place</option>@foreach ($cities as $code => $name)<option value="{{ $code }}" @selected(old('municipality_code') == $code)>{{ $name }}</option>@endforeach</select></div>
                    <div class="lg-field"><label for="stationAddress">Operating address</label><input id="stationAddress" name="address" class="lg-input" maxlength="255" value="{{ old('address') }}" required placeholder="Building, street, barangay"></div>
                    <button type="submit" class="lg-btn">Add station</button>
                </form>
            </section>
        @endif
    </div>
</x-logistics.layout>
