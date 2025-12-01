<?php

use CampusLite\Controllers\AttendanceController;

if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/views/attendance_faculty.php
$attendance = AttendanceController::getAll('faculty');
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
        <!-- table-controls removed (search/actions removed) -->
        <div class="table-responsive table-compact" id="tableContainer">
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

<script src="/public/assets/js/attendance.js"></script>
