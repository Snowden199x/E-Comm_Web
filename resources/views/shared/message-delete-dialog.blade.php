<style>#messageDeleteDialog::backdrop{background:rgba(0,0,0,.55)}</style>
<dialog id="messageDeleteDialog" aria-labelledby="messageDeleteTitle" style="width:min(92vw,420px);border:0;border-radius:16px;padding:24px;color:#241729;box-shadow:0 20px 60px #0004">
    <h2 id="messageDeleteTitle" style="font-size:18px;font-weight:700">Delete this message?</h2>
    <p style="margin:12px 0 22px;color:#665b6a;font-size:14px;line-height:1.5">This message and its attachment will be permanently deleted for everyone in the conversation. This cannot be undone.</p>
    <p id="messageDeleteError" role="alert" hidden style="color:#b91c1c;font-size:13px;margin-bottom:12px"></p>
    <div style="display:flex;justify-content:flex-end;gap:8px">
        <button type="button" id="messageDeleteCancel" style="border:1px solid #d9cddd;border-radius:8px;padding:8px 14px;background:white">Cancel</button>
        <button type="button" id="messageDeleteConfirm" style="border:0;border-radius:8px;padding:8px 14px;background:#52245b;color:white;font-weight:600">Delete message</button>
    </div>
</dialog>
<script>
(() => {
    const dialog = document.getElementById('messageDeleteDialog');
    const confirm = document.getElementById('messageDeleteConfirm');
    const error = document.getElementById('messageDeleteError');
    let selected = null;
    document.addEventListener('click', event => {
        const button = event.target.closest('[data-delete-message]');
        if (!button) return;
        selected = button;
        error.hidden = true;
        dialog.showModal();
    });
    document.getElementById('messageDeleteCancel').addEventListener('click', () => dialog.close());
    confirm.addEventListener('click', async () => {
        if (!selected) return;
        confirm.disabled = true;
        try {
            const response = await fetch(selected.dataset.deleteMessage, {
                method: 'DELETE',
                headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content}
            });
            if (!response.ok) throw new Error('Message could not be deleted. Please try again.');
            const row = selected.closest('[data-message-id]');
            const id = Number(row?.dataset.messageId || selected.dataset.messageId || 0);
            row?.remove();
            window.dispatchEvent(new CustomEvent('message-deleted', {detail: {id}}));
            dialog.close();
            selected = null;
        } catch (problem) { error.textContent = problem.message; error.hidden = false; }
        finally { confirm.disabled = false; }
    });
})();
</script>
