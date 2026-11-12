// Seller · Feedback: quick replies and character counter inside the review panel.
document.addEventListener('click', event => {
    const chip = event.target.closest('[data-reply-template]');
    if (!chip) return;
    const field = chip.closest('form')?.querySelector('[data-reply-field]');
    if (!field) return;
    field.value = chip.dataset.replyTemplate;
    field.dispatchEvent(new Event('input', { bubbles: true }));
    field.focus();
    field.setSelectionRange(field.value.length, field.value.length);
});

document.addEventListener('input', event => {
    const field = event.target.closest('[data-reply-field]');
    if (!field) return;
    const counter = field.closest('form')?.querySelector('[data-reply-count]');
    if (!counter) return;
    const max = Number(field.maxLength) || 2000;
    counter.textContent = `${field.value.length} / ${max}`;
    counter.classList.toggle('is-near', field.value.length > max * 0.9);
});
