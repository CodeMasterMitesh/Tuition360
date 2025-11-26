<?php
if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/views/dashboard.php
require_once __DIR__ . '/../controllers/BranchController.php';
require_once __DIR__ . '/../controllers/UserController.php';
require_once __DIR__ . '/../controllers/CourseController.php';
require_once __DIR__ . '/../controllers/StudentController.php';
require_once __DIR__ . '/../controllers/FacultyController.php';

$branches = BranchController::getAll();
$users = UserController::getAll();
$courses = CourseController::getAll();

// Precompute branch-wise counts (students and faculty)
$branchStats = [];
foreach ($branches as $b) {
    $bid = $b['id'];
    $students = StudentController::getAll($bid);
    $faculties = FacultyController::getAll($bid);
    $branchStats[$bid] = [
        'branch' => $b,
        'students' => is_array($students) ? count($students) : 0,
        'faculty' => is_array($faculties) ? count($faculties) : 0,
    ];
}
?>
<?php include __DIR__ . '/partials/nav.php'; ?>
<div class="dashboard-container" style="width:100%;max-width:none;padding-left:16px;padding-right:16px;">
        <!-- Dashboard header and controls removed for minimal look -->

        <div class="row">
            <div class="col-md-4 col-lg-3 mb-4">
                <div class="card text-bg-primary">
                    <div class="card-body position-relative">
                        <i class="fas fa-code-branch card-icon"></i>
                        <h5 class="card-title">Total Branches</h5>
                        <p class="card-text"><?= number_format(count($branches)) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-3 mb-4">
                <div class="card text-bg-success">
                    <div class="card-body position-relative">
                        <i class="fas fa-users card-icon"></i>
                        <h5 class="card-title">Total Users</h5>
                        <p class="card-text"><?= number_format(count($users)) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-3 mb-4">
                <div class="card text-bg-warning">
                    <div class="card-body position-relative">
                        <i class="fas fa-book card-icon"></i>
                        <h5 class="card-title">Total Courses</h5>
                        <p class="card-text"><?= number_format(count($courses)) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-3 mb-4">
                <div class="card text-bg-danger">
                    <div class="card-body position-relative">
                        <i class="fas fa-user-graduate card-icon"></i>
                        <h5 class="card-title">Total Students</h5>
                        <p class="card-text"><?= number_format(array_sum(array_map(fn($bs) => $bs['students'], $branchStats))) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-3 mb-4">
                <div class="card text-bg-info">
                    <div class="card-body position-relative">
                        <i class="fas fa-chalkboard-teacher card-icon"></i>
                        <h5 class="card-title">Total Faculty</h5>
                        <p class="card-text"><?= number_format(array_sum(array_map(fn($bs) => $bs['faculty'], $branchStats))) ?></p>
                    </div>
                </div>
            </div>
        </div>
        <!-- Branch-wise stats -->
        <div class="row mt-3">
            <div class="col-12 col-lg-6">
                <div class="row g-3">
                    <?php foreach ($branchStats as $bs): $b = $bs['branch']; ?>
                        <div class="col-12">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start flex-wrap">
                                        <div>
                                            <h6 class="card-title mb-1"><?= htmlspecialchars($b['name'] ?? 'Branch') ?></h6>
                                            <small class="text-muted"><?= htmlspecialchars($b['address'] ?? '') ?></small>
                                        </div>
                                        <div class="d-flex flex-column align-items-end gap-2">
                                            <span class="status-badge status-active branch-students" style="cursor:pointer;min-width:120px;" data-branch="<?= intval($b['id']) ?>">Students: <?= intval($bs['students']) ?></span>
                                            <span class="status-badge status-inactive branch-faculty" style="cursor:pointer;min-width:120px;" data-branch="<?= intval($b['id']) ?>">Faculty: <?= intval($bs['faculty']) ?></span>
                                        </div>
                                    </div>
                                    <div class="mt-3 text-end">
                                        <small class="text-muted">ID: <?= intval($b['id']) ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-12 col-lg-6 d-flex flex-column gap-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Calendar</h5>
                        <div id="dashboard-calendar"></div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Reminders</h5>
                        <div id="dashboard-reminders"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Courses list below branch cards, left side -->
        <div class="row mt-2">
            <div class="col-12 col-lg-6" style="order:-1;">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Courses (<?= count($courses) ?>)</h5>
                        <ul class="list-unstyled mt-3">
                            <?php if (empty($courses)): ?>
                                <li class="text-muted">No courses found</li>
                            <?php else: ?>
                                <?php foreach (array_slice($courses, 0, 10) as $c): ?>
                                    <li class="py-2 border-bottom">
                                        <strong><?= htmlspecialchars($c['title'] ?? $c['name'] ?? '') ?></strong>
                                        <div class="text-muted small">Branch: <?= intval($c['branch_id'] ?: 0) ?> • Fee: <?= htmlspecialchars($c['total_fee'] ?? '') ?></div>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                        <?php if (count($courses) > 10): ?>
                            <div class="mt-2 text-end"><a href="index.php?page=courses" class="btn btn-sm btn-action">View All Courses</a></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<!-- Branch Students Modal -->
<div class="modal fade" id="branchStudentsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Branch Students</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="branchStudentsTableContainer"></div>
            </div>
        </div>
    </div>
</div>

<!-- Branch Faculty Modal -->
<div class="modal fade" id="branchFacultyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Branch Faculty</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="branchFacultyTableContainer"></div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Open students modal
    document.querySelectorAll('.branch-students').forEach(function(el) {
        el.addEventListener('click', function() {
            const branchId = el.getAttribute('data-branch');
            fetch(`api/students.php?action=list&branch_id=${branchId}`)
                .then(res => res.json())
                .then(data => {
                    let html = `<table class='table table-striped table-bordered' id='branchStudentsTable'><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Status</th></tr></thead><tbody>`;
                    if (data.success && Array.isArray(data.data)) {
                        data.data.forEach(s => {
                            html += `<tr><td>${s.id}</td><td>${s.name}</td><td>${s.email}</td><td>${s.mobile || s.phone || ''}</td><td>${s.status}</td></tr>`;
                        });
                    } else {
                        html += `<tr><td colspan='5'>No students found</td></tr>`;
                    }
                    html += `</tbody></table>`;
                    document.getElementById('branchStudentsTableContainer').innerHTML = html;
                    // Initialize DataTable
                    setTimeout(() => {
                        if (window.$ && window.$.fn.dataTable) {
                            $('#branchStudentsTable').DataTable({
                                pageLength: 10,
                                lengthMenu: [10, 25, 50, 100],
                                searching: true
                            });
                        }
                    }, 100);
                    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('branchStudentsModal'));
                    modal.show();
                });
        });
    });
    // Open faculty modal
    document.querySelectorAll('.branch-faculty').forEach(function(el) {
        el.addEventListener('click', function() {
            const branchId = el.getAttribute('data-branch');
            fetch(`api/faculty.php?action=list&branch_id=${branchId}`)
                .then(res => res.json())
                .then(data => {
                    let html = `<table class='table table-striped table-bordered' id='branchFacultyTable'><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Status</th></tr></thead><tbody>`;
                    if (data.success && Array.isArray(data.data)) {
                        data.data.forEach(f => {
                            html += `<tr><td>${f.id}</td><td>${f.name}</td><td>${f.email}</td><td>${f.mobile || ''}</td><td>${f.status}</td></tr>`;
                        });
                    } else {
                        html += `<tr><td colspan='5'>No faculty found</td></tr>`;
                    }
                    html += `</tbody></table>`;
                    document.getElementById('branchFacultyTableContainer').innerHTML = html;
                    // Initialize DataTable
                    setTimeout(() => {
                        if (window.$ && window.$.fn.dataTable) {
                            $('#branchFacultyTable').DataTable({
                                pageLength: 10,
                                lengthMenu: [10, 25, 50, 100],
                                searching: true
                            });
                        }
                    }, 100);
                    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('branchFacultyModal'));
                    modal.show();
                });
        });
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css">
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Calendar setup
    var calendarEl = document.getElementById('dashboard-calendar');
    if (calendarEl) {
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            height: 400,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            events: async function(fetchInfo, successCallback, failureCallback) {
                // Fetch lecture counts per day (replace with your API)
                try {
                    let res = await fetch('api/attendance.php?action=calendar');
                    let data = await res.json();
                    // data should be [{date: 'YYYY-MM-DD', count: N, branch_id, lectures: [...]}, ...]
                    let events = [];
                    if (data.success && Array.isArray(data.data)) {
                        data.data.forEach(ev => {
                            events.push({
                                title: ev.count + ' Lectures',
                                start: ev.date,
                                extendedProps: { branch_id: ev.branch_id, lectures: ev.lectures }
                            });
                        });
                    }
                    successCallback(events);
                } catch (e) { failureCallback(e); }
            },
            eventClick: function(info) {
                // Show modal with branch-wise lecture details
                let lectures = info.event.extendedProps.lectures || [];
                let html = `<table class='table table-bordered'><thead><tr><th>Branch</th><th>Faculty/Employee</th><th>Time</th></tr></thead><tbody>`;
                if (lectures.length) {
                    lectures.forEach(l => {
                        html += `<tr><td>${l.branch_name || l.branch_id}</td><td>${l.faculty_name || l.employee_name || ''}</td><td>${l.time || ''}</td></tr>`;
                    });
                } else {
                    html += `<tr><td colspan='3'>No lectures found</td></tr>`;
                }
                html += `</tbody></table>`;
                let modal = document.createElement('div');
                modal.className = 'modal fade';
                modal.innerHTML = `<div class='modal-dialog'><div class='modal-content'><div class='modal-header'><h5 class='modal-title'>Lectures on ${info.event.startStr}</h5><button type='button' class='btn-close' data-bs-dismiss='modal'></button></div><div class='modal-body'>${html}</div></div></div>`;
                document.body.appendChild(modal);
                var bsModal = new bootstrap.Modal(modal);
                bsModal.show();
                modal.addEventListener('hidden.bs.modal', function() { document.body.removeChild(modal); });
            }
        });
        calendar.render();
    }

    // Birthday reminders
    var remindersEl = document.getElementById('dashboard-reminders');
    if (remindersEl) {
        Promise.all([
            fetch('api/students.php?action=list').then(r => r.json()),
            fetch('api/employee.php?action=list').then(r => r.json())
        ]).then(([students, employees]) => {
            let today = new Date();
            let mmdd = (today.getMonth()+1).toString().padStart(2,'0') + '-' + today.getDate().toString().padStart(2,'0');
            let studentBirthdays = (students.data||[]).filter(s => (s.dob||'').substr(5,5) === mmdd);
            let employeeBirthdays = (employees.data||[]).filter(e => (e.dob||'').substr(5,5) === mmdd);
            let html = '';
            if (studentBirthdays.length) {
                html += `<div><strong>Student Birthdays Today:</strong><ul>`;
                studentBirthdays.forEach(s => { html += `<li>${s.name} (${s.dob})</li>`; });
                html += `</ul></div>`;
            }
            if (employeeBirthdays.length) {
                html += `<div><strong>Employee Birthdays Today:</strong><ul>`;
                employeeBirthdays.forEach(e => { html += `<li>${e.name} (${e.dob})</li>`; });
                html += `</ul></div>`;
            }
            if (!html) html = '<div class="text-muted">No birthdays today.</div>';
            remindersEl.innerHTML = html;
        });
    }
});
</script>
<?php include __DIR__ . '/partials/footer.php'; ?>
