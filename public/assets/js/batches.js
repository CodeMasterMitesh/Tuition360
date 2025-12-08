// batches.js - centralized logic for batches.php
function initBatches() {
    // Initialize DataTable with column filters
    try { initAdvancedTable('#batches-table'); } catch(e) { console.error('initBatches: initAdvancedTable failed', e); }

    // page fade-in
    const container = document.querySelector('.dashboard-container'); if (container) container.classList.add('show');

    // Selection handling for delete-selected header button
    const selectAll = document.getElementById('select-all-batches');
    const headerDeleteBtn = document.getElementById('delete-selected-batches-header');
    function updateSelectionUI() {
        const any = !!document.querySelectorAll('#batches-table tbody .row-select:checked').length;
        if (headerDeleteBtn) headerDeleteBtn.style.display = any ? '' : 'none';
        if (selectAll) {
            const total = document.querySelectorAll('#batches-table tbody .row-select').length;
            const checked = document.querySelectorAll('#batches-table tbody .row-select:checked').length;
            selectAll.checked = total>0 && checked === total;
        }
    }
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            const checked = !!this.checked;
            document.querySelectorAll('#batches-table tbody .row-select').forEach(cb => cb.checked = checked);
            updateSelectionUI();
        });
    }
    document.addEventListener('change', function(e){ if (e.target && e.target.classList && e.target.classList.contains('row-select')) updateSelectionUI(); });
    updateSelectionUI();
}

window.initBatches = initBatches;
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initBatches); else try { initBatches(); } catch(e) { console.error('initBatches immediate failed', e); }

function showAddBatchModal() {
    const form = document.getElementById('addBatchForm');
    if (form) {
        form.reset();
        Array.from(form.elements).forEach(el=>el.disabled=false);
    }
    const bid = document.getElementById('batchId'); if (bid) bid.value = '';
    const titleEl = document.querySelector('#addBatchModal .modal-title'); if (titleEl) titleEl.innerText = 'Add New Batch';
    const saveBtn = document.getElementById('saveBatchBtn'); if (saveBtn) { saveBtn.textContent = 'Save Batch'; saveBtn.style.display = ''; }
    // reset days checkboxes
    document.querySelectorAll('.day-checkbox').forEach(cb => cb.checked = false);
    const daysHidden = document.getElementById('daysOfWeek'); if (daysHidden) daysHidden.value = '';
    const branchSel = document.getElementById('batchBranch'); if (branchSel) branchSel.value = '0';
    const courseSel = document.getElementById('batchCourse'); if (courseSel) courseSel.value = '0';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('addBatchModal')).show();
}

async function editBatch(id) {
    if (window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer');
    try {
        const res = await CRUD.get(`api/batches.php?action=get&id=${encodeURIComponent(id)}`);
        if (res.success && res.data) {
            const b = res.data;
            document.getElementById('batchId').value = b.id || '';
            document.querySelector('#addBatchForm [name="title"]').value = b.title || b.name || '';
            document.querySelector('#addBatchForm [name="start_date"]').value = b.start_date || '';
            document.querySelector('#addBatchForm [name="end_date"]').value = b.end_date || '';
            document.getElementById('batchCourse') && (document.getElementById('batchCourse').value = b.course_id || 0);
            document.getElementById('batchBranch') && (document.getElementById('batchBranch').value = b.branch_id || 0);
            document.querySelector('#addBatchForm [name="capacity"]').value = b.capacity || 30;
            document.querySelector('#addBatchForm [name="time_slot"]').value = b.time_slot || '';
            document.querySelector('#addBatchForm [name="status"]').value = b.status || 'planned';
            // set days checkboxes
            if (b.days_of_week) {
                const parts = String(b.days_of_week).split(/[,;|\s]+/).map(s=>s.trim()).filter(Boolean);
                document.querySelectorAll('.day-checkbox').forEach(cb => { cb.checked = parts.includes(cb.value); });
                const daysHidden = document.getElementById('daysOfWeek'); if (daysHidden) daysHidden.value = parts.join(',');
            } else {
                document.querySelectorAll('.day-checkbox').forEach(cb => cb.checked = false);
                const daysHidden = document.getElementById('daysOfWeek'); if (daysHidden) daysHidden.value = '';
            }

            // ensure form is editable for edit
            const form = document.getElementById('addBatchForm'); if (form) Array.from(form.elements).forEach(el => el.disabled = false);
            const saveBtn = document.getElementById('saveBatchBtn'); if (saveBtn) { saveBtn.style.display = ''; saveBtn.textContent = 'Update Batch'; }
            document.querySelector('#addBatchModal .modal-title') && (document.querySelector('#addBatchModal .modal-title').innerText = 'Edit Batch');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('addBatchModal')).show();
        } else {
            CRUD.toastError && CRUD.toastError('Batch not found');
        }
    } catch (e) {
        CRUD.toastError && CRUD.toastError('Failed to load batch: ' + e.message);
    } finally {
        CRUD.hideLoading && CRUD.hideLoading();
    }
}

async function viewBatch(id) {
    await editBatch(id);
    const form = document.getElementById('addBatchForm'); if (form) Array.from(form.elements).forEach(el => el.disabled = true);
    const saveBtn = document.getElementById('saveBatchBtn'); if (saveBtn) saveBtn.style.display = 'none';
    document.querySelector('#addBatchModal .modal-title') && (document.querySelector('#addBatchModal .modal-title').innerText = 'View Batch');
}

async function deleteBatch(id) {
    if (!confirm('Delete batch ' + id + '?')) return;
    if (window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer');
    try {
        const p = new URLSearchParams(); p.append('id', id);
        const res = await CRUD.post('api/batches.php?action=delete', p);
        if (res.success) refreshTable(); else CRUD.toastError && CRUD.toastError('Delete failed');
    } catch (e) { CRUD.toastError && CRUD.toastError('Delete failed: ' + e.message); }
    finally { CRUD.hideLoading && CRUD.hideLoading(); }
}

async function saveBatch() {
    const form = document.getElementById('addBatchForm');
    // assemble days_of_week from checkboxes into hidden input
    const selectedDays = Array.from(document.querySelectorAll('.day-checkbox:checked')).map(cb => cb.value);
    const daysHidden = document.getElementById('daysOfWeek'); if (daysHidden) daysHidden.value = selectedDays.join(',');
    const params = new FormData(form);
    if (!params.get('title')) { CRUD.toastError && CRUD.toastError('Title required'); return; }
    const id = params.get('id');
    const action = id ? 'update' : 'create';
    if (window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer');
    try {
        const res = await CRUD.post('api/batches.php?action=' + action, params);
        if (res.success) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('addBatchModal')).hide();
            CRUD.toastSuccess && CRUD.toastSuccess(res.message || 'Saved');
            refreshTable();
        } else {
            CRUD.toastError && CRUD.toastError('Save failed: ' + (res.message || res.error || 'Unknown'));
        }
    } catch (e) { CRUD.toastError && CRUD.toastError('Request failed: ' + e.message); }
    finally { CRUD.hideLoading && CRUD.hideLoading(); }
}

async function deleteSelectedBatches() {
    const checked = Array.from(document.querySelectorAll('#batches-table tbody .row-select:checked')).map(cb => cb.dataset.id).filter(Boolean);
    if (!checked.length) { CRUD.toastError && CRUD.toastError('No batches selected'); return; }
    if (!confirm(`Delete ${checked.length} selected batch(es)?`)) return;
    if (window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer');
    try {
        for (const id of checked) {
            const p = new URLSearchParams(); p.append('id', id);
            const res = await CRUD.post('api/batches.php?action=delete', p);
            if (!res.success) console.error('Failed to delete batch', id, res);
        }
        CRUD.toastSuccess && CRUD.toastSuccess('Deleted selected batches');
        refreshTable();
    } catch (e) { CRUD.toastError && CRUD.toastError('Bulk delete failed: ' + e.message); }
    finally { CRUD.hideLoading && CRUD.hideLoading(); }
}

function refreshTable() { if (window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer'); setTimeout(() => window.location.reload(), 800); }
