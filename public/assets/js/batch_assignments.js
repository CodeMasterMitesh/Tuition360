// batch_assignments.js
function initBatchAssignments(){
    try { initAdvancedTable('#assignments-table'); } catch(e) { console.error('initBatchAssignments: initAdvancedTable failed', e); }
    const container = document.querySelector('.dashboard-container'); if (container) container.classList.add('show');
    const selectAll = document.getElementById('select-all-assignments');
    const headerDelete = document.getElementById('delete-selected-assignments-header');
    function updateUI(){
        const any = !!document.querySelectorAll('#assignments-table tbody .row-select:checked').length;
        if (headerDelete) headerDelete.style.display = any ? '' : 'none';
        if (selectAll){ const total = document.querySelectorAll('#assignments-table tbody .row-select').length; const checked = document.querySelectorAll('#assignments-table tbody .row-select:checked').length; selectAll.checked = total>0 && checked===total; }
    }
    if (selectAll) selectAll.addEventListener('change', ()=>{ const c = !!selectAll.checked; document.querySelectorAll('#assignments-table tbody .row-select').forEach(cb=>cb.checked=c); updateUI(); });
    document.addEventListener('change', function(e){ if (e.target && e.target.classList && e.target.classList.contains('row-select')) updateUI(); });
    updateUI();
    // keyboard shortcuts (Ctrl+F to focus filter, Ctrl+N to open Add modal)
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'f') {
            e.preventDefault();
            const input = document.querySelector('#assignments-table thead tr.filters input');
            if (input) { input.focus(); input.select && input.select(); }
        }
        if (e.ctrlKey && e.key === 'n') {
            e.preventDefault();
            if (typeof showAddModal === 'function') showAddModal('addAssignmentModal','addAssignmentForm');
            else {
                const btn = document.querySelector('[data-bs-target="#addAssignmentModal"]');
                btn && btn.click();
            }
        }
    });
}

// populate subjects when a batch is selected
document.addEventListener('DOMContentLoaded', function(){
    try {
        const batchSelect = document.getElementById('assignmentBatch');
        const subjectsSelect = document.getElementById('assignmentSubjects');
        // subject map injected by server (if present)
        const SUBJECT_MAP = window.__subjectMap || {};
        if (batchSelect && subjectsSelect) {
            batchSelect.addEventListener('change', async function(){
                const opt = batchSelect.options[batchSelect.selectedIndex];
                const courseId = opt ? opt.getAttribute('data-course') : null;
                subjectsSelect.innerHTML = '';
                if (!courseId) return;
                try {
                    const res = await fetch(`api/courses.php?action=get_subjects&id=${encodeURIComponent(courseId)}`, {credentials: 'same-origin'});
                    const json = await res.json();
                    const ids = Array.isArray(json.data) ? json.data.map(r => r.subject_id || r.id || r) : [];
                    ids.forEach(id => {
                        const title = SUBJECT_MAP[id] || (`Subject ${id}`);
                        const opt = document.createElement('option'); opt.value = id; opt.textContent = title; subjectsSelect.appendChild(opt);
                    });
                } catch(e) { console.warn('Failed to load course subjects', e); }
            });
        }
    } catch(e){ console.warn('subjects init failed', e); }
});

// Student add-more + autocomplete
function createStudentRow(initial) {
    const wrapper = document.createElement('div'); wrapper.className = 'd-flex gap-2 mb-2 align-items-center student-row';
    const input = document.createElement('input'); input.type = 'text'; input.className = 'form-control student-search'; input.placeholder = 'Search student by name, email or phone';
    const hidden = document.createElement('input'); hidden.type = 'hidden'; hidden.name = 'user_ids[]'; hidden.className = 'student-id';
    const removeBtn = document.createElement('button'); removeBtn.type='button'; removeBtn.className='btn btn-sm btn-outline-danger'; removeBtn.innerHTML='<i class="fas fa-trash"></i>';
    const suggestions = document.createElement('div'); suggestions.className='autocomplete-suggestions border bg-white position-absolute'; suggestions.style.zIndex = 1055; suggestions.style.display='none'; suggestions.style.maxHeight='200px'; suggestions.style.overflow='auto'; suggestions.style.minWidth='240px';

    const container = document.createElement('div'); container.style.position='relative'; container.appendChild(input); container.appendChild(suggestions);
    wrapper.appendChild(container); wrapper.appendChild(hidden); wrapper.appendChild(removeBtn);

    // debounce
    let timer = null;
    input.addEventListener('input', function(){
        clearTimeout(timer);
        const q = input.value.trim();
        hidden.value = '';
        if (!q) { suggestions.style.display='none'; suggestions.innerHTML=''; return; }
        timer = setTimeout(async function(){
            try {
                const res = await fetch(`api/students.php?action=search&q=${encodeURIComponent(q)}`, {credentials: 'same-origin'});
                const json = await res.json();
                const rows = Array.isArray(json.data) ? json.data : json.data || [];
                suggestions.innerHTML = '';
                if (!rows.length) { suggestions.style.display='none'; return; }
                rows.forEach(r => {
                    const it = document.createElement('div'); it.className='p-2 suggestion-item'; it.style.cursor='pointer'; it.textContent = (r.name || '') + (r.email ? ' — '+r.email : '') + (r.mobile ? ' ('+r.mobile+')' : '');
                    it.addEventListener('click', function(){
                        input.value = r.name || r.email || r.mobile || '';
                        hidden.value = r.id || '';
                        suggestions.style.display='none'; suggestions.innerHTML='';
                    });
                    suggestions.appendChild(it);
                });
                suggestions.style.display = 'block';
            } catch(e) { console.warn('student search failed', e); }
        }, 250);
    });

    removeBtn.addEventListener('click', function(){ wrapper.remove(); });
    if (initial && initial.id) {
        input.value = initial.name || initial.email || '';
        hidden.value = initial.id;
    }
    return wrapper;
}

document.addEventListener('DOMContentLoaded', function(){
    try {
        const container = document.getElementById('assignmentStudentsContainer');
        const addBtn = document.getElementById('addStudentRowBtn');
        if (!container || !addBtn) return;
        // add one empty row by default
        container.appendChild(createStudentRow());
        addBtn.addEventListener('click', function(){ container.appendChild(createStudentRow()); });
    } catch(e){ console.warn('student rows init failed', e); }
});

window.initBatchAssignments = initBatchAssignments;
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initBatchAssignments); else try { initBatchAssignments(); } catch(e) { console.error('initBatchAssignments immediate failed', e); }

async function editAssignment(id){ if (window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer'); try{ const res = await CRUD.get(`api/batch_assignments.php?action=get&id=${encodeURIComponent(id)}`); if (res.success && res.data){ const a = res.data; document.getElementById('assignmentId').value = a.id || ''; document.getElementById('assignmentBatch').value = a.batch_id || 0; document.getElementById('assignmentUser').value = a.user_id || 0; document.querySelector('#addAssignmentForm [name="role"]').value = a.role || 'faculty'; if (a.assigned_at){ const dt = new Date(a.assigned_at); // format to local datetime-local
            const iso = new Date(dt.getTime() - dt.getTimezoneOffset()*60000).toISOString().slice(0,16);
            document.getElementById('assignmentAt').value = iso;
        } else document.getElementById('assignmentAt').value = '';
        // trigger batch change to populate subjects, then set subjects and student selections
        const batchEl = document.getElementById('assignmentBatch');
        const subjectsEl = document.getElementById('assignmentSubjects');
        const studentsEl = document.getElementById('assignmentStudents');
        if (batchEl) {
            // set value then dispatch change so subjects populate
            batchEl.value = a.batch_id || 0;
            batchEl.dispatchEvent(new Event('change'));
            setTimeout(function(){
                try {
                    // set subjects if provided
                    if (Array.isArray(a.subjects) && subjectsEl) {
                        Array.from(subjectsEl.options).forEach(opt => { opt.selected = a.subjects.indexOf(opt.value) !== -1 || a.subjects.indexOf(parseInt(opt.value)) !== -1; });
                    }
                    // if role is student and we have a user_id that could be a single student, select it
                    if (a.role === 'student' && studentsEl) {
                        // if backend stored multiple students, it would come via separate rows; select the user_id if present
                        Array.from(studentsEl.options).forEach(opt => { opt.selected = (parseInt(opt.value) === parseInt(a.user_id)); });
                    }
                } catch(e) { console.warn('failed to set subjects/students in editAssignment', e); }
            }, 300);
        }
        bootstrap.Modal.getOrCreateInstance(document.getElementById('addAssignmentModal')).show(); } else CRUD.toastError && CRUD.toastError('Not found'); }catch(e){ CRUD.toastError && CRUD.toastError('Failed: '+e.message);} finally{ CRUD.hideLoading && CRUD.hideLoading(); } }

async function saveAssignment(){ const form = document.getElementById('addAssignmentForm'); const params = new FormData(form); const id = params.get('id'); const action = id ? 'update' : 'create'; try{ const res = await CRUD.post('api/batch_assignments.php?action='+action, params); if (res.success){ bootstrap.Modal.getOrCreateInstance(document.getElementById('addAssignmentModal')).hide(); CRUD.toastSuccess && CRUD.toastSuccess('Saved'); setTimeout(()=>location.reload(),600); } else CRUD.toastError && CRUD.toastError('Save failed'); }catch(e){ CRUD.toastError && CRUD.toastError('Request failed: '+e.message); } }

async function deleteAssignment(id){ if (!confirm('Delete assignment '+id+'?')) return; try{ const p = new URLSearchParams(); p.append('id', id); const res = await CRUD.post('api/batch_assignments.php?action=delete', p); if (res.success) { CRUD.toastSuccess && CRUD.toastSuccess('Deleted'); setTimeout(()=>location.reload(),600);} else CRUD.toastError && CRUD.toastError('Delete failed'); }catch(e){ CRUD.toastError && CRUD.toastError('Request failed: '+e.message); } }

async function deleteSelectedAssignments(){ const checked = Array.from(document.querySelectorAll('#assignments-table tbody .row-select:checked')).map(cb=>cb.dataset.id).filter(Boolean); if (!checked.length){ CRUD.toastError && CRUD.toastError('No assignments selected'); return; } if (!confirm(`Delete ${checked.length} selected assignment(s)?`)) return; for (const id of checked){ try{ const p = new URLSearchParams(); p.append('id', id); await CRUD.post('api/batch_assignments.php?action=delete', p); }catch(e){ console.error('Failed', id, e); } } CRUD.toastSuccess && CRUD.toastSuccess('Deleted selected'); setTimeout(()=>location.reload(),600); }

function exportToExcel() {
    if (window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer');
    setTimeout(() => {
        window.location.href = '?page=batch_assignments&export=excel';
        if (window.CRUD && CRUD.hideLoading) CRUD.hideLoading();
    }, 800);
}

function printTable() {
    const table = document.getElementById('assignments-table').cloneNode(true);
    const w = window.open('', '_blank');
    w.document.write(`<html><head><title>Batch Assignments</title><style>body{font-family:Arial}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:8px}</style></head><body><h2>Batch Assignments</h2>${table.outerHTML}<p>Generated on: ${new Date().toLocaleDateString()}</p></body></html>`);
    w.document.close();
    w.print();
}

function refreshTable() { if (window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer'); setTimeout(()=>location.reload(),700); }