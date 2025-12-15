<?php
if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }

$currentUser = $_SESSION['user'] ?? null;
$userId = $currentUser['id'] ?? 0;
$userName = $currentUser['name'] ?? 'Employee';
$userRole = $currentUser['role'] ?? 'employee';
$entityType = strtolower($userRole) === 'faculty' ? 'faculty' : 'employee';

require_once __DIR__ . '/../controllers/LeaveController.php';

// Leave data
$leaves = \CampusLite\Controllers\LeaveController::getByUserId($userId);
$leaveStats = [
    'total' => count($leaves),
    'approved' => count(array_filter($leaves, fn($l) => ($l['status'] ?? '') === 'approved')),
    'pending' => count(array_filter($leaves, fn($l) => ($l['status'] ?? '') === 'applied')),
    'rejected' => count(array_filter($leaves, fn($l) => ($l['status'] ?? '') === 'rejected')),
];

// All scheduled batches assigned to current user (faculty/employee)
$today = date('Y-m-d');
$allSchedules = [];
$batchStudents = [];
$studentCache = [];

if ($userRole === 'faculty' || $userRole === 'employee') {
    // Fetch schedules where faculty_id or assigned user matches current user
    $schedStmt = mysqli_prepare($conn, "SELECT sb.id, sb.batch_id, sb.start_date, sb.end_date, sb.start_time, sb.end_time, sb.student_ids, sb.faculty_id, COALESCE(sb.notes, 'Session') AS title, b.title AS batch_title FROM schedule_batches sb LEFT JOIN batches b ON b.id = sb.batch_id WHERE sb.status = 'active' AND (sb.faculty_id = ? OR sb.batch_id IN (SELECT batch_id FROM batch_assignments WHERE user_id = ? AND role IN ('faculty','employee'))) ORDER BY sb.start_date DESC, sb.start_time ASC");
    $uid = (int)$userId;
    mysqli_stmt_bind_param($schedStmt, 'ii', $uid, $uid);
    if (mysqli_stmt_execute($schedStmt)) {
        $res = mysqli_stmt_get_result($schedStmt);
        while ($row = mysqli_fetch_assoc($res)) { $allSchedules[] = $row; }
    }
    
    // Preload batch enrollments
    if (!empty($allSchedules)) {
        $batchIds = array_values(array_unique(array_filter(array_map(fn($s) => (int)($s['batch_id'] ?? 0), $allSchedules))));
        if ($batchIds) {
            $in = implode(',', array_fill(0, count($batchIds), '?'));
            $types = str_repeat('i', count($batchIds));
            $sql = "SELECT e.batch_id, s.id AS student_id, s.name AS student_name FROM enrollments e JOIN students s ON s.id = e.student_id WHERE e.status = 'active' AND e.batch_id IN ($in) ORDER BY s.name";
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, $types, ...$batchIds);
                if (mysqli_stmt_execute($stmt)) {
                    $res = mysqli_stmt_get_result($stmt);
                    while ($row = mysqli_fetch_assoc($res)) {
                        $bid = (int)($row['batch_id'] ?? 0);
                        if (!isset($batchStudents[$bid])) $batchStudents[$bid] = [];
                        $batchStudents[$bid][] = $row;
                        $studentCache[(int)$row['student_id']] = $row['student_name'];
                    }
                }
            }
        }
        // Collect explicitly referenced students
        $explicitIds = [];
        foreach ($allSchedules as $s) {
            if (!empty($s['student_ids'])) {
                $ids = json_decode($s['student_ids'], true);
                if (is_array($ids)) {
                    foreach ($ids as $sid) { $sid = (int)$sid; if ($sid) $explicitIds[] = $sid; }
                }
            }
        }
        $explicitIds = array_values(array_unique($explicitIds));
        if ($explicitIds) {
            $in = implode(',', array_fill(0, count($explicitIds), '?'));
            $types = str_repeat('i', count($explicitIds));
            $sql = "SELECT id AS student_id, name AS student_name FROM students WHERE id IN ($in)";
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, $types, ...$explicitIds);
                if (mysqli_stmt_execute($stmt)) {
                    $res = mysqli_stmt_get_result($stmt);
                    while ($row = mysqli_fetch_assoc($res)) {
                        $studentCache[(int)$row['student_id']] = $row['student_name'];
                    }
                }
            }
        }
    }
}

// Today's attendance (respect faculty/employee entity type)
$todayAttendance = null;
$stmt = mysqli_prepare($conn, "SELECT status FROM attendance WHERE entity_type = ? AND entity_id = ? AND date = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'sis', $entityType, $userId, $today);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$todayAttendance = mysqli_fetch_assoc($result);

// Upcoming schedules (limit to keep light)
$schedules = [];
$schedSql = "SELECT sb.id,
                    sb.start_date,
                    sb.end_date,
                    sb.start_time,
                    sb.end_time,
                    COALESCE(sb.notes, 'Session') AS title,
                    b.title AS batch_title
             FROM schedule_batches sb
             LEFT JOIN batches b ON b.id = sb.batch_id
             WHERE sb.status = 'active'
             ORDER BY sb.start_date ASC, sb.start_time ASC
             LIMIT 100";
$schedRes = mysqli_query($conn, $schedSql);
if ($schedRes) {
    while ($row = mysqli_fetch_assoc($schedRes)) {
        $schedules[] = $row;
    }
}
?>

<div class="container-fluid dashboard-container fade-in show">
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h3 class="mb-1">Welcome, <?= htmlspecialchars($userName) ?>!</h3>
            <p class="text-muted">Your work overview</p>
        </div>
        <div class="col-auto d-flex gap-2">
            <a class="btn btn-outline-primary" href="index.php?page=attendance_employee">
                <i class="fas fa-user-check me-2"></i>Attendance
            </a>
            <a class="btn btn-outline-secondary" href="index.php?page=leaves">
                <i class="fas fa-umbrella-beach me-2"></i>Leaves
            </a>
        </div>
    </div>

    <div class="row g-3">
        <!-- Attendance card -->
        <div class="col-md-3">
            <div class="card text-bg-primary h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="card-title mb-0"><i class="fas fa-calendar-check me-2"></i>Today's Attendance</h6>
                        <span class="badge bg-light text-primary"><?= htmlspecialchars($today) ?></span>
                    </div>
                    <h3 class="mb-1">
                        <?php if ($todayAttendance): ?>
                            <?= ucfirst($todayAttendance['status']) ?>
                        <?php else: ?>
                            Not Marked
                        <?php endif; ?>
                    </h3>
                    <p class="mb-0 small text-light opacity-75">Mark your attendance from Attendance page.</p>
                </div>
            </div>
        </div>

        <!-- Leave stats -->
        <div class="col-md-9">
            <div class="row g-3">
                <div class="col-sm-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-muted mb-1">Total Leaves</h6>
                                    <h3 class="mb-0"><?= $leaveStats['total'] ?></h3>
                                </div>
                                <span class="badge bg-secondary-subtle text-secondary">All</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-muted mb-1">Pending</h6>
                                    <h3 class="mb-0 text-warning"><?= $leaveStats['pending'] ?></h3>
                                </div>
                                <span class="badge bg-warning-subtle text-warning">Awaiting</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-muted mb-1">Approved</h6>
                                    <h3 class="mb-0 text-success"><?= $leaveStats['approved'] ?></h3>
                                </div>
                                <span class="badge bg-success-subtle text-success">Approved</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4 g-3">
        <!-- Calendar + schedules -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Schedule Calendar</h5>
                    <div class="btn-group btn-group-sm" role="group">
                        <button class="btn btn-outline-secondary" id="calPrev" type="button"><i class="fas fa-chevron-left"></i></button>
                        <button class="btn btn-outline-secondary" id="calToday" type="button">Today</button>
                        <button class="btn btn-outline-secondary" id="calNext" type="button"><i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="scheduleCalendar" class="calendar-grid"></div>
                    <div class="small text-muted mt-2">Click a date to view schedule items below.</div>
                </div>
            </div>
        </div>

        <!-- Upcoming schedule list -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Upcoming Schedules</h5>
                    <span class="badge bg-primary-subtle text-primary"><?= count($schedules) ?></span>
                </div>
                <div class="card-body">
                    <?php if (empty($schedules)): ?>
                        <p class="text-muted text-center mb-0">No schedules found.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush small" id="scheduleList">
                            <?php foreach ($schedules as $sched): ?>
                                <?php
                                    $dateLabel = $sched['start_date'] ?? '';
                                    $timeLabel = trim(($sched['start_time'] ?? '') . ' - ' . ($sched['end_time'] ?? ''));
                                    $title = $sched['title'] ?: 'Session';
                                    $batch = $sched['batch_title'] ?: 'Batch';
                                ?>
                                <div class="list-group-item py-2">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <div class="fw-semibold"><?= htmlspecialchars($title) ?></div>
                                            <div class="text-muted">Batch: <?= htmlspecialchars($batch) ?></div>
                                        </div>
                                        <div class="text-end">
                                            <div class="badge bg-light text-dark"><?= htmlspecialchars($dateLabel) ?></div>
                                            <div class="text-muted"><?= htmlspecialchars($timeLabel) ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- My Scheduled Batches - Student Attendance -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">My Scheduled Batches</h6>
                    <span class="badge bg-primary-subtle text-primary"><?= count($allSchedules) ?></span>
                </div>
                <div class="card-body">
                    <?php if (empty($allSchedules)): ?>
                        <p class="text-muted mb-0">No scheduled batches assigned to you.</p>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($allSchedules as $sched): ?>
                                <?php
                                    $bid = (int)($sched['batch_id'] ?? 0);
                                    $schedId = (int)($sched['id'] ?? 0);
                                    $students = $batchStudents[$bid] ?? [];
                                    if (empty($students) && !empty($sched['student_ids'])) {
                                        $ids = json_decode($sched['student_ids'], true);
                                        if (is_array($ids)) {
                                            foreach ($ids as $sid) {
                                                $sid = (int)$sid;
                                                if ($sid && isset($studentCache[$sid])) {
                                                    $students[] = ['batch_id' => $bid, 'student_id' => $sid, 'student_name' => $studentCache[$sid]];
                                                }
                                            }
                                        }
                                    }
                                    $studentCount = count($students);
                                ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="card border-0 shadow-sm h-100 hover-lift" style="cursor:pointer; transition: all 0.3s ease;" onclick="openBatchAttendanceModal(<?= $schedId ?>, '<?= htmlspecialchars($sched['batch_title'] ?? 'Batch', ENT_QUOTES) ?>')">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="card-title mb-0 fw-semibold"><?= htmlspecialchars($sched['batch_title'] ?? 'Batch') ?></h6>
                                                <span class="badge bg-primary-subtle text-primary small"><?= $studentCount ?></span>
                                            </div>
                                            <div class="small text-muted mb-3">
                                                <div class="mb-1"><i class="fas fa-clock me-1"></i> <?= htmlspecialchars($sched['start_time'] ?? '-') ?> - <?= htmlspecialchars($sched['end_time'] ?? '-') ?></div>
                                                <div><i class="fas fa-calendar me-1"></i> <?= htmlspecialchars($sched['start_date'] ?? '-') ?> to <?= htmlspecialchars($sched['end_date'] ?? '-') ?></div>
                                            </div>
                                            <button class="btn btn-sm btn-primary w-100" onclick="event.stopPropagation(); openBatchAttendanceModal(<?= $schedId ?>, '<?= htmlspecialchars($sched['batch_title'] ?? 'Batch', ENT_QUOTES) ?>')">
                                                <i class="fas fa-edit"></i> Mark Attendance
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Attendance Modal -->
    <div class="modal fade" id="batchAttendanceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Mark Student Attendance - <span id="modalBatchName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" class="form-control" id="batchAttDate" value="<?= htmlspecialchars($today) ?>" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Common Note (Optional)</label>
                        <textarea class="form-control" id="batchAttNote" rows="2" placeholder="Applies to all students"></textarea>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle" id="modalStudentTable">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="modalStudentList">
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveBatchAttendance()">Save Attendance</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Batch-wise Attendance Report -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Batch-wise Attendance Report</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small">Batch</label>
                            <select class="form-select form-select-sm" id="reportBatch">
                                <option value="">-- Select Batch --</option>
                                <option value="all">All Batches</option>
                                <?php 
                                $uniqueBatches = [];
                                foreach ($allSchedules as $sched) {
                                    $bid = (int)($sched['batch_id'] ?? 0);
                                    if ($bid && !isset($uniqueBatches[$bid])) {
                                        $uniqueBatches[$bid] = $sched['batch_title'] ?? 'Batch';
                                    }
                                }
                                foreach ($uniqueBatches as $bid => $title):
                                ?>
                                    <option value="<?= $bid ?>"><?= htmlspecialchars($title) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">From Date</label>
                            <input type="date" class="form-control form-control-sm" id="reportFromDate" value="<?= date('Y-m-01') ?>" />
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">To Date</label>
                            <input type="date" class="form-control form-control-sm" id="reportToDate" value="<?= htmlspecialchars($today) ?>" />
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Status Filter</label>
                            <select class="form-select form-select-sm" id="reportStatus">
                                <option value="all">All</option>
                                <option value="present">Present Only</option>
                                <option value="absent">Absent Only</option>
                                <option value="leave">Leave Only</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button class="btn btn-sm btn-primary w-100" onclick="loadAttendanceReport()">
                                <i class="fas fa-search"></i> Generate Report
                            </button>
                        </div>
                    </div>
                    <div id="reportContainer" class="table-responsive" style="display:none;">
                        <table class="table table-sm table-bordered align-middle" id="reportTable">
                            <thead class="table-light" id="reportTableHead">
                                <tr>
                                    <th>Student</th>
                                    <th>Batch</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Note</th>
                                </tr>
                            </thead>
                            <tbody id="reportBody">
                            </tbody>
                        </table>
                    </div>
                    <div id="reportEmpty" class="text-center text-muted py-4">
                        <i class="fas fa-chart-bar fa-2x mb-2"></i>
                        <p class="mb-0">Select batch and date range to view attendance report</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Leaves -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Leave Requests</h5>
                    <a class="btn btn-sm btn-outline-secondary" href="index.php?page=leaves">View all</a>
                </div>
                <div class="card-body">
                    <?php if (empty($leaves)): ?>
                        <p class="text-muted text-center mb-0">No leave requests found</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>From</th>
                                        <th>To</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                        <th>Requested On</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($leaves, 0, 8) as $leave): ?>
                                        <?php
                                            $status = $leave['status'] ?? 'applied';
                                            $badgeClass = $status === 'approved' ? 'bg-success' : ($status === 'rejected' ? 'bg-danger' : 'bg-warning text-dark');
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($leave['from_date'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($leave['to_date'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($leave['reason'] ?? '') ?></td>
                                            <td><span class="badge <?= $badgeClass ?>"><?= ucfirst($status) ?></span></td>
                                            <td><?= htmlspecialchars($leave['applied_on'] ?? '') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; }
.calendar-cell { border: 1px solid #e5e7eb; border-radius: 6px; padding: 8px; min-height: 70px; position: relative; cursor: pointer; }
.calendar-cell .date { font-weight: 600; font-size: 0.9rem; }
.calendar-cell .dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 4px; }
.calendar-cell.has-event { background: #f6f9ff; }
.calendar-cell.today { border-color: #0d6efd; box-shadow: 0 0 0 1px #0d6efd20; }
.schedule-pill { display: block; font-size: 0.8rem; margin-top: 6px; padding: 4px 6px; border-radius: 4px; background: #e9f2ff; color: #0d47a1; }
.hover-lift { transition: transform 0.2s ease, box-shadow 0.2s ease; }
.hover-lift:hover { transform: translateY(-4px); box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important; }
</style>

<script>
// Store batch data globally for modal
window.batchData = <?= json_encode($allSchedules) ?>;
window.studentCache = <?= json_encode($batchStudents) ?>;
window.allStudentCache = <?= json_encode($studentCache) ?>;

// Open batch attendance modal
function openBatchAttendanceModal(scheduleId, batchName) {
    const sched = window.batchData.find(s => s.id == scheduleId);
    if (!sched) { alert('Schedule not found'); return; }
    const bid = parseInt(sched.batch_id);
    let students = window.studentCache[bid] || [];
    if (!students.length && sched.student_ids) {
        const ids = JSON.parse(sched.student_ids);
        ids.forEach(sid => {
            const found = window.allStudentCache[sid];
            if (found) students.push({ student_id: sid, student_name: found });
        });
    }
    document.getElementById('modalBatchName').textContent = batchName;
    const dateInput = document.getElementById('batchAttDate');
    if (sched.start_date) dateInput.min = sched.start_date;
    if (sched.end_date) dateInput.max = sched.end_date;
    const tbody = document.getElementById('modalStudentList');
    tbody.innerHTML = '';
    students.forEach(stu => {
        const tr = document.createElement('tr');
        tr.setAttribute('data-student-id', stu.student_id);
        tr.innerHTML = `
            <td>${stu.student_name}</td>
            <td>
                <select class="form-select form-select-sm" name="status" aria-label="Status">
                    <option value="present" selected>Present</option>
                    <option value="absent">Absent</option>
                    <option value="leave">Leave</option>
                </select>
            </td>
        `;
        tbody.appendChild(tr);
    });
    bootstrap.Modal.getOrCreateInstance(document.getElementById('batchAttendanceModal')).show();
}

// Save attendance from modal
async function saveBatchAttendance() {
    const batchName = document.getElementById('modalBatchName').textContent;
    const schedId = window.batchData.find(s => s.batch_title === batchName)?.id;
    if (!schedId) { alert('Schedule not found'); return; }
    const sched = window.batchData.find(s => s.id === schedId);
    const attDate = document.getElementById('batchAttDate').value;
    const commonNote = document.getElementById('batchAttNote').value;
    const rows = document.querySelectorAll('#modalStudentList tr');
    const records = [];
    rows.forEach(row => {
        const sid = row.getAttribute('data-student-id');
        const status = row.querySelector('select[name="status"]')?.value || 'present';
        records.push({ student_id: sid, status, note: commonNote });
    });
    if (!records.length) { alert('No students to save'); return; }
    const form = new FormData();
    form.append('batch_id', sched.batch_id);
    form.append('schedule_id', schedId);
    form.append('date', attDate);
    form.append('records', JSON.stringify(records));
    form.append('csrf_token', '<?= $_SESSION['csrf_token'] ?? '' ?>');
    try {
        const res = await fetch('api/attendance.php?action=mark_students', { method: 'POST', body: form });
        const data = await res.json();
        if (data && data.success) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('batchAttendanceModal')).hide();
            alert('Student attendance saved');
        } else {
            alert('Save failed: ' + (data.message || 'Unknown error'));
        }
    } catch (e) {
        alert('Request failed: ' + (e.message || e));
    }
}

// Calendar setup
(function(){
    const schedData = <?= json_encode($schedules) ?>;
    const calendarEl = document.getElementById('scheduleCalendar');
    const listEl = document.getElementById('scheduleList');
    const todayBtn = document.getElementById('calToday');
    const prevBtn = document.getElementById('calPrev');
    const nextBtn = document.getElementById('calNext');

    let current = new Date();

    function formatDate(d){ return d.toISOString().slice(0,10); }
    function firstOfMonth(d){ return new Date(d.getFullYear(), d.getMonth(), 1); }
    function lastOfMonth(d){ return new Date(d.getFullYear(), d.getMonth()+1, 0); }

    function render(){
        if (!calendarEl) return;
        const start = firstOfMonth(current);
        const end = lastOfMonth(current);
        const startWeekday = start.getDay();
        const daysInMonth = end.getDate();
        const todayStr = formatDate(new Date());

        const eventsByDate = {};
        schedData.forEach(ev => {
            const d = ev.start_date;
            if (!eventsByDate[d]) eventsByDate[d] = [];
            eventsByDate[d].push(ev);
        });

        const cells = [];
        // Padding before first day
        for(let i=0;i<startWeekday;i++){ cells.push('<div></div>'); }
        for(let day=1; day<=daysInMonth; day++){
            const dateStr = `${start.getFullYear()}-${String(start.getMonth()+1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
            const events = eventsByDate[dateStr] || [];
            const hasEvent = events.length > 0;
            const isToday = dateStr === todayStr;
            let pills = '';
            events.slice(0,2).forEach(ev => {
                const title = ev.title || 'Session';
                const batch = ev.batch_title || 'Batch';
                pills += `<span class="schedule-pill">${title} (${batch})</span>`;
            });
            if (events.length > 2) {
                pills += `<span class="schedule-pill">+${events.length-2} more</span>`;
            }
            cells.push(`<div class="calendar-cell ${hasEvent ? 'has-event' : ''} ${isToday ? 'today' : ''}" data-date="${dateStr}">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="date">${day}</span>
                    ${hasEvent ? '<span class="dot bg-primary"></span>' : ''}
                </div>
                ${pills}
            </div>`);
        }
        calendarEl.innerHTML = cells.join('');

        calendarEl.querySelectorAll('.calendar-cell').forEach(cell => {
            cell.addEventListener('click', () => {
                const d = cell.getAttribute('data-date');
                highlightList(d);
            });
        });
    }

    function highlightList(dateStr){
        if (!listEl) return;
        const items = listEl.querySelectorAll('.list-group-item');
        items.forEach(item => {
            const badge = item.querySelector('.badge');
            if (badge && badge.textContent.trim() === dateStr) {
                item.classList.add('bg-light');
            } else {
                item.classList.remove('bg-light');
            }
        });
    }

    if (todayBtn) todayBtn.onclick = () => { current = new Date(); render(); };
    if (prevBtn) prevBtn.onclick = () => { current = new Date(current.getFullYear(), current.getMonth()-1, 1); render(); };
    if (nextBtn) nextBtn.onclick = () => { current = new Date(current.getFullYear(), current.getMonth()+1, 1); render(); };

    render();
})();

// Attendance Report Functions
function loadAttendanceReport() {
    const batchId = document.getElementById('reportBatch').value;
    const fromDate = document.getElementById('reportFromDate').value;
    const toDate = document.getElementById('reportToDate').value;
    const statusFilter = document.getElementById('reportStatus').value;

    if (!batchId) {
        alert('Please select a batch');
        return;
    }
    if (!fromDate || !toDate) {
        alert('Please select date range');
        return;
    }
    if (new Date(fromDate) > new Date(toDate)) {
        alert('From date cannot be later than To date');
        return;
    }

    // Show loading
    const reportBody = document.getElementById('reportBody');
    const reportContainer = document.getElementById('reportContainer');
    const reportEmpty = document.getElementById('reportEmpty');
    const colspan = 5;
    reportBody.innerHTML = `<tr><td colspan="${colspan}" class="text-center"><span class="spinner-border spinner-border-sm me-2"></span>Loading...</td></tr>`;
    reportContainer.style.display = 'block';
    reportEmpty.style.display = 'none';

    fetch(`api/attendance.php?action=report`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': '<?= $_SESSION['csrf_token'] ?? '' ?>'
        },
        body: JSON.stringify({
            batch_id: batchId,
            from_date: fromDate,
            to_date: toDate,
            status_filter: statusFilter,
            csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>'
        })
    })
    .then(r => r.json())
    .then(data => {
        console.log('Report response:', data);
        if (data.success) {
            const records = data.records || [];
            if (records.length === 0) {
                reportBody.innerHTML = `<tr><td colspan="${colspan}" class="text-center text-muted">No attendance records found for selected criteria</td></tr>`;
                return;
            }
            
            let html = '';
            records.forEach(rec => {
                const statusBadge = rec.status === 'present' ? '<span class="badge bg-success-subtle text-success">Present</span>' :
                                   rec.status === 'absent' ? '<span class="badge bg-danger-subtle text-danger">Absent</span>' :
                                   '<span class="badge bg-warning-subtle text-warning">Leave</span>';
                html += `<tr>
                    <td>${rec.student_name || 'N/A'}</td>
                    <td class="small text-muted">${rec.batch_title || '-'}</td>
                    <td>${rec.date || 'N/A'}</td>
                    <td>${statusBadge}</td>
                    <td class="small text-muted">${rec.note || '-'}</td>
                </tr>`;
            });
            reportBody.innerHTML = html;
        } else {
            reportBody.innerHTML = `<tr><td colspan="${colspan}" class="text-center text-danger">${data.message || 'Failed to load report'}</td></tr>`;
        }
    })
    .catch(err => {
        console.error('Report error:', err);
        reportBody.innerHTML = `<tr><td colspan="${colspan}" class="text-center text-danger">Error loading report. Please try again.</td></tr>`;
    });
}
</script>
