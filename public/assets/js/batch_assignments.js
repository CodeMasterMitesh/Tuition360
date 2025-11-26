// batch_assignments.js
document.addEventListener('DOMContentLoaded', function(){
    initAdvancedTable('#assignments-table');
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
});

async function editAssignment(id){ if (window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer'); try{ const res = await CRUD.get(`api/batch_assignments.php?action=get&id=${encodeURIComponent(id)}`); if (res.success && res.data){ const a = res.data; document.getElementById('assignmentId').value = a.id || ''; document.getElementById('assignmentBatch').value = a.batch_id || 0; document.getElementById('assignmentUser').value = a.user_id || 0; document.querySelector('#addAssignmentForm [name="role"]').value = a.role || 'faculty'; if (a.assigned_at){ const dt = new Date(a.assigned_at); // format to local datetime-local
            const iso = new Date(dt.getTime() - dt.getTimezoneOffset()*60000).toISOString().slice(0,16);
            document.getElementById('assignmentAt').value = iso;
        } else document.getElementById('assignmentAt').value = '';
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