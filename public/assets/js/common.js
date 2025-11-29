// Common helpers for DataTables and modal handling
function initAdvancedTable(tableSelector) {
    const table = $(tableSelector);
    const thead = table.find('thead');
    const filterRow = $('<tr>').addClass('filters');
    thead.find('tr').first().children().each(function() {
        const th = $('<th>');
        if ($(this).find('input[type="checkbox"]').length || $(this).text().trim() === 'Actions') {
            th.html('');
        } else {
            th.html('<input type="text" class="form-control form-control-sm" placeholder="Search">');
        }
        filterRow.append(th);
    });
    thead.append(filterRow);
    const dataTable = table.DataTable({ 
        dom: 'lrtip', 
        orderCellsTop:true, 
        fixedHeader:true, 
        pageLength:10, 
        lengthMenu:[10,25,50,100], 
        responsive: {
            details: {
                type: 'column',
                target: 'tr'
            }
        },
        columnDefs:[
            {orderable:false,targets:[0,-1]},
            {responsivePriority: 1, targets: 0},
            {responsivePriority: 2, targets: -1}
        ]
    });
    table.find('thead').on('keyup change', 'tr.filters input', function(){ const idx=$(this).closest('th').index(); const val=$(this).val(); if (dataTable.column(idx).search()!==val) dataTable.column(idx).search(val).draw(); });
    return dataTable;
}

function showAddModal(modalId, formId, opts = {}) {
    const form = document.getElementById(formId);
    if (form) form.reset();
    // reset modal title and save button to defaults for Add mode
    try {
        const titleEl = document.getElementById('assignmentModalTitle');
        if (titleEl) titleEl.textContent = opts.title || 'Add Assignment';
        const saveBtn = document.getElementById('assignmentSaveBtn');
        if (saveBtn) saveBtn.textContent = opts.saveLabel || 'Save';
    } catch(e) { /* ignore if elements not present */ }
    // ensure CSRF hidden input exists in the form (for non-AJAX submissions)
    try {
        if (form) {
            let csrfInput = form.querySelector('input[name="csrf_token"]');
            if (!csrfInput) {
                const token = (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content) || window.__csrfToken || '';
                csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = token;
                form.appendChild(csrfInput);
            } else {
                // update value in case token rotated
                csrfInput.value = (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content) || window.__csrfToken || csrfInput.value;
            }
        }
    } catch(e) { /* ignore */ }
    // reset selects inside subjects-dynamic if exists
    const subjectsDiv = document.getElementById('subjects-dynamic');
    if (subjectsDiv) {
        while (subjectsDiv.children.length > 1) subjectsDiv.removeChild(subjectsDiv.lastChild);
        const sel = subjectsDiv.querySelector('select[name="subjects[]"]');
        if (sel) sel.selectedIndex = 0;
    }
    // enable fields
    if (form) Array.from(form.elements).forEach(el => el.disabled = false);
    const modalEl = document.getElementById(modalId);
    if (modalEl) bootstrap.Modal.getOrCreateInstance(modalEl).show();
}

// Declarative modal opener: listens for elements with data-modal-target
(function attachDeclarativeModalHandler(){
    function handleClick(e) {
        const el = e.target.closest && e.target.closest('[data-modal-target]');
        if (!el) return;
        const modalId = el.getAttribute('data-modal-target');
        const formId = el.getAttribute('data-modal-form') || null;
        if (modalId) {
            e.preventDefault();
            try { showAddModal(modalId, formId); } catch(err) { console.error('showAddModal failed', err); }
        }
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function(){ document.body.addEventListener('click', handleClick); });
    else document.body.addEventListener('click', handleClick);
})();

// Global refresh helper: clears cached lists and notifies listeners
function refreshGlobalLists() {
    try {
        delete window._cachedBranches;
        delete window._cachedCourses;
        const ev = new CustomEvent('globalListsRefreshed');
        window.dispatchEvent(ev);
    } catch(e) { console.error('refreshGlobalLists failed', e); }
}

// Notify user when lists are refreshed
window.addEventListener('globalListsRefreshed', function(){
    try {
        if (window.CRUD && typeof window.CRUD.toastSuccess === 'function') {
            window.CRUD.toastSuccess('Branch and course lists refreshed');
        } else {
            // fallback small toast
            const el = document.createElement('div');
            el.style.position = 'fixed'; el.style.top = '1rem'; el.style.right = '1rem'; el.style.zIndex = 2000;
            el.className = 'alert alert-success'; el.innerText = 'Branch and course lists refreshed';
            document.body.appendChild(el);
            setTimeout(() => el.remove(), 3000);
        }
    } catch(e) { console.error('globalListsRefreshed handler failed', e); }
});

// Utility: fetch JSON safe
async function fetchJson(url, opts) {
    const defaultOpts = { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } };
    opts = opts || {};
    // merge headers
    opts.headers = Object.assign({}, defaultOpts.headers, opts.headers || {});
    if (!opts.credentials) opts.credentials = defaultOpts.credentials;

    const res = await fetch(url, opts);
    const text = await res.text();
    // try parse JSON
    let data = null;
    try {
        data = text ? JSON.parse(text) : null;
    } catch (e) {
        // not JSON
        if (!res.ok) {
            const err = new Error(text || res.statusText || 'Request failed');
            err.status = res.status;
            throw err;
        }
        return text;
    }

    if (!res.ok) {
        const err = new Error((data && data.message) ? data.message : res.statusText || 'Request failed');
        err.status = res.status;
        err.data = data;
        // If unauthorized or forbidden, redirect to login page to re-authenticate
        if (err.status === 401 || err.status === 403) {
            try { window.location.href = '/login.php'; } catch(e) {}
        }
        throw err;
    }
    return data;
}
