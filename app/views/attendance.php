<?php

use CampusLite\Controllers\AttendanceController;

if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/views/attendance.php
$attendance = AttendanceController::getAll();
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$totalAttendance = count($attendance);
$totalPages = 1;
?>

<div class="container-fluid dashboard-container fade-in">
    <?php
    $page_icon = 'fas fa-calendar-check';
    $page_title = 'Attendance';
    $show_actions = true;
    $action_buttons = [
        ['label' => 'Export Excel', 'class' => 'btn-primary', 'onclick' => "exportToExcel('attendance')", 'icon' => 'fas fa-file-excel'],
    ];
    $add_button = ['label' => 'Mark Attendance', 'modal' => 'addAttendanceModal', 'form' => 'addAttendanceForm'];
    include __DIR__ . '/partials/page-header.php';
    ?>
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

<script src="/public/assets/js/attendance.js"></script>
