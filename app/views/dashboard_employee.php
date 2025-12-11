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

// Today's attendance (respect faculty/employee entity type)
$todayAttendance = null;
$today = date('Y-m-d');
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
</style>

<script>
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
</script>
