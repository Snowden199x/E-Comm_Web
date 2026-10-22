{{-- PREVIEW ONLY: hard-coded 30-day figures from routes/logistics_preview_reports.php. Spec section 8A (Company Admin > Reports).
     Counts scale with the chosen range; percentages and times stay as they are. Backend later: one aggregate query per report. --}}
<x-logistics.shell title="Reports" role="company">
    <div class="lg-page"
         x-data="{
            reports: @js($reports),
            tab: 'volume',
            days: 30,
            baseDays: {{ (int) $baseDays }},
            pillTone: { 'Available': 'lg-pill--green', 'On trip': 'lg-pill--blue', 'Maintenance': 'lg-pill--amber' },
            init() {
                const h = location.hash.slice(1);
                if (h && this.reports[h]) this.tab = h;
            },
            setTab(k) { this.tab = k; history.replaceState(null, '', '#' + k); },
            get f() { return this.days / this.baseDays; },
            get cur() { return this.reports[this.tab]; },
            fmt(v, type) {
                if (v === null || v === undefined) return '\u2014';
                if (type === 'count') return Math.round(v * this.f).toLocaleString('en-PH');
                if (type === 'pct') return Number(v).toFixed(1) + '%';
                if (type === 'hours') return (Number.isInteger(v) ? v : Number(v).toFixed(1)) + ' hours';
                if (type === 'num') return Number(v).toLocaleString('en-PH');
                return v;
            },
            kpi(key, i) { const k = this.reports[key].kpis[i]; return this.fmt(k[1], k[2]); },
            isNum(c) { return ['num', 'count', 'pct', 'hours'].includes(c[1]); },
            opt(c) { return c[2] || {}; },
            colMax(ci) { return Math.max(1, ...this.cur.rows.map(r => r[ci] || 0)); },
            meterW(c, v, ci) { const o = this.opt(c); return Math.max(2, Math.min(100, (o.target || o.share) ? v : v / this.colMax(ci) * 100)); },
            meterTone(c, v) {
                const o = this.opt(c);
                if (!o.target) return '';
                return (o.better === 'high' ? v >= o.target : v <= o.target) ? 'lg-meter--ok' : 'lg-meter--warn';
            },
            onTarget(c, v) { const o = this.opt(c); return o.target ? ((o.better === 'high' ? v >= o.target : v <= o.target) ? ' (on target)' : ' (off target)') : ''; }
         }">
        <div class="lg-page-head">
            <div>
                <h1>Reports</h1>
                <p>Compare every hub, station, rider, driver and vehicle in your network.</p>
            </div>
            <div class="lg-page-head__actions">
                <select class="lg-select" aria-label="Date range" style="width:auto" x-model.number="days">
                    <option value="7">Last 7 days</option>
                    <option value="30">Last 30 days</option>
                    <option value="90">Last 90 days</option>
                </select>
                <button type="button" class="lg-btn lg-btn--outline"
                    @click="$dispatch('lg-toast', { type: 'info', message: 'PDF export is not connected in this preview.' })"><x-logistics.icon name="download" :size="18" /> Export PDF</button>
            </div>
        </div>

        <nav class="lg-tabs" aria-label="Report types">
            @foreach ($reports as $key => $r)
                <button type="button" class="lg-tab" :aria-pressed="tab === '{{ $key }}'" @click="setTab('{{ $key }}')">{{ $r['label'] }}</button>
            @endforeach
        </nav>

        @foreach ($reports as $key => $r)
            <div class="lg-grid lg-grid--4" x-show="tab === '{{ $key }}'" @if (! $loop->first) x-cloak @endif>
                @foreach ($r['kpis'] as $i => [$label, $value, $type, $icon, $tone])
                    <div class="lg-stat">
                        <span class="lg-stat__icon lg-stat__icon--{{ $tone }}"><x-logistics.icon :name="$icon" :size="24" /></span>
                        <div class="lg-stat__body">
                            <p class="lg-stat__label">{{ $label }}</p>
                            <p class="lg-stat__value" x-text="kpi('{{ $key }}', {{ $i }})">{{ $value }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach

        <section class="lg-card" aria-labelledby="repTitle">
            <div class="lg-card__head">
                <div>
                    <h2 class="lg-section-title" id="repTitle" x-text="cur.label"></h2>
                    <p class="lg-section-sub" x-text="cur.intro + ' Showing the last ' + days + ' days.'"></p>
                </div>
            </div>
            <div class="lg-table-wrap">
                <table class="lg-table lg-table--stack lg-table--mid">
                    <thead>
                        <tr>
                            <template x-for="(c, ci) in cur.cols" :key="tab + ci">
                                <th :class="isNum(c) ? 'is-num' : ''" x-text="c[0]"></th>
                            </template>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, ri) in cur.rows" :key="tab + ri">
                            <tr>
                                <template x-for="(c, ci) in cur.cols" :key="ci">
                                    <td :data-label="c[0]" :class="isNum(c) && !opt(c).meter ? 'is-num' : ''" :style="opt(c).meter ? 'min-width:170px' : ''">
                                        <template x-if="row[ci] === null">
                                            <span class="lg-muted" x-text="ci === 0 ? '' : '\u2014'"></span>
                                        </template>
                                        <template x-if="row[ci] !== null && c[1] === 'title'">
                                            <span class="lg-cell-title" x-text="row[ci]"></span>
                                        </template>
                                        <template x-if="row[ci] !== null && c[1] === 'pill'">
                                            <span class="lg-pill" :class="pillTone[row[ci]] || 'lg-pill--gray'" x-text="row[ci]"></span>
                                        </template>
                                        <template x-if="row[ci] !== null && c[1] !== 'title' && c[1] !== 'pill' && !opt(c).meter">
                                            <span x-text="fmt(row[ci], c[1])"></span>
                                        </template>
                                        <template x-if="row[ci] !== null && opt(c).meter">
                                            <div class="lg-meter-row">
                                                <div class="lg-meter-row__top"><strong x-text="fmt(row[ci], c[1])"></strong><span class="sr-only" x-text="onTarget(c, row[ci])"></span></div>
                                                <div class="lg-meter" :class="meterTone(c, row[ci])" role="presentation"><span :style="'--w:' + meterW(c, row[ci], ci) + '%'"></span></div>
                                            </div>
                                        </template>
                                    </td>
                                </template>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-logistics.shell>
