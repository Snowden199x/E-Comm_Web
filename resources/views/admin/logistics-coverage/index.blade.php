<x-admin.layout title="Logistics coverage">
    @vite('resources/css/admin/registrations.css')

    @php
        $tabs = [
            'needs' => ['Needs action', 'triangle-alert'],
            'assigned' => ['Primary set', 'check-circle'],
            'auto' => ['Automatic', 'settings'],
            'all' => ['All municipalities', 'map-pin'],
        ];

        // Same colours as the other status chips: warning = waiting, danger = needs a fix, success = done.
        $statePill = [
            'inactive' => ['Station inactive', 'border-vd-danger-line bg-vd-danger-bg text-vd-danger-ink'],
            'needs' => ['Needs a primary', 'border-vd-warning-line bg-vd-warning-bg text-vd-warning-ink'],
            'assigned' => ['Primary set', 'border-vd-success-line bg-vd-success-bg text-vd-success-ink'],
            'auto' => ['Automatic', 'border-vd-line-strong bg-vd-tint text-vd-muted'],
        ];

        $emptyCopy = [
            'needs' => ['check-circle', 'green', 'Nothing needs action', 'Every municipality with more than one station has a working primary.'],
            'assigned' => ['map-pin', 'purple', 'No primaries set yet', 'Municipalities where you choose a primary station appear here.'],
            'auto' => ['map-pin', 'purple', 'No automatic municipalities', 'Municipalities with exactly one active station appear here.'],
            'all' => ['map-pin', 'purple', 'No active stations yet', 'Coverage appears here once a Logistics company activates a Station Hub.'],
        ];

        $focus = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40';
        $secondaryBtn = "inline-flex h-9 items-center rounded-lg border border-vd-line-strong bg-white px-4 text-[13px] font-medium text-vd-plum transition-colors duration-200 hover:bg-vd-tint {$focus}";
        $clearQuery = array_filter(['status' => $status]);
    @endphp

    <div class="mx-auto w-full max-w-[1280px] p-4 sm:p-5 lg:p-6">

        <div class="mb-4">
            <h1 class="font-display text-xl font-semibold leading-tight text-vd-ink sm:text-2xl">Logistics coverage</h1>
            <p class="mt-1 text-sm text-vd-muted">Choose the primary station for municipalities with more than one. A municipality with one station is matched automatically.</p>
        </div>

        <x-admin.flash class="mb-4" />

        {{-- Status cards with counts: links that keep the search term. Same look as Registrations and Logistics documents. --}}
        <nav aria-label="Coverage status" data-skeleton="table" class="mb-4 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
            @foreach ($tabs as $key => [$tabLabel, $tabIcon])
                @php $active = $status === $key; @endphp
                <a href="{{ route('admin.logistics-coverage.index', array_filter(['status' => $key, 'q' => $search ?: null])) }}" x-target.push="main-content" data-skeleton="table"
                    style="--i: {{ $loop->index }}" @if ($active) aria-current="page" @endif
                    @class([
                        'rg-card flex items-center gap-3 rounded-[18px] border px-4 py-3 text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vd-plum/40',
                        'border-vd-plum bg-[#DDD0DB] ring-2 ring-vd-orchid/30' => $active,
                        'border-vd-orchid bg-[#E6DEE5] hover:-translate-y-0.5 hover:border-vd-plum hover:bg-[#E0D5DF]' => ! $active,
                    ])>
                    <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-vd-orchid text-white">
                        <x-admin.icon :name="$tabIcon" class="h-[18px] w-[18px]" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate font-display text-[13px] font-medium text-vd-ink">{{ $tabLabel }}</span>
                        <span class="block font-display text-lg font-semibold leading-tight tabular-nums text-vd-ink">{{ number_format($counts[$key]) }}</span>
                    </span>
                </a>
            @endforeach
        </nav>

        @if ($waiting > 0)
            <div class="mb-4 flex items-start gap-3 rounded-xl border border-vd-warning-line bg-vd-warning-bg p-4 text-sm text-vd-warning-ink" role="status">
                <x-admin.icon name="clock" class="mt-0.5 h-[18px] w-[18px] flex-shrink-0" />
                <p>{{ number_format($waiting) }} ready {{ \Illuminate\Support\Str::plural('order', $waiting) }} {{ $waiting === 1 ? 'is' : 'are' }} waiting for a route. Setting a primary station retries them.</p>
            </div>
        @endif

        {{-- Search --}}
        <form method="GET" action="{{ route('admin.logistics-coverage.index') }}" x-target.push="main-content" data-skeleton="table" role="search"
            class="mb-4 flex w-full items-center gap-2 lg:max-w-[440px]">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="relative flex-1">
                <x-admin.icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-vd-plum" />
                <input type="search" name="q" value="{{ $search }}" autocomplete="off" aria-label="Search municipalities"
                    placeholder="Search municipality, province or station"
                    class="h-10 w-full rounded-full border border-vd-orchid bg-vd-paper pl-10 pr-3 text-[13px] text-vd-ink placeholder:text-vd-muted transition-colors duration-200
                           hover:border-vd-plum hover:bg-white focus:border-vd-plum focus:bg-white focus:outline-none focus:ring-2 focus:ring-vd-plum/20">
            </div>
            @if ($search !== '')
                <a href="{{ route('admin.logistics-coverage.index', $clearQuery) }}" x-target.push="main-content" data-skeleton="table"
                    class="inline-flex h-10 items-center rounded-full px-3 text-[13px] font-medium text-vd-plum transition-colors duration-200 hover:bg-vd-lavender {{ $focus }}">
                    Clear search
                </a>
            @endif
        </form>

        <div class="overflow-hidden rounded-xl border border-vd-line bg-white">
            @if ($areas->isEmpty())
                @if ($search !== '')
                    <x-admin.empty-state filtered filtered-title="No matching municipalities" filtered-text="Check the spelling of the municipality, province or station.">
                        <a href="{{ route('admin.logistics-coverage.index', $clearQuery) }}" x-target.push="main-content" data-skeleton="table" class="{{ $secondaryBtn }}">Clear search</a>
                    </x-admin.empty-state>
                @else
                    @php [$emptyIcon, $emptyTone, $emptyTitle, $emptyText] = $emptyCopy[$status]; @endphp
                    <x-admin.empty-state :icon="$emptyIcon" :tone="$emptyTone" :title="$emptyTitle" :text="$emptyText">
                        @if ($status !== 'all' && $counts['all'] > 0)
                            <a href="{{ route('admin.logistics-coverage.index', ['status' => 'all']) }}" x-target.push="main-content" data-skeleton="table" class="{{ $secondaryBtn }}">Show all municipalities</a>
                        @endif
                    </x-admin.empty-state>
                @endif
            @else
                <div class="thin-scroll overflow-x-auto">
                    <table class="w-full min-w-[860px] text-left text-[13px]">
                        <caption class="sr-only">Primary station for each municipality</caption>
                        <thead>
                            <tr class="border-b border-vd-line bg-vd-tint text-xs text-vd-muted">
                                <th scope="col" class="px-4 py-2.5 font-medium">Municipality</th>
                                <th scope="col" class="px-4 py-2.5 font-medium">Stations</th>
                                <th scope="col" class="px-4 py-2.5 font-medium">Status</th>
                                <th scope="col" class="px-4 py-2.5 font-medium">Primary station</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-vd-line">
                            @foreach ($areas as $area)
                                @php
                                    [$pillLabel, $pillClass] = $statePill[$area->state];
                                    $count = $area->stations->count();
                                    $current = (string) ($area->selected ?? '');
                                    $companyNames = $area->companies->take(3)->implode(', ') . ($area->companies->count() > 3 ? ' +' . ($area->companies->count() - 3) : '');
                                @endphp
                                <tr style="--i: {{ min($loop->index, 12) }}">
                                    <td class="px-4 py-3 align-top">
                                        <p class="font-medium text-vd-ink">{{ $area->municipality }}</p>
                                        <p class="text-xs text-vd-muted">{{ $area->province }}</p>
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <p class="text-vd-ink">{{ $count }} {{ \Illuminate\Support\Str::plural('station', $count) }}</p>
                                        @if ($companyNames !== '')
                                            <p class="max-w-[220px] truncate text-xs text-vd-muted" title="{{ $area->companies->implode(', ') }}">{{ $companyNames }}</p>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 align-top">
                                        <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $pillClass }}">{{ $pillLabel }}</span>
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        @if ($area->state === 'auto')
                                            <p class="text-vd-ink">{{ $area->stations->first()->label }}</p>
                                            <p class="text-xs text-vd-muted">Matched automatically</p>
                                        @elseif ($count === 0)
                                            <p class="max-w-[360px] text-xs text-vd-danger-ink">{{ $area->inactive_label }} is inactive and no other station is active here. Ask a Logistics company to activate one.</p>
                                        @else
                                            <form method="POST" action="{{ route('admin.logistics-coverage.update') }}" class="flex flex-wrap items-center gap-2"
                                                x-data="{ v: @js($current), saved: @js($current) }">
                                                @csrf @method('PUT')
                                                <input type="hidden" name="province_code" value="{{ $area->province_code }}">
                                                <input type="hidden" name="municipality_code" value="{{ $area->municipality_code }}">
                                                <label class="sr-only" for="primary-{{ $area->key }}">Primary station for {{ $area->municipality }}</label>
                                                <select id="primary-{{ $area->key }}" name="station_hub_id" x-model="v"
                                                    class="h-9 min-w-[240px] max-w-full rounded-lg border border-vd-line-strong bg-white px-3 text-[13px] text-vd-ink transition-colors duration-200 hover:border-vd-plum-soft focus:border-vd-plum focus:outline-none focus:ring-2 focus:ring-vd-plum/20">
                                                    @if ($current === '')
                                                        <option value="">Choose a station</option>
                                                    @endif
                                                    @foreach ($area->stations as $station)
                                                        <option value="{{ $station->id }}" @selected((string) $station->id === $current)>{{ $station->label }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" :disabled="v === '' || v === saved"
                                                    class="inline-flex h-9 items-center rounded-lg bg-vd-plum px-4 text-[13px] font-semibold text-white transition-colors duration-200 hover:bg-vd-plum-hover disabled:cursor-not-allowed disabled:opacity-40 {{ $focus }}">
                                                    {{ $area->state === 'assigned' ? 'Change primary' : 'Set primary' }}
                                                </button>
                                            </form>
                                            @if ($area->state === 'inactive')
                                                <p class="mt-1.5 text-xs text-vd-danger-ink">{{ $area->inactive_label ?? 'The saved station' }} is inactive. Choose another station.</p>
                                            @elseif ($area->state === 'needs')
                                                <p class="mt-1.5 text-xs text-vd-warning-ink">Orders here wait until you choose a primary.</p>
                                            @elseif ($area->assigned_at)
                                                <p class="mt-1.5 text-xs text-vd-muted">Set by {{ $area->assigned_by ?? 'an admin' }} on {{ $area->assigned_at->format('M j, Y') }}</p>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @include('admin.partials.pagination', ['paginator' => $areas, 'asLinks' => true])
            @endif
        </div>
    </div>
</x-admin.layout>
