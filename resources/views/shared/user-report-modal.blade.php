<dialog id="userReportModal" class="ur-dialog" aria-labelledby="userReportTitle">
    <div class="ur-panel">
        <div class="ur-head">
            <div>
                <p class="ur-eyebrow">Account report</p>
                <h2 id="userReportTitle">Report {{ $reportTargetName }}</h2>
            </div>
            <button type="button" class="ur-close" data-report-close aria-label="Close report form">&times;</button>
        </div>
        <p class="ur-intro">Tell us what happened in this conversation. Admin will review the report before taking action.</p>
        <form id="userReportForm" action="{{ $reportStoreUrl }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="context" value="{{ $reportContext }}">
            <label class="ur-field" for="userReportReason"><span>Reason</span>
                <select id="userReportReason" name="reason" required>
                    <option value="">Select a reason</option>
                    @php
                        $reportReasons = $reportRole === 'buyer'
                            ? ['Misleading listing', 'Item not received', 'Seller harassment', 'Suspected fraud', 'Other']
                            : ['Bogus order', 'Abusive messages', 'Suspected fraud', 'Repeated cancellations', 'Other'];
                    @endphp
                    @foreach($reportReasons as $reason)
                        <option value="{{ $reason }}">{{ $reason }}</option>
                    @endforeach
                </select>
            </label>
            <label class="ur-field" for="userReportDescription"><span>What happened?</span>
                <textarea id="userReportDescription" name="description" minlength="20" maxlength="2000" rows="5" required placeholder="Describe what happened and when. Do not include passwords or payment details."></textarea>
                <small>At least 20 characters.</small>
            </label>
            <label class="ur-field" for="userReportEvidence"><span>Photos or PDF (optional)</span>
                <input id="userReportEvidence" type="file" name="evidence[]" accept=".jpg,.jpeg,.png,.webp,.pdf" multiple>
                <small>Up to 5 files, 5 MB each.</small>
            </label>
            <p id="userReportError" class="ur-error" role="alert" hidden></p>
            <div class="ur-actions"><button type="button" class="ur-secondary" data-report-close>Cancel</button><button type="submit" class="ur-primary">Send report</button></div>
        </form>
    </div>
</dialog>
<p id="userReportNotice" class="ur-notice" role="status" hidden></p>
<script>
(() => {
    const dialog = document.getElementById('userReportModal');
    const form = document.getElementById('userReportForm');
    const error = document.getElementById('userReportError');
    const notice = document.getElementById('userReportNotice');

    document.querySelectorAll('[data-report-open]').forEach(button => button.addEventListener('click', () => {
        form.reset();
        error.hidden = true;
        dialog.showModal();
    }));
    dialog.querySelectorAll('[data-report-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
    dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
    form.addEventListener('submit', async event => {
        event.preventDefault();
        error.hidden = true;
        const files = document.getElementById('userReportEvidence').files;
        if (files.length > 5 || [...files].some(file => file.size > 5 * 1024 * 1024)) {
            error.textContent = 'Choose up to 5 files, 5 MB each.';
            error.hidden = false;
            return;
        }
        const submit = form.querySelector('[type="submit"]');
        submit.disabled = true;
        try {
            const response = await fetch(form.action, {
                method: 'POST', body: new FormData(form),
                headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value},
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(Object.values(data.errors || {})[0]?.[0] || data.message || 'Could not send the report.');
            dialog.close();
            notice.textContent = data.message;
            notice.hidden = false;
            setTimeout(() => { notice.hidden = true; }, 6000);
        } catch (problem) {
            error.textContent = problem.message;
            error.hidden = false;
        } finally {
            submit.disabled = false;
        }
    });
})();
</script>
