// Use global assignment to avoid redeclaration when the script loads multiple times.
window.FAC_UPLOAD_BASE = window.FAC_UPLOAD_BASE || '/public/uploads/faculty/';

function initFaculty() {
    try { initAdvancedTable('#faculty-table'); } catch (e) { console.error('initFaculty: initAdvancedTable failed', e); }
    const container = document.querySelector('.dashboard-container'); if (container) container.classList.add('show');

    const selectAll = document.getElementById('select-all-faculty');
    const headerDeleteBtn = document.getElementById('delete-selected-faculty-header');
    const topDeleteBtn = document.getElementById('delete-selected-faculty');
    const updateSelectionUI = () => {
        const any = !!document.querySelectorAll('#faculty-table tbody .row-select:checked').length;
        if (headerDeleteBtn) headerDeleteBtn.style.display = any ? '' : 'none';
        if (topDeleteBtn) topDeleteBtn.style.display = any ? '' : 'none';
        if (selectAll) {
            const total = document.querySelectorAll('#faculty-table tbody .row-select').length;
            const checked = document.querySelectorAll('#faculty-table tbody .row-select:checked').length;
            selectAll.checked = total > 0 && checked === total;
        }
    };
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            const checked = !!this.checked;
            document.querySelectorAll('#faculty-table tbody .row-select').forEach(cb => cb.checked = checked);
            updateSelectionUI();
        });
    }
    document.addEventListener('change', e => { if (e.target && e.target.classList && e.target.classList.contains('row-select')) updateSelectionUI(); });
    updateSelectionUI();

    document.addEventListener('keydown', e => {
        if (e.ctrlKey && e.key === 'f') { e.preventDefault(); const input = document.querySelector('#faculty-table thead tr.filters input'); if (input) { input.focus(); input.select && input.select(); } }
        if (e.ctrlKey && e.key === 'n') { e.preventDefault(); const btn = document.querySelector('[data-bs-target="#addFacultyModal"]'); if (btn) btn.click(); }
    });

    const addBtn = document.querySelector('[data-bs-target="#addFacultyModal"]');
    if (addBtn && !addBtn.dataset.facultyPrepared) {
        addBtn.dataset.facultyPrepared = '1';
        addBtn.addEventListener('click', prepareAddFaculty);
    }
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initFaculty); else try { initFaculty(); } catch (e) { console.error('initFaculty immediate failed', e); }

function exportToExcel() { if (window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer'); setTimeout(() => { window.location.href = '?page=faculty&export=excel'; if (window.CRUD && CRUD.hideLoading) CRUD.hideLoading(); }, 800); }
function printTable() { const table = document.getElementById('faculty-table').cloneNode(true); const w = window.open('', '_blank'); w.document.write(`<html><head><title>Faculty</title><style>body{font-family:Arial}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:8px}</style></head><body><h2>Faculty</h2>${table.outerHTML}<p>Generated on: ${new Date().toLocaleDateString()}</p></body></html>`); w.document.close(); w.print(); }
function refreshTable() { if (window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer'); setTimeout(() => location.reload(), 700); }

function setDocInfo(targetId, filename) {
    const el = document.getElementById(targetId); if (!el) return;
    if (filename) { el.innerHTML = `Existing: <a href="${FAC_UPLOAD_BASE + filename}" target="_blank" rel="noopener">${filename}</a>`; el.style.display = ''; }
    else { el.textContent = ''; el.style.display = 'none'; }
}

function clearPhotoPreview() {
    const img = document.getElementById('facultyPhotoPreview'); if (img) { img.src = ''; img.style.display = 'none'; }
    const removeBtn = document.getElementById('removeFacultyPhoto'); if (removeBtn) removeBtn.style.display = 'none';
    const input = document.getElementById('facultyPhotoInput'); if (input) input.value = '';
}

function addFacultyEducationRow(prefill) {
    const wrap = document.getElementById('facultyEducationList'); if (!wrap) return;
    const row = document.createElement('div'); row.className = 'row g-2 align-items-end mb-2';
    row.innerHTML = `
        <div class="col-md-2"><input class="form-control" placeholder="Degree" value="${prefill?.degree || ''}"></div>
        <div class="col-md-3"><input class="form-control" placeholder="University/Institute" value="${prefill?.institute || ''}"></div>
        <div class="col-md-2"><input type="date" class="form-control" value="${prefill?.from_date || ''}"></div>
        <div class="col-md-2"><input type="date" class="form-control" value="${prefill?.to_date || ''}"></div>
        <div class="col-md-1"><input class="form-control" placeholder="Grade" value="${prefill?.grade || ''}"></div>
        <div class="col-md-2"><input class="form-control" placeholder="Specialization" value="${prefill?.specialization || ''}"></div>
        <div class="col-auto"><button type="button" class="btn btn-sm btn-outline-danger">Remove</button></div>`;
    row.querySelector('button').addEventListener('click', () => row.remove());
    wrap.appendChild(row);
}

function addFacultyEmploymentRow(prefill) {
    const wrap = document.getElementById('facultyEmploymentList'); if (!wrap) return;
    const row = document.createElement('div'); row.className = 'row g-2 align-items-end mb-2';
    row.innerHTML = `
        <div class="col-md-3"><input class="form-control" placeholder="Organisation" value="${prefill?.organisation || ''}"></div>
        <div class="col-md-3"><input class="form-control" placeholder="Designation" value="${prefill?.designation || ''}"></div>
        <div class="col-md-2"><input type="date" class="form-control" value="${prefill?.from_date || ''}"></div>
        <div class="col-md-2"><input type="date" class="form-control" value="${prefill?.to_date || ''}"></div>
        <div class="col-md-2"><input type="number" step="0.01" class="form-control" placeholder="Annual CTC" value="${prefill?.annual_ctc || ''}"></div>
        <div class="col-auto"><button type="button" class="btn btn-sm btn-outline-danger">Remove</button></div>`;
    row.querySelector('button').addEventListener('click', () => row.remove());
    wrap.appendChild(row);
}

function collectFacultyEducation() {
    const wrap = document.getElementById('facultyEducationList'); if (!wrap) return [];
    return [...wrap.children]
        .map(r => {
            const inputs = [...r.querySelectorAll('input')];
            return { degree: inputs[0].value.trim(), institute: inputs[1].value.trim(), from_date: inputs[2].value, to_date: inputs[3].value, grade: inputs[4].value.trim(), specialization: inputs[5].value.trim() };
        })
        .filter(row => Object.values(row).some(v => (v || '').toString().trim() !== ''));
}

function collectFacultyEmployment() {
    const wrap = document.getElementById('facultyEmploymentList'); if (!wrap) return [];
    return [...wrap.children]
        .map(r => {
            const inputs = [...r.querySelectorAll('input')];
            return { organisation: inputs[0].value.trim(), designation: inputs[1].value.trim(), from_date: inputs[2].value, to_date: inputs[3].value, annual_ctc: inputs[4].value };
        })
        .filter(row => Object.values(row).some(v => (v || '').toString().trim() !== ''));
}

if (!window.__facultyRowHandlersAttached) {
    window.__facultyRowHandlersAttached = true;
    document.addEventListener('click', function (e) {
        if (e.target && e.target.id === 'addFacultyEducationRow') addFacultyEducationRow();
        if (e.target && e.target.id === 'addFacultyEmploymentRow') addFacultyEmploymentRow();
    });
}

function renderFacultyForm(f, mode = 'edit') {
    const setVal = (sel, val) => { const el = document.querySelector(sel); if (el) { if (el.type === 'file') el.value = ''; else el.value = val ?? ''; } };
    const setDate = (sel, val) => { const el = document.querySelector(sel); if (el) el.value = (val || '').slice(0, 10); };
    document.getElementById('facultyId').value = f.id || '';
    setVal('#addFacultyForm [name="name"]', f.name);
    setVal('#addFacultyForm [name="email"]', f.email);
    setVal('#addFacultyForm [name="mobile"]', f.mobile || f.phone);
    const branchEl = document.getElementById('facultyBranch'); if (branchEl) branchEl.value = f.branch_id || 0;
    setDate('#addFacultyForm [name="dob"]', f.dob);
    setVal('#addFacultyForm [name="gender"]', f.gender);
    setVal('#addFacultyForm [name="marital_status"]', f.marital_status);
    setDate('#addFacultyForm [name="joining_date"]', f.joining_date);
    setDate('#addFacultyForm [name="resign_date"]', f.resign_date);
    setVal('#addFacultyForm [name="in_time"]', f.in_time);
    setVal('#addFacultyForm [name="out_time"]', f.out_time);
    setVal('#addFacultyForm [name="address"]', f.address);
    setVal('#addFacultyForm [name="area"]', f.area);
    setVal('#addFacultyForm [name="city"]', f.city);
    setVal('#addFacultyForm [name="pincode"]', f.pincode);
    setVal('#addFacultyForm [name="state"]', f.state);
    setVal('#addFacultyForm [name="country"]', f.country);
    setDocInfo('aadharFileInfoFaculty', f.aadhar_card);
    setDocInfo('panFileInfoFaculty', f.pan_card);
    setDocInfo('passportFileInfoFaculty', f.passport);
    const img = document.getElementById('facultyPhotoPreview');
    const removeBtn = document.getElementById('removeFacultyPhoto');
    if (img) {
        if (f.profile_photo) { img.src = FAC_UPLOAD_BASE + f.profile_photo; img.style.display = ''; if (removeBtn && mode !== 'view') removeBtn.style.display = ''; }
        else { clearPhotoPreview(); if (removeBtn) removeBtn.style.display = 'none'; }
    }
    const eduWrap = document.getElementById('facultyEducationList'); if (eduWrap) eduWrap.innerHTML = '';
    const empWrap = document.getElementById('facultyEmploymentList'); if (empWrap) empWrap.innerHTML = '';
    if (Array.isArray(f.education) && f.education.length) f.education.forEach(item => addFacultyEducationRow(item)); else addFacultyEducationRow();
    if (Array.isArray(f.employment) && f.employment.length) f.employment.forEach(item => addFacultyEmploymentRow(item)); else addFacultyEmploymentRow();
    const form = document.getElementById('addFacultyForm');
    if (form) Array.from(form.elements).forEach(el => el.disabled = (mode === 'view'));
    const title = document.getElementById('facultyModalTitle'); if (title) title.innerText = mode === 'view' ? 'View Faculty' : 'Edit Faculty';
    const saveBtn = document.getElementById('saveFacultyBtn'); if (saveBtn) { saveBtn.style.display = mode === 'view' ? 'none' : ''; saveBtn.innerText = 'Update Faculty'; }
    bootstrap.Modal.getOrCreateInstance(document.getElementById('addFacultyModal')).show();
}

async function editFaculty(id) {
    if (window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer');
    try {
        const res = await CRUD.get(`api/faculty.php?action=get&id=${encodeURIComponent(id)}`);
        if (res.success && res.data) { renderFacultyForm(res.data, 'edit'); }
        else { window.CRUD && CRUD.toastError && CRUD.toastError('Faculty not found'); }
    } catch (e) { window.CRUD && CRUD.toastError && CRUD.toastError('Failed to load: ' + e.message); }
    finally { window.CRUD && CRUD.hideLoading && CRUD.hideLoading(); }
}

async function viewFaculty(id) {
    if (window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer');
    try {
        const res = await CRUD.get(`api/faculty.php?action=get&id=${encodeURIComponent(id)}`);
        if (res.success && res.data) { renderFacultyForm(res.data, 'view'); }
        else { window.CRUD && CRUD.toastError && CRUD.toastError('Faculty not found'); }
    } catch (e) { window.CRUD && CRUD.toastError && CRUD.toastError('Failed to load: ' + e.message); }
    finally { window.CRUD && CRUD.hideLoading && CRUD.hideLoading(); }
}

async function deleteFaculty(id) {
    if (!confirm('Delete faculty ' + id + '?')) return;
    if (window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer');
    try {
        const params = new URLSearchParams(); params.append('id', id);
        const res = await CRUD.post('api/faculty.php?action=delete', params);
        if (res.success) { window.CRUD && CRUD.toastSuccess && CRUD.toastSuccess(res.message || 'Deleted'); refreshTable(); } else { window.CRUD && CRUD.toastError && CRUD.toastError('Delete failed'); }
    } catch (e) { window.CRUD && CRUD.toastError && CRUD.toastError('Delete failed: ' + e.message); }
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
    const form = document.getElementById('addFacultyForm'); if (form) { form.reset(); Array.from(form.elements).forEach(el => el.disabled = false); }
    document.getElementById('facultyId').value = '';
    clearPhotoPreview();
    setDocInfo('aadharFileInfoFaculty', null); setDocInfo('panFileInfoFaculty', null); setDocInfo('passportFileInfoFaculty', null);
    const eduWrap = document.getElementById('facultyEducationList'); if (eduWrap) { eduWrap.innerHTML = ''; addFacultyEducationRow(); }
    const empWrap = document.getElementById('facultyEmploymentList'); if (empWrap) { empWrap.innerHTML = ''; addFacultyEmploymentRow(); }
    const title = document.getElementById('facultyModalTitle'); if (title) title.innerText = 'Add New Faculty';
    const saveBtn = document.getElementById('saveFacultyBtn'); if (saveBtn) { saveBtn.innerText = 'Save Faculty'; saveBtn.style.display = ''; }
}

async function saveFaculty() {
    const form = document.getElementById('addFacultyForm'); const params = new FormData(form);
    params.append('education', JSON.stringify(collectFacultyEducation()));
    params.append('employment', JSON.stringify(collectFacultyEmployment()));
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

document.addEventListener('change', function (e) {
    if (e.target && e.target.id === 'facultyPhotoInput') {
        const input = e.target; const file = input.files && input.files[0];
        const img = document.getElementById('facultyPhotoPreview');
        const removeBtn = document.getElementById('removeFacultyPhoto');
        if (file) {
            const reader = new FileReader();
            reader.onload = ev => { img.src = ev.target.result; img.style.display = ''; if (removeBtn) removeBtn.style.display = ''; };
            reader.readAsDataURL(file);
        } else { clearPhotoPreview(); }
    }
});

document.addEventListener('click', async function (e) {
    if (e.target && e.target.id === 'removeFacultyPhoto') {
        const id = document.getElementById('facultyId').value;
        const input = document.getElementById('facultyPhotoInput'); if (input) input.value = '';
        clearPhotoPreview();
        if (id) {
            try { const res = await CRUD.post('api/faculty.php?action=delete-photo', new URLSearchParams({ id })); if (res.success) { window.CRUD && CRUD.toastSuccess && CRUD.toastSuccess('Photo removed'); } else { window.CRUD && CRUD.toastError && CRUD.toastError(res.message || 'Failed to remove'); } } catch (err) { window.CRUD && CRUD.toastError && CRUD.toastError('Remove failed: ' + err.message); }
        }
    }
});

