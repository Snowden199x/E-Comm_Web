<x-logistics.shell title="Hub Managers" role="company">
    <div class="lg-page" x-data="{ open: false }">
        <div class="lg-page-head">
            <div>
                <h1>Hub Managers</h1>
                <p>Each manager can only see and act on their own hub.</p>
            </div>
            <div class="lg-page-head__actions">
                <button type="button" class="lg-btn" @click="open = true">+ Add manager</button>
            </div>
        </div>

        <section class="lg-card" aria-label="Hub managers">
            <div class="lg-table-wrap">
                <table class="lg-table">
                    <thead><tr><th>Manager</th><th>Hub</th><th>Role</th><th>Last login</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($managers as $m)
                        <tr>
                            <td>
                                <div class="lg-person">
                                    <span class="lg-avatar lg-avatar--sm" aria-hidden="true">{{ mb_substr($m['name'], 0, 1) }}</span>
                                    <span><strong>{{ $m['name'] }}</strong><small>{{ $m['email'] }}</small></span>
                                </div>
                            </td>
                            <td>{{ $m['hub'] }}</td>
                            <td><span class="lg-pill lg-pill--{{ $m['role'] === 'Hub manager' ? 'brand' : 'gray' }}">{{ $m['role'] }}</span></td>
                            <td>{{ $m['last_login'] }}</td>
                            <td><span class="lg-pill lg-pill--{{ $m['status'] === 'Active' ? 'green' : 'amber' }}">{{ $m['status'] }}</span></td>
                            <td>
                                <button type="button" class="lg-btn lg-btn--outline lg-btn--sm">
                                    {{ $m['status'] === 'Invited' ? 'Resend invite' : 'Deactivate' }}
                                </button>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <div class="lg-overlay" x-show="open" x-cloak @click.self="open = false" @keydown.escape.window="open = false">
            <div class="lg-modal" role="dialog" aria-modal="true" aria-labelledby="addMgrTitle">
                <form @submit.prevent="open = false">
                    <div class="lg-modal__head">
                        <div><h3 id="addMgrTitle">Add manager</h3><p>They receive a temporary password by email.</p></div>
                        <button type="button" class="lg-toast__close" @click="open = false" aria-label="Close"><x-logistics.icon name="x" :size="18" /></button>
                    </div>
                    <div class="lg-modal__body" style="display:grid;gap:14px">
                        <div class="lg-field"><label for="mName">Full name</label><input id="mName" class="lg-input"></div>
                        <div class="lg-field"><label for="mEmail">Email</label><input id="mEmail" type="email" class="lg-input"></div>
                        <div class="lg-field">
                            <label for="mHub">Hub</label>
                            <select id="mHub" class="lg-select">
                                @foreach ($hubNames as $h)<option>{{ $h }}</option>@endforeach
                            </select>
                        </div>
                        <div class="lg-field">
                            <label for="mRole">Role</label>
                            <select id="mRole" class="lg-select"><option>Hub manager</option><option>Hub staff (sorter)</option></select>
                        </div>
                    </div>
                    <div class="lg-modal__foot">
                        <button type="button" class="lg-btn lg-btn--outline" @click="open = false">Cancel</button>
                        <button type="submit" class="lg-btn">Send invite</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-logistics.shell>