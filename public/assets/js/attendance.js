// attendance.js - shared attendance page behaviors (no per-column filters)
(function(){
    function initAttendance() {
        try {
            const table = $('#attendance-table');
            if (!table || !table.length) return;
            // Initialize DataTable without injecting column filter row
            const dataTable = table.DataTable({ dom: 'lrtip', orderCellsTop:true, fixedHeader:true, pageLength:10, lengthMenu:[10,25,50,100], responsive:true, columnDefs:[{orderable:false, targets:-1}] });
            // Optionally expose the instance
            table.data('datatable-instance', dataTable);
        } catch(e) {
            console.error('initAttendance failed', e);
        }

        // make container show animation consistent
        try { document.querySelector('.dashboard-container').classList.add('show'); } catch(e){}

        // keyboard shortcuts: Ctrl+N to open Add modal
        document.addEventListener('keydown', function(e){
            if (e.ctrlKey && (e.key === 'n' || e.key === 'N')) {
                e.preventDefault();
                const btn = document.querySelector('[data-bs-target="#addAttendanceModal"]');
                if (btn) btn.click();
            }
        });
    }

    function exportToExcel(pageName) {
        CRUD.showLoading('tableContainer');
        setTimeout(() => {
            const page = pageName || (new URLSearchParams(window.location.search).get('page')) || 'attendance';
            window.location.href = '?page=' + encodeURIComponent(page) + '&export=excel';
            CRUD.hideLoading();
        }, 600);
    }

    function printTable() {
        try {
            const table = document.getElementById('attendance-table');
            if (!table) return;
            const cloned = table.cloneNode(true);
            const w = window.open('', '_blank');
            w.document.write(`<html><head><title>Attendance</title><style>body{font-family:Arial,sans-serif}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:8px;text-align:left}th{background:#f8f9fa}</style></head><body><h2>Attendance</h2>${cloned.outerHTML}<p>Generated on: ${new Date().toLocaleString()}</p></body></html>`);
            w.document.close();
            w.print();
        } catch(e) { console.error('printTable failed', e); }
    }

    function refreshTable() { CRUD.showLoading('tableContainer'); setTimeout(()=>location.reload(),500); }

    async function editAttendance(id) {
        CRUD.showLoading('tableContainer');
        try {
            const res = await CRUD.get(`api/attendance.php?action=get&id=${encodeURIComponent(id)}`);
            if (res && res.success && res.data) {
                const a = res.data;
                const idEl = document.getElementById('attendanceId'); if (idEl) idEl.value = a.id || '';
                const form = document.getElementById('addAttendanceForm');
                if (form) {
                    try { if (form.querySelector('[name="student"]')) form.querySelector('[name="student"]').value = a.student || ''; } catch(e){}
                    try { if (form.querySelector('[name="date"]')) form.querySelector('[name="date"]').value = a.date || ''; } catch(e){}
                    try { if (form.querySelector('[name="status"]')) form.querySelector('[name="status"]').value = a.status || 'present'; } catch(e){}
                    try { if (form.querySelector('[name="in_time"]')) form.querySelector('[name="in_time"]').value = a.in_time || ''; } catch(e){}
                    try { if (form.querySelector('[name="out_time"]')) form.querySelector('[name="out_time"]').value = a.out_time || ''; } catch(e){}
                    try { if (form.querySelector('[name="entity_id"]')) form.querySelector('[name="entity_id"]').value = a.entity_id || ''; } catch(e){}
                    try { if (form.querySelector('[name="branch_id"]')) form.querySelector('[name="branch_id"]').value = a.branch_id || 0; } catch(e){}
                    try { if (form.querySelector('[name="note"]')) form.querySelector('[name="note"]').value = a.note || ''; } catch(e){}
                }
                // ensure fields enabled and save visible
                if (form) Array.from(form.elements).forEach(el => el.disabled = false);
                const saveBtn = document.querySelector('#addAttendanceModal .btn-primary'); if (saveBtn) saveBtn.style.display = '';
                const modalEl = document.getElementById('addAttendanceModal'); if (modalEl) bootstrap.Modal.getOrCreateInstance(modalEl).show();
            } else {
                CRUD.toastError('Record not found');
            }
        } catch(e) { CRUD.toastError('Failed: ' + (e.message || e)); }
        finally { CRUD.hideLoading(); }
    }

    async function viewAttendance(id) {
        await editAttendance(id);
        const form = document.getElementById('addAttendanceForm');
        if (form) Array.from(form.elements).forEach(el => el.disabled = true);
        const saveBtn = document.querySelector('#addAttendanceModal .btn-primary'); if (saveBtn) saveBtn.style.display = 'none';
        const titleEl = document.querySelector('#addAttendanceModal .modal-title'); if (titleEl) titleEl.innerText = 'View Attendance';
    }

    async function deleteAttendance(id) {
        if (!confirm('Are you sure you want to delete this attendance record?')) return;
        CRUD.showLoading('tableContainer');
        try {
            const params = new URLSearchParams(); params.append('id', id);
            const res = await CRUD.post('api/attendance.php?action=delete', params);
            if (res && res.success) { CRUD.toastSuccess(res.message || 'Deleted'); refreshTable(); }
            else CRUD.toastError('Delete failed');
        } catch(e) { CRUD.toastError('Delete request failed: ' + (e.message || e)); }
        finally { CRUD.hideLoading(); }
    }

    async function saveAttendance() {
        const form = document.getElementById('addAttendanceForm');
        if (!form) return;
        const params = new FormData(form);
        // basic validations
        if (form.querySelector('[name="entity_id"]') && !params.get('entity_id')) { CRUD.toastError('Entity is required'); return; }
        if (form.querySelector('[name="branch_id"]') && !params.get('branch_id')) { /* branch optional in some variants */ }
        CRUD.modalLoadingStart(document.getElementById('addAttendanceModal'));
        try {
            const res = await CRUD.post('api/attendance.php?action=mark', params);
            if (res && res.success) { const modalEl = document.getElementById('addAttendanceModal'); bootstrap.Modal.getOrCreateInstance(modalEl).hide(); CRUD.toastSuccess(res.message || 'Saved'); refreshTable(); }
            else CRUD.toastError('Save failed: ' + (res.message || res.error || 'Unknown'));
        } catch(e) { CRUD.toastError('Save request failed: ' + (e.message || e)); }
        finally { CRUD.modalLoadingStop(document.getElementById('addAttendanceModal')); }
    }

    // expose to global scope so inline onclick attributes still work
    window.initAttendance = initAttendance;
    window.exportToExcel = exportToExcel;
    window.printTable = printTable;
    window.refreshTable = refreshTable;
    window.editAttendance = editAttendance;
    window.viewAttendance = viewAttendance;
    window.deleteAttendance = deleteAttendance;
    window.saveAttendance = saveAttendance;

    // auto init on DOM ready
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initAttendance);
    else initAttendance();

})();
// attendance.js - used by attendance pages
function initAttendance(){ try { initAdvancedTable('#attendance-table'); } catch(e){ console.error('initAttendance failed', e); } const c=document.querySelector('.dashboard-container'); if(c) c.classList.add('show'); }
if(document.readyState==='loading') document.addEventListener('DOMContentLoaded', initAttendance); else try{ initAttendance(); }catch(e){console.error(e);} 

function exportToExcel(pageName){ if(window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer'); setTimeout(()=>{ window.location.href='?page='+ (pageName||'attendance') +'&export=excel'; if(window.CRUD && CRUD.hideLoading) CRUD.hideLoading(); },800); }
function refreshTable(){ if(window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer'); setTimeout(()=>location.reload(),600); }
async function editAttendance(id){ if(window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer'); try{ const res=await CRUD.get(`api/attendance.php?action=get&id=${encodeURIComponent(id)}`); if(res.success&&res.data){ const a=res.data; document.getElementById('attendanceId').value=a.id||''; document.querySelector('#addAttendanceForm [name="student"]').value=a.student||''; document.querySelector('#addAttendanceForm [name="date"]').value=a.date||''; document.querySelector('#addAttendanceForm [name="status"]').value=a.status||'present'; document.getElementById('attendanceBranchId') && (document.getElementById('attendanceBranchId').value=a.branch_id ?? 0); const form=document.getElementById('addAttendanceForm'); if(form) Array.from(form.elements).forEach(el=>el.disabled=false); document.querySelector('#addAttendanceModal .modal-title') && (document.querySelector('#addAttendanceModal .modal-title').innerText='Edit Attendance'); const saveBtn=document.querySelector('#addAttendanceModal .btn-primary'); if(saveBtn){ saveBtn.style.display=''; saveBtn.innerText='Update'; } bootstrap.Modal.getOrCreateInstance(document.getElementById('addAttendanceModal')).show(); } else window.CRUD && CRUD.toastError && CRUD.toastError('Record not found'); }catch(e){ window.CRUD && CRUD.toastError && CRUD.toastError('Failed: '+e.message);} finally{ window.CRUD && CRUD.hideLoading && CRUD.hideLoading(); } }
async function viewAttendance(id){ await editAttendance(id); const form=document.getElementById('addAttendanceForm'); if(form) Array.from(form.elements).forEach(el=>el.disabled=true); const saveBtn=document.querySelector('#addAttendanceModal .btn-primary'); if(saveBtn) saveBtn.style.display='none'; document.querySelector('#addAttendanceModal .modal-title') && (document.querySelector('#addAttendanceModal .modal-title').innerText='View Attendance'); }
async function deleteAttendance(id){ if(!confirm('Delete attendance '+id+'?')) return; if(window.CRUD && CRUD.showLoading) CRUD.showLoading('tableContainer'); try{ const p=new URLSearchParams(); p.append('id', id); const res=await CRUD.post('api/attendance.php?action=delete', p); if(res.success){ window.CRUD && CRUD.toastSuccess && CRUD.toastSuccess('Deleted'); refreshTable(); } else window.CRUD && CRUD.toastError && CRUD.toastError('Delete failed'); }catch(e){ window.CRUD && CRUD.toastError && CRUD.toastError('Delete failed: '+e.message);} finally{ window.CRUD && CRUD.hideLoading && CRUD.hideLoading(); } }
async function saveAttendance(){ const form=document.getElementById('addAttendanceForm'); const params=new FormData(form); if(!params.get('student')){ window.CRUD && CRUD.toastError && CRUD.toastError('Student is required'); return; } const modalEl=document.getElementById('addAttendanceModal'); window.CRUD && CRUD.modalLoadingStart && CRUD.modalLoadingStart(modalEl); try{ const res=await CRUD.post('api/attendance.php?action=mark', params); if(res.success){ bootstrap.Modal.getOrCreateInstance(modalEl).hide(); window.CRUD && CRUD.toastSuccess && CRUD.toastSuccess(res.message||'Saved'); refreshTable(); } else window.CRUD && CRUD.toastError && CRUD.toastError('Save failed: '+(res.message||res.error||'Unknown')); }catch(e){ window.CRUD && CRUD.toastError && CRUD.toastError('Save request failed: '+e.message);} finally{ window.CRUD && CRUD.modalLoadingStop && CRUD.modalLoadingStop(modalEl); } }
