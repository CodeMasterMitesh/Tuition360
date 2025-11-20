<?php
// app/views/attendance_students.php
$attendance = [];
$controllerFile = __DIR__ . '/../controllers/AttendanceController.php';
if (file_exists($controllerFile)) {
    require_once $controllerFile;
    $cls = 'AttendanceController';
    if (class_exists($cls) && method_exists($cls, 'getAll')) {
        $attendance = $cls::getAll('student');
    }
}
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$totalAttendance = count($attendance);
$totalPages = 1;
?>
<?php include __DIR__ . '/partials/nav.php'; ?>
<div class="container-fluid dashboard-container fade-in">
    <div class="breadcrumb-container d-flex justify-content-between align-items-center">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=dashboard"><i class="fas fa-home"></i> Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-user-graduate"></i> Student Attendance</li>
            </ol>
        </nav>
        <button class="btn btn-primary btn-action" data-bs-toggle="modal" data-bs-target="#addAttendanceModal">
            <i class="fas fa-plus"></i> Mark Attendance
        </button>
    </div>
    <div class="advanced-table-container">
        <div class="table-controls">
            <div class="table-header">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="form-control" id="searchInput" placeholder="Search attendance..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="action-buttons">
                    <button class="btn btn-success btn-action" onclick="exportToExcel()">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </button>
                    <button class="btn btn-secondary btn-action" onclick="printTable()">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <button class="btn btn-info btn-action" onclick="refreshTable()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>
        </div>
        <div class="table-responsive position-relative" id="tableContainer">
            <table class="table data-table" id="attendance-table">
                <thead>
                    <tr>
                        <th width="80">ID</th>
                        <th>Student</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th width="150" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if (empty($attendance)): ?>
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <h4>No attendance records found</h4>
                                    <p>No attendance records match your search criteria</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAttendanceModal">
                                        <i class="fas fa-plus"></i> Mark First Attendance
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($attendance as $record): ?>
                            <tr>
                                <td><?= htmlspecialchars($record['id'] ?? '') ?></td>
                                <td><?= htmlspecialchars($record['student'] ?? '') ?></td>
                                <td><?= htmlspecialchars($record['date'] ?? '') ?></td>
                                <td>
                                    <?php if (isset($record['status'])): ?>
                                        <span class="status-badge <?= $record['status'] === 'present' ? 'status-active' : 'status-inactive' ?>">
                                            <?= ucfirst($record['status']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge status-inactive">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <button class="btn btn-sm btn-outline-primary btn-table" onclick="editAttendance(<?= $record['id'] ?? 0 ?>)" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-info btn-table" onclick="viewAttendance(<?= $record['id'] ?? 0 ?>)" title="View">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger btn-table" onclick="deleteAttendance(<?= $record['id'] ?? 0 ?>)" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<!-- Add Attendance Modal -->
<div class="modal fade" id="addAttendanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Mark Attendance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addAttendanceForm">
                    <input type="hidden" name="id" id="attendanceId" value="">
                    <input type="hidden" name="branch_id" id="attendanceBranchId" value="0">
                    <div class="mb-3">
                        <label class="form-label">Student</label>
                        <input type="text" class="form-control" name="student" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" class="form-control" name="date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-control" name="status" required>
                            <option value="present">Present</option>
                            <option value="absent">Absent</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveAttendance()">Save Attendance</button>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
<script>
    // minimal client-side scripts reused from attendance.php
    document.addEventListener('DOMContentLoaded', function() { document.querySelector('.dashboard-container').classList.add('show'); });
    function exportToExcel(){ CRUD.showLoading('tableContainer'); setTimeout(()=>{ window.location.href='?page=attendance_students&export=excel'; CRUD.hideLoading(); },800);} 
    function printTable(){ const table=document.getElementById('attendance-table').cloneNode(true); const w=window.open('','_blank'); w.document.write(`<html><head><title>Attendance</title></head><body><h2>Attendance</h2>${table.outerHTML}</body></html>`); w.document.close(); w.print(); }
    function refreshTable(){ CRUD.showLoading('tableContainer'); setTimeout(()=>location.reload(),600);} 
    async function editAttendance(id){ CRUD.showLoading('tableContainer'); try{ const res=await CRUD.get(`api/attendance.php?action=get&id=${encodeURIComponent(id)}`); if(res.success&&res.data){ const a=res.data; document.getElementById('attendanceId').value=a.id||''; document.querySelector('#addAttendanceForm [name="student"]').value=a.student||''; document.querySelector('#addAttendanceForm [name="date"]').value=a.date||''; document.querySelector('#addAttendanceForm [name="status"]').value=a.status||'present'; document.getElementById('attendanceBranchId').value=a.branch_id??0; const modalEl=document.getElementById('addAttendanceModal'); const modal=bootstrap.Modal.getOrCreateInstance(modalEl); modal.show(); } else CRUD.toastError('Record not found'); }catch(e){ CRUD.toastError('Failed: '+e.message);} finally{ CRUD.hideLoading(); } }
    async function viewAttendance(id){ await editAttendance(id); const form=document.getElementById('addAttendanceForm'); Array.from(form.elements).forEach(el=>el.disabled=true); const saveBtn=document.querySelector('#addAttendanceModal .btn-primary'); if(saveBtn) saveBtn.style.display='none'; document.querySelector('#addAttendanceModal .modal-title').innerText='View Attendance'; }
    async function deleteAttendance(id){ if(!confirm('Delete attendance '+id+'?')) return; CRUD.showLoading('tableContainer'); try{ const p=new URLSearchParams(); p.append('id', id); const res=await CRUD.post('api/attendance.php?action=delete', p); if(res.success){ CRUD.toastSuccess(res.message||'Deleted'); refreshTable(); } else CRUD.toastError('Delete failed'); }catch(e){ CRUD.toastError('Delete failed: '+e.message);} finally{ CRUD.hideLoading(); } }
    async function saveAttendance(){ const form=document.getElementById('addAttendanceForm'); const params=new FormData(form); if(!params.get('student')){ CRUD.toastError('Student is required'); return;} const modalEl=document.getElementById('addAttendanceModal'); CRUD.modalLoadingStart(modalEl); try{ const res=await CRUD.post('api/attendance.php?action=mark', params); if(res.success){ const modal=bootstrap.Modal.getOrCreateInstance(modalEl); modal.hide(); CRUD.toastSuccess(res.message||'Saved'); refreshTable(); } else CRUD.toastError('Save failed: '+(res.message||res.error||'Unknown')); }catch(e){ CRUD.toastError('Save request failed: '+e.message);} finally{ CRUD.modalLoadingStop(modalEl); } }
</script>
