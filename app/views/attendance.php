<?php
if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/views/attendance.php
$attendance = [];
$controllerFile = __DIR__ . '/../controllers/AttendanceController.php';
if (file_exists($controllerFile)) {
    require_once $controllerFile;
    $cls = 'AttendanceController';
    if (class_exists($cls) && method_exists($cls, 'getAll')) {
        $attendance = $cls::getAll();
    }
}
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$totalAttendance = count($attendance);
$totalPages = 1;
?>
<?php include __DIR__ . '/partials/nav.php'; ?>
<div class="container-fluid dashboard-container fade-in">
    <!-- Breadcrumbs -->
    <div class="breadcrumb-container d-flex justify-content-between align-items-center">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=dashboard"><i class="fas fa-home"></i> Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-calendar-check"></i> Attendance</li>
            </ol>
        </nav>
        <button class="btn btn-primary btn-action" data-bs-toggle="modal" data-bs-target="#addAttendanceModal">
            <i class="fas fa-plus"></i> Mark Attendance
        </button>
    </div>
    <!-- Table Container -->
    <div class="advanced-table-container">
        <!-- table-controls removed (search/actions removed) -->
        <!-- Table -->
        <div class="table-responsive" id="tableContainer">
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
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination-container">
            <div class="pagination-info">
                Showing <?= count($attendance) ?> of <?= $totalAttendance ?> records
            </div>
            <nav aria-label="Page navigation">
                <ul class="pagination mb-0">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=attendance&page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=attendance&page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=attendance&page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
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
    // search input removed — rely on DataTables filters if needed
    // Smooth fade-in effect for page content
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelector('.dashboard-container').classList.add('show');
    });
    // Export to Excel
    function exportToExcel() {
        showLoading();
        setTimeout(() => {
            window.location.href = '?page=attendance&export=excel';
            hideLoading();
        }, 1000);
    }
    // Print table
    function printTable() {
        const table = document.getElementById('attendance-table').cloneNode(true);
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Attendance Report</title>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f8f9fa; }
                    </style>
                </head>
                <body>
                    <h2>Attendance Report</h2>
                    ${table.outerHTML}
                    <p>Generated on: ${new Date().toLocaleDateString()}</p>
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
    }
    // Refresh table
    function refreshTable() {
        showLoading();
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    }
    // Loading states
    function showLoading() {
        const container = document.getElementById('tableContainer');
        const overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.innerHTML = `
            <div class="spinner-border text-primary spinner" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        `;
        container.style.position = 'relative';
        container.appendChild(overlay);
    }
    function hideLoading() {
        const overlay = document.querySelector('.loading-overlay');
        if (overlay) overlay.remove();
    }
    // Attendance management functions
    async function editAttendance(id) {
        showLoading();
        try {
            const res = await CRUD.get(`api/attendance.php?action=get&id=${encodeURIComponent(id)}`);
            if (res.success && res.data) {
                const a = res.data;
                document.getElementById('attendanceId').value = a.id || '';
                document.querySelector('#addAttendanceForm [name="student"]').value = a.student || '';
                document.querySelector('#addAttendanceForm [name="date"]').value = a.date || '';
                document.querySelector('#addAttendanceForm [name="status"]').value = a.status || 'present';
                document.getElementById('attendanceBranchId').value = a.branch_id ?? 0;
                setModalModeAttendance('edit');
                const modalEl = document.getElementById('addAttendanceModal');
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            } else {
                alert('Record not found');
            }
        } catch (e) { alert('Failed to load record: ' + e.message); }
        finally { CRUD.hideLoading(); }
    }

    async function viewAttendance(id) {
        showLoading();
        try {
            const res = await CRUD.get(`api/attendance.php?action=get&id=${encodeURIComponent(id)}`);
            if (res.success && res.data) {
                const a = res.data;
                document.getElementById('attendanceId').value = a.id || '';
                document.querySelector('#addAttendanceForm [name="student"]').value = a.student || '';
                document.querySelector('#addAttendanceForm [name="date"]').value = a.date || '';
                document.querySelector('#addAttendanceForm [name="status"]').value = a.status || 'present';
                document.getElementById('attendanceBranchId').value = a.branch_id ?? 0;
                setModalModeAttendance('view');
                const modalEl = document.getElementById('addAttendanceModal');
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            } else {
                alert('Record not found');
            }
        } catch (e) { alert('Failed to load record: ' + e.message); }
        finally { CRUD.hideLoading(); }
    }

    async function deleteAttendance(id) {
        if (!confirm('Are you sure you want to delete this attendance record?')) return;
        CRUD.showLoading('tableContainer');
        try {
            const params = new URLSearchParams(); params.append('id', id);
            const res = await CRUD.post('api/attendance.php?action=delete', params);
            if (res.success) refreshTable(); else alert('Delete failed');
        } catch (e) { alert('Delete request failed: ' + e.message); }
        finally { CRUD.hideLoading(); }
    }

    function setModalModeAttendance(mode) {
        const form = document.getElementById('addAttendanceForm');
        const saveBtn = document.querySelector('#addAttendanceModal .btn-primary');
        if (mode === 'view') {
            Array.from(form.elements).forEach(el => el.disabled = true);
            saveBtn.style.display = 'none';
            document.querySelector('#addAttendanceModal .modal-title').innerText = 'View Attendance';
        } else {
            Array.from(form.elements).forEach(el => el.disabled = false);
            saveBtn.style.display = '';
            document.querySelector('#addAttendanceModal .modal-title').innerText = mode === 'edit' ? 'Edit Attendance' : 'Mark Attendance';
        }
    }

    async function saveAttendance() {
        const form = document.getElementById('addAttendanceForm');
        const params = new FormData(form);
        if (!params.get('student')) { alert('Student is required'); return; }
        CRUD.showLoading('tableContainer');
        try {
            const res = await CRUD.post('api/attendance.php?action=mark', params);
            if (res.success) {
                const modalEl = document.getElementById('addAttendanceModal');
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.hide();
                refreshTable();
            } else {
                alert('Save failed: ' + (res.message || res.error || 'Unknown'));
            }
        } catch (e) { alert('Save request failed: ' + e.message); }
        finally { CRUD.hideLoading(); }
    }
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'f') {
            e.preventDefault();
            const si = document.getElementById('searchInput'); if (si) si.focus();
        }
        if (e.ctrlKey && e.key === 'n') {
            e.preventDefault();
            document.querySelector('[data-bs-target="#addAttendanceModal"]').click();
        }
    });
</script>
