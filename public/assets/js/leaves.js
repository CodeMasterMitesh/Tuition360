// leaves.js - per-page JS for Leaves
function initLeaves() {
    try { initAdvancedTable('#leaves-table'); } catch(e){ console.error('initLeaves: initAdvancedTable failed', e); }
    const container = document.querySelector('.dashboard-container'); if (container) container.classList.add('show');

    // Keyboard: Ctrl+F focus filter, Ctrl+N add
    document.addEventListener('keydown', function(e){ if (e.ctrlKey && e.key === 'f') { e.preventDefault(); const input=document.querySelector('#leaves-table thead tr.filters input'); if (input) { input.focus(); input.select && input.select(); } } if (e.ctrlKey && e.key === 'n') { e.preventDefault(); const btn=document.querySelector('[data-modal-target="#addLeaveModal"]') || document.querySelector('[data-bs-target="#addLeaveModal"]'); btn && btn.click(); } });

    // Reset form when opening modal for new leave
    const modal = document.getElementById('addLeaveModal');
    if (modal) {
        modal.addEventListener('show.bs.modal', function(e) {
            const leaveId = document.getElementById('leaveId');
            if (!leaveId || !leaveId.value) {
                const form = document.getElementById('addLeaveForm');
                if (form) form.reset();
                // Set defaults for staff (non-admins)
                if (!window.IS_ADMIN) {
                    const userIdField = form.querySelector('[name="user_id"]'); if (userIdField) userIdField.value = window.CURRENT_USER_ID || '';
                    const staffNameField = form.querySelector('[name="staff_name"]'); if (staffNameField) staffNameField.value = window.CURRENT_USER_NAME || '';
                }
                const branchField = form.querySelector('[name="branch_id"]'); if (branchField) branchField.value = window.CURRENT_BRANCH_ID || '';
                // Enable all fields and show save button
                Array.from(form.elements).forEach(el => el.disabled = false);
                const saveBtn = modal.querySelector('.btn-primary'); if (saveBtn) { saveBtn.style.display = ''; saveBtn.textContent = 'Apply'; }
                const titleEl = modal.querySelector('.modal-title'); if (titleEl) titleEl.textContent = 'Apply Leave';
            }
        });
    }
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initLeaves); else try { initLeaves(); } catch(e){ console.error('initLeaves immediate failed', e); }

function exportToExcel(){ if (window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer'); setTimeout(()=>{ window.location.href='?page=leaves&export=excel'; if (window.CRUD && CRUD.hideLoading) CRUD.hideLoading(); },800); }
function refreshTable(){ if (window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer'); setTimeout(()=>location.reload(),700); }

async function viewLeave(id){ if (window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer'); try{ const res = await CRUD.get(`api/leaves.php?action=get&id=${encodeURIComponent(id)}`); if (res.success && res.data){ const a = res.data; document.getElementById('leaveId').value = a.id || ''; const userIdField = document.querySelector('#addLeaveForm [name="user_id"]'); if (userIdField) userIdField.value = a.user_id || (window.CURRENT_USER_ID || ''); const branchField = document.querySelector('#addLeaveForm [name="branch_id"]'); if (branchField) branchField.value = a.branch_id || (window.CURRENT_BRANCH_ID || ''); const staffNameField = document.querySelector('#addLeaveForm [name="staff_name"]'); if (staffNameField) staffNameField.value = window.CURRENT_USER_NAME || ''; const fromField = document.querySelector('#addLeaveForm [name="from_date"]'); if (fromField) fromField.value = a.from_date || ''; const toField = document.querySelector('#addLeaveForm [name="to_date"]'); if (toField) toField.value = a.to_date || ''; const typeField = document.querySelector('#addLeaveForm [name="leave_type"]'); if (typeField) typeField.value = a.leave_type || ''; const reasonField = document.querySelector('#addLeaveForm [name="reason"]'); if (reasonField) reasonField.value = a.reason || ''; const form = document.getElementById('addLeaveForm'); if (form) Array.from(form.elements).forEach(el=>el.disabled=true); const saveBtn=document.querySelector('#addLeaveModal .btn-primary'); if(saveBtn) saveBtn.style.display='none'; document.querySelector('#addLeaveModal .modal-title').innerText='View Leave'; bootstrap.Modal.getOrCreateInstance(document.getElementById('addLeaveModal')).show(); } else { window.CRUD && CRUD.toastError && CRUD.toastError('Record not found'); } } catch(e){ window.CRUD && CRUD.toastError && CRUD.toastError('Failed: '+e.message); } finally { window.CRUD && CRUD.hideLoading && CRUD.hideLoading(); } }

async function deleteLeave(id){ if(!confirm('Delete leave '+id+'?')) return; if (window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer'); try{ const p=new URLSearchParams(); p.append('id', id); const res = await CRUD.post('api/leaves.php?action=delete', p); if (res.success) { window.CRUD && CRUD.toastSuccess && CRUD.toastSuccess(res.message||'Deleted'); refreshTable(); } else window.CRUD && CRUD.toastError && CRUD.toastError('Delete failed'); } catch(e){ window.CRUD && CRUD.toastError && CRUD.toastError('Delete failed: '+e.message);} finally{ window.CRUD && CRUD.hideLoading && CRUD.hideLoading(); } }

async function decideLeave(id, status){ const action = status === 'approved' ? 'Approve' : 'Reject'; if(!confirm(action+' leave request #'+id+'?')) return; if (window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer'); try{ const p=new URLSearchParams(); p.append('leave_id', id); p.append('status', status); p.append('decided_by', window.CURRENT_USER_ID || ''); const res = await CRUD.post('api/leaves.php?action=decide', p); if (res.success) { window.CRUD && CRUD.toastSuccess && CRUD.toastSuccess(res.message||'Leave '+status); refreshTable(); } else window.CRUD && CRUD.toastError && CRUD.toastError('Failed to '+action.toLowerCase()); } catch(e){ window.CRUD && CRUD.toastError && CRUD.toastError('Request failed: '+e.message);} finally{ window.CRUD && CRUD.hideLoading && CRUD.hideLoading(); } }

async function saveLeave(){ const form = document.getElementById('addLeaveForm'); const params = new FormData(form); if (!window.IS_ADMIN) { params.set('user_id', window.CURRENT_USER_ID || params.get('user_id') || ''); } params.set('branch_id', window.CURRENT_BRANCH_ID || params.get('branch_id') || ''); if (!params.get('user_id')) { window.CRUD && CRUD.toastError && CRUD.toastError('User required'); return; } if (!params.get('from_date') || !params.get('to_date')) { window.CRUD && CRUD.toastError && CRUD.toastError('Dates required'); return; } if (!params.get('leave_type')) { window.CRUD && CRUD.toastError && CRUD.toastError('Leave type required'); return; } if (!params.get('reason')) { window.CRUD && CRUD.toastError && CRUD.toastError('Reason required'); return; } const modalEl = document.getElementById('addLeaveModal'); window.CRUD && CRUD.modalLoadingStart && CRUD.modalLoadingStart(modalEl); try{ const res = await CRUD.post('api/leaves.php?action=apply', params); if (res.success) { bootstrap.Modal.getOrCreateInstance(modalEl).hide(); window.CRUD && CRUD.toastSuccess && CRUD.toastSuccess(res.message||'Saved'); refreshTable(); } else window.CRUD && CRUD.toastError && CRUD.toastError('Save failed: '+(res.message||res.error||'Unknown')); } catch(e){ window.CRUD && CRUD.toastError && CRUD.toastError('Request failed: '+e.message); } finally{ window.CRUD && CRUD.modalLoadingStop && CRUD.modalLoadingStop(modalEl); } } 
