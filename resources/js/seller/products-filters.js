// Products & Inventory — Category / Subcategory filter behaviour.
// The two selects have no name; they feed ONE hidden `category` value (subcategory wins, else category), so the
// existing server filter keeps working. See docs/features/seller/backend-needs-2026-10-07.md for the optional controller upgrade.
const form = document.getElementById('piFilters');
const category = document.getElementById('piCat');
const sub = document.getElementById('piSub');
const hidden = document.getElementById('piCategoryValue');
const hint = document.getElementById('piSubHint');
const pop = document.getElementById('piFPop');

if (form && category && sub && hidden) {
    const syncSubs = () => {
        const parent = category.value;
        let available = 0;
        [...sub.options].forEach(option => {
            if (!option.value) return;
            const show = parent !== '' && option.dataset.parent === parent;
            option.hidden = !show;
            option.disabled = !show;
            if (show) available++;
        });
        if (sub.selectedOptions[0]?.disabled) sub.value = '';
        sub.disabled = available === 0;
        if (hint) hint.textContent = parent === '' ? 'Pick a category first to see its subcategories.' : (available ? `${available} subcategor${available === 1 ? 'y' : 'ies'} in this category.` : 'This category has no subcategories with your products.');
    };
    const syncValue = () => { hidden.value = sub.value || category.value || ''; };

    category.addEventListener('change', () => { syncSubs(); syncValue(); });
    sub.addEventListener('change', syncValue);
    form.addEventListener('submit', () => { syncValue(); hidden.disabled = hidden.value === ''; });
    syncSubs();
    syncValue();
}

// Close the filter popover on outside click or Escape.
if (pop) {
    document.addEventListener('click', event => { if (pop.open && !pop.contains(event.target)) pop.open = false; });
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && pop.open) { pop.open = false; pop.querySelector('summary')?.focus(); } });
}