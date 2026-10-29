@props([
    'lat' => null,
    'lng' => null,
    'address' => '',
    'latName' => 'latitude',
    'lngName' => 'longitude',
    'emitOnly' => false,
    'fillForm' => true,
    'title' => 'Pin the address on the map',
    'description' => 'Search a place or drag the pin. The written address below is filled from the map, and you can still edit it.',
    'button' => 'Set address on map',
])

@php
    $mapsKey = (string) config('services.geoapify.key');
@endphp

{{--
    Geoapify address pin.
    Leaflet draws the map with Geoapify raster tiles; Geoapify Geocoding powers the place search
    and reverse geocoding. Everything is requested only when the dialog opens. Searching a place
    or moving the pin fills the address fields in the nearest form (province, municipality/city,
    barangay, street, ZIP). Coordinates stay in hidden inputs and are never shown to the user;
    they see the formatted address instead.
--}}
<div x-ref="root" x-data="vendoAddressMap({
        lat: @js($lat),
        lng: @js($lng),
        address: @js($address),
        key: @js($mapsKey),
        fillForm: @js((bool) $fillForm),
    })" x-init="init()" @keydown.escape.window="close()" {{ $attributes }}>
    @unless ($emitOnly)
        <input type="hidden" name="{{ $latName }}" :value="point.lat ?? ''">
        <input type="hidden" name="{{ $lngName }}" :value="point.lng ?? ''">
    @endunless

    <div class="flex flex-wrap items-start gap-x-3 gap-y-1.5">
        <button type="button" @click="open()"
            class="inline-flex h-9 items-center gap-1.5 rounded-md border border-[#805487] bg-white px-3.5 text-[12px] font-medium text-[#52245b] transition-colors duration-200 ease-vendo hover:bg-[#f5ecf6] active:scale-[0.98]">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s7-5.6 7-11a7 7 0 1 0-14 0c0 5.4 7 11 7 11z" /><circle cx="12" cy="10" r="2.6" /></svg>
            <span x-text="point.lat ? 'Change on map' : @js($button)">{{ $button }}</span>
        </button>
        <p class="min-w-0 flex-1 text-[11px] leading-4 text-[#6f5f73]">
            <span x-show="currentAddress" x-cloak x-text="currentAddress"></span>
            <span x-show="!currentAddress" class="text-[#8a7a8e]">No map pin yet. The typed address still works.</span>
        </p>
    </div>

    <div x-show="modal" x-cloak class="fixed inset-0 z-[70] flex items-end justify-center sm:items-center sm:p-4"
        role="dialog" aria-modal="true" aria-labelledby="vb-map-title" x-effect="document.body.style.overflow = modal ? 'hidden' : ''">
        <div x-show="modal" @click="close()"
            x-transition:enter="transition-opacity duration-300 ease-vendo" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity duration-200 ease-vendo"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="absolute inset-0 bg-[#1b0b1e]/55"></div>

        <div x-show="modal"
            x-transition:enter="transition duration-300 ease-vendo" x-transition:enter-start="translate-y-6 opacity-0 sm:translate-y-3 sm:scale-95"
            x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
            x-transition:leave="transition duration-200 ease-vendo" x-transition:leave-start="translate-y-0 opacity-100 sm:scale-100"
            x-transition:leave-end="translate-y-6 opacity-0 sm:scale-95"
            class="relative w-full rounded-t-2xl bg-white p-4 shadow-2xl sm:max-w-[640px] sm:rounded-2xl sm:p-5">
            <button type="button" @click="close()" aria-label="Close map"
                class="absolute right-3 top-3 z-10 grid h-8 w-8 place-items-center rounded-full bg-white/95 text-[#8a7a8e] shadow transition-colors duration-200 hover:bg-[#f3e8f5] hover:text-[#402143]">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg>
            </button>

            <h2 id="vb-map-title" class="pr-8 text-[16px] font-semibold text-[#2b1730]">{{ $title }}</h2>
            <p class="mt-0.5 text-[12px] leading-5 text-[#7a6a7e]">{{ $description }}</p>

            <div class="relative mt-3">
                <label for="vb-map-search" class="sr-only">Search an address</label>
                <input id="vb-map-search" x-ref="search" type="text" autocomplete="off" @keydown.enter.prevent
                    @input.debounce.400ms="search()"
                    placeholder="Search a street, barangay, city or landmark"
                    class="h-10 w-full rounded-md border border-[#e5dce7] bg-white pl-9 pr-3 text-[13px] text-[#2b1730] placeholder:text-[#9a8a9d] focus:border-[#805487] focus:ring-[#805487]">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#8a7a8e]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.4-3.4" /></svg>

                <div x-show="results.length" x-cloak
                    class="absolute inset-x-0 top-full z-20 mt-1 max-h-56 overflow-y-auto rounded-md border border-[#e5dce7] bg-white py-1 shadow-[0_18px_40px_-16px_rgba(43,23,48,0.5)]">
                    <template x-for="(result, index) in results" :key="index">
                        <button type="button" @click="selectResult(result)"
                            class="block w-full px-3 py-2 text-left text-[12px] leading-4 text-[#2b1730] transition-colors duration-150 hover:bg-[#faf5fa]">
                            <span x-text="result.formatted"></span>
                        </button>
                    </template>
                </div>
                <p x-show="searching" x-cloak class="mt-1 text-[11px] text-[#805487]">Searching…</p>
                <p x-show="searchError" x-cloak class="mt-1 text-[11px] text-[#a32b43]" x-text="searchError"></p>
            </div>

            <div class="relative mt-3 overflow-hidden rounded-lg border border-[#e5dce7] bg-[#f3edf4]">
                <div x-ref="canvas" class="h-[300px] w-full sm:h-[340px]" role="application" aria-label="Map: drag the pin to the address"></div>
                <div x-show="loading" x-cloak class="absolute inset-0 grid place-items-center bg-[#f3edf4]/85">
                    <span class="vb-skel h-3 w-28 rounded"></span>
                </div>
                <p x-show="error" x-cloak class="absolute inset-x-3 bottom-3 rounded-md bg-[#fdf1f3] px-3 py-2 text-[12px] text-[#a32b43]" role="alert" x-text="error"></p>
            </div>

            <div class="mt-3 rounded-md bg-[#faf7fb] px-3 py-2.5">
                <p class="text-[11px] font-medium uppercase tracking-wide text-[#9a8a9d]">Address at the pin</p>
                <p class="mt-0.5 break-words text-[13px] leading-5 text-[#2b1730]" x-text="currentAddress || 'Move the pin to see the address'"></p>
                <p x-show="filling" x-cloak class="mt-1 text-[11px] text-[#805487]">Filling the address fields…</p>
            </div>

            <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                <button type="button" @click="useDeviceLocation()" :disabled="locating"
                    class="inline-flex h-9 items-center gap-1.5 rounded-md border border-[#805487] bg-white px-3.5 text-[12px] font-medium text-[#52245b] transition-colors duration-200 hover:bg-[#f5ecf6] disabled:opacity-60">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3.2" /><path d="M12 2v3M12 19v3M2 12h3M19 12h3" /></svg>
                    <span x-text="locating ? 'Getting location…' : 'Use my current location'"></span>
                </button>
                <p class="text-[11px] text-[#8a7a8e]">Search results and the pin share only this address with Vendo.</p>
            </div>

            <div class="mt-4 flex flex-wrap justify-end gap-2.5">
                <button type="button" @click="close()"
                    class="inline-flex h-10 items-center rounded-md border border-[#e5dce7] px-5 text-[13px] font-medium text-[#3d2a42] transition-colors duration-200 hover:bg-[#faf5fa]">Cancel</button>
                <button type="button" @click="save()" :disabled="!point.lat || !currentAddress"
                    class="inline-flex h-10 items-center justify-center rounded-md bg-[#402143] px-5 text-[13px] font-medium text-white transition-all duration-300 ease-vendo hover:bg-[#52245b] active:scale-[0.97] disabled:cursor-not-allowed disabled:bg-[#c9bccc]">Use this address</button>
            </div>
        </div>
    </div>

    @once
        <script>
        (() => {
            const GEOCODE = 'https://api.geoapify.com/v1/geocode';

            const normalize = (value) => String(value || '')
                .toLowerCase()
                .replace(/^(city of|municipality of|barangay|brgy\.?)\s+/g, '')
                .replace(/\s+city$/g, '')
                .replace(/[^a-z0-9]+/g, '');
            const keyName = (value) => ['ncr', 'nationalcapitalregion', 'metropolitanmanila', 'metromanila'].includes(normalize(value))
                ? 'metromanila' : normalize(value);
            window.vendoAddressKey = keyName;

            const optionFor = (select, name) => {
                const target = keyName(name);
                return [...select.options].find((option) => option.value !== ''
                    && (keyName(option.value) === target || keyName(option.textContent.split('—')[0]) === target));
            };

            const waitFor = (check, timeout = 6000) => new Promise((resolve) => {
                const started = Date.now();
                const tick = () => {
                    if (check()) return resolve(true);
                    if (Date.now() - started >= timeout) return resolve(false);
                    setTimeout(tick, 80);
                };
                tick();
            });

            const setInput = (scope, name, value) => {
                if (!value) return;
                const field = scope.querySelector(`[name="${name}"]`);
                if (!field || field.tagName === 'SELECT') return;
                field.value = value;
                field.dispatchEvent(new Event('input', { bubbles: true }));
                field.dispatchEvent(new Event('change', { bubbles: true }));
            };

            const choose = (select, name) => {
                if (!select || !name) return false;
                const option = optionFor(select, name);
                if (!option) return false;
                select.value = option.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                return true;
            };

            // Fills the nearest form's address fields from the pin's reverse-geocoded address.
            window.vendoFillAddress = async (scope, parts) => {
                if (!scope) return;
                setInput(scope, 'street', parts.street);
                setInput(scope, 'zip_code', parts.zip);
                const province = scope.querySelector('[name="province"]');
                if (!province || !parts.province) return;
                if (!choose(province, parts.province)) return;
                const city = scope.querySelector('[name="municipality"]');
                if (city && parts.city) {
                    await waitFor(() => optionFor(city, parts.city));
                    choose(city, parts.city);
                }
                const barangay = scope.querySelector('[name="barangay"]');
                if (barangay && parts.barangay) {
                    await waitFor(() => optionFor(barangay, parts.barangay));
                    choose(barangay, parts.barangay);
                }
            };

            const partsFrom = (properties) => {
                const city = properties.city || properties.county || properties.district || '';
                let barangay = properties.suburb || properties.neighbourhood || properties.quarter || properties.district || '';
                if (!barangay || keyName(barangay) === keyName(city)) barangay = '';
                const street = [properties.housenumber, properties.street].filter(Boolean).join(' ')
                    || properties.address_line1 || '';
                return {
                    province: properties.state || properties.region || '',
                    city,
                    barangay,
                    street,
                    zip: properties.postcode || '',
                };
            };

            const formattedFrom = (properties) => properties.formatted
                || [properties.address_line1, properties.address_line2].filter(Boolean).join(', ');

            const loadLeaflet = () => {
                if (window.L) return Promise.resolve();
                if (window.__vendoLeaflet) return window.__vendoLeaflet;
                window.__vendoLeaflet = new Promise((resolve, reject) => {
                    const css = document.createElement('link');
                    css.rel = 'stylesheet';
                    css.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                    document.head.appendChild(css);
                    const script = document.createElement('script');
                    script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                    script.async = true;
                    script.onload = () => resolve();
                    script.onerror = () => { window.__vendoLeaflet = null; reject(new Error('The map library could not load. Check your connection and try again.')); };
                    document.head.appendChild(script);
                });
                return window.__vendoLeaflet;
            };

            window.vendoAddressMap = (config) => ({
                key: config.key || '',
                fillForm: config.fillForm !== false,
                modal: false,
                loading: false,
                filling: false,
                locating: false,
                searching: false,
                error: '',
                searchError: '',
                results: [],
                map: null,
                marker: null,
                searchTimer: null,
                point: { lat: config.lat ? Number(config.lat) : null, lng: config.lng ? Number(config.lng) : null },
                currentAddress: config.address || '',

                init() {},

                open() {
                    this.error = '';
                    this.searchError = '';
                    this.results = [];
                    this.modal = true;
                    this.$nextTick(() => this.build());
                },

                close() { this.modal = false; },

                build() {
                    if (!this.$refs.canvas) return;
                    if (!this.key) {
                        this.error = 'Geoapify is not configured. Add GEOAPIFY_API_KEY to the server environment.';
                        return;
                    }
                    this.loading = true;
                    loadLeaflet().then(() => {
                        if (!this.$refs.canvas) return;
                        if (!this.map) {
                            const hasPin = this.point.lat !== null && this.point.lng !== null;
                            const center = hasPin ? [this.point.lat, this.point.lng] : [12.8797, 121.7740];
                            this.map = L.map(this.$refs.canvas, { zoomControl: true }).setView(center, hasPin ? 16 : 6);
                            L.tileLayer('https://maps.geoapify.com/v1/tile/osm-bright/{z}/{x}/{y}.png?apiKey=' + encodeURIComponent(this.key), {
                                maxZoom: 20,
                                attribution: 'Powered by <a href="https://www.geoapify.com/" target="_blank" rel="noopener">Geoapify</a> | &copy; OpenStreetMap contributors',
                            }).addTo(this.map);
                            if (hasPin) this.placeMarker(this.point);
                            this.map.on('click', (event) => this.moveTo(event.latlng.lat, event.latlng.lng));
                        } else {
                            this.map.invalidateSize();
                        }
                    }).catch((error) => { this.error = error.message; })
                        .finally(() => { this.loading = false; });
                },

                placeMarker(position) {
                    const point = { lat: Number(position.lat), lng: Number(position.lng) };
                    this.point = point;
                    if (!this.map) return;
                    if (!this.marker) {
                        this.marker = L.marker([point.lat, point.lng], { draggable: true }).addTo(this.map);
                        this.marker.on('dragend', () => {
                            const next = this.marker.getLatLng();
                            this.moveTo(next.lat, next.lng);
                        });
                    } else {
                        this.marker.setLatLng([point.lat, point.lng]);
                    }
                },

                moveTo(lat, lng) {
                    this.placeMarker({ lat, lng });
                    this.reverseGeocode(lat, lng);
                },

                search() {
                    const text = String(this.$refs.search?.value || '').trim();
                    this.searchError = '';
                    this.results = [];
                    if (text.length < 3) return;
                    if (!this.key) {
                        this.searchError = 'Geoapify is not configured. Add GEOAPIFY_API_KEY to the server environment.';
                        return;
                    }
                    this.searching = true;
                    const url = GEOCODE + '/search?text=' + encodeURIComponent(text)
                        + '&limit=5&countrycodes=ph&lang=en&apiKey=' + encodeURIComponent(this.key);
                    fetch(url, { headers: { Accept: 'application/json' } })
                        .then((response) => response.ok
                            ? response.json()
                            : Promise.reject(new Error(response.status === 401 || response.status === 403
                                ? 'Geoapify rejected the API key. Check GEOAPIFY_API_KEY and its restrictions.'
                                : 'Address search is unavailable right now. Try again.')))
                        .then((data) => {
                            this.results = (data.features || []).map((feature) => {
                                const coords = feature.geometry?.coordinates;
                                return {
                                    lat: feature.properties.lat ?? coords?.[1],
                                    lng: feature.properties.lon ?? coords?.[0],
                                    formatted: formattedFrom(feature.properties),
                                    parts: partsFrom(feature.properties),
                                };
                            }).filter((result) => result.formatted && result.lat != null && result.lng != null);
                        })
                        .catch((error) => { this.searchError = error.message; })
                        .finally(() => { this.searching = false; });
                },

                selectResult(result) {
                    this.results = [];
                    if (result.lat == null || result.lng == null) return;
                    this.map?.setView([result.lat, result.lng], 17);
                    this.placeMarker({ lat: result.lat, lng: result.lng });
                    this.applyAddress(result.formatted, result.parts, true);
                },

                reverseGeocode(lat, lng) {
                    if (!this.key) {
                        this.error = 'Geoapify is not configured. Add GEOAPIFY_API_KEY to the server environment.';
                        return;
                    }
                    this.filling = true;
                    const url = GEOCODE + '/reverse?lat=' + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lng)
                        + '&limit=1&countrycodes=ph&lang=en&apiKey=' + encodeURIComponent(this.key);
                    fetch(url, { headers: { Accept: 'application/json' } })
                        .then((response) => response.ok
                            ? response.json()
                            : Promise.reject(new Error(response.status === 401 || response.status === 403
                                ? 'Geoapify rejected the API key. Check GEOAPIFY_API_KEY and its restrictions.'
                                : 'No address was found for this pin. Move it a little and try again.')))
                        .then((data) => {
                            const properties = data.features?.[0]?.properties;
                            if (!properties) throw new Error('No address was found for this pin. Move it a little and try again.');
                            this.applyAddress(formattedFrom(properties), partsFrom(properties), true);
                        })
                        .catch((error) => { this.error = error.message; })
                        .finally(() => { this.filling = false; });
                },

                applyAddress(formatted, parts, fill) {
                    this.currentAddress = formatted || '';
                    if (fill && this.fillForm) {
                        this.filling = true;
                        window.vendoFillAddress(this.$refs.root.closest('form'), parts).finally(() => { this.filling = false; });
                    }
                    this.$dispatch('maps-address', {
                        lat: this.point.lat,
                        lng: this.point.lng,
                        formatted: this.currentAddress,
                        components: parts,
                    });
                },

                useDeviceLocation() {
                    this.error = '';
                    if (!navigator.geolocation) {
                        this.error = 'This browser does not provide device location.';
                        return;
                    }
                    this.locating = true;
                    navigator.geolocation.getCurrentPosition((position) => {
                        const { latitude, longitude } = position.coords;
                        this.map?.setView([latitude, longitude], 17);
                        this.placeMarker({ lat: latitude, lng: longitude });
                        this.reverseGeocode(latitude, longitude);
                        this.locating = false;
                    }, (error) => {
                        this.error = error.code === 1
                            ? 'Allow location access in your browser to use the device location.'
                            : 'Could not get the device location. Try again where the address is.';
                        this.locating = false;
                    }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
                },

                save() {
                    if (this.point.lat === null || this.point.lng === null || !this.currentAddress) {
                        this.error = 'Search a place or move the pin to the address first.';
                        return;
                    }
                    this.$dispatch('maps-address', {
                        lat: this.point.lat,
                        lng: this.point.lng,
                        formatted: this.currentAddress,
                        components: null,
                    });
                    this.close();
                },
            });
        })();
        </script>
    @endonce
</div>
