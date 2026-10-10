<x-logistics.shell title="Hubs" role="company">
    <div class="lg-page" x-data="{ open: false, type: 'province' }">
        <div class="lg-page-head">
            <div>
                <h1>Hubs</h1>
                <p>A province hub sorts and ships between provinces. Stations deliver to buyers in one municipality.</p>
            </div>
            <div class="lg-page-head__actions">
                <button type="button" class="lg-btn" @click="open = true">+ Add hub</button>
            </div>
        </div>

        @foreach ($provinces as $province)
            <section class="lg-card" aria-label="{{ $province['name'] }}">
                <div class="lg-card__head">
                    <div>
                        <h2 class="lg-section-title">{{ $province['name'] }} <span class="lg-pill lg-pill--violet">Province hub</span></h2>
                        <p class="lg-section-sub">{{ $province['place'] }} &middot; Manager: {{ $province['manager'] }}</p>
                    </div>
                    <span class="lg-pill lg-pill--green">Active</span>
                </div>
                <div class="lg-table-wrap">
                    <table class="lg-table">
                        <thead><tr><th>Station</th><th>Manager</th><th class="is-num">Riders</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        @foreach ($province['stations'] as $s)
                            <tr>
                                <td><div class="lg-cell-title">{{ $s['name'] }}</div><div class="lg-cell-sub">{{ $s['municipality'] }}</div></td>
                                <td>{{ $s['manager'] }}</td>
                                <td class="is-num">{{ $s['riders'] }}</td>
                                <td><span class="lg-pill lg-pill--{{ $s['status'] === 'Active' ? 'green' : 'amber' }}">{{ $s['status'] }}</span></td>
                                <td><button type="button" class="lg-btn lg-btn--outline lg-btn--sm">Edit</button></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endforeach

        {{-- Add hub modal --}}
        <div class="lg-overlay" x-show="open" x-cloak @click.self="open = false" @keydown.escape.window="open = false">
            <div class="lg-modal" role="dialog" aria-modal="true" aria-labelledby="addHubTitle">
                <form @submit.prevent="open = false">
                    <div class="lg-modal__head">
                        <div><h3 id="addHubTitle">Add hub</h3><p>The hub manager gets a temporary password and must change it on first login.</p></div>
                        <button type="button" class="lg-toast__close" @click="open = false" aria-label="Close"><x-logistics.icon name="x" :size="18" /></button>
                    </div>
                    <div class="lg-modal__body" style="display:grid;gap:14px">
                        <div class="lg-field">
                            <label for="hubType">Hub type</label>
                            <select id="hubType" class="lg-select" x-model="type">
                                <option value="province">Province hub</option>
                                <option value="station">Municipality station</option>
                            </select>
                        </div>
                        <div class="lg-field" x-show="type === 'station'" x-cloak>
                            <label for="hubParent">Reports to province hub</label>
                            <select id="hubParent" class="lg-select">
                                @foreach ($provinces as $p)<option>{{ $p['name'] }}</option>@endforeach
                            </select>
                        </div>
                        <div class="lg-field"><label for="hubProv">Province</label><input id="hubProv" class="lg-input" placeholder="Laguna"></div>
                        <div class="lg-field" x-show="type === 'station'" x-cloak><label for="hubMun">Municipality</label><input id="hubMun" class="lg-input" placeholder="Santa Cruz"></div>
                        <div class="lg-field"><label for="mgrName">Manager name</label><input id="mgrName" class="lg-input" placeholder="Full name"></div>
                        <div class="lg-field"><label for="mgrEmail">Manager email</label><input id="mgrEmail" type="email" class="lg-input" placeholder="manager@company.com"></div>
                    </div>
                    <div class="lg-modal__foot">
                        <button type="button" class="lg-btn lg-btn--outline" @click="open = false">Cancel</button>
                        <button type="submit" class="lg-btn">Create hub</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-logistics.shell>