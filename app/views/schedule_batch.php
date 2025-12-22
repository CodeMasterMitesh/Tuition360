<?php

use CampusLite\Controllers\{BranchController, BatchController};

if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
$branches = BranchController::getAll();
$batches = BatchController::getAll();
$page_icon = 'fas fa-calendar-days';
$page_title = 'Schedule Batch';
$add_button = ['label' => 'Add Schedule', 'modal' => 'addScheduleModal', 'form' => 'addScheduleForm'];
$action_buttons = [];
$show_actions = true;
?>

<div class="container-fluid dashboard-container fade-in">
    <?php include __DIR__ . '/partials/page-header.php'; ?>
    <div class="advanced-table-container">
        <div class="table-responsive table-compact" id="tableContainer">
            <table class="table data-table" id="schedule-table">
                <thead>
                    <tr>
                        <th width="70">ID</th>
                        <th>Batch</th>
                        <th>Recurrence</th>
                        <th>Window</th>
                        <th>Timing</th>
                        <th>Faculty</th>
                        <th>Subjects</th>
                        <th>Students</th>
                        <th>Status</th>
                        <th width="150" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody"></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="addScheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="scheduleModalTitle">Add Schedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addScheduleForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                    <input type="hidden" name="id" id="scheduleId" value="">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Branch</label>
                            <select class="form-select" name="branch_id" id="scheduleBranch" required>
                                <option value="">-- Select Branch --</option>
                                <?php foreach ($branches as $b): ?>
                                    <option value="<?= intval($b['id']) ?>"><?= htmlspecialchars($b['name'] ?? '') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Batch</label>
                            <select class="form-select" name="batch_id" id="scheduleBatch" required>
                                <option value="">-- Select Branch First --</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Faculty (from batch)</label>
                            <div id="facultyListContainer"></div>
                            <small class="text-muted">Assigned faculty will be shown here</small>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Students (from batch)</label>
                            <div class="table-responsive" id="studentsTableWrapper" style="display:none;">
                                <table class="table table-sm table-bordered">
                                    <thead>
                                        <tr>
                                            <th width="40"><input type="checkbox" id="selectAllStudents"></th>
                                            <th>Student Name</th>
                                        </tr>
                                    </thead>
                                    <tbody id="studentsListContainer"></tbody>
                                </table>
                            </div>
                            <small class="text-muted">Select students for this schedule</small>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Subjects (from batch course)</label>
                            <div class="table-responsive" id="subjectsTableWrapper" style="display:none;">
                                <table class="table table-sm table-bordered">
                                    <thead>
                                        <tr>
                                            <th width="40"><input type="checkbox" id="selectAllSubjects"></th>
                                            <th>Subject Name</th>
                                        </tr>
                                    </thead>
                                    <tbody id="subjectsListContainer"></tbody>
                                </table>
                            </div>
                            <small class="text-muted">Select subjects for this schedule</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Recurrence</label>
                            <select class="form-select" name="recurrence" id="scheduleRecurrence" required>
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>
                        <div class="col-md-4 recurrence recurrence-daily recurrence-weekly recurrence-monthly">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date" id="scheduleStartDate">
                        </div>
                        <div class="col-md-4 recurrence recurrence-daily recurrence-weekly recurrence-monthly">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date" id="scheduleEndDate">
                        </div>
                        <div class="col-md-4 recurrence recurrence-daily">
                            <label class="form-label">Time</label>
                            <div class="d-flex gap-2">
                                <input type="time" class="form-control" name="start_time" id="scheduleStartTime">
                                <input type="time" class="form-control" name="end_time" id="scheduleEndTime">
                            </div>
                        </div>
                        <div class="col-md-4 recurrence recurrence-weekly" style="display:none;">
                            <label class="form-label">Weekdays</label>
                            <div class="d-flex flex-wrap gap-2" id="scheduleWeekdays">
                                <?php $days = [['0','Sun'],['1','Mon'],['2','Tue'],['3','Wed'],['4','Thu'],['5','Fri'],['6','Sat']];
                                foreach ($days as [$val,$lbl]): ?>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="weekdays[]" id="wd_<?= $val ?>" value="<?= $val ?>">
                                        <label class="form-check-label" for="wd_<?= $val ?>"><?= $lbl ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <small class="text-muted">Select one or more weekdays</small>
                        </div>
                        <div class="col-md-4 recurrence recurrence-weekly" style="display:none;">
                            <label class="form-label">Time</label>
                            <div class="d-flex gap-2">
                                <input type="time" class="form-control" name="start_time_weekly" id="scheduleStartTimeWeekly">
                                <input type="time" class="form-control" name="end_time_weekly" id="scheduleEndTimeWeekly">
                            </div>
                        </div>
                        <div class="col-md-4 recurrence recurrence-monthly" style="display:none;">
                            <label class="form-label">Day of Month</label>
                            <input type="number" min="1" max="31" class="form-control" name="day_of_month" id="scheduleDayOfMonth" placeholder="e.g. 15">
                        </div>
                        <div class="col-md-4 recurrence recurrence-monthly" style="display:none;">
                            <label class="form-label">Time</label>
                            <div class="d-flex gap-2">
                                <input type="time" class="form-control" name="start_time_monthly" id="scheduleStartTimeMonthly">
                                <input type="time" class="form-control" name="end_time_monthly" id="scheduleEndTimeMonthly">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" id="scheduleNotes" rows="2" placeholder="Optional note"></textarea>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="scheduleStatus">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="saveScheduleBtn" class="btn btn-primary" onclick="saveSchedule()">Save Schedule</button>
            </div>
        </div>
    </div>
</div>

<script src="/public/assets/js/schedule_batch.js"></script>
