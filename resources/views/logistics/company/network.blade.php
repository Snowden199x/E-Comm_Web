{{-- PREVIEW ONLY: hard-coded data from routes/logistics_preview_data.php. Spec section 8A (Network Overview). Read-only: no create or edit actions. --}}
@php
    $tone = ['Active' => 'green', 'Setup' => 'amber', 'Inactive' => 'gray'];
    $stationTotal = collect($hubs)->sum(fn ($h) => count($h['stations']));
@endphp
<x-logistics.shell title="Network Overview" role="company">
    <div class="lg-page" x-data="{ q: '' }">
        <div class="lg-page-head">
            <div>
                <h1>Network Overview</h1>
                <p>{{ count($hubs) }} Province Hubs and {{ $stationTotal }} Station Hubs. To add or change a hub, use Province Hubs.</p>
            </div>
            <div class="lg-page-head__actions">
                <a href="{{ route('lgp.company.hubs') }}" class="lg-btn lg-btn--outline">Go to Province Hubs</a>
            </div>
        </div>

        <div class="lg-toolbar">
            <div class="lg-search">
                <x-logistics.icon name="search" :size="18" />
                <input type="search" class="lg-input" x-model="q" placeholder="Search hub, station or municipality" aria-label="Search the network">
            </div>
        </div>

        <section class="lg-card" aria-label="Hub network">
            @foreach ($hubs as $slug => $hub)
                @php $search = strtolower($hub['name'].' '.$hub['province'].' '.collect($hub['stations'])->map(fn ($s) => $s['name'].' '.$s['municipality'])->implode(' ')); @endphp
                <div x-show="!q.trim() || @js($search).includes(q.trim().toLowerCase())" style="{{ $loop->first ? '' : 'border-top:1px solid var(--lg-line)' }}">
                    <div class="lg-card__head">
                        <div>
                            <h2 class="lg-section-title"><a href="{{ route('lgp.company.hubs.show', $slug) }}">{{ $hub['name'] }}</a></h2>
                            <p class="lg-section-sub">{{ $hub['province'] }} · {{ count($hub['stations']) }} {{ count($hub['stations']) === 1 ? 'station' : 'stations' }}</p>
                        </div>
                        <span class="lg-pill lg-pill--{{ $tone[$hub['status']] ?? 'gray' }}">{{ $hub['status'] }}</span>
                    </div>
                    @if (count($hub['stations']))
                        <ul class="lg-tree__children">
                            @foreach ($hub['stations'] as $s)
                                <li class="lg-tree__leaf" x-show="!q.trim() || @js(strtolower($s['name'].' '.$s['municipality'].' '.$hub['name'])).includes(q.trim().toLowerCase())">
                                    <div>
                                        <div class="lg-cell-title">{{ $s['name'] }}</div>
                                        <div class="lg-cell-sub">{{ $s['municipality'] }}</div>
                                    </div>
                                    <span class="lg-pill lg-pill--{{ $tone[$s['status']] ?? 'gray' }}">{{ $s['status'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="lg-muted" style="margin:0 24px 16px 36px">No Station Hubs yet.</p>
                    @endif
                </div>
            @endforeach
        </section>
    </div>
</x-logistics.shell>