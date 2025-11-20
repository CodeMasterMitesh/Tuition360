<?php
// app/views/attendance_faculty.php
$attendance = [];
$controllerFile = __DIR__ . '/../controllers/AttendanceController.php';
if (file_exists($controllerFile)) {
    require_once $controllerFile;
    $cls = 'AttendanceController';
    if (class_exists($cls) && method_exists($cls, 'getAll')) {
        $attendance = $cls::getAll('faculty');
    }
}
// Load faculties and branches for the modal dropdowns
$faculties = [];
$branches = [];
$dbFile = __DIR__ . '/../../config/db.php';
if (file_exists($dbFile)) {
    require_once $dbFile;
    $fRes = mysqli_query($conn, "SELECT id, name FROM users WHERE role = 'faculty' ORDER BY name");
    if ($fRes) while ($r = mysqli_fetch_assoc($fRes)) $faculties[] = $r;
    $bRes = mysqli_query($conn, "SELECT id, name FROM branches ORDER BY name");
    if ($bRes) while ($r = mysqli_fetch_assoc($bRes)) $branches[] = $r;
}
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$totalAttendance = count($attendance);
$totalPages = 1;

// build faculty id -> name map for fast lookup when rendering table
$facultyMap = [];
foreach ($faculties as $f) {
    $facultyMap[$f['id']] = $f['name'];
}
?>
<?php include __DIR__ . '/partials/nav.php'; ?>
<div class="container-fluid dashboard-container fade-in">
    <div class="breadcrumb-container d-flex justify-content-between align-items-center">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=dashboard"><i class="fas fa-home"></i> Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-chalkboard-teacher"></i> Faculty Attendance</li>
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
                        <th>Faculty</th>
                        <th>Date</th>
                        <th>In Time</th>
                        <th>Out Time</th>
                        <th>Status</th>
                        <th width="150" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if (empty($attendance)): ?>
                        <tr>
                            <td colspan="7">
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
                                <td>
                                    <?php
                                        $fid = $record['entity_id'] ?? null;
                                        $displayName = '';
                                        if ($fid && isset($facultyMap[$fid])) $displayName = $facultyMap[$fid];
                                        elseif (!empty($record['student'])) $displayName = $record['student'];
                                        elseif ($fid) $displayName = 'ID: ' . $fid;
                                    ?>
                                    <?= htmlspecialchars($displayName) ?>
                                </td>
                                <td><?= htmlspecialchars($record['date'] ?? '') ?></td>
                                <td><?= htmlspecialchars($record['in_time'] ?? '') ?></td>
                                <td><?= htmlspecialchars($record['out_time'] ?? '') ?></td>
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
                    <input type="hidden" name="entity_type" value="faculty">
                    <div class="mb-3">
                        <label class="form-label">Faculty</label>
                        <select class="form-control" name="entity_id" id="entitySelect" required>
                            <option value="">-- Select Faculty --</option>
                            <?php foreach ($faculties as $f): ?>
                                <option value="<?= htmlspecialchars($f['id']) ?>"><?= htmlspecialchars($f['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Branch</label>
                        <select class="form-control" name="branch_id" id="branchSelect" required>
                            <option value="">-- Select Branch --</option>
                            <?php foreach ($branches as $b): ?>
                                <option value="<?= htmlspecialchars($b['id']) ?>"><?= htmlspecialchars($b['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" class="form-control" name="date" required>
                    </div>
                    <div class="mb-3 d-flex gap-2">
                        <div class="flex-fill">
                            <label class="form-label">In Time</label>
                            <input type="time" class="form-control" name="in_time">
                        </div>
                        <div class="flex-fill">
                            <label class="form-label">Out Time</label>
                            <input type="time" class="form-control" name="out_time">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-control" name="status" required>
                            <option value="present">Present</option>
                            <option value="absent">Absent</option>
                            <option value="leave">Leave</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Note (optional)</label>
                        <input type="text" class="form-control" name="note">
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
    document.addEventListener('DOMContentLoaded', function() { document.querySelector('.dashboard-container').classList.add('show'); });
    function exportToExcel(){ CRUD.showLoading('tableContainer'); setTimeout(()=>{ window.location.href='?page=attendance_faculty&export=excel'; CRUD.hideLoading(); },800);} 
    function printTable(){ const table=document.getElementById('attendance-table').cloneNode(true); const w=window.open('','_blank'); w.document.write(`<html><head><title>Faculty Attendance</title></head><body><h2>Faculty Attendance</h2>${table.outerHTML}</body></html>`); w.document.close(); w.print(); }
    function refreshTable(){ CRUD.showLoading('tableContainer'); setTimeout(()=>location.reload(),600);} 
    async function editAttendance(id){
        CRUD.showLoading('tableContainer');
        try{
            const res = await CRUD.get(`api/attendance.php?action=get&id=${encodeURIComponent(id)}`);
            if(res.success && res.data){
                const a = res.data;
                document.getElementById('attendanceId').value = a.id || '';
                // set faculty select
                if(a.entity_id) document.querySelector('#addAttendanceForm [name="entity_id"]').value = a.entity_id;
                // set branch select
                if(a.branch_id) document.querySelector('#addAttendanceForm [name="branch_id"]').value = a.branch_id;
                document.querySelector('#addAttendanceForm [name="date"]').value = a.date || '';
                document.querySelector('#addAttendanceForm [name="in_time"]').value = a.in_time || '';
                document.querySelector('#addAttendanceForm [name="out_time"]').value = a.out_time || '';
                document.querySelector('#addAttendanceForm [name="status"]').value = a.status || 'present';
                document.querySelector('#addAttendanceForm [name="note"]').value = a.note || '';
                const modalEl = document.getElementById('addAttendanceModal');
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            } else CRUD.toastError('Record not found');
        }catch(e){ CRUD.toastError('Failed: ' + e.message); }
        finally{ CRUD.hideLoading(); }
    }
    async function viewAttendance(id){ await editAttendance(id); const form=document.getElementById('addAttendanceForm'); Array.from(form.elements).forEach(el=>el.disabled=true); const saveBtn=document.querySelector('#addAttendanceModal .btn-primary'); if(saveBtn) saveBtn.style.display='none'; document.querySelector('#addAttendanceModal .modal-title').innerText='View Attendance'; }
    async function deleteAttendance(id){ if(!confirm('Delete attendance '+id+'?')) return; CRUD.showLoading('tableContainer'); try{ const p=new URLSearchParams(); p.append('id', id); const res=await CRUD.post('api/attendance.php?action=delete', p); if(res.success){ CRUD.toastSuccess(res.message||'Deleted'); refreshTable(); } else CRUD.toastError('Delete failed'); }catch(e){ CRUD.toastError('Delete failed: '+e.message);} finally{ CRUD.hideLoading(); } }
    async function saveAttendance(){
        const form = document.getElementById('addAttendanceForm');
        const params = new FormData(form);
        if(!params.get('entity_id')){ CRUD.toastError('Faculty is required'); return; }
        if(!params.get('branch_id')){ CRUD.toastError('Branch is required'); return; }
        const modalEl = document.getElementById('addAttendanceModal');
        CRUD.modalLoadingStart(modalEl);
        try{
            const res = await CRUD.post('api/attendance.php?action=mark', params);
            if(res.success){
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.hide();
                CRUD.toastSuccess(res.message||'Saved');
                refreshTable();
            } else CRUD.toastError('Save failed: ' + (res.message || res.error || 'Unknown'));
        }catch(e){ CRUD.toastError('Save request failed: ' + e.message); }
        finally{ CRUD.modalLoadingStop(modalEl); }
    }
</script>
