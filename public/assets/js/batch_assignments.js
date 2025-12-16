// batch_assignments.js
(function() {
'use strict';

let availableSubjects = [];

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

async function loadSubjectsForBatch(batchId){
    const batchSelect = document.getElementById('assignmentBatch');
    if (!batchSelect) return [];
    const opt = Array.from(batchSelect.options).find(o => String(o.value) === String(batchId));
    const courseId = opt ? opt.getAttribute('data-course') : null;
    if (!courseId) { availableSubjects = []; return []; }
    try {
        const res = await fetch(`api/courses.php?action=get_subjects&id=${encodeURIComponent(courseId)}`, {credentials: 'same-origin'});
        const json = await res.json();
        const rows = Array.isArray(json.data) ? json.data : (json.data ? [json.data] : []);
        availableSubjects = rows.map(r => ({
            id: r.subject_id || r.id || r,
            title: r.title || (window.__subjectMap ? window.__subjectMap[r.subject_id || r.id] : null) || (`Subject ${r.subject_id || r.id}`)
        }));
        return availableSubjects;
    } catch(e) { console.warn('Failed to load course subjects', e); return []; }
}

// Subject row creation
function createSubjectRow(initial) {
    const tr = document.createElement('tr');
    tr.className = 'subject-row';
    
    const tdSubject = document.createElement('td');
    const select = document.createElement('select');
    select.className = 'form-control form-control-sm subject-select';
    select.name = 'subjects[]';
    select.required = true;
    
    const defaultOpt = document.createElement('option');
    defaultOpt.value = '';
    defaultOpt.textContent = '-- Select Subject --';
    select.appendChild(defaultOpt);
    
    availableSubjects.forEach(s => {
        const opt = document.createElement('option');
        opt.value = s.id;
        opt.textContent = s.title;
        if (initial && initial.id == s.id) opt.selected = true;
        select.appendChild(opt);
    });
    
    tdSubject.appendChild(select);
    
    const tdAction = document.createElement('td');
    tdAction.className = 'text-center';
    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'btn btn-sm btn-outline-danger';
    removeBtn.innerHTML = '<i class="fas fa-trash"></i>';
    removeBtn.addEventListener('click', function() {
        tr.remove();
        updateSubjectsTableVisibility();
    });
    tdAction.appendChild(removeBtn);
    
    tr.appendChild(tdSubject);
    tr.appendChild(tdAction);
    
    return tr;
}

function updateSubjectsTableVisibility() {
    const wrapper = document.getElementById('assignmentSubjectsTableWrapper');
    const container = document.getElementById('assignmentSubjectsContainer');
    if (wrapper && container) {
        wrapper.style.display = container.querySelectorAll('.subject-row').length > 0 ? '' : 'none';
    }
}

function toDateTimeLocal(val){
    if (!val) return '';
    // Accepts `YYYY-MM-DD HH:MM:SS` or ISO; normalizes to `YYYY-MM-DDTHH:MM` for datetime-local
    try {
        let normalized = String(val).replace(' ', 'T');
        // Strip seconds if present
        const match = normalized.match(/^(\d{4}-\d{2}-\d{2})[ T](\d{2}:\d{2})(?::\d{2})?/);
        if (match) normalized = `${match[1]}T${match[2]}`;
        const dt = new Date(normalized);
        if (isNaN(dt.getTime())) return '';
        const iso = new Date(dt.getTime() - dt.getTimezoneOffset()*60000).toISOString();
        return iso.slice(0,16);
    } catch(e){ return ''; }
}

// populate subjects when a batch is selected
function initBatchSubjects() {
    try {
        const batchSelect = document.getElementById('assignmentBatch');
        if (batchSelect) {
            // Check if listener is already attached (to avoid duplicates)
            if (batchSelect.dataset.listenerAttached !== 'true') {
                batchSelect.addEventListener('change', async function(){
                    await loadSubjectsForBatch(batchSelect.value);
                    // Clear existing subject rows when batch changes
                    const container = document.getElementById('assignmentSubjectsContainer');
                    if (container) {
                        container.querySelectorAll('.subject-row').forEach(r => r.remove());
                        updateSubjectsTableVisibility();
                    }
                });
                batchSelect.dataset.listenerAttached = 'true';
            }
        }
        
        // Initialize Add Subject button
        const addSubjectBtn = document.getElementById('addSubjectRowBtn');
        const container = document.getElementById('assignmentSubjectsContainer');
        if (addSubjectBtn && container) {
            // Check if listener is already attached
            if (addSubjectBtn.dataset.listenerAttached !== 'true') {
                addSubjectBtn.addEventListener('click', function() {
                    // console.log('Add Subject clicked, available subjects:', availableSubjects.length);
                    if (availableSubjects.length === 0) {
                        alert('Please select a batch first to load available subjects.');
                        return;
                    }
                    const row = createSubjectRow();
                    container.appendChild(row);
                    updateSubjectsTableVisibility();
                });
                addSubjectBtn.dataset.listenerAttached = 'true';
            }
        }
    } catch(e){ console.warn('subjects init failed', e); }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBatchSubjects);
} else {
    try { initBatchSubjects(); } catch(e) { console.warn('initBatchSubjects immediate failed', e); }
}

// Student add-more + autocomplete
function createStudentRow(initial) {
    const wrapper = document.createElement('div'); wrapper.className = 'd-flex gap-2 mb-2 align-items-center student-row';
    const input = document.createElement('input'); input.type = 'text'; input.className = 'form-control student-search'; input.placeholder = 'Search student by name, email or phone';
    const hidden = document.createElement('input'); hidden.type = 'hidden'; hidden.name = 'students_ids[]'; hidden.className = 'student-id';
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

function initStudentRows() {
    try {
        const container = document.getElementById('assignmentStudentsContainer');
        const addBtn = document.getElementById('addStudentRowBtn');
        if (!container || !addBtn) return;
        
        // Check if listener is already attached
        if (addBtn.dataset.listenerAttached !== 'true') {
            // add one empty row by default if none
            if (!container.querySelector('.student-row')) container.appendChild(createStudentRow());
            
            addBtn.addEventListener('click', function(){ 
                // console.log('Add Student clicked');
                container.appendChild(createStudentRow()); 
            });
            
            addBtn.dataset.listenerAttached = 'true';
        }
    } catch(e){ console.warn('student rows init failed', e); }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initStudentRows);
} else {
    try { initStudentRows(); } catch(e) { console.warn('initStudentRows immediate failed', e); }
}

window.initBatchAssignments = initBatchAssignments;
window.editAssignment = editAssignment;
window.deleteAssignment = deleteAssignment;
window.saveAssignment = saveAssignment;
window.deleteSelectedAssignments = deleteSelectedAssignments;
window.exportToExcel = exportToExcel;
window.printTable = printTable;
window.refreshTable = refreshTable;

// Reset function to clear dynamic content when opening Add modal
function resetAssignmentModal() {
    // console.log('Resetting assignment modal to Add mode');
    
    // Clear all student rows
    const studentsContainer = document.getElementById('assignmentStudentsContainer');
    if (studentsContainer) {
        studentsContainer.querySelectorAll('.student-row').forEach(r => r.remove());
        // Add one empty row
        studentsContainer.appendChild(createStudentRow());
    }
    
    // Clear all subject rows
    const subjectsContainer = document.getElementById('assignmentSubjectsContainer');
    if (subjectsContainer) {
        subjectsContainer.querySelectorAll('.subject-row').forEach(r => r.remove());
        updateSubjectsTableVisibility();
    }
    
    // Reset available subjects
    availableSubjects = [];
    
    // Reset form hidden fields
    const assignmentIdField = document.getElementById('assignmentId');
    if (assignmentIdField) assignmentIdField.value = '';
    
    // Reset dropdowns
    const batchSelect = document.getElementById('assignmentBatch');
    if (batchSelect) batchSelect.value = '0';
    
    const userSelect = document.getElementById('assignmentUser');
    if (userSelect) userSelect.value = '0';
    
    // Reset modal title and button (already done by showAddModal but just to be sure)
    const titleEl = document.getElementById('assignmentModalTitle');
    if (titleEl) titleEl.textContent = 'Add Assignment';
    const saveBtn = document.getElementById('assignmentSaveBtn');
    if (saveBtn) saveBtn.textContent = 'Save';
}

window.resetAssignmentModal = resetAssignmentModal;

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initBatchAssignments); else try { initBatchAssignments(); } catch(e) { console.error('initBatchAssignments immediate failed', e); }

// Add modal event listener to reinitialize buttons when modal is shown
let isEditMode = false; // Track whether we're in edit mode

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        const modalEl = document.getElementById('addAssignmentModal');
        if (modalEl) {
            // Use 'show.bs.modal' to reset BEFORE modal is visible
            modalEl.addEventListener('show.bs.modal', function() {
                // console.log('Modal about to show, isEditMode:', isEditMode);
                
                // If not in edit mode, this is Add mode - reset everything
                if (!isEditMode) {
                    // console.log('Add mode detected, resetting modal BEFORE show');
                    // Use setTimeout to let showAddModal finish its reset first
                    setTimeout(() => {
                        resetAssignmentModal();
                    }, 0);
                }
                
                // Reset the flag after checking
                isEditMode = false;
            });
            
            // Use 'shown.bs.modal' to reinitialize buttons AFTER modal is visible
            modalEl.addEventListener('shown.bs.modal', function() {
                // console.log('Modal shown, reinitializing buttons');
                initStudentRows();
                initBatchSubjects();
            });
        }
    });
} else {
    const modalEl = document.getElementById('addAssignmentModal');
    if (modalEl) {
        // Use 'show.bs.modal' to reset BEFORE modal is visible
        modalEl.addEventListener('show.bs.modal', function() {
            // console.log('Modal about to show, isEditMode:', isEditMode);
            
            // If not in edit mode, this is Add mode - reset everything
            if (!isEditMode) {
                // console.log('Add mode detected, resetting modal BEFORE show');
                // Use setTimeout to let showAddModal finish its reset first
                setTimeout(() => {
                    resetAssignmentModal();
                }, 0);
            }
            
            // Reset the flag after checking
            isEditMode = false;
        });
        
        // Use 'shown.bs.modal' to reinitialize buttons AFTER modal is visible
        modalEl.addEventListener('shown.bs.modal', function() {
            // console.log('Modal shown, reinitializing buttons');
            initStudentRows();
            initBatchSubjects();
        });
    }
}

async function editAssignment(id){
    // Set flag to indicate we're opening in edit mode
    isEditMode = true;
    
    if (window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer');
    try {
        const res = await CRUD.get(`api/batch_assignments.php?action=get&id=${encodeURIComponent(id)}`);
        if (res.success && res.data){
            const a = res.data;
            document.getElementById('assignmentId').value = a.id || '';
            document.getElementById('assignmentUser').value = a.user_id || 0;
            document.querySelector('#addAssignmentForm [name="role"]').value = a.role || 'faculty';
            document.getElementById('assignmentAt').value = toDateTimeLocal(a.assigned_at);

            const studentsContainer = document.getElementById('assignmentStudentsContainer');
            const subjectsContainer = document.getElementById('assignmentSubjectsContainer');
            
            // Load subjects for the batch first
            await loadSubjectsForBatch(a.batch_id || 0);
            
            // NOW set the batch value (after loading subjects)
            document.getElementById('assignmentBatch').value = a.batch_id || 0;
            
            // Populate subject rows if subject_ids are stored
            if (subjectsContainer) {
                subjectsContainer.querySelectorAll('.subject-row').forEach(r => r.remove());
                const subjectIds = Array.isArray(a.subject_ids) ? a.subject_ids : [];
                
                if (subjectIds.length > 0) {
                    subjectIds.forEach(subjId => {
                        const row = createSubjectRow({ id: subjId });
                        subjectsContainer.appendChild(row);
                    });
                }
                updateSubjectsTableVisibility();
            }

            // populate student rows if there are students_ids stored (junction-aware)
            const sids = Array.isArray(a.students_ids) ? a.students_ids : (Array.isArray(a.user_ids) ? a.user_ids : (a.students_ids ? [a.students_ids] : []));
            if (sids && sids.length && studentsContainer) {
                studentsContainer.querySelectorAll('.student-row').forEach(n => n.remove());
                for (const sid of sids) {
                    try {
                        let studentData = null;
                        try { if (window.__studentMap && window.__studentMap[sid]) studentData = { id: sid, name: window.__studentMap[sid] }; } catch(e){}
                        if (!studentData) {
                            const sres = await fetch(`api/students.php?action=get&id=${encodeURIComponent(sid)}`, {credentials: 'same-origin'});
                            const sj = await sres.json();
                            if (sj && sj.success && sj.data) studentData = sj.data; else if (sj && sj.id) studentData = sj;
                        }
                        const row = createStudentRow(studentData || { id: sid, name: '' });
                        studentsContainer.appendChild(row);
                    } catch(e) { console.warn('failed to load student for edit', e); }
                }
            }

            // set modal to Edit mode labels
            try {
                const titleEl = document.getElementById('assignmentModalTitle'); if (titleEl) titleEl.textContent = 'Edit Assignment';
                const saveBtn = document.getElementById('assignmentSaveBtn'); if (saveBtn) saveBtn.textContent = 'Update';
            } catch(e) { /* ignore */ }
            
            // Reinitialize button listeners after content is loaded
            setTimeout(() => {
                initStudentRows();
                initBatchSubjects();
            }, 100);
            
            bootstrap.Modal.getOrCreateInstance(document.getElementById('addAssignmentModal')).show();
        } else {
            CRUD.toastError && CRUD.toastError('Not found');
        }
    } catch(e){
        CRUD.toastError && CRUD.toastError('Failed: '+e.message);
    } finally {
        CRUD.hideLoading && CRUD.hideLoading();
    }
}

async function saveAssignment(){ 
    const form = document.getElementById('addAssignmentForm'); 
    const params = new FormData(form);
    
    // Remove empty student IDs
    const allStudentIds = params.getAll('students_ids[]');
    params.delete('students_ids[]');
    allStudentIds.forEach(id => {
        if (id && id.trim()) {
            params.append('students_ids[]', id);
        }
    });
    
    // Remove empty subject IDs
    const allSubjectIds = params.getAll('subjects[]');
    params.delete('subjects[]');
    allSubjectIds.forEach(id => {
        if (id && id.trim()) {
            params.append('subjects[]', id);
        }
    });
    
    const id = params.get('id'); 
    const action = id ? 'update' : 'create'; 
    try{ 
        const res = await CRUD.post('api/batch_assignments.php?action='+action, params); 
        if (res.success){ 
            bootstrap.Modal.getOrCreateInstance(document.getElementById('addAssignmentModal')).hide(); 
            CRUD.toastSuccess && CRUD.toastSuccess('Saved'); 
            setTimeout(()=>location.reload(),600); 
        } else CRUD.toastError && CRUD.toastError('Save failed'); 
    }catch(e){ 
        CRUD.toastError && CRUD.toastError('Request failed: '+e.message); 
    } 
}

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

})(); // End of IIFE