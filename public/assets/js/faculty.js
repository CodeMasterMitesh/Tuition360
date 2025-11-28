// faculty.js - extracted from app/views/faculty.php
// faculty.js - page logic following pattern used in students.js / subjects.js
function initFaculty() {
    // init DataTable with per-column filters
    try { initAdvancedTable('#faculty-table'); } catch(e) { console.error('initFaculty: initAdvancedTable failed', e); }

    // page fade-in
    const container = document.querySelector('.dashboard-container'); if (container) container.classList.add('show');

    // selection handling similar to subjects.js
    const selectAll = document.getElementById('select-all-faculty');
    const headerDeleteBtn = document.getElementById('delete-selected-faculty-header');
    const topDeleteBtn = document.getElementById('delete-selected-faculty');

    function updateSelectionUI() {
        const any = !!document.querySelectorAll('#faculty-table tbody .row-select:checked').length;
        if (headerDeleteBtn) headerDeleteBtn.style.display = any ? '' : 'none';
        if (topDeleteBtn) topDeleteBtn.style.display = any ? '' : 'none';
        if (selectAll) {
            const total = document.querySelectorAll('#faculty-table tbody .row-select').length;
            const checked = document.querySelectorAll('#faculty-table tbody .row-select:checked').length;
            selectAll.checked = total > 0 && checked === total;
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            const checked = !!this.checked;
            document.querySelectorAll('#faculty-table tbody .row-select').forEach(cb => cb.checked = checked);
            updateSelectionUI();
        });
    }
    document.addEventListener('change', function(e){ if (e.target && e.target.classList && e.target.classList.contains('row-select')) updateSelectionUI(); });
    updateSelectionUI();

    // keyboard: Ctrl+F focuses first filter input, Ctrl+N opens add modal
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'f') {
            e.preventDefault();
            const input = document.querySelector('#faculty-table thead tr.filters input'); if (input) { input.focus(); input.select && input.select(); }
        }
        if (e.ctrlKey && e.key === 'n') {
            e.preventDefault();
            const btn = document.querySelector('[data-bs-target="#addFacultyModal"]'); if (btn) btn.click();
        }
    });
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initFaculty); else try { initFaculty(); } catch(e) { console.error('initFaculty immediate failed', e); }

function exportToExcel() {
    if (window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer');
    setTimeout(() => { window.location.href = '?page=faculty&export=excel'; if (window.CRUD && CRUD.hideLoading) CRUD.hideLoading(); }, 800);
}

function printTable() {
    const table = document.getElementById('faculty-table').cloneNode(true);
    const w = window.open('', '_blank');
    w.document.write(`<html><head><title>Faculty</title><style>body{font-family:Arial}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:8px}</style></head><body><h2>Faculty</h2>${table.outerHTML}<p>Generated on: ${new Date().toLocaleDateString()}</p></body></html>`);
    w.document.close(); w.print();
}

function refreshTable() { if (window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer'); setTimeout(() => location.reload(), 700); }

async function editFaculty(id) {
    if (window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer');
    try {
        const res = await CRUD.get(`api/faculty.php?action=get&id=${encodeURIComponent(id)}`);
        if (res.success && res.data) {
            const f = res.data;
            document.getElementById('facultyId').value = f.id || '';
            document.querySelector('#addFacultyForm [name="name"]').value = f.name || '';
            document.querySelector('#addFacultyForm [name="email"]').value = f.email || '';
            document.querySelector('#addFacultyForm [name="mobile"]').value = f.mobile || f.phone || '';
            document.getElementById('facultyBranch') && (document.getElementById('facultyBranch').value = f.branch_id || 0);
            // ensure form is editable for edit
            try {
                const form = document.getElementById('addFacultyForm'); if (form) Array.from(form.elements).forEach(el => el.disabled = false);
            } catch (e) {}
            const title = document.getElementById('facultyModalTitle'); if (title) title.innerText = 'Edit Faculty';
            const saveBtn = document.getElementById('saveFacultyBtn'); if (saveBtn) { saveBtn.innerText = 'Update Faculty'; saveBtn.style.display = ''; }
            bootstrap.Modal.getOrCreateInstance(document.getElementById('addFacultyModal')).show();
        } else {
            if (window.CRUD && CRUD.toastError) CRUD.toastError('Faculty not found'); else alert('Faculty not found');
        }
    } catch (e) { if (window.CRUD && CRUD.toastError) CRUD.toastError('Failed to load: ' + e.message); else alert('Failed: ' + e.message); }
    finally { window.CRUD && CRUD.hideLoading && CRUD.hideLoading(); }
}

async function viewFaculty(id) {
    if (window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer');
    try {
        const res = await CRUD.get(`api/faculty.php?action=get&id=${encodeURIComponent(id)}`);
        if (res.success && res.data) {
            const f = res.data;
            document.getElementById('facultyId').value = f.id || '';
            document.querySelector('#addFacultyForm [name="name"]').value = f.name || '';
            document.querySelector('#addFacultyForm [name="email"]').value = f.email || '';
            document.querySelector('#addFacultyForm [name="mobile"]').value = f.mobile || f.phone || '';
            document.getElementById('facultyBranch') && (document.getElementById('facultyBranch').value = f.branch_id || 0);
            // disable all form controls for view-only
            try {
                const form = document.getElementById('addFacultyForm'); if (form) Array.from(form.elements).forEach(el => el.disabled = true);
            } catch (e) {}
            const title = document.getElementById('facultyModalTitle'); if (title) title.innerText = 'View Faculty';
            const saveBtn = document.getElementById('saveFacultyBtn'); if (saveBtn) { saveBtn.style.display = 'none'; }
            bootstrap.Modal.getOrCreateInstance(document.getElementById('addFacultyModal')).show();
        } else {
            if (window.CRUD && CRUD.toastError) CRUD.toastError('Faculty not found'); else alert('Faculty not found');
        }
    } catch (e) { if (window.CRUD && CRUD.toastError) CRUD.toastError('Failed to load: ' + e.message); else alert('Failed: ' + e.message); }
    finally { window.CRUD && CRUD.hideLoading && CRUD.hideLoading(); }
}

async function deleteFaculty(id) {
    if (!confirm('Delete faculty ' + id + '?')) return;
    if (window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer');
    try {
        const params = new URLSearchParams(); params.append('id', id);
        const res = await CRUD.post('api/faculty.php?action=delete', params);
        if (res.success) { window.CRUD && CRUD.toastSuccess && CRUD.toastSuccess(res.message || 'Deleted'); refreshTable(); } else { window.CRUD && CRUD.toastError && CRUD.toastError('Delete failed'); }
    } catch (e) { if (window.CRUD && CRUD.toastError) CRUD.toastError('Delete failed: ' + e.message); else alert('Delete failed: ' + e.message); }
    finally { window.CRUD && CRUD.hideLoading && CRUD.hideLoading(); }
}

async function deleteSelectedFaculty() {
    const checked = Array.from(document.querySelectorAll('#faculty-table tbody .row-select:checked')).map(cb => cb.dataset.id).filter(Boolean);
    if (!checked.length) { window.CRUD && CRUD.toastError && CRUD.toastError('No faculty selected'); return; }
    if (!confirm(`Delete ${checked.length} selected faculty(s)?`)) return;
    if (window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer');
    try {
        for (const id of checked) {
            const params = new URLSearchParams(); params.append('id', id);
            const res = await CRUD.post('api/faculty.php?action=delete', params);
            if (!res.success) console.error('Failed to delete faculty', id, res);
        }
        window.CRUD && CRUD.toastSuccess && CRUD.toastSuccess('Deleted selected faculty');
        refreshTable();
    } catch (e) { window.CRUD && CRUD.toastError && CRUD.toastError('Bulk delete failed: ' + e.message); }
    finally { window.CRUD && CRUD.hideLoading && CRUD.hideLoading(); }
}

function prepareAddFaculty() {
    try {
        const form = document.getElementById('addFacultyForm'); if (form) form.reset();
        // enable fields
        if (form) Array.from(form.elements).forEach(el => el.disabled = false);
        const idEl = document.getElementById('facultyId'); if (idEl) idEl.value = '';
        const title = document.getElementById('facultyModalTitle'); if (title) title.innerText = 'Add New Faculty';
        const saveBtn = document.getElementById('saveFacultyBtn'); if (saveBtn) { saveBtn.innerText = 'Save Faculty'; saveBtn.style.display = ''; }
    } catch (e) { /* ignore */ }
}

async function saveFaculty() {
    const form = document.getElementById('addFacultyForm'); const params = new FormData(form);
    if (!params.get('name')) { window.CRUD && CRUD.toastError && CRUD.toastError('Name required'); return; }
    const modalEl = document.getElementById('addFacultyModal'); if (window.CRUD && CRUD.modalLoadingStart) CRUD.modalLoadingStart(modalEl);
    try {
        const id = params.get('id'); const action = id ? 'update' : 'create';
        const res = await CRUD.post('api/faculty.php?action=' + action, params);
        if (res.success) { bootstrap.Modal.getOrCreateInstance(modalEl).hide(); window.CRUD && CRUD.toastSuccess && CRUD.toastSuccess(res.message || 'Saved'); refreshTable(); }
        else { window.CRUD && CRUD.toastError && CRUD.toastError('Save failed: ' + (res.message || res.error || 'Unknown')); }
    } catch (e) { window.CRUD && CRUD.toastError && CRUD.toastError('Request failed: ' + e.message); }
    finally { window.CRUD && CRUD.modalLoadingStop && CRUD.modalLoadingStop(modalEl); }
}

