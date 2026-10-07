{{--
    Buyer seller-chat page. The thread markup and script are shared with the Seller side
    (shared/marketplace-thread.blade.php, shared/user-report-modal.blade.php), so the new look is a buyer-only skin:
    the .mc-page--buyer rules at the end of resources/css/buyer/layout.css. Nothing here changes what sellers see.
    The script below only adds small helpers to the report form (reason hint, character counter, file chips,
    "Sending..."). It does not touch how the report is submitted, and the form works the same without it.
--}}
<x-buyer.layout title="Chat | Vendo">
    @vite(['resources/css/shared/marketplace-chat.css', 'resources/css/shared/user-report.css'])
    <div class="mc-page mc-page--buyer vb-reveal">
        @include('shared.marketplace-thread')
    </div>

    <script>
    (() => {
        const form = document.getElementById('userReportForm');
        if (!form) return;

        // 1. A short hint under the reason (what to include)
        const hints = {
            'Misleading listing': 'Say what the listing claimed and what you actually received or saw.',
            'Item not received': 'Mention the order number and when it was expected to arrive.',
            'Seller harassment': 'Describe the messages or behavior and roughly when it happened.',
            'Suspected fraud': 'Explain what made it look suspicious. Do not share passwords or payment details.',
            'Other': 'Give as much detail as you can so the team can review it.',
        };
        const reason = document.getElementById('userReportReason');
        const hint = document.createElement('p');
        hint.className = 'ur-hint';
        hint.hidden = true;
        reason.insertAdjacentElement('afterend', hint);
        reason.addEventListener('change', () => {
            hint.textContent = hints[reason.value] || '';
            hint.hidden = !hint.textContent;
        });

        // 2. Character counter for "What happened?"
        const text = document.getElementById('userReportDescription');
        const counter = text.parentElement.querySelector('small');
        const count = () => {
            const n = text.value.trim().length;
            counter.textContent = n >= 20 ? n + ' / 2000 characters' : n + ' / 20 characters minimum';
            counter.classList.toggle('is-ok', n >= 20);
        };
        text.addEventListener('input', count);
        count();

        // 3. Chosen evidence files as chips (names are set with textContent, never as HTML)
        const evidence = document.getElementById('userReportEvidence');
        const list = document.createElement('ul');
        list.className = 'ur-files';
        evidence.parentElement.querySelector('small').insertAdjacentElement('beforebegin', list);
        const size = (bytes) => bytes >= 1048576 ? (bytes / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(bytes / 1024)) + ' KB';
        const files = () => {
            list.replaceChildren();
            [...evidence.files].forEach((file, index) => {
                const item = document.createElement('li');
                const name = document.createElement('span');
                const meta = document.createElement('em');
                name.textContent = file.name;
                meta.textContent = size(file.size);
                item.append(name, meta);
                if (index >= 5 || file.size > 5 * 1024 * 1024) item.classList.add('is-bad');
                list.append(item);
            });
        };
        evidence.addEventListener('change', files);
        form.addEventListener('reset', () => setTimeout(() => { files(); count(); hint.hidden = true; }, 0));

        // 4. "Sending..." while the shared script has the submit button disabled
        const submit = form.querySelector('[type="submit"]');
        const label = submit.textContent;
        new MutationObserver(() => { submit.textContent = submit.disabled ? 'Sending...' : label; })
            .observe(submit, { attributes: true, attributeFilter: ['disabled'] });
    })();
    </script>
</x-buyer.layout>