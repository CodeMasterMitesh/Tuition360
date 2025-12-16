// attendance.js - shared attendance page behaviors (no per-column filters)

// attendance.js - employee/faculty attendance
(function(){
    function initAttendance() {
        try {
            const table = $('#attendance-table');
            if (!table || !table.length) return;
            // prevent double-initialization which can throw column count warnings
            if ($.fn.DataTable.isDataTable(table)) {
                table.DataTable().destroy();
            }

            // Add per-column filters (skip actions column)
            const thead = table.find('thead');
            thead.find('tr.filters').remove();
            const headerCells = thead.find('tr').first().children();
            const filterRow = $('<tr>').addClass('filters');
            headerCells.each(function() {
                const th = $('<th>');
                const text = ($(this).text() || '').trim().toLowerCase();
                if (text === 'actions') {
                    th.html('');
                } else {
                    th.html('<input type="text" class="form-control form-control-sm" placeholder="Search...">');
                }
                filterRow.append(th);
            });
            thead.append(filterRow);

            // Initialize DataTable
            const dataTable = table.DataTable({
                dom: 'lrtip',
                orderCellsTop: true,
                fixedHeader: true,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                responsive: true,
                columnDefs: [
                    { orderable: false, targets: -1 },
                    { responsivePriority: 1, targets: 0 },
                    { responsivePriority: 2, targets: -1 }
                ]
            });
            // Wire up column filters
            thead.find('tr.filters th').each(function(index) {
                const input = $(this).find('input');
                if (!input.length) return;
                input.on('keyup change', function() {
                    const val = this.value;
                    if (dataTable.column(index).search() !== val) {
                        dataTable.column(index).search(val).draw();
                    }
                });
            });
            table.data('datatable-instance', dataTable);
        } catch (e) {
            console.error('initAttendance failed', e);
        }

        // Show animation class
        try { document.querySelector('.dashboard-container')?.classList.add('show'); } catch (e) {}

        // Ctrl+N opens modal
        document.addEventListener('keydown', function(e){
            if (e.ctrlKey && (e.key === 'n' || e.key === 'N')) {
                e.preventDefault();
                document.querySelector('[data-bs-target="#addAttendanceModal"]')?.click();
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
                    // For student attendance
                    try { if (form.querySelector('#studentSelect')) form.querySelector('#studentSelect').value = a.entity_id || ''; } catch(e){}
                    try { if (form.querySelector('#studentId')) form.querySelector('#studentId').value = a.entity_id || ''; } catch(e){}
                    try { if (form.querySelector('[name="date"]')) form.querySelector('[name="date"]').value = a.date || ''; } catch(e){}
                    try { if (form.querySelector('[name="status"]')) form.querySelector('[name="status"]').value = a.status || 'present'; } catch(e){}
                    try { if (form.querySelector('[name="in_time"]')) form.querySelector('[name="in_time"]').value = a.in_time || ''; } catch(e){}
                    try { if (form.querySelector('[name="out_time"]')) form.querySelector('[name="out_time"]').value = a.out_time || ''; } catch(e){}
                    try { if (form.querySelector('[name="note"]')) form.querySelector('[name="note"]').value = a.note || ''; } catch(e){}
                    try { if (form.querySelector('[name="branch_id"]')) form.querySelector('[name="branch_id"]').value = a.branch_id || 0; } catch(e){}
                    try { if (form.querySelector('[name="batch_id"]')) form.querySelector('[name="batch_id"]').value = a.batch_id || ''; } catch(e){}
                    
                    // For employee/faculty attendance (backward compatibility)
                    try { if (form.querySelector('[name="employee_name"]')) form.querySelector('[name="employee_name"]').value = a.employee_name || (window.CURRENT_EMPLOYEE_NAME || ''); } catch(e){}
                    try { if (form.querySelector('[name="entity_type"]')) form.querySelector('[name="entity_type"]').value = a.entity_type || (window.CURRENT_ENTITY_TYPE || 'employee'); } catch(e){}
                    try { if (form.querySelector('[name="entity_id"]')) form.querySelector('[name="entity_id"]').value = a.entity_id || ''; } catch(e){}
                }
                // Update modal title
                const titleEl = document.querySelector('#addAttendanceModal .modal-title'); 
                if (titleEl) titleEl.innerText = 'Edit Attendance';
                
                // Update button text to 'Update'
                const saveBtn = document.querySelector('#addAttendanceModal .modal-footer .btn-primary');
                if (saveBtn) saveBtn.textContent = 'Update Attendance';
                
                // ensure fields enabled and save visible
                if (form) Array.from(form.elements).forEach(el => el.disabled = false);
                if (saveBtn) saveBtn.style.display = '';
                const modalEl = document.getElementById('addAttendanceModal'); if (modalEl) bootstrap.Modal.getOrCreateInstance(modalEl).show();
            } else {
                CRUD.toastError('Record not found');
            }
        } catch(e) { CRUD.toastError('Failed: ' + (e.message || e)); }
        finally { CRUD.hideLoading(); }
    }

    async function viewAttendance(id) {
        CRUD.showLoading('tableContainer');
        try {
            const res = await CRUD.get(`api/attendance.php?action=get&id=${encodeURIComponent(id)}`);
            if (res && res.success && res.data) {
                const a = res.data;
                
                // Check if we have a dedicated view modal
                const viewModal = document.getElementById('viewAttendanceModal');
                if (viewModal) {
                    // Use dedicated view modal for students
                    document.getElementById('viewStudentName').textContent = a.student_name || 'N/A';
                    document.getElementById('viewBatch').textContent = a.batch_title || '-';
                    document.getElementById('viewDate').textContent = a.date || '-';
                    
                    const statusEl = document.getElementById('viewStatus');
                    const status = a.status || 'absent';
                    const badgeClass = status === 'present' ? 'success' : (status === 'leave' ? 'warning' : 'danger');
                    statusEl.innerHTML = `<span class="badge bg-${badgeClass}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;
                    
                    document.getElementById('viewInTime').textContent = a.in_time || '-';
                    document.getElementById('viewOutTime').textContent = a.out_time || '-';
                    document.getElementById('viewBranch').textContent = a.branch_name || '-';
                    document.getElementById('viewNote').textContent = a.note || '-';
                    
                    bootstrap.Modal.getOrCreateInstance(viewModal).show();
                } else {
                    // Fallback to edit modal in readonly mode
                    await editAttendance(id);
                    const form = document.getElementById('addAttendanceForm');
                    if (form) Array.from(form.elements).forEach(el => el.disabled = true);
                    const saveBtn = document.querySelector('#addAttendanceModal .btn-primary'); if (saveBtn) saveBtn.style.display = 'none';
                    const titleEl = document.querySelector('#addAttendanceModal .modal-title'); if (titleEl) titleEl.innerText = 'View Attendance';
                }
            } else {
                CRUD.toastError('Record not found');
            }
        } catch(e) { CRUD.toastError('Failed: ' + (e.message || e)); }
        finally { CRUD.hideLoading(); }
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
        
        // For student attendance page
        const studentSelect = document.getElementById('studentSelect');
        const studentId = document.getElementById('studentId');
        if (studentSelect && studentSelect.value) {
            params.set('entity_id', studentSelect.value);
            params.set('entity_type', 'student');
        } else if (studentId && studentId.value) {
            params.set('entity_id', studentId.value);
            params.set('entity_type', 'student');
        }
        
        // For employee/faculty attendance page (backward compatibility)
        if (window.CURRENT_ENTITY_TYPE) {
            params.set('entity_type', window.CURRENT_ENTITY_TYPE);
        }
        if (window.CURRENT_EMPLOYEE_ID) {
            params.set('entity_id', window.CURRENT_EMPLOYEE_ID);
        }
        
        // Validation
        if (!params.get('entity_type')) { CRUD.toastError('Entity type is required'); return; }
        if (!params.get('entity_id')) { CRUD.toastError('Student/Person is required'); return; }
        if (!params.get('date')) { CRUD.toastError('Date is required'); return; }
        if (!params.get('status')) { CRUD.toastError('Status is required'); return; }
        
        CRUD.modalLoadingStart(document.getElementById('addAttendanceModal'));
        try {
            const res = await CRUD.post('api/attendance.php?action=mark', params);
            if (res && res.success) { 
                const modalEl = document.getElementById('addAttendanceModal'); 
                bootstrap.Modal.getOrCreateInstance(modalEl).hide(); 
                CRUD.toastSuccess(res.message || 'Saved'); 
                refreshTable(); 
            }
            else CRUD.toastError('Save failed: ' + (res.message || res.error || 'Unknown'));
        } catch(e) { CRUD.toastError('Save request failed: ' + (e.message || e)); }
        finally { CRUD.modalLoadingStop(document.getElementById('addAttendanceModal')); }
    }


    function resetAttendanceModal() {
        const form = document.getElementById('addAttendanceForm');
        if (form) form.reset();
        const idField = document.getElementById('attendanceId');
        if (idField) idField.value = '';
        const titleEl = document.querySelector('#addAttendanceModal .modal-title');
        if (titleEl) titleEl.textContent = 'Mark Attendance';
        const saveBtn = document.querySelector('#addAttendanceModal .modal-footer .btn-primary');
        if (saveBtn) saveBtn.textContent = 'Save Attendance';
    }

    // expose
    window.initAttendance = initAttendance;
    window.exportToExcel = exportToExcel;
    window.printTable = printTable;
    window.refreshTable = refreshTable;
    window.editAttendance = editAttendance;
    window.resetAttendanceModal = resetAttendanceModal;
    window.viewAttendance = viewAttendance;
    window.deleteAttendance = deleteAttendance;
    window.saveAttendance = saveAttendance;

    // Set defaults when opening the modal for new entry
    const addModal = document.getElementById('addAttendanceModal');
    if (addModal) {
        addModal.addEventListener('show.bs.modal', (ev) => {
            const idEl = document.getElementById('attendanceId');
            if (idEl && !idEl.value) {
                const form = document.getElementById('addAttendanceForm');
                const today = new Date().toISOString().slice(0,10);
                form?.querySelector('[name="employee_name"]') && (form.querySelector('[name="employee_name"]').value = window.CURRENT_EMPLOYEE_NAME || '');
                form?.querySelector('[name="entity_type"]') && (form.querySelector('[name="entity_type"]').value = window.CURRENT_ENTITY_TYPE || 'employee');
                form?.querySelector('[name="entity_id"]') && (form.querySelector('[name="entity_id"]').value = window.CURRENT_EMPLOYEE_ID || '');
                form?.querySelector('[name="date"]') && (form.querySelector('[name="date"]').value = today);
                form?.querySelector('[name="status"]') && (form.querySelector('[name="status"]').value = 'present');
                form?.querySelector('[name="in_time"]') && (form.querySelector('[name="in_time"]').value = '');
                form?.querySelector('[name="out_time"]') && (form.querySelector('[name="out_time"]').value = '');
                form?.querySelector('[name="note"]') && (form.querySelector('[name="note"]').value = '');
            }
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initAttendance);
    else initAttendance();
})();
