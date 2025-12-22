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
    
    // Preload batch assignments and students
    if (!empty($allSchedules)) {
        $batchIds = array_values(array_unique(array_filter(array_map(fn($s) => (int)($s['batch_id'] ?? 0), $allSchedules))));
        if ($batchIds) {
            $in = implode(',', array_fill(0, count($batchIds), '?'));
            $types = str_repeat('i', count($batchIds));
            // Get students from batch_assignments via batch_assignment_students
            $sql = "SELECT ba.batch_id, s.id AS student_id, s.name AS student_name 
                    FROM batch_assignments ba 
                    JOIN batch_assignment_students bas ON bas.assignment_id = ba.id 
                    JOIN students s ON s.id = bas.student_id 
                    WHERE ba.batch_id IN ($in) 
                    ORDER BY s.name";
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

// Load all students with batch info for report filtering
// Query students from batch_assignments via batch_assignment_students
$reportStudents = [];
$seenPairs = [];

$reportSql = "SELECT DISTINCT 
              s.id as student_id, 
              s.name as student_name, 
              ba.batch_id, 
              b.title as batch_title
              FROM students s
              JOIN batch_assignment_students bas ON bas.student_id = s.id
              JOIN batch_assignments ba ON ba.id = bas.assignment_id
              JOIN batches b ON b.id = ba.batch_id
              WHERE (s.status = 1 OR s.status = 'active' OR s.status IS NOT NULL)
              ORDER BY s.name ASC, b.title ASC";

$reportResult = mysqli_query($conn, $reportSql);
if ($reportResult && mysqli_num_rows($reportResult) > 0) {
    while ($row = mysqli_fetch_assoc($reportResult)) {
        $studentId = (int)($row['student_id'] ?? 0);
        $batchId = (int)($row['batch_id'] ?? 0);
        
        if ($studentId > 0 && $batchId > 0) {
            $pair = $studentId . '_' . $batchId;
            
            if (!isset($seenPairs[$pair])) {
                $seenPairs[$pair] = true;
                $reportStudents[] = [
                    'student_id' => $studentId,
                    'student_name' => $row['student_name'] ?? 'Unknown',
                    'batch_id' => $batchId,
                    'batch_title' => $row['batch_title'] ?? 'Unknown'
                ];
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

<style>
.report-filters {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid #e9ecef;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
.report-filters .form-label {
    font-size: 0.813rem;
    font-weight: 500;
    color: #495057;
    margin-bottom: 0.375rem;
}
.report-table-container {
    overflow-x: auto;
    max-height: 600px;
}
.report-table {
    font-size: 0.813rem;
}
.report-table th {
    position: sticky;
    top: 0;
    background: #fff;
    z-index: 10;
    white-space: nowrap;
    font-size: 0.813rem;
    padding: 0.5rem;
    background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
    border-bottom: 2px solid #dee2e6;
}
.report-table td {
    white-space: nowrap;
    padding: 0.5rem;
    border-bottom: 1px solid #dee2e6;
}
.report-table tbody tr:hover {
    background: #f8f9fa !important;
}
.attendance-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    font-weight: 700;
    font-size: 0.875rem;
    color: white;
}
.badge-present {
    background-color: #28a745;
}
.badge-absent {
    background-color: #dc3545;
}
.badge-leave {
    background-color: #ffc107;
    color: #000;
}
.badge-holiday {
    background-color: #6c757d;
}
.time-cell {
    font-family: 'Courier New', monospace;
    font-size: 0.75rem;
}
.time-info {
    font-size: 0.7rem;
    color: #666;
    margin-top: 2px;
}
.legend-item {
    display: inline-flex;
    align-items: center;
    margin-right: 1rem;
    font-size: 0.813rem;
    gap: 0.5rem;
}
</style>

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

    <!-- My Attendance Report -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>My Attendance Report</h6>
                    <button class="btn btn-sm btn-primary" onclick="loadMyAttendanceReport()">
                        <i class="fas fa-sync me-1"></i>Load Report
                    </button>
                </div>
                <div class="card-body">
                    <div class="report-filters mb-3">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="myFromDate" class="form-label">From Date</label>
                                <input type="date" class="form-control form-control-sm" id="myFromDate" 
                                       value="<?= date('Y-m-01') ?>" />
                            </div>
                            <div class="col-md-4">
                                <label for="myToDate" class="form-label">To Date</label>
                                <input type="date" class="form-control form-control-sm" id="myToDate" 
                                       value="<?= date('Y-m-d') ?>" />
                            </div>
                        </div>
                    </div>
                    <div class="report-table-container">
                        <table class="table table-sm table-bordered report-table">
                            <thead id="myReportHead">
                                <tr><th class="text-center text-muted">Select date range and click Load Report</th></tr>
                            </thead>
                            <tbody id="myReportBody"></tbody>
                        </table>
                    </div>
                    <div class="mt-2">
                        <div class="legend-item">
                            <div class="attendance-badge badge-present">P</div> Present
                        </div>
                        <div class="legend-item">
                            <div class="attendance-badge badge-absent">A</div> Absent
                        </div>
                        <div class="legend-item">
                            <div class="attendance-badge badge-leave">L</div> Leave
                        </div>
                        <div class="legend-item">
                            <div class="attendance-badge badge-holiday">H</div> Holiday
                        </div>
                    </div>
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
                            <select class="form-select form-select-sm" id="reportBatch" onchange="updateStudentFilter()">
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
                        <div class="col-md-3">
                            <label class="form-label small">Student</label>
                            <select class="form-select form-select-sm" id="reportStudent">
                                <option value="all">All Students</option>
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
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <button class="btn btn-sm btn-primary" onclick="loadAttendanceReport()">
                                <i class="fas fa-search"></i> Generate Report
                            </button>
                            <button class="btn btn-sm btn-outline-secondary ms-2" onclick="exportAttendanceReport()">
                                <i class="fas fa-file-excel"></i> Export to Excel
                            </button>
                        </div>
                    </div>
                    <div id="reportContainer" class="table-responsive" style="display:none;">
                        <table class="table table-sm table-bordered align-middle text-center" id="reportTable" style="font-size: 0.85rem;">
                            <thead class="table-light" id="reportTableHead">
                                <!-- Will be populated dynamically -->
                            </thead>
                            <tbody id="reportBody">
                            </tbody>
                        </table>
                        <div class="mt-2 small text-muted">
                            <span class="badge bg-success me-2">P</span> Present
                            <span class="badge bg-danger ms-3 me-2">A</span> Absent
                            <span class="badge bg-warning ms-3 me-2">L</span> Leave
                            <span class="badge bg-secondary ms-3 me-2">H</span> Holiday (Sunday)
                        </div>
                    </div>
                    <div id="reportEmpty" class="text-center text-muted py-4">
                        <i class="fas fa-chart-bar fa-2x mb-2"></i>
                        <p class="mb-0">Select batch and date range to view attendance report</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upcoming Sessions -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-video me-2"></i>Upcoming Sessions</h6>
                    <button class="btn btn-sm btn-primary" onclick="loadUpcomingSessions()">
                        <i class="fas fa-sync me-1"></i>Refresh
                    </button>
                </div>
                <div class="card-body">
                    <div id="upcomingSessionsContainer" style="min-height: 200px;">
                        <p class="text-muted text-center py-4"><i class="fas fa-spinner fa-spin me-2"></i>Loading sessions...</p>
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

/* Upcoming sessions cards */
.session-card { border: 1px solid #e9ecef; border-radius: 10px; padding: 12px; background: #fff; }
.session-card .title { font-weight: 600; }
.session-card .meta { font-size: 0.8rem; color: #6c757d; }
.session-card .badge-time { background: #e9f2ff; color: #0d47a1; }
.session-card .badge-date { background: #f6f9ff; color: #0d6efd; }
.session-card .actions { display: flex; gap: 8px; }
.section-title { font-weight: 600; font-size: 0.95rem; color: #343a40; }
.divider { border: 0; border-top: 1px solid #e9ecef; margin: 1rem 0; }

/* Modal styles */
.attachment-list { list-style: none; padding-left: 0; margin: 0; }
.attachment-list li { display: flex; align-items: center; justify-content: space-between; padding: 6px 8px; border: 1px dashed #dee2e6; border-radius: 6px; margin-bottom: 6px; background: #f8f9fa; }
.note-hint { font-size: 0.75rem; color: #6c757d; }
</style>

<script>
// Current user context for session completion (avoid const to prevent re-declare on AJAX nav)
window.CURRENT_USER_ID = window.CURRENT_USER_ID || <?= (int)($userId ?? 0) ?>;
window.CURRENT_USER_ROLE = window.CURRENT_USER_ROLE || '<?= htmlspecialchars($userRole) ?>';

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

// My Attendance Report Function
function loadMyAttendanceReport() {
    const fromDate = document.getElementById('myFromDate').value;
    const toDate = document.getElementById('myToDate').value;
    
    if (!fromDate || !toDate) {
        CRUD.toastError('Please select date range');
        return;
    }
    
    const reportHead = document.getElementById('myReportHead');
    const reportBody = document.getElementById('myReportBody');
    
    reportHead.innerHTML = '<tr><th class="text-center">Loading...</th></tr>';
    reportBody.innerHTML = '<tr><td class="text-center">Please wait...</td></tr>';
    
    fetch('../api/attendance.php?action=faculty_report', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': '<?= $_SESSION['csrf_token'] ?? '' ?>'
        },
        body: JSON.stringify({
            branch_id: 'all',
            faculty_id: '<?= $userId ?>',
            from_date: fromDate,
            to_date: toDate,
            csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>'
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success && data.records) {
            renderMyAttendanceReport(data.records, fromDate, toDate);
        } else {
            reportHead.innerHTML = '<tr><th class="text-center text-danger">Error</th></tr>';
            reportBody.innerHTML = `<tr><td class="text-center text-danger">${data.message || 'Failed to load report'}</td></tr>`;
        }
    })
    .catch(err => {
        console.error('Report error:', err);
        reportHead.innerHTML = '<tr><th class="text-center text-danger">Error</th></tr>';
        reportBody.innerHTML = '<tr><td class="text-center text-danger">Error loading report. Please try again.</td></tr>';
    });
}

function renderMyAttendanceReport(records, fromDate, toDate) {
    const reportHead = document.getElementById('myReportHead');
    const reportBody = document.getElementById('myReportBody');
    
    if (records.length === 0) {
        reportHead.innerHTML = '<tr><th class="text-center text-muted">No records found</th></tr>';
        reportBody.innerHTML = '';
        return;
    }
    
    // Generate date range
    const start = new Date(fromDate);
    const end = new Date(toDate);
    const dateHeaders = [];
    
    for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
        const dateStr = d.toISOString().split('T')[0];
        const dayName = d.toLocaleDateString('en-US', { weekday: 'short' });
        const day = d.getDate();
        const month = d.toLocaleDateString('en-US', { month: 'short' });
        const isSunday = d.getDay() === 0;
        
        dateHeaders.push({ date: dateStr, dayName, day, month, isSunday });
    }
    
    // Build header row with dates
    let headerHtml = '<tr><th style="background: #f8f9fa; text-align: left; padding: 0.75rem !important; min-width: 180px;">Date</th><th style="background: #f8f9fa; text-align: left; padding: 0.75rem !important; min-width: 200px;">Batch</th><th style="background: #f8f9fa; text-align: center; padding: 0.75rem !important;">Status</th><th style="background: #f8f9fa; text-align: center; padding: 0.75rem !important;">In Time</th><th style="background: #f8f9fa; text-align: center; padding: 0.75rem !important;">Out Time</th></tr>';
    reportHead.innerHTML = headerHtml;
    
    // Build attendance map: key = "date", value = array of records
    const attendanceMap = {};
    records.forEach(rec => {
        const key = rec.date;
        if (!attendanceMap[key]) {
            attendanceMap[key] = [];
        }
        attendanceMap[key].push(rec);
    });
    
    // Build body rows - one row per date
    let bodyHtml = '';
    dateHeaders.forEach((header, idx) => {
        const dayRecords = attendanceMap[header.date] || [];
        const rowBg = idx % 2 === 0 ? '#ffffff' : '#f8f9fa';
        const cellBg = header.isSunday ? '#fff3cd' : rowBg;
        
        if (header.isSunday && dayRecords.length === 0) {
            // Sunday with no records - show holiday
            bodyHtml += `<tr style="background: ${cellBg};">
                <td style="padding: 0.75rem !important;">${header.day} ${header.month} (${header.dayName})</td>
                <td style="padding: 0.75rem !important;">-</td>
                <td class="text-center"><span class="badge bg-secondary">Holiday</span></td>
                <td class="text-center">-</td>
                <td class="text-center">-</td>
            </tr>`;
        } else if (dayRecords.length === 0) {
            // No records - show absent
            bodyHtml += `<tr style="background: ${rowBg};">
                <td style="padding: 0.75rem !important;">${header.day} ${header.month} (${header.dayName})</td>
                <td style="padding: 0.75rem !important;">-</td>
                <td class="text-center"><span class="badge bg-danger">Absent</span></td>
                <td class="text-center">-</td>
                <td class="text-center">-</td>
            </tr>`;
        } else {
            // Has attendance records - show each one
            dayRecords.forEach((rec, recIdx) => {
                const statusMap = { 
                    'present': ['Present', 'bg-success'], 
                    'absent': ['Absent', 'bg-danger'], 
                    'leave': ['Leave', 'bg-warning'] 
                };
                const [statusLabel, statusClass] = statusMap[rec.status] || ['Unknown', 'bg-secondary'];
                
                const inTime = rec.in_time ? rec.in_time.substring(0, 5) : '-';
                const outTime = rec.out_time ? rec.out_time.substring(0, 5) : '-';
                const batchName = rec.batch_name || '-';
                
                // Show date only for first record of the day
                const dateDisplay = recIdx === 0 ? `${header.day} ${header.month} (${header.dayName})` : '';
                
                bodyHtml += `<tr style="background: ${cellBg};">
                    <td style="padding: 0.75rem !important;">${dateDisplay}</td>
                    <td style="padding: 0.75rem !important; font-size: 0.813rem;">${escapeHtml(batchName)}</td>
                    <td class="text-center"><span class="badge ${statusClass}">${statusLabel}</span></td>
                    <td class="text-center time-cell">${inTime}</td>
                    <td class="text-center time-cell">${outTime}</td>
                </tr>`;
            });
        }
    });
    
    reportBody.innerHTML = bodyHtml;
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

// Store student data for filtering
window.reportStudentData = <?= json_encode($reportStudents ?? []) ?>;
// console.log('=== Report Data Debug ===');
// console.log('Initial Report Students Data:', window.reportStudentData);
// console.log('Count:', window.reportStudentData.length);
// console.log('PHP Report Students Count:', <?= count($reportStudents ?? []) ?>);
if (window.reportStudentData.length > 0) {
    // console.log('First student:', window.reportStudentData[0]);
}

// Update student filter based on batch selection
function updateStudentFilter() {
    const batchId = document.getElementById('reportBatch').value;
    const studentSelect = document.getElementById('reportStudent');
    const students = window.reportStudentData || [];
    
    // Always start with "All Students" option
    studentSelect.innerHTML = '<option value="all">All Students</option>';
    
    // If no batch selected, only show "All Students"
    if (!batchId || batchId === '') {
        return;
    }
    
    // console.log('Batch ID:', batchId, 'Type:', typeof batchId);
    // console.log('Total students in data:', students.length);
    // console.log('All Student Data:', students);
    
    let filteredStudents = [];
    
    if (batchId === 'all') {
        // Show all students from all batches
        filteredStudents = students;
        // console.log('All batches selected, showing all students:', filteredStudents.length);
    } else {
        // Show only students from selected batch
        const selectedBatchId = parseInt(batchId);
        // console.log('Selected batch ID (parsed):', selectedBatchId);
        
        filteredStudents = students.filter(s => {
            const match = parseInt(s.batch_id) === selectedBatchId;
            if (match) {
                // console.log('Match found:', s.student_name, 'batch_id:', s.batch_id);
            }
            return match;
        });
        // console.log('Filtered Students count:', filteredStudents.length);
    }
    
    // Remove duplicates by student_id
    const uniqueStudents = [];
    const seenIds = new Set();
    filteredStudents.forEach(s => {
        if (!seenIds.has(s.student_id)) {
            seenIds.add(s.student_id);
            uniqueStudents.push(s);
        }
    });
    
    // Sort by name
    uniqueStudents.sort((a, b) => a.student_name.localeCompare(b.student_name));
    
    // console.log('Final unique students:', uniqueStudents.length);
    
    // Add student options
    uniqueStudents.forEach(student => {
        const option = document.createElement('option');
        option.value = student.student_id;
        option.textContent = student.student_name;
        studentSelect.appendChild(option);
    });
    
    // Show message if no students found
    if (uniqueStudents.length === 0 && batchId !== 'all') {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = 'No students in this batch';
        option.disabled = true;
        studentSelect.appendChild(option);
    }
}

// Initialize student filter on page load
document.addEventListener('DOMContentLoaded', function() {
    // console.log('Page loaded, reportStudentData:', window.reportStudentData);
    // console.log('Data length:', window.reportStudentData ? window.reportStudentData.length : 0);
    
    // If no data loaded, try to fetch it
    if (!window.reportStudentData || window.reportStudentData.length === 0) {
        // console.log('No student data available, attempting to fetch from API...');
        fetch('api/students.php?action=getall', {
            method: 'GET',
            headers: {'Content-Type': 'application/json'}
        })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.students) {
                // Convert to report format (assuming API returns students with batch info)
                window.reportStudentData = data.students.map(s => ({
                    student_id: s.id,
                    student_name: s.name,
                    batch_id: s.batch_id || 0,
                    batch_title: s.batch_title || ''
                }));
                // console.log('Fetched student data:', window.reportStudentData);
            }
        })
        .catch(err => console.error('Failed to fetch student data:', err));
    }
    
    // Trigger update if a batch is pre-selected
    const batchSelect = document.getElementById('reportBatch');
    if (batchSelect && batchSelect.value) {
        setTimeout(updateStudentFilter, 500);
    }
});

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
    const reportHead = document.getElementById('reportTableHead');
    const reportContainer = document.getElementById('reportContainer');
    const reportEmpty = document.getElementById('reportEmpty');
    
    reportHead.innerHTML = '<tr><th colspan="100" class="text-center"><span class="spinner-border spinner-border-sm me-2"></span>Loading...</th></tr>';
    reportBody.innerHTML = '';
    reportContainer.style.display = 'block';
    reportEmpty.style.display = 'none';

    const studentId = document.getElementById('reportStudent').value;

    fetch(`api/attendance.php?action=report`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': '<?= $_SESSION['csrf_token'] ?? '' ?>'
        },
        body: JSON.stringify({
            batch_id: batchId,
            student_id: studentId,
            from_date: fromDate,
            to_date: toDate,
            status_filter: statusFilter,
            csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>'
        })
    })
    .then(r => r.json())
    .then(data => {
        // console.log('Report response:', data);
        if (data.success) {
            const records = data.records || [];
            
            // Generate date range
            const start = new Date(fromDate);
            const end = new Date(toDate);
            const dates = [];
            const dateHeaders = [];
            
            for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
                const dateStr = d.toISOString().split('T')[0];
                dates.push(dateStr);
                const day = d.getDate();
                const month = d.toLocaleString('default', { month: 'short' });
                const dayName = d.toLocaleString('default', { weekday: 'short' });
                dateHeaders.push({ date: dateStr, day: day, month: month, dayName: dayName, isSunday: d.getDay() === 0 });
            }
            
            if (dates.length === 0) {
                reportHead.innerHTML = '<tr><th class="text-center text-muted">Invalid date range</th></tr>';
                reportBody.innerHTML = '';
                return;
            }
            
            // Build student attendance map
            const studentMap = {};
            records.forEach(rec => {
                const studentId = rec.entity_id;
                const studentName = rec.student_name || 'Unknown';
                const batchTitle = rec.batch_title || '-';
                const startTime = rec.start_time || '';
                const endTime = rec.end_time || '';
                const date = rec.date;
                const status = rec.status || 'absent';
                
                if (!studentMap[studentId]) {
                    // Create batch display with times if available
                    let batchDisplay = batchTitle;
                    if (startTime && endTime) {
                        // Format time to readable format (HH:MM)
                        const start = startTime.substring(0, 5);
                        const end = endTime.substring(0, 5);
                        batchDisplay += ` <small class="text-muted">(${start} - ${end})</small>`;
                    }
                    
                    studentMap[studentId] = {
                        name: studentName,
                        batch: batchDisplay,
                        attendance: {}
                    };
                }
                studentMap[studentId].attendance[date] = status;
            });
            
            const students = Object.values(studentMap);
            
            if (students.length === 0) {
                reportHead.innerHTML = '<tr><th class="text-center text-muted">No students found for selected batch</th></tr>';
                reportBody.innerHTML = '';
                return;
            }
            
            // Build header row
            let headerHtml = '<tr><th rowspan="2" class="align-middle" style="min-width: 150px;">Student</th><th rowspan="2" class="align-middle" style="min-width: 120px;">Batch</th>';
            dateHeaders.forEach(dh => {
                const bgClass = dh.isSunday ? 'bg-secondary-subtle' : '';
                headerHtml += `<th class="${bgClass}" style="min-width: 40px;">${dh.day}<br><small>${dh.month}</small></th>`;
            });
            headerHtml += '</tr><tr>';
            dateHeaders.forEach(dh => {
                const bgClass = dh.isSunday ? 'bg-secondary-subtle' : '';
                headerHtml += `<th class="small ${bgClass}">${dh.dayName}</th>`;
            });
            headerHtml += '</tr>';
            reportHead.innerHTML = headerHtml;
            
            // Build body rows
            let bodyHtml = '';
            students.forEach(student => {
                bodyHtml += `<tr>`;
                bodyHtml += `<td class="text-start fw-semibold">${student.name}</td>`;
                bodyHtml += `<td class="text-start small text-muted">${student.batch}</td>`;
                
                dates.forEach((date, idx) => {
                    const isSunday = dateHeaders[idx].isSunday;
                    const attendance = student.attendance[date];
                    
                    let cellContent = '';
                    let cellClass = '';
                    
                    if (isSunday) {
                        // Sunday - show holiday
                        cellContent = '<span class="badge bg-secondary">H</span>';
                        cellClass = 'bg-secondary-subtle';
                    } else if (attendance) {
                        // Has attendance record
                        if (statusFilter !== 'all' && attendance !== statusFilter) {
                            // Filtered out - show dash
                            cellContent = '-';
                            cellClass = 'text-muted';
                        } else if (attendance === 'present') {
                            cellContent = '<span class="badge bg-success">P</span>';
                        } else if (attendance === 'leave') {
                            cellContent = '<span class="badge bg-warning text-dark">L</span>';
                        } else {
                            cellContent = '<span class="badge bg-danger">A</span>';
                        }
                    } else {
                        // No data - default absent
                        if (statusFilter === 'all' || statusFilter === 'absent') {
                            cellContent = '<span class="badge bg-danger">A</span>';
                        } else {
                            cellContent = '-';
                            cellClass = 'text-muted';
                        }
                    }
                    
                    bodyHtml += `<td class="${cellClass}">${cellContent}</td>`;
                });
                
                bodyHtml += `</tr>`;
            });
            
            reportBody.innerHTML = bodyHtml;
        } else {
            reportHead.innerHTML = '<tr><th class="text-center text-danger">Error</th></tr>';
            reportBody.innerHTML = `<tr><td class="text-center text-danger">${data.message || 'Failed to load report'}</td></tr>`;
        }
    })
    .catch(err => {
        console.error('Report error:', err);
        reportHead.innerHTML = '<tr><th class="text-center text-danger">Error</th></tr>';
        reportBody.innerHTML = '<tr><td class="text-center text-danger">Error loading report. Please try again.</td></tr>';
    });
}

function exportAttendanceReport() {
    alert('Export functionality will be implemented soon!');
}

// Load Upcoming Sessions
async function loadUpcomingSessions() {
    const container = document.getElementById('upcomingSessionsContainer');
    try {
        const res = await CRUD.get('api/session_completion.php?action=occurrences&scope=upcoming');
        if (!res.success || !Array.isArray(res.data)) {
            container.innerHTML = '<p class="text-muted text-center py-4">No upcoming sessions</p>';
            return;
        }

        const sessions = res.data;
        if (sessions.length === 0) {
            container.innerHTML = '<p class="text-muted text-center py-4"><i class="fas fa-inbox me-2"></i>No upcoming sessions scheduled</p>';
            return;
        }
        const todayStr = new Date().toISOString().slice(0,10);
        const todaySessions = sessions.filter(s => s.session_date === todayStr);
        const futureSessions = sessions.filter(s => s.session_date > todayStr);

        function renderCards(list){
            if (list.length === 0) return '<p class="text-muted">No sessions</p>';
            let html = '<div class="row g-2">';
            list.forEach(session => {
                const date = new Date(session.session_date);
                const formattedDate = date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
                const payload = encodeURIComponent(JSON.stringify(session));
                const statusClass = session.latest_status === 'completed' ? 'bg-success' : 'bg-warning text-dark';
                const statusText = (session.latest_status || 'Pending').toUpperCase();
                const completionCount = session.completion_count || 0;
                html += `
                <div class="col-md-6">
                  <div class="session-card hover-lift">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <span class="badge badge-date">${formattedDate}</span>
                      <span class="badge ${statusClass}">${statusText}</span>
                    </div>
                    <div class="title">${session.batch_title || 'Session'}</div>
                    <div class="meta"><i class="far fa-clock me-1"></i>${session.start_time} - ${session.end_time}</div>
                    ${completionCount > 0 ? `<div class="meta"><i class="fas fa-check-circle me-1" style="color: #28a745;"></i>${completionCount} completion(s)</div>` : ''}
                    <div class="actions mt-2">
                      <button class="btn btn-sm btn-outline-primary" onclick='openSessionDetails(decodeURIComponent("${payload}"))'><i class="fas fa-edit me-1"></i>Add Details</button>
                      ${completionCount > 0 ? `<button class="btn btn-sm btn-outline-info" onclick='openSessionView(${session.occurrence_id})'><i class="fas fa-eye me-1"></i>View</button>` : ''}
                    </div>
                  </div>
                </div>`;
            });
            html += '</div>';
            return html;
        }

        let html = '';
        html += '<div class="section-title">Today</div>' + renderCards(todaySessions);
        html += '<div class="divider"></div>';
        html += '<div class="section-title">Upcoming</div>' + renderCards(futureSessions);
        container.innerHTML = html;
    } catch(err) {
        console.error('Failed to load sessions', err);
        container.innerHTML = '<p class="text-danger text-center py-4">Failed to load sessions</p>';
    }
}

// Auto-load on page init
document.addEventListener('DOMContentLoaded', function() {
    loadUpcomingSessions();
    resetNotesFields();
});
</script>

<!-- Session Details Modal -->
<div class="modal fade" id="sessionDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Session Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2 small text-muted">
            <div><strong>Batch:</strong> <span id="sdBatch"></span></div>
            <div><strong>Date:</strong> <span id="sdDate"></span></div>
            <div><strong>Time:</strong> <span id="sdTime"></span></div>
        </div>
        <form id="sessionCompletionForm" onsubmit="submitSessionCompletion(event)">
            <input type="hidden" name="occurrence_id" />
            <input type="hidden" name="schedule_id" />
            <input type="hidden" name="batch_id" />
                        <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                        <option value="completed" selected>Completed</option>
                                        <option value="pending">Pending</option>
                                        <option value="cancelled">Cancelled</option>
                                </select>
                        </div>
            <div class="mb-3">
                <label class="form-label">Actual Start Time</label>
                <input type="time" class="form-control" name="actual_start_time" />
            </div>
            <div class="mb-3">
                <label class="form-label">Actual End Time</label>
                <input type="time" class="form-control" name="actual_end_time" />
            </div>
            <div class="mb-3">
                <label class="form-label">Completion Code</label>
                <input type="text" class="form-control" name="completion_code" placeholder="Enter code" />
            </div>
            <div class="mb-3">
                <label class="form-label">Notes</label>
                <div id="notesContainer"></div>
                <div class="d-flex gap-2 mt-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addNoteField()"><i class="fas fa-plus me-1"></i>Add another note</button>
                    <div class="note-hint">Notes are saved exactly as entered (HTML/code allowed). Add multiple using the button.</div>
                </div>
            </div>
                        <div class="mb-3">
                                <label class="form-label">Attachments</label>
                                <div class="d-flex align-items-center gap-2">
                                        <input type="file" class="form-control" id="sessionAttachments" multiple accept="application/pdf,video/*,audio/*,image/*,.doc,.docx,.xls,.xlsx" />
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearAttachmentSelection()"><i class="fas fa-times me-1"></i>Clear</button>
                                </div>
                                <ul class="attachment-list mt-2" id="attachmentPreview"></ul>
                        </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick="submitSessionCompletion()">Save Details</button>
      </div>
    </div>
  </div>
</div>

<!-- Session View Modal - Timeline of all completions -->
<div class="modal fade" id="sessionViewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h5 class="modal-title">
          <i class="fas fa-file-alt me-2"></i>Session Details
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3 pb-2" style="border-bottom: 1px solid #e9ecef;">
            <div class="row g-2">
                <div class="col-sm-6">
                    <div><strong class="text-muted">Batch</strong></div>
                    <div id="svBatch" class="fs-6"></div>
                </div>
                <div class="col-sm-6">
                    <div><strong class="text-muted">Date & Time</strong></div>
                    <div id="svDateTime" class="fs-6"></div>
                </div>
            </div>
        </div>

        <!-- Completions Timeline -->
        <div id="completionsTimeline">
            <p class="text-muted text-center py-4"><i class="fas fa-spinner fa-spin me-2"></i>Loading...</p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function openSessionDetails(session) {
    try { if (typeof session === 'string') session = JSON.parse(session); } catch(e) {}
    const modalEl = document.getElementById('sessionDetailsModal');
    const formEl = document.getElementById('sessionCompletionForm');
    if (!modalEl || !formEl) return;

    document.getElementById('sdBatch').textContent = session.batch_title || 'Session';
    document.getElementById('sdDate').textContent = session.session_date;
    document.getElementById('sdTime').textContent = `${session.start_time} - ${session.end_time}`;

    formEl.occurrence_id.value = session.occurrence_id;
    formEl.schedule_id.value = session.schedule_id;
    formEl.batch_id.value = session.batch_id;

    // Reset status, notes, attachments
    formEl.status.value = 'completed';
    resetNotesFields();
    window.pendingSessionFiles = [];
    document.getElementById('attachmentPreview').innerHTML = '';
    const fileInput = document.getElementById('sessionAttachments');
    fileInput.value = '';
    fileInput.onchange = () => handleAttachmentSelection(fileInput.files);
    
    // Auto-generate completion code: SESSION-YYYYMMDD-HHMM-RANDOM
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const mins = String(now.getMinutes()).padStart(2, '0');
    const random = Math.random().toString(36).substring(2, 7).toUpperCase();
    const autoCode = `SESSION-${year}${month}${day}-${hours}${mins}-${random}`;
    formEl.completion_code.value = autoCode;

    bootstrap.Modal.getOrCreateInstance(modalEl).show();
}

async function submitSessionCompletion(evt) {
    evt?.preventDefault?.();
    const formEl = document.getElementById('sessionCompletionForm');
    const occurrenceId = parseInt(formEl.occurrence_id.value || 0);
    const scheduleId = parseInt(formEl.schedule_id.value || 0);
    const batchId = parseInt(formEl.batch_id.value || 0);
    const actualStart = formEl.actual_start_time.value || null;
    const actualEnd = formEl.actual_end_time.value || null;
    const code = formEl.completion_code.value || '';
    const noteValues = Array.from(formEl.querySelectorAll('textarea[name="notes[]"]')).map(t => (t.value || '').trim()).filter(Boolean);
    const notes = noteValues.join('\n\n');
    const status = formEl.status.value || 'completed';

    if (!occurrenceId || !scheduleId || !batchId) {
        alert('Missing session identifiers');
        return;
    }

    const data = new FormData();
    data.append('occurrence_id', occurrenceId);
    data.append('schedule_id', scheduleId);
    data.append('batch_id', batchId);
    data.append('completed_by', CURRENT_USER_ID);
    if (CURRENT_USER_ROLE === 'faculty') data.append('faculty_id', CURRENT_USER_ID);
    else data.append('employee_id', CURRENT_USER_ID);
    if (actualStart) data.append('actual_start_time', actualStart);
    if (actualEnd) data.append('actual_end_time', actualEnd);
    if (code) data.append('completion_code', code);
    if (notes) data.append('notes', notes);
    data.append('status', status);
    data.append('csrf_token', '<?= $_SESSION['csrf_token'] ?? '' ?>');

    try {
        let res = await fetch('api/session_completion.php?action=create', { method: 'POST', body: data });
        let json = await res.json();
        if (!json.success || !json.data || !json.data.id) {
            alert('Failed to save session details');
            return;
        }
        const completionId = json.data.id;

        // Update status if needed
        if (status === 'completed') {
            const compForm = new FormData();
            compForm.append('id', completionId);
            compForm.append('actual_start_time', actualStart || '');
            compForm.append('actual_end_time', actualEnd || '');
            compForm.append('completion_code', code || '');
            compForm.append('notes', notes || '');
            compForm.append('csrf_token', '<?= $_SESSION['csrf_token'] ?? '' ?>');
            res = await fetch('api/session_completion.php?action=complete', { method: 'POST', body: compForm });
            json = await res.json();
            if (!json.success) {
                alert('Failed to mark completed');
            }
        } else if (status === 'cancelled') {
            const cancelForm = new FormData();
            cancelForm.append('id', completionId);
            cancelForm.append('csrf_token', '<?= $_SESSION['csrf_token'] ?? '' ?>');
            res = await fetch('api/session_completion.php?action=cancel', { method: 'POST', body: cancelForm });
            json = await res.json();
            if (!json.success) {
                alert('Failed to cancel session');
            }
        }

        // Upload attachments (multiple)
        await uploadSelectedAttachments(completionId);

        // Persist each note as its own entry (no auto line splitting)
        if (noteValues.length > 0) {
            for (const noteText of noteValues) {
                const nf = new FormData();
                nf.append('completion_id', completionId);
                nf.append('note_text', noteText);
                nf.append('note_type', 'general');
                nf.append('csrf_token', '<?= $_SESSION['csrf_token'] ?? '' ?>');
                try { await fetch('api/session_completion.php?action=add_note', { method: 'POST', body: nf }); } catch(e) {}
            }
        }

        bootstrap.Modal.getOrCreateInstance(document.getElementById('sessionDetailsModal')).hide();
        loadUpcomingSessions();
    } catch(e) {
        console.error(e);
        alert('Request failed');
    }
}

function handleAttachmentSelection(fileList) {
    window.pendingSessionFiles = Array.from(fileList || []);
    const preview = document.getElementById('attachmentPreview');
    preview.innerHTML = '';
    window.pendingSessionFiles.forEach((f, idx) => {
        const li = document.createElement('li');
        li.innerHTML = `<span><i class="far fa-file me-2"></i>${f.name} <small class="text-muted">(${Math.round(f.size/1024)} KB)</small></span>`;
        preview.appendChild(li);
    });
}

function clearAttachmentSelection() {
    window.pendingSessionFiles = [];
    document.getElementById('attachmentPreview').innerHTML = '';
    document.getElementById('sessionAttachments').value = '';
}

// Notes helpers (multi-note support without auto line splitting)
function addNoteField(initialValue = '') {
    const container = document.getElementById('notesContainer');
    if (!container) return;
    const idx = container.children.length + 1;
    const wrapper = document.createElement('div');
    wrapper.className = 'note-block mb-2';
    wrapper.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="badge bg-light text-dark">Note ${idx}</span>
            <button type="button" class="btn btn-sm btn-link text-danger" onclick="removeNoteField(this)"><i class="fas fa-times"></i></button>
        </div>
        <textarea class="form-control note-input" name="notes[]" rows="3" placeholder="Add note..."></textarea>
    `;
    container.appendChild(wrapper);
    renumberNoteBadges();
    if (initialValue) {
        const ta = wrapper.querySelector('textarea');
        if (ta) ta.value = initialValue;
    }
}

function removeNoteField(btn) {
    const wrapper = btn.closest('.note-block');
    if (wrapper && wrapper.parentNode) {
        wrapper.parentNode.removeChild(wrapper);
        renumberNoteBadges();
    }
    // Always keep at least one note field
    const container = document.getElementById('notesContainer');
    if (container && container.children.length === 0) {
        addNoteField();
    }
}

function renumberNoteBadges() {
    const container = document.getElementById('notesContainer');
    if (!container) return;
    Array.from(container.children).forEach((child, idx) => {
        const badge = child.querySelector('.badge');
        if (badge) badge.textContent = `Note ${idx + 1}`;
    });
}

function resetNotesFields() {
    const container = document.getElementById('notesContainer');
    if (!container) return;
    container.innerHTML = '';
    addNoteField();
}

async function uploadSelectedAttachments(completionId) {
    const files = window.pendingSessionFiles || [];
    for (const f of files) {
        const form = new FormData();
        form.append('completion_id', completionId);
        form.append('description', f.name);
        form.append('file', f);
        form.append('csrf_token', '<?= $_SESSION['csrf_token'] ?? '' ?>');
        try {
            const res = await fetch('api/session_completion.php?action=upload_attachment', { method: 'POST', body: form });
            const json = await res.json();
            if (!json.success) {
                console.warn('Attachment upload failed:', f.name);
            }
        } catch(e) {
            console.error('Upload error:', e);
        }
    }
}

// View Session - Timeline of all completions
async function openSessionView(occurrenceId) {
    const modalEl = document.getElementById('sessionViewModal');
    if (!modalEl) return;

    // Fetch all completions for this occurrence
    try {
        const res = await fetch(`api/session_completion.php?action=get_by_occurrence&occurrence_id=${occurrenceId}`);
        const json = await res.json();
        
        if (!json.success || !Array.isArray(json.data)) {
            document.getElementById('completionsTimeline').innerHTML = '<p class="text-danger">Failed to load session details</p>';
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
            return;
        }

        const completions = json.data;
        if (completions.length === 0) {
            document.getElementById('completionsTimeline').innerHTML = '<p class="text-muted">No completions recorded</p>';
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
            return;
        }

        // Set header info from first completion
        const first = completions[0];
        document.getElementById('svBatch').textContent = first.batch_title || 'Session';
        const dateStr = first.session_date || '';
        const timeStr = `${first.scheduled_start || '--:--'} to ${first.scheduled_end || '--:--'}`;
        document.getElementById('svDateTime').innerHTML = `<div>${dateStr}</div><div class="small text-muted">${timeStr}</div>`;

        // Build timeline
        let timelineHtml = '<div style="position: relative; padding-left: 30px;">';
        completions.forEach((comp, idx) => {
            const statusClass = comp.status === 'completed' ? 'bg-success' : 
                               comp.status === 'cancelled' ? 'bg-danger' : 'bg-warning text-dark';
            const statusText = (comp.status || 'pending').toUpperCase();
            const created = new Date(comp.created_at).toLocaleString();
            const user = comp.completed_by_name || comp.faculty_name || comp.employee_name || 'Unknown';
            
            timelineHtml += `
            <div style="margin-bottom: 20px; position: relative;">
              <!-- Timeline dot -->
              <div style="position: absolute; left: -30px; width: 16px; height: 16px; border-radius: 50%; background: #0d6efd; top: 2px;"></div>
              
              <!-- Card -->
              <div class="card border-light shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                  <div>
                    <span class="badge ${statusClass} me-2">${statusText}</span>
                    <small class="text-muted">by ${escapeHtml(user)} on ${created}</small>
                  </div>
                </div>
                <div class="card-body">
                  <div class="row g-2 mb-3">
                    ${comp.actual_start_time ? `<div class="col-sm-6"><strong class="text-muted">Start:</strong> ${comp.actual_start_time}</div>` : ''}
                    ${comp.actual_end_time ? `<div class="col-sm-6"><strong class="text-muted">End:</strong> ${comp.actual_end_time}</div>` : ''}
                  </div>
                  ${comp.completion_code ? `<div class="mb-2"><strong class="text-muted">Code:</strong> <code>${escapeHtml(comp.completion_code)}</code></div>` : ''}
                  
                  <!-- Comments from session_notes -->
                  ${comp.comments && Array.isArray(comp.comments) && comp.comments.length > 0 ? `
                    <div class="mt-2">
                      <strong class="text-muted d-block mb-1">Comments (${comp.comments.length}):</strong>
                      <div style="padding: 8px; background: #f0f7ff; border-left: 3px solid #0d6efd; border-radius: 4px; max-height: 200px; overflow-y: auto;">
                        ${comp.comments.map(note => `
                          <div class="mb-1" style="padding-bottom: 6px; border-bottom: 1px solid #e0e7ff;">
                            <small class="text-muted"><i class="far fa-clock me-1"></i>${new Date(note.created_at).toLocaleString()}</small>
                            <div style="margin-top: 2px;">${escapeHtml(note.note_text)}</div>
                          </div>
                        `).join('')}
                      </div>
                    </div>
                  ` : ''}
                  
                  <!-- Attachments -->
                  ${comp.attachments && Array.isArray(comp.attachments) && comp.attachments.length > 0 ? `
                    <div class="mt-2">
                      <strong class="text-muted d-block mb-1">Attachments:</strong>
                      <div class="d-flex flex-wrap gap-2">
                        ${comp.attachments.map(att => {
                            const isVideo = att.file_type?.includes('video');
                            const isAudio = att.file_type?.includes('audio');
                            const isImage = att.file_type?.includes('image');
                            const icon = isVideo ? 'fa-video' : isAudio ? 'fa-volume-up' : isImage ? 'fa-image' : 'fa-file';
                            return `
                            <a href="${att.file_path}" target="_blank" class="btn btn-sm btn-outline-primary">
                              <i class="fas ${icon} me-1"></i>${att.file_name}
                            </a>`;
                        }).join('')}
                      </div>
                    </div>
                  ` : ''}
                </div>
              </div>
            </div>`;
        });
        
        timelineHtml += '</div>';
        document.getElementById('completionsTimeline').innerHTML = timelineHtml;
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
        
    } catch(e) {
        console.error('Error loading session view:', e);
        document.getElementById('completionsTimeline').innerHTML = '<p class="text-danger">Error loading session details</p>';
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text || '').replace(/[&<>"']/g, m => map[m]);
}
</script>
