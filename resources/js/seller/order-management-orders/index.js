/**
 * Vendo – Seller / Order Management / Orders
 *
 * Handles:
 * - Order details drawer
 * - Status/search/date filters
 * - Client-side pagination
 */

(function () {
    'use strict';

    /* ---------------------------------------------------------
       Drawer (view order details)
       --------------------------------------------------------- */
    var layout        = document.getElementById('omoLayout');
    var drawer        = document.getElementById('omoDrawer');
    var closeBtn      = document.getElementById('omoDrawerClose');
    var drawerTitle   = document.getElementById('omoDrawerTitle');
    var drawerOrderId = document.getElementById('omoDrawerOrderId');
    var drawerCustomer = document.getElementById('omoDrawerCustomer');

    function openDrawer(btn) {
        var row = btn.closest('tr');
        var orderId = btn.getAttribute('data-order');
        var customer = row ? row.getAttribute('data-customer') : null;

        document.querySelectorAll('.omo-view-btn').forEach(function (b) {
            b.classList.remove('is-active');
        });
        btn.classList.add('is-active');

        if (orderId) {
            drawerTitle.textContent = 'Order #' + orderId;
            drawerOrderId.textContent = '#' + orderId;
        }
        if (customer) {
            drawerCustomer.textContent = customer;
        }

        layout.classList.add('is-open');
        drawer.setAttribute('aria-hidden', 'false');
    }

    function closeDrawer() {
        layout.classList.remove('is-open');
        drawer.setAttribute('aria-hidden', 'true');
        document.querySelectorAll('.omo-view-btn').forEach(function (b) {
            b.classList.remove('is-active');
        });
    }

    document.getElementById('omoTableBody').addEventListener('click', function (e) {
        var btn = e.target.closest('.omo-view-btn');
        if (!btn) return;

        var isOpen = layout.classList.contains('is-open') && btn.classList.contains('is-active');
        isOpen ? closeDrawer() : openDrawer(btn);
    });

    closeBtn.addEventListener('click', closeDrawer);
    document.getElementById('omoAccept').addEventListener('click', closeDrawer);
    document.getElementById('omoDecline').addEventListener('click', closeDrawer);

    /* ---------------------------------------------------------
       Filtering + pagination
       --------------------------------------------------------- */
    var rows         = Array.prototype.slice.call(document.querySelectorAll('#omoTableBody tr[data-status]'));
    var noResults    = document.getElementById('omoNoResults');
    var resultCount  = document.getElementById('omoResultCount');
    var tabs         = Array.prototype.slice.call(document.querySelectorAll('#omoTabs .omo-tab'));
    var statusBtn    = document.getElementById('omoStatusBtn');
    var statusPanel  = document.getElementById('omoStatusPanel');
    var statusLabel  = document.getElementById('omoStatusLabel');
    var statusOptions = Array.prototype.slice.call(document.querySelectorAll('.omo-status-option'));
    var dateBtn      = document.getElementById('omoDateBtn');
    var datePanel    = document.getElementById('omoDatePanel');
    var dateLabel    = document.getElementById('omoDateLabel');
    var dateFromInput = document.getElementById('omoDateFrom');
    var dateToInput   = document.getElementById('omoDateTo');
    var searchInput  = document.getElementById('omoSearchInput');

    var prevPageBtn  = document.getElementById('omoPrevPage');
    var nextPageBtn  = document.getElementById('omoNextPage');
    var pagerPages   = document.getElementById('omoPagerPages');

    var PAGE_SIZE = 10;
    var currentPage = 1;
    var filteredRows = [];

    var state = {
        status: 'all',
        search: '',
        dateFrom: '',
        dateTo: ''
    };

    // Badge counts, computed once from the actual row data.
    var counts = { new: 0, pack: 0, pickup: 0, pending: 0, completed: 0 };
    rows.forEach(function (row) {
        counts[row.getAttribute('data-status')]++;
    });

    document.querySelectorAll('.omo-tab__badge').forEach(function (el) {
        el.textContent = counts[el.getAttribute('data-count')] || 0;
    });

    function rowMatchesFilters(row) {
        var q = state.search.trim().toLowerCase();

        if (state.status !== 'all' && row.getAttribute('data-status') !== state.status) {
            return false;
        }

        if (q) {
            var customer = (row.getAttribute('data-customer') || '').toLowerCase();
            var orderId  = (row.getAttribute('data-order') || '').toLowerCase();

            if (customer.indexOf(q) === -1 && orderId.indexOf(q) === -1) {
                return false;
            }
        }

        if (state.dateFrom && row.getAttribute('data-date') < state.dateFrom) {
            return false;
        }

        if (state.dateTo && row.getAttribute('data-date') > state.dateTo) {
            return false;
        }

        return true;
    }

    function renderPagination() {
        var totalPages = Math.max(1, Math.ceil(filteredRows.length / PAGE_SIZE));

        if (currentPage > totalPages) {
            currentPage = totalPages;
        }

        pagerPages.innerHTML = '';

        for (var page = 1; page <= totalPages; page++) {
            var pageBtn = document.createElement('button');
            pageBtn.type = 'button';
            pageBtn.textContent = page;
            pageBtn.setAttribute('aria-label', 'Page ' + page);

            if (page === currentPage) {
                pageBtn.classList.add('is-active');
                pageBtn.setAttribute('aria-current', 'page');
            }

            pageBtn.addEventListener('click', (function (selectedPage) {
                return function () {
                    currentPage = selectedPage;
                    renderPage();
                };
            })(page));

            pagerPages.appendChild(pageBtn);
        }

        prevPageBtn.disabled = currentPage === 1 || filteredRows.length === 0;
        nextPageBtn.disabled = currentPage === totalPages || filteredRows.length === 0;
    }

    function renderPage() {
        rows.forEach(function (row) {
            row.style.display = 'none';
        });

        var start = (currentPage - 1) * PAGE_SIZE;
        var end = Math.min(start + PAGE_SIZE, filteredRows.length);

        for (var i = start; i < end; i++) {
            filteredRows[i].style.display = '';
        }

        noResults.style.display = filteredRows.length === 0 ? '' : 'none';

        if (filteredRows.length === 0) {
            resultCount.textContent = 'Showing 0 out of ' + rows.length + ' entries';
        } else {
            resultCount.textContent =
                'Showing ' + (start + 1) + '–' + end +
                ' out of ' + filteredRows.length + ' entries';
        }

        renderPagination();
    }

    function applyFilters(resetPage) {
        filteredRows = rows.filter(rowMatchesFilters);

        if (resetPage !== false) {
            currentPage = 1;
        }

        renderPage();
    }

    // ---- Tabs ----
    function syncTabsFromStatus() {
        tabs.forEach(function (t) {
            t.classList.toggle(
                'is-active',
                t.getAttribute('data-status') === state.status
            );
        });
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            state.status = tab.getAttribute('data-status');
            syncTabsFromStatus();
            syncStatusDropdownFromStatus();
            applyFilters(true);
        });
    });

    // ---- Status dropdown ----
    function syncStatusDropdownFromStatus() {
        var match = statusOptions.filter(function (o) {
            return o.getAttribute('data-status') === state.status;
        })[0];

        statusOptions.forEach(function (o) {
            o.classList.remove('is-selected');
        });

        if (match) {
            match.classList.add('is-selected');
            statusLabel.textContent =
                state.status === 'all'
                    ? 'All status'
                    : match.querySelector('span').textContent;
        } else {
            statusLabel.textContent = 'All status';
        }
    }

    statusOptions.forEach(function (opt) {
        opt.addEventListener('click', function () {
            state.status = opt.getAttribute('data-status');
            syncStatusDropdownFromStatus();
            syncTabsFromStatus();
            closePanel(statusPanel, statusBtn);
            applyFilters(true);
        });
    });

    // ---- Search ----
    searchInput.addEventListener('input', function (e) {
        state.search = e.target.value;
        applyFilters(true);
    });

    // ---- Date dropdown ----
    var ANCHOR = '2026-05-20';

    function toISO(d) {
        return d.getFullYear() + '-' +
            String(d.getMonth() + 1).padStart(2, '0') + '-' +
            String(d.getDate()).padStart(2, '0');
    }

    document.querySelectorAll('.omo-date-preset').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var preset = btn.getAttribute('data-preset');
            var anchor = new Date(ANCHOR + 'T00:00:00');
            var from = '', to = '', label = 'All Dates';

            if (preset === 'today') {
                from = to = ANCHOR;
                label = 'Today';
            } else if (preset === '7days') {
                var weekAgo = new Date(anchor);
                weekAgo.setDate(weekAgo.getDate() - 6);
                from = toISO(weekAgo);
                to = ANCHOR;
                label = 'Last 7 Days';
            } else if (preset === 'month') {
                var first = new Date(anchor.getFullYear(), anchor.getMonth(), 1);
                from = toISO(first);
                to = ANCHOR;
                label = 'This Month';
            }

            dateFromInput.value = from;
            dateToInput.value = to;
            state.dateFrom = from;
            state.dateTo = to;
            dateLabel.textContent = label;
            closePanel(datePanel, dateBtn);
            applyFilters(true);
        });
    });

    document.getElementById('omoDateApply').addEventListener('click', function () {
        state.dateFrom = dateFromInput.value || '';
        state.dateTo = dateToInput.value || '';

        if (state.dateFrom && state.dateTo) {
            dateLabel.textContent = state.dateFrom + ' – ' + state.dateTo;
        } else if (state.dateFrom) {
            dateLabel.textContent = 'From ' + state.dateFrom;
        } else if (state.dateTo) {
            dateLabel.textContent = 'Until ' + state.dateTo;
        } else {
            dateLabel.textContent = 'All Dates';
        }

        closePanel(datePanel, dateBtn);
        applyFilters(true);
    });

    document.getElementById('omoDateClear').addEventListener('click', function () {
        dateFromInput.value = '';
        dateToInput.value = '';
        state.dateFrom = '';
        state.dateTo = '';
        dateLabel.textContent = 'All Dates';
        applyFilters(true);
    });

    // ---- Dropdown open/close ----
    function openPanel(panel, btn) {
        closeAllPanels();
        panel.classList.add('is-open');
        btn.setAttribute('aria-expanded', 'true');
    }

    function closePanel(panel, btn) {
        panel.classList.remove('is-open');
        btn.setAttribute('aria-expanded', 'false');
    }

    function closeAllPanels() {
        closePanel(statusPanel, statusBtn);
        closePanel(datePanel, dateBtn);
    }

    statusBtn.addEventListener('click', function (e) {
        e.stopPropagation();

        statusPanel.classList.contains('is-open')
            ? closePanel(statusPanel, statusBtn)
            : openPanel(statusPanel, statusBtn);
    });

    dateBtn.addEventListener('click', function (e) {
        e.stopPropagation();

        datePanel.classList.contains('is-open')
            ? closePanel(datePanel, dateBtn)
            : openPanel(datePanel, dateBtn);
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.omo-filter')) {
            closeAllPanels();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeAllPanels();
        }
    });

    // ---- Pagination buttons ----
    prevPageBtn.addEventListener('click', function () {
        if (currentPage > 1) {
            currentPage--;
            renderPage();
        }
    });

    nextPageBtn.addEventListener('click', function () {
        var totalPages = Math.ceil(filteredRows.length / PAGE_SIZE);

        if (currentPage < totalPages) {
            currentPage++;
            renderPage();
        }
    });

    applyFilters(true);
})();
