// Seller · Complaints: open and close the case dialogs.
document.addEventListener('click', event => {
    const open = event.target.closest('[data-cmp-open]');
    if (open) document.getElementById(open.dataset.cmpOpen)?.showModal();
    if (event.target.closest('[data-cmp-close]')) event.target.closest('dialog')?.close();
    if (event.target.matches('dialog.cmp-dialog')) event.target.close(); // click on the backdrop
});
