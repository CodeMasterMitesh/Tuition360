<?php

use CampusLite\Controllers\AttendanceController;

if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/views/attendance_students.php
$attendance = AttendanceController::getAll('student');
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
                <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-user-graduate"></i> Student Attendance</li>
            </ol>
        </nav>
        <button class="btn btn-primary btn-action" data-bs-toggle="modal" data-bs-target="#addAttendanceModal">
            <i class="fas fa-plus"></i> Mark Attendance
        </button>
    </div>
    <div class="advanced-table-container">
        <!-- table-controls removed (search/actions removed) -->
        <div class="table-responsive" id="tableContainer">
            <table class="table data-table" id="attendance-table">
                <thead>
                    <tr>
                        <th width="60">ID</th>
                        <th>Student</th>
                        <th>Batch</th>
                        <th>Branch</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th width="150" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if (empty($attendance)): ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <h4>No attendance records found</h4>
                                    <p>No student attendance records available</p>
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
                                    <div class="fw-semibold"><?= htmlspecialchars($record['student_name'] ?? 'N/A') ?></div>
                                    <?php if (!empty($record['student_email'])): ?>
                                        <small class="text-muted"><?= htmlspecialchars($record['student_email']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($record['batch_title'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($record['branch_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($record['date'] ?? '') ?></td>
                                <td>
                                    <?php 
                                    $status = $record['status'] ?? 'absent';
                                    $badgeClass = $status === 'present' ? 'bg-success' : ($status === 'leave' ? 'bg-warning' : 'bg-danger');
                                    ?>
                                    <span class="badge <?= $badgeClass ?>">
                                        <?= ucfirst($status) ?>
                                    </span>
                                </td>
                                <td class="small text-muted"><?= htmlspecialchars($record['note'] ?? '-') ?></td>
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
<!-- Add/Edit Attendance Modal -->
<div class="modal fade" id="addAttendanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Mark Student Attendance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addAttendanceForm">
                    <input type="hidden" name="id" id="attendanceId" value="">
                    <input type="hidden" name="entity_type" value="student">
                    <input type="hidden" name="entity_id" id="studentId">
                    <input type="hidden" name="branch_id" id="attendanceBranchId" value="<?= $_SESSION['branch_id'] ?? 0 ?>">
                    <input type="hidden" name="recorded_by" value="<?= $_SESSION['user']['id'] ?? 0 ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Student <span class="text-danger">*</span></label>
                        <select class="form-select" name="student_select" id="studentSelect" required>
                            <option value="">-- Select Student --</option>
                            <?php
                            require_once __DIR__ . '/../controllers/StudentController.php';
                            $students = \CampusLite\Controllers\StudentController::getAll();
                            foreach ($students as $student):
                            ?>
                                <option value="<?= $student['id'] ?>" data-batch="<?= htmlspecialchars($student['batch_title'] ?? '') ?>" data-email="<?= htmlspecialchars($student['email'] ?? '') ?>">
                                    <?= htmlspecialchars($student['name']) ?>
                                    <?php if (!empty($student['batch_title'])): ?>
                                        (<?= htmlspecialchars($student['batch_title']) ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted" id="studentInfo"></small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="date" id="attendanceDate" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select" name="status" id="attendanceStatus" required>
                            <option value="present">Present</option>
                            <option value="absent">Absent</option>
                            <option value="leave">Leave</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">In Time</label>
                        <input type="time" class="form-control" name="in_time" id="attendanceInTime">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Out Time</label>
                        <input type="time" class="form-control" name="out_time" id="attendanceOutTime">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="note" id="attendanceNote" rows="3" placeholder="Additional notes..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveBtn" onclick="saveAttendance()">Save Attendance</button>
            </div>
        </div>
    </div>
</div>

<!-- View Attendance Modal -->
<div class="modal fade" id="viewAttendanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">View Attendance Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Student Name</label>
                        <p class="form-control-plaintext" id="viewStudentName">-</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Batch</label>
                        <p class="form-control-plaintext" id="viewBatch">-</p>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Date</label>
                        <p class="form-control-plaintext" id="viewDate">-</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Status</label>
                        <p class="form-control-plaintext" id="viewStatus">-</p>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">In Time</label>
                        <p class="form-control-plaintext" id="viewInTime">-</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Out Time</label>
                        <p class="form-control-plaintext" id="viewOutTime">-</p>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-12">
                        <label class="form-label fw-bold">Branch</label>
                        <p class="form-control-plaintext" id="viewBranch">-</p>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-12">
                        <label class="form-label fw-bold">Notes</label>
                        <p class="form-control-plaintext" id="viewNote">-</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// Student selector change handler
document.addEventListener('DOMContentLoaded', function() {
    const studentSelect = document.getElementById('studentSelect');
    const studentId = document.getElementById('studentId');
    const studentInfo = document.getElementById('studentInfo');
    
    if (studentSelect) {
        studentSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (studentId) studentId.value = this.value;
            if (studentInfo) {
                const batch = selectedOption.dataset.batch || '';
                const email = selectedOption.dataset.email || '';
                let info = [];
                if (batch) info.push(`Batch: ${batch}`);
                if (email) info.push(`Email: ${email}`);
                studentInfo.textContent = info.join(' | ');
            }
        });
    }
    
    // Reset form when modal closes
    const addModal = document.getElementById('addAttendanceModal');
    if (addModal) {
        addModal.addEventListener('hidden.bs.modal', function() {
            const form = document.getElementById('addAttendanceForm');
            if (form) form.reset();
            document.getElementById('attendanceId').value = '';
            document.getElementById('studentId').value = '';
            document.getElementById('studentInfo').textContent = '';
            document.getElementById('modalTitle').textContent = 'Mark Student Attendance';
            document.getElementById('saveBtn').style.display = '';
        });
    }
});
</script>

<script src="/public/assets/js/attendance.js"></script>
