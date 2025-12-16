<?php

use CampusLite\Controllers\AttendanceController;

if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/views/attendance_employee.php — now shared for employee & faculty (self attendance)
$currentUser = $_SESSION['user'] ?? null;
$currentUserName = $currentUser['name'] ?? 'User';
$currentUserId = $currentUser['id'] ?? 0;
$currentBranch = $_SESSION['branch_id'] ?? ($currentUser['branch_id'] ?? 0);
$role = strtolower($currentUser['role'] ?? 'employee');
$entityType = ($role === 'faculty') ? 'faculty' : 'employee';
$isAdmin = in_array($role, ['super_admin', 'branch_admin'], true);

// Fetch faculty batches if user is faculty
$facultyBatches = [];
if ($entityType === 'faculty') {
    $stmt = mysqli_prepare($conn, "SELECT DISTINCT ba.batch_id, b.title FROM batch_assignments ba JOIN batches b ON b.id = ba.batch_id WHERE ba.user_id = ? AND ba.role = 'faculty' ORDER BY b.title");
    mysqli_stmt_bind_param($stmt, 'i', $currentUserId);
    if ($stmt && mysqli_stmt_execute($stmt)) {
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {
            $facultyBatches[] = $row;
        }
    }
}

// Fetch attendance: admins see all staff, others see their own
$attendance = [];
if ($isAdmin) {
    $stmt = mysqli_prepare($conn, "SELECT a.*, b.title as batch_name FROM attendance a LEFT JOIN batches b ON b.id = a.batch_id WHERE a.entity_type IN ('employee','faculty') ORDER BY a.date DESC, a.id DESC");
} else {
    $stmt = mysqli_prepare($conn, "SELECT a.*, b.title as batch_name FROM attendance a LEFT JOIN batches b ON b.id = a.batch_id WHERE a.entity_type = ? AND a.entity_id = ? ORDER BY a.date DESC, a.id DESC");
    mysqli_stmt_bind_param($stmt, 'si', $entityType, $currentUserId);
}
if ($stmt && mysqli_stmt_execute($stmt)) {
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) { $attendance[] = $row; }
}

// Preload names for display
$employeeNames = [$currentUserId => $currentUserName];
$nameRes = mysqli_query($conn, "SELECT id, name FROM users WHERE role IN ('employee','faculty')");
if ($nameRes) {
    while ($row = mysqli_fetch_assoc($nameRes)) {
        $employeeNames[$row['id']] = $row['name'];
    }
}

$today = date('Y-m-d');
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$totalAttendance = count($attendance);
$totalPages = 1;
?>

<div class="container-fluid dashboard-container fade-in">
    <div class="breadcrumb-container d-flex justify-content-between align-items-center">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=dashboard"><i class="fas fa-home"></i> Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-briefcase"></i> Attendance</li>
            </ol>
        </nav>
        <button class="btn btn-primary btn-action" data-bs-toggle="modal" data-bs-target="#addAttendanceModal" onclick="resetAttendanceModal()">
            <i class="fas fa-plus"></i> Mark Attendance
        </button>
    </div>
    <div class="advanced-table-container">
        <!-- table-controls removed (search/actions removed) -->
        <div class="table-responsive table-compact" id="tableContainer">
            <table class="table data-table" id="attendance-table">
                <thead>
                    <tr>
                        <th width="60">ID</th>
                        <th>Staff</th>
                        <th>Batch</th>
                        <th>Date</th>
                        <th>In</th>
                        <th>Out</th>
                        <th>Status</th>
                        <th>Note</th>
                        <th width="150" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if (empty($attendance)): ?>
                        <tr>
                            <td>
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <h4>No attendance records found</h4>
                                    <p>No attendance records match your search criteria</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAttendanceModal" onclick="resetAttendanceModal()">
                                        <i class="fas fa-plus"></i> Mark First Attendance
                                    </button>
                                </div>
                            </td>
                            <td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($attendance as $record): ?>
                            <tr>
                                <td><?= htmlspecialchars($record['id'] ?? '') ?></td>
                                <td><?= htmlspecialchars(ucfirst($record['entity_type'] ?? '')) ?> - <?= htmlspecialchars($employeeNames[$record['entity_id'] ?? 0] ?? ('Staff #' . ($record['entity_id'] ?? ''))) ?></td>
                                <td><?= htmlspecialchars($record['batch_name'] ?? '-') ?></td>
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
                                <td><?= htmlspecialchars($record['note'] ?? '') ?></td>
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
                    <input type="hidden" name="branch_id" id="attendanceBranchId" value="<?= htmlspecialchars($currentBranch) ?>">
                    <input type="hidden" name="entity_type" id="attendanceEntityType" value="<?= htmlspecialchars($entityType) ?>">
                    <input type="hidden" name="entity_id" id="attendanceEntityId" value="<?= htmlspecialchars($currentUserId) ?>">
                    <input type="hidden" name="recorded_by" value="<?= htmlspecialchars($currentUserId) ?>">
                    <div class="mb-3">
                        <label class="form-label"><?= $entityType === 'faculty' ? 'Faculty' : 'Employee' ?></label>
                        <input type="text" class="form-control" name="employee_name" value="<?= htmlspecialchars($currentUserName) ?>" readonly>
                    </div>
                    <?php if ($entityType === 'faculty' && !empty($facultyBatches)): ?>
                    <div class="mb-3">
                        <label class="form-label">Batch</label>
                        <select class="form-control" name="batch_id" id="attendanceBatchId">
                            <option value="">Select Batch (Optional)</option>
                            <?php foreach ($facultyBatches as $batch): ?>
                                <option value="<?= htmlspecialchars($batch['batch_id']) ?>"><?= htmlspecialchars($batch['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" class="form-control" name="date" value="<?= htmlspecialchars($today) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">In Time</label>
                        <input type="time" class="form-control" name="in_time">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Out Time</label>
                        <input type="time" class="form-control" name="out_time">
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
                        <label class="form-label">Note</label>
                        <textarea class="form-control" name="note" rows="2" placeholder="Optional note"></textarea>
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

<script src="/public/assets/js/attendance.js"></script>
<script>
window.CURRENT_EMPLOYEE_ID = <?= json_encode($currentUserId) ?>;
window.CURRENT_EMPLOYEE_NAME = <?= json_encode($currentUserName) ?>;
window.CURRENT_ENTITY_TYPE = <?= json_encode($entityType) ?>;
</script>
