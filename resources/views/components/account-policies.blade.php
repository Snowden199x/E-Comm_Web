@props(['policies'])

@once
<style>
.ap-panel { margin: 24px 0; border: 1px solid #e8e0e9; border-radius: 14px; background: #fff; color: #241b29; scroll-margin-top: 90px; }
.ap-panel__head { padding: 22px 24px 18px; }
.ap-panel__head h2 { margin: 0; font-size: 17px; font-weight: 700; line-height: 1.4; }
.ap-panel__head p { margin: 5px 0 0; color: #716778; font-size: 13px; line-height: 1.5; }
.ap-row { display: flex; align-items: center; gap: 16px; padding: 18px 24px; border-top: 1px solid #eee8ef; }
.ap-icon { flex: none; width: 38px; height: 42px; display: grid; place-items: center; border: 1px solid #e6dae8; border-radius: 9px; color: #704278; background: #fcf9fc; }
.ap-icon svg { width: 21px; height: 21px; }
.ap-row__info { flex: 1; min-width: 0; }
.ap-row__info h3 { margin: 0; font-size: 14px; font-weight: 600; overflow-wrap: anywhere; }
.ap-meta { display: flex; flex-wrap: wrap; gap: 5px 12px; margin-top: 6px; color: #746b79; font-size: 12px; line-height: 1.5; }
.ap-view, .ap-close { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 9px 13px; border: 1px solid #d8c8dc; border-radius: 8px; background: #fff; color: #5c2864; font: inherit; font-size: 12px; font-weight: 600; cursor: pointer; }
.ap-view { flex: none; }
.ap-view svg { width: 14px; height: 14px; }
.ap-view:hover, .ap-close:hover { background: #f8f1f9; border-color: #916299; }
.ap-view:focus-visible, .ap-close:focus-visible { outline: 2px solid #704278; outline-offset: 3px; }
.ap-empty { margin: 0; padding: 0 24px 24px; color: #746b79; font-size: 13px; }
.ap-dialog { width: min(720px, calc(100vw - 32px)); max-width: none; max-height: min(88vh, 880px); max-height: min(88dvh, 880px); padding: 0; margin: auto; border: 1px solid #e5dce8; border-radius: 16px; background: #fff; color: #241b29; box-shadow: 0 20px 60px #241b2933; overflow: hidden; }
.ap-dialog[open] { display: flex; flex-direction: column; }
.ap-dialog::backdrop { background: rgba(26, 15, 30, .48); }
.ap-dialog__head { display: flex; align-items: flex-start; justify-content: space-between; gap: 18px; padding: 22px 26px 18px; border-bottom: 1px solid #eee8ef; flex: none; }
.ap-dialog__head h2 { margin: 0; font-size: 19px; font-weight: 700; line-height: 1.4; }
.ap-dialog__body { min-height: 0; overflow-y: auto; overscroll-behavior: contain; padding: 24px 26px; }
.ap-dialog__body:focus-visible { outline: 2px solid #704278; outline-offset: -4px; }
.ap-dialog__footer { display: flex; justify-content: flex-end; padding: 14px 26px; border-top: 1px solid #eee8ef; flex: none; background: #fdfbfe; }
.ap-dialog__footer .ap-close { background: #5c2864; border-color: #5c2864; color: #fff; min-width: 80px; }
.ap-dialog__head .ap-close { width: 32px; height: 32px; padding: 0; border-color: transparent; font-size: 24px; line-height: 1; }
@media (max-width: 520px) {
    .ap-panel__head, .ap-row { padding: 18px; }
    .ap-row { flex-wrap: wrap; gap: 12px; }
    .ap-row__info { flex-basis: calc(100% - 54px); }
    .ap-view { margin-left: 50px; }
    .ap-dialog__head, .ap-dialog__body { padding: 18px; }
    .ap-dialog__footer { padding: 12px 18px; }
}
</style>
@endonce

<section class="ap-panel" id="accountPolicies" aria-labelledby="accountPoliciesTitle">
    <header class="ap-panel__head">
        <h2 id="accountPoliciesTitle">Policies</h2>
        <p>Read the current policies for your account.</p>
    </header>
    @forelse($policies as $policy)
        <div class="ap-row">
            <span class="ap-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H6a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8z"/><path d="M14 3v5h5M8 12h8M8 16h6"/></svg></span>
            <div class="ap-row__info">
                <h3>{{ $policy->name }}</h3>
                <div class="ap-meta"><span>Version {{ $policy->version }}</span><span>Updated {{ $policy->updated_at->format('M j, Y') }}</span></div>
            </div>
            <button type="button" class="ap-view" data-account-policy-open="account-policy-{{ $policy->id }}" aria-haspopup="dialog" aria-controls="account-policy-{{ $policy->id }}" aria-label="View {{ $policy->name }}">View policy <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="m6 3 5 5-5 5"/></svg></button>
        </div>
        <dialog id="account-policy-{{ $policy->id }}" class="ap-dialog" aria-labelledby="account-policy-title-{{ $policy->id }}">
            <header class="ap-dialog__head">
                <div><h2 id="account-policy-title-{{ $policy->id }}">{{ $policy->name }}</h2><div class="ap-meta"><span>Version {{ $policy->version }}</span><span>Updated {{ $policy->updated_at->format('M j, Y') }}</span></div></div>
                <button type="button" class="ap-close" data-account-policy-close aria-label="Close policy">×</button>
            </header>
            <div class="ap-dialog__body" tabindex="0" aria-label="Policy content"><x-policy-content :content="$policy->content" /></div>
            <footer class="ap-dialog__footer"><button type="button" class="ap-close" data-account-policy-close>Close</button></footer>
        </dialog>
    @empty
        <p class="ap-empty">No policies are available yet. Published policies will appear here.</p>
    @endforelse
</section>

@once
<script>
(() => {
    if (window.vendoAccountPoliciesBound) return;
    window.vendoAccountPoliciesBound = true;
    document.addEventListener('click', event => {
        const opener = event.target.closest('[data-account-policy-open]');
        if (opener) {
            const dialog = document.getElementById(opener.dataset.accountPolicyOpen);
            if (dialog && !dialog.open) { dialog.showModal(); dialog.querySelector('.ap-dialog__body').scrollTop = 0; }
        }
        const closer = event.target.closest('[data-account-policy-close]');
        if (closer) closer.closest('dialog').close();
        if (event.target.matches('dialog.ap-dialog')) {
            const bounds = event.target.getBoundingClientRect();
            if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) event.target.close();
        }
    });
})();
</script>
@endonce
