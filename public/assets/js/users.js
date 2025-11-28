// users.js - extracted from users.php
function initUsers() {
    try { initAdvancedTable('#users-table'); } catch(e) { console.error('initUsers: initAdvancedTable failed', e); }
    const container = document.querySelector('.dashboard-container'); if (container) container.classList.add('show');

    // Selection handling
    const selectAll = document.getElementById('select-all-users');
    const headerDeleteBtn = document.getElementById('delete-selected-users-header');
    function updateSelectionUI() {
        const any = !!document.querySelectorAll('#users-table tbody .row-select:checked').length;
        if (headerDeleteBtn) headerDeleteBtn.style.display = any ? '' : 'none';
        if (selectAll) {
            const total = document.querySelectorAll('#users-table tbody .row-select').length;
            const checked = document.querySelectorAll('#users-table tbody .row-select:checked').length;
            selectAll.checked = total > 0 && checked === total;
        }
    }
    if (selectAll) selectAll.addEventListener('change', function(){ const checked = !!this.checked; document.querySelectorAll('#users-table tbody .row-select').forEach(cb=>cb.checked=checked); updateSelectionUI(); });
    document.addEventListener('change', function(e){ if (e.target && e.target.classList && e.target.classList.contains('row-select')) updateSelectionUI(); });
    updateSelectionUI();
}
window.initUsers = initUsers;
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initUsers); else try { initUsers(); } catch(e){ console.error('initUsers immediate failed', e); }

function exportToExcel(){ if (window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer'); setTimeout(()=>{ window.location.href='?page=users&export=excel'; if (window.CRUD && CRUD.hideLoading) CRUD.hideLoading(); },800); }
function printTable(){ const table=document.getElementById('users-table').cloneNode(true); const w=window.open('','_blank'); w.document.write(`<html><head><title>Users</title></head><body><h2>Users</h2>${table.outerHTML}</body></html>`); w.document.close(); w.print(); }
function refreshTable(){ if (window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer'); setTimeout(()=>location.reload(),600); }

async function editUser(id){ if (window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer'); try{ const res = await CRUD.get(`api/users.php?action=get&id=${encodeURIComponent(id)}`); if (res.success && res.data){ const u = res.data; document.getElementById('userId').value=u.id||''; document.querySelector('#addUserForm [name="name"]').value=u.name||''; document.querySelector('#addUserForm [name="email"]').value=u.email||''; document.querySelector('#addUserForm [name="mobile"]').value=u.mobile||''; document.querySelector('#addUserForm [name="role"]').value=u.role||'staff'; document.querySelector('#addUserForm [name="branch_id"]').value=u.branch_id||''; // ensure editable
            const form = document.getElementById('addUserForm'); if (form) Array.from(form.elements).forEach(el=>el.disabled=false);
            const modalEl = document.getElementById('addUserModal'); const modal = bootstrap.Modal.getOrCreateInstance(modalEl); document.querySelector('#addUserModal .modal-title') && (document.querySelector('#addUserModal .modal-title').innerText='Edit User'); const saveBtn = modalEl.querySelector('.btn-primary'); if (saveBtn) { saveBtn.style.display=''; saveBtn.innerText='Update User'; }
            modal.show(); } else { window.CRUD && CRUD.toastError && CRUD.toastError('User not found'); } } catch(e){ window.CRUD && CRUD.toastError && CRUD.toastError('Failed: '+e.message); } finally{ window.CRUD && CRUD.hideLoading && CRUD.hideLoading(); } }

async function viewUser(id){ if (window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer'); try{ const res = await CRUD.get(`api/users.php?action=get&id=${encodeURIComponent(id)}`); if (res.success && res.data){ const u = res.data; document.getElementById('userId').value=u.id||''; document.querySelector('#addUserForm [name="name"]').value=u.name||''; document.querySelector('#addUserForm [name="email"]').value=u.email||''; document.querySelector('#addUserForm [name="mobile"]').value=u.mobile||''; document.querySelector('#addUserForm [name="role"]').value=u.role||'staff'; document.querySelector('#addUserForm [name="branch_id"]').value=u.branch_id||''; const form=document.getElementById('addUserForm'); if (form) Array.from(form.elements).forEach(el=>el.disabled=true); const saveBtn=document.querySelector('#addUserModal .btn-primary'); if(saveBtn) saveBtn.style.display='none'; document.querySelector('#addUserModal .modal-title').innerText='View User'; const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('addUserModal')); modal.show(); } else { window.CRUD && CRUD.toastError && CRUD.toastError('User not found'); } } catch(e){ window.CRUD && CRUD.toastError && CRUD.toastError('Failed: '+e.message); } finally { window.CRUD && CRUD.hideLoading && CRUD.hideLoading(); } }

async function deleteUser(id){ if(!confirm('Delete user '+id+'?')) return; if (window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer'); try{ const p = new URLSearchParams(); p.append('id', id); const res = await CRUD.post('api/users.php?action=delete', p); if (res.success) { window.CRUD && CRUD.toastSuccess && CRUD.toastSuccess(res.message || 'Deleted'); refreshTable(); } else { window.CRUD && CRUD.toastError && CRUD.toastError('Delete failed'); } } catch(e){ window.CRUD && CRUD.toastError && CRUD.toastError('Delete failed: '+e.message); } finally { window.CRUD && CRUD.hideLoading && CRUD.hideLoading(); } }

async function saveUser(){ const form=document.getElementById('addUserForm'); const params=new FormData(form); if(!params.get('name')||!params.get('email')){ window.CRUD && CRUD.toastError && CRUD.toastError('Name and email required'); return; } const modalEl=document.getElementById('addUserModal'); window.CRUD && CRUD.modalLoadingStart && CRUD.modalLoadingStart(modalEl); try{ const id=params.get('id'); const action = id ? 'update' : 'create'; const res = await CRUD.post('api/users.php?action='+action, params); if (res.success) { bootstrap.Modal.getOrCreateInstance(modalEl).hide(); window.CRUD && CRUD.toastSuccess && CRUD.toastSuccess(res.message || 'Saved'); refreshTable(); } else { window.CRUD && CRUD.toastError && CRUD.toastError('Save failed: ' + (res.message || res.error || 'Unknown')); } } catch(e){ window.CRUD && CRUD.toastError && CRUD.toastError('Request failed: ' + e.message); } finally { window.CRUD && CRUD.modalLoadingStop && CRUD.modalLoadingStop(modalEl); } }
