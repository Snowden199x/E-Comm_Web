<x-admin.layout title="Logistics Coverage">
    <div class="mx-auto w-full max-w-[1100px] p-4 sm:p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-[#2B1730]">Logistics Coverage</h1>
            <p class="mt-1 text-sm text-[#675A69]">Choose the primary station for each municipality. A location with one active station is matched automatically.</p>
        </div>

        @if (session('success'))
            <p class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="status">{{ session('success') }}</p>
        @endif
        @if ($errors->any())
            <p class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ $errors->first() }}</p>
        @endif

        <div class="overflow-x-auto rounded-xl border border-[#E8DFE9] bg-white">
            <table class="w-full min-w-[650px] text-left text-sm">
                <thead class="border-b border-[#E8DFE9] bg-[#FAF8FB] text-[#675A69]">
                    <tr><th class="px-4 py-3 font-medium">Municipality</th><th class="px-4 py-3 font-medium">Available stations</th><th class="px-4 py-3 font-medium">Primary station</th></tr>
                </thead>
                <tbody class="divide-y divide-[#E8DFE9]">
                    @forelse ($areas as $areaKey => $stations)
                        @php
                            $first = $stations->first();
                            $selected = $primary->get($areaKey)?->station_hub_id;
                        @endphp
                        <tr>
                            <td class="px-4 py-3 align-top"><strong class="text-[#2B1730]">{{ $first->municipality }}</strong><br><span class="text-[#675A69]">{{ $first->province }}</span></td>
                            <td class="px-4 py-3 align-top text-[#675A69]">{{ $stations->count() }} {{ $stations->count() === 1 ? 'station' : 'stations' }}</td>
                            <td class="px-4 py-3 align-top">
                                <form method="POST" action="{{ route('admin.logistics-coverage.update') }}" class="flex flex-wrap items-center gap-2">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="province_code" value="{{ $first->province_code }}">
                                    <input type="hidden" name="municipality_code" value="{{ $first->municipality_code }}">
                                    <label class="sr-only" for="primary-{{ $first->id }}">Primary station for {{ $first->municipality }}</label>
                                    <select id="primary-{{ $first->id }}" name="station_hub_id" class="min-w-[250px] rounded-lg border border-[#D8CDD9] bg-white px-3 py-2 text-[#2B1730] focus:outline-none focus:ring-2 focus:ring-[#6B3972]" required>
                                        @if ($stations->count() > 1 && ! $selected)<option value="">Choose a primary station</option>@endif
                                        @foreach ($stations as $station)
                                            <option value="{{ $station->id }}" @selected((int) ($selected ?: ($stations->count() === 1 ? $station->id : 0)) === (int) $station->id)>{{ $station->company?->business_name }} · {{ $station->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="rounded-lg bg-[#5A2D62] px-4 py-2 font-medium text-white hover:bg-[#48204F] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#5A2D62]">Save</button>
                                </form>
                                @if ($selected && ! $stations->contains('id', $selected))
                                    <p class="mt-2 text-xs text-red-700">The assigned station is inactive. Select an active station.</p>
                                @elseif ($stations->count() > 1 && ! $selected)
                                    <p class="mt-2 text-xs text-amber-800">Automatic routing waits for a primary station.</p>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-8 text-center text-[#675A69]">No active Station Hubs yet. Create a Province Hub and Station Hub first.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin.layout>
