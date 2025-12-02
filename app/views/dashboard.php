<?php

use CampusLite\Controllers\{BatchController, BranchController, CourseController, FacultyController, StudentController, UserController};

if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/views/dashboard.php

$branches = BranchController::getAll();
$users = UserController::getAll();
$courses = CourseController::getAll();

// Precompute branch-wise counts (students and faculty)
$branchStats = [];
foreach ($branches as $b) {
    $bid = $b['id'];
    $students = StudentController::getAll($bid);
    $faculties = FacultyController::getAll($bid);
    $batches = BatchController::getAll($bid);
    $branchStats[$bid] = [
        'branch' => $b,
        'students' => is_array($students) ? count($students) : 0,
        'faculty' => is_array($faculties) ? count($faculties) : 0,
        'batches' => is_array($batches) ? count($batches) : 0,
    ];
}
?>

<div class="dashboard-container" style="width:100%;max-width:none;">
        <!-- Dashboard header and controls removed for minimal look -->

        <div class="row summary-row g-3 g-md-2">
            <div class="col-6 col-sm-6 col-md-3">
                <div class="card text-bg-primary summary-card">
                    <div class="card-body position-relative">
                        <i class="fas fa-code-branch card-icon"></i>
                        <h6 class="card-title">Branches</h6>
                        <p class="card-text"><?= number_format(count($branches)) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-sm-6 col-md-3">
                <div class="card text-bg-success summary-card">
                    <div class="card-body position-relative">
                        <i class="fas fa-users card-icon"></i>
                        <h6 class="card-title">Users</h6>
                        <p class="card-text"><?= number_format(count($users)) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-sm-6 col-md-3">
                <div class="card text-bg-warning summary-card">
                    <div class="card-body position-relative">
                        <i class="fas fa-book card-icon"></i>
                        <h6 class="card-title">Courses</h6>
                        <p class="card-text"><?= number_format(count($courses)) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-sm-6 col-md-3">
                <div class="card text-bg-danger summary-card">
                    <div class="card-body position-relative">
                        <i class="fas fa-user-graduate card-icon"></i>
                        <h6 class="card-title">Students</h6>
                        <p class="card-text"><?= number_format(array_sum(array_map(fn($bs) => $bs['students'], $branchStats))) ?></p>
                    </div>
                </div>
            </div>
        </div>
        <!-- Branch cards (single horizontal row) -->
        <div class="row mt-3 mt-md-4">
            <div class="col-12">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 mb-md-2 gap-2">
                    <h5 class="mb-0 section-title">Branches Overview</h5>
                    <div>
                        <button id="branchToggleBtn" class="btn btn-sm btn-outline-secondary" title="Toggle branch view"><i class="fas fa-expand-arrows-alt"></i> <span class="d-none d-sm-inline">Toggle View</span></button>
                    </div>
                </div>
                <div id="branchListContainer">
                    <div class="branch-list" style="display:flex;gap:0.75rem;overflow-x:auto;padding-bottom:0.5rem;padding-top:0.5rem;-webkit-overflow-scrolling:touch;">
                        <?php foreach ($branchStats as $bs): $b = $bs['branch']; ?>
                            <div class="card branch-card" style="min-width:220px;flex:0 0 auto;">
                                <div class="card-body small p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 branch-name" style="font-size:0.95rem;font-weight:600"><?= htmlspecialchars($b['name'] ?? 'Branch') ?></h6>
                                            <small class="text-muted d-block branch-address" style="font-size:0.75rem;line-height:1.3"><?= htmlspecialchars($b['address'] ?? '') ?></small>
                                        </div>
                                        <div class="text-end ms-2">
                                            <div class="branch-student-count" style="font-weight:700;font-size:1.1rem;color:var(--primary-color)"><?= intval($bs['students']) ?></div>
                                            <small class="text-muted d-block" style="font-size:0.7rem">Students</small>
                                        </div>
                                    </div>
                                    <div class="mt-2 d-flex gap-2 justify-content-between branch-actions">
                                        <button class="btn btn-sm btn-outline-primary branch-students flex-fill" data-branch="<?= intval($b['id']) ?>" data-bs-toggle="tooltip" title="View students in <?= htmlspecialchars($b['name'] ?? '') ?>"><i class="fas fa-user-graduate"></i> <span class="d-none d-lg-inline">Students</span></button>
                                        <button class="btn btn-sm btn-outline-secondary branch-faculty flex-fill" data-branch="<?= intval($b['id']) ?>" data-bs-toggle="tooltip" title="View faculty for <?= htmlspecialchars($b['name'] ?? '') ?>"><i class="fas fa-chalkboard-teacher"></i> <span class="d-none d-lg-inline">Faculty</span></button>
                                        <button class="btn btn-sm btn-outline-dark branch-batches flex-fill" data-branch="<?= intval($b['id']) ?>" data-bs-toggle="tooltip" title="View batches for <?= htmlspecialchars($b['name'] ?? '') ?>"> <i class="fas fa-layer-group"></i> <span style="font-size:0.85rem;margin-left:2px"><?= intval($bs['batches'] ?? 0) ?></span></button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- Calendar and Reminders side-by-side -->
        <div class="row mt-3 mt-md-4">
            <div class="col-12 col-lg-8 mb-3 mb-lg-0">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title mb-3"><i class="fas fa-calendar-alt me-2"></i>Calendar</h5>
                        <div id="dashboard-calendar" class="calendar-wrapper"></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title mb-3"><i class="fas fa-bell me-2"></i>Reminders</h5>
                        <div id="dashboard-reminders" class="reminders-wrapper"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Courses full-width -->
        <div class="row mt-3 mt-md-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0"><i class="fas fa-book me-2"></i>Courses <span class="badge bg-primary"><?= count($courses) ?></span></h5>
                            <?php if (count($courses) > 12): ?>
                                <a href="index.php?page=courses" class="btn btn-sm btn-action d-none d-md-inline-flex"><i class="fas fa-arrow-right me-1"></i>View All</a>
                            <?php endif; ?>
                        </div>
                        <?php if (empty($courses)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No courses found</p>
                            </div>
                        <?php else: ?>
                            <ul class="list-unstyled row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3 mb-0">
                                <?php foreach (array_slice($courses, 0, 12) as $c): ?>
                                    <?php
                                        $capacity = intval($c['total_capacity'] ?? 0);
                                        $enrolled = intval($c['enrolled_count'] ?? 0);
                                        $seats_left = max(0, $capacity - $enrolled);
                                    ?>
                                    <li class="col">
                                        <div class="p-3 border rounded h-100 course-card-item">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <strong class="course-title"><?= htmlspecialchars($c['title'] ?? $c['name'] ?? '') ?></strong>
                                                <span class="badge bg-secondary ms-2"><?= htmlspecialchars($c['duration_months'] ?? '') ?> mo</span>
                                            </div>
                                            <div class="text-muted small mb-2">
                                                <i class="fas fa-code-branch me-1"></i>Branch: <?= intval($c['branch_id'] ?: 0) ?>
                                            </div>
                                            <div class="mt-2 pt-2 border-top d-flex justify-content-between align-items-center">
                                                <div class="small text-muted">
                                                    <i class="fas fa-money-bill-wave me-1"></i><?= htmlspecialchars($c['total_fee'] ?? '0.00') ?>
                                                </div>
                                                <div class="small">
                                                    <strong class="text-primary">Seats:</strong> <span class="badge bg-info"><?= $seats_left ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php if (count($courses) > 12): ?>
                                <div class="mt-3 text-center d-md-none">
                                    <a href="index.php?page=courses" class="btn btn-sm btn-action w-100"><i class="fas fa-arrow-right me-1"></i>View All Courses</a>
                                </div>
                                <div class="mt-3 text-end d-none d-md-block">
                                    <a href="index.php?page=courses" class="btn btn-sm btn-action"><i class="fas fa-arrow-right me-1"></i>View All Courses</a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
<script>
// Render reminders (birthdays) into the provided element. Exposed globally so other init paths can call it.
window.renderDashboardReminders = async function(remindersEl) {
    if (!remindersEl) return;
    remindersEl.innerHTML = '<div class="text-muted">Loading reminders...</div>';
    try {
        const [studentsResp, employeesResp] = await Promise.all([
            fetchJson('api/students.php?action=list'),
            fetchJson('api/employee.php?action=list')
        ]);
        console.debug('reminders: studentsResp', studentsResp, 'employeesResp', employeesResp);
        const students = (studentsResp && studentsResp.success && Array.isArray(studentsResp.data)) ? studentsResp.data : (Array.isArray(studentsResp) ? studentsResp : (studentsResp.data && Array.isArray(studentsResp.data) ? studentsResp.data : []));
        const employees = (employeesResp && employeesResp.success && Array.isArray(employeesResp.data)) ? employeesResp.data : (Array.isArray(employeesResp) ? employeesResp : (employeesResp.data && Array.isArray(employeesResp.data) ? employeesResp.data : []));
        let today = new Date();
        let mmdd = (today.getMonth()+1).toString().padStart(2,'0') + '-' + today.getDate().toString().padStart(2,'0');
        let studentBirthdays = (students||[]).filter(s => (s.dob||'').substr(5,5) === mmdd);
        let employeeBirthdays = (employees||[]).filter(e => (e.dob||'').substr(5,5) === mmdd);
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
    } catch (err) {
        console.error('Failed to load reminders', err);
        remindersEl.innerHTML = '<div class="text-muted">Could not load reminders.</div>';
    }
};

function initDashboard() {
    // Open students modal
    document.querySelectorAll('.branch-students').forEach(function(el) {
        el.addEventListener('click', function() {
            const branchId = el.getAttribute('data-branch');
            fetchJson(`api/students.php?action=list&branch_id=${branchId}`)
                .then(data => {
                    console.debug('branchStudents response', branchId, data);
                    let html = `<table class='table table-striped table-bordered' id='branchStudentsTable'><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Status</th></tr></thead><tbody>`;
                    const rows = (data && data.success && data.data) ? (Array.isArray(data.data) ? data.data : Object.values(data.data)) : [];
                    if (rows.length) {
                        rows.forEach(s => {
                            html += `<tr><td>${s.id}</td><td>${s.name}</td><td>${s.email}</td><td>${s.mobile || s.phone || ''}</td><td>${s.status}</td></tr>`;
                        });
                    } else {
                        html += `<tr><td colspan='5'>No students found</td></tr>`;
                    }
                    html += `</tbody></table>`;
                    const container = document.getElementById('branchStudentsTableContainer');
                    if (container) container.innerHTML = html; else console.warn('branchStudentsTableContainer not found');
                    const modalEl = document.getElementById('branchStudentsModal');
                    if (modalEl) {
                        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                        // initialize DataTable with JS data after modal shown to avoid DOM races
                        const onShown = function() {
                            try {
                                if (window.$ && window.$.fn.dataTable) {
                                    if ($.fn.dataTable.isDataTable('#branchStudentsTable')) {
                                        $('#branchStudentsTable').DataTable().clear().destroy();
                                    }
                                    $('#branchStudentsTable').DataTable({
                                        data: rows,
                                        columns: [
                                            { data: 'id' },
                                            { data: 'name' },
                                            { data: 'email' },
                                            { data: function(r){ return r.mobile || r.phone || ''; } },
                                            { data: 'status' }
                                        ],
                                        pageLength: 10,
                                        lengthMenu: [10,25,50,100],
                                        searching: true,
                                        responsive: {
                                            details: {
                                                type: 'column',
                                                target: 'tr'
                                            }
                                        },
                                        columnDefs: [
                                            {responsivePriority: 1, targets: 0},
                                            {responsivePriority: 2, targets: -1}
                                        ]
                                    });
                                    setTimeout(function(){ try { $('#branchStudentsTable').DataTable().draw(false); } catch(e){} }, 300);
                                }
                            } catch(e) { console.warn('DataTable init failed for branchStudentsTable', e); }
                            modalEl.removeEventListener('shown.bs.modal', onShown);
                        };
                        modalEl.addEventListener('shown.bs.modal', onShown);
                        modal.show();
                    }
                }).catch(err => {
                    console.error('Failed to load branch students', err);
                    const container = document.getElementById('branchStudentsTableContainer');
                    if (container) container.innerHTML = `<div class="text-danger">Could not load students: ${err.message || 'Error'}</div>`;
                });
        });
    });

    // Open faculty modal
    document.querySelectorAll('.branch-faculty').forEach(function(el) {
        el.addEventListener('click', function() {
            const branchId = el.getAttribute('data-branch');
            fetchJson(`api/faculty.php?action=list&branch_id=${branchId}`)
                .then(data => {
                    console.debug('branchFaculty response', branchId, data);
                    let html = `<table class='table table-striped table-bordered' id='branchFacultyTable'><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Status</th></tr></thead><tbody>`;
                    const rows = (data && data.success && data.data) ? (Array.isArray(data.data) ? data.data : Object.values(data.data)) : [];
                    if (rows.length) {
                        rows.forEach(f => {
                            html += `<tr><td>${f.id}</td><td>${f.name}</td><td>${f.email}</td><td>${f.mobile || ''}</td><td>${f.status}</td></tr>`;
                        });
                    } else {
                        html += `<tr><td colspan='5'>No faculty found</td></tr>`;
                    }
                    html += `</tbody></table>`;
                    const container = document.getElementById('branchFacultyTableContainer');
                    if (container) container.innerHTML = html; else console.warn('branchFacultyTableContainer not found');
                    const modalEl = document.getElementById('branchFacultyModal');
                    if (modalEl) {
                        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                        const onShown = function() {
                            try {
                                    if (window.$ && window.$.fn.dataTable) {
                                        if ($.fn.dataTable.isDataTable('#branchFacultyTable')) {
                                            $('#branchFacultyTable').DataTable().clear().destroy();
                                        }
                                        $('#branchFacultyTable').DataTable({
                                            data: rows,
                                            columns: [
                                                { data: 'id' },
                                                { data: 'name' },
                                                { data: 'email' },
                                                { data: function(r){ return r.mobile || ''; } },
                                                { data: 'status' }
                                            ],
                                            pageLength: 10,
                                            lengthMenu: [10,25,50,100],
                                            searching: true,
                                            responsive: {
                                                details: {
                                                    type: 'column',
                                                    target: 'tr'
                                                }
                                            },
                                            columnDefs: [
                                                {responsivePriority: 1, targets: 0},
                                                {responsivePriority: 2, targets: -1}
                                            ]
                                        });
                                        setTimeout(function(){ try { $('#branchFacultyTable').DataTable().draw(false); } catch(e){} }, 300);
                                    }
                            } catch(e) { console.warn('DataTable init failed for branchFacultyTable', e); }
                            modalEl.removeEventListener('shown.bs.modal', onShown);
                        };
                        modalEl.addEventListener('shown.bs.modal', onShown);
                        modal.show();
                    }
                }).catch(err => {
                    console.error('Failed to load branch faculty', err);
                    const container = document.getElementById('branchFacultyTableContainer');
                    if (container) container.innerHTML = `<div class="text-danger">Could not load faculty: ${err.message || 'Error'}</div>`;
                });
        });
    });

    // Open batches modal
    document.querySelectorAll('.branch-batches').forEach(function(el) {
        el.addEventListener('click', function() {
            const branchId = el.getAttribute('data-branch');
            fetchJson(`api/batches.php?action=list&branch_id=${branchId}`)
                .then(data => {
                    console.debug('branchBatches response', branchId, data);
                    let html = `<table class='table table-striped table-bordered' id='branchBatchesTable'><thead><tr><th>ID</th><th>Title</th><th>Course</th><th>Start</th><th>End</th><th>Capacity</th><th>Status</th></tr></thead><tbody>`;
                    const rows = (data && data.success && data.data) ? (Array.isArray(data.data) ? data.data : Object.values(data.data)) : [];
                    if (rows.length) {
                        rows.forEach(b => {
                            html += `<tr><td>${b.id}</td><td>${b.title}</td><td>${b.course_title || ''}</td><td>${b.start_date || ''}</td><td>${b.end_date || ''}</td><td>${b.capacity || ''}</td><td>${b.status || ''}</td></tr>`;
                        });
                    } else {
                        html += `<tr><td colspan='7'>No batches found</td></tr>`;
                    }
                    html += `</tbody></table>`;
                    const container = document.getElementById('branchBatchesTableContainer');
                    if (container) container.innerHTML = html; else console.warn('branchBatchesTableContainer not found');
                    const modalEl = document.getElementById('branchBatchesModal');
                    if (modalEl) {
                        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                        const onShown = function() {
                            try {
                                if (window.$ && window.$.fn.dataTable) {
                                    if ($.fn.dataTable.isDataTable('#branchBatchesTable')) {
                                        $('#branchBatchesTable').DataTable().clear().destroy();
                                    }
                                    $('#branchBatchesTable').DataTable({
                                        data: rows,
                                        columns: [
                                            { data: 'id' },
                                            { data: 'title' },
                                            { data: 'course_title', defaultContent: '' },
                                            { data: 'start_date', defaultContent: '' },
                                            { data: 'end_date', defaultContent: '' },
                                            { data: 'capacity', defaultContent: '' },
                                            { data: 'status', defaultContent: '' }
                                        ],
                                        pageLength: 10,
                                        lengthMenu: [10,25,50,100],
                                        searching: true,
                                        responsive: {
                                            details: {
                                                type: 'column',
                                                target: 'tr'
                                            }
                                        },
                                        columnDefs: [
                                            {responsivePriority: 1, targets: 0},
                                            {responsivePriority: 2, targets: -1}
                                        ]
                                    });
                                    setTimeout(function(){ try { $('#branchBatchesTable').DataTable().draw(false); } catch(e){} }, 300);
                                }
                            } catch(e) { console.warn('DataTable init failed for branchBatchesTable', e); }
                            modalEl.removeEventListener('shown.bs.modal', onShown);
                        };
                        modalEl.addEventListener('shown.bs.modal', onShown);
                        modal.show();
                    }
                }).catch(err => {
                    console.error('Failed to load branch batches', err);
                    const container = document.getElementById('branchBatchesTableContainer');
                    if (container) container.innerHTML = `<div class="text-danger">Could not load batches: ${err.message || 'Error'}</div>`;
                });
        });
    });

    // Birthday reminders
    var remindersEl = document.getElementById('dashboard-reminders');
    if (remindersEl) {
        // use centralized renderer that normalizes API responses
        window.renderDashboardReminders(remindersEl);
    }

    // Calendar initialization
    var calendarEl = document.getElementById('dashboard-calendar');
    if (calendarEl) {
        if (typeof FullCalendar === 'undefined') {
            calendarEl.innerHTML = '<div class="text-muted">Calendar library not loaded. Install FullCalendar CSS/JS or check console.</div>';
            console.error('FullCalendar is not defined. Check that the script loaded correctly.');
        } else {
            // remove any existing calendar content
            calendarEl.innerHTML = '';
                var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: window.innerWidth < 768 ? 'dayGridMonth' : 'dayGridMonth',
                height: window.innerWidth < 768 ? 450 : (window.innerWidth < 992 ? 500 : 600),
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: window.innerWidth < 768 ? 'dayGridMonth' : 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                expandRows: true,
                aspectRatio: window.innerWidth < 768 ? 1.2 : 1.5,
                events: async function(fetchInfo, successCallback, failureCallback) {
                    try {
                        let data = await fetchJson('api/attendance.php?action=calendar');
                        let events = [];
                        if (data.success && Array.isArray(data.data)) {
                            data.data.forEach(ev => {
                                events.push({ title: ev.count + ' Lectures', start: ev.date, extendedProps: { branch_id: ev.branch_id, lectures: ev.lectures } });
                            });
                        }
                        successCallback(events);
                    } catch (e) { failureCallback(e); }
                },
                eventClick: function(info) {
                    let lectures = info.event.extendedProps.lectures || [];
                    let html = `<table class='table table-bordered'><thead><tr><th>Branch</th><th>Faculty/Employee</th><th>Time</th></tr></thead><tbody>`;
                    if (lectures.length) {
                        lectures.forEach(l => { html += `<tr><td>${l.branch_name || l.branch_id}</td><td>${l.faculty_name || l.employee_name || ''}</td><td>${l.time || ''}</td></tr>`; });
                    } else { html += `<tr><td colspan='3'>No lectures found</td></tr>`; }
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
            // expose calendar so resize/updateSize can be called after AJAX navigation or window resize
            try { window._dashboardCalendar = calendar; } catch(e){}
            if (!window._dashboardResizeAttached) {
                window.addEventListener('resize', function() { 
                    if (window._dashboardCalendar && typeof window._dashboardCalendar.updateSize === 'function') {
                        window._dashboardCalendar.updateSize();
                        // Adjust calendar height on resize
                        var calendarEl = document.getElementById('dashboard-calendar');
                        if (calendarEl && window._dashboardCalendar) {
                            var newHeight = window.innerWidth < 768 ? 450 : (window.innerWidth < 992 ? 500 : 600);
                            window._dashboardCalendar.setOption('height', newHeight);
                        }
                    }
                });
                window._dashboardResizeAttached = true;
            }
        }
    }
}

// expose initializer so AJAX loader can call it after replacing content
window.initPage = window.initDashboard = initDashboard;

// call once on initial load (but only after shared helpers are available)
(function ensureInit() {
    if (typeof fetchJson === 'function') {
        initDashboard();
        return;
    }
    // poll for a short time in case common.js loads after this script
    var attempts = 0;
    var iv = setInterval(function() {
        attempts++;
        if (typeof fetchJson === 'function') {
            clearInterval(iv);
            initDashboard();
        } else if (attempts > 50) { // ~5s timeout
            clearInterval(iv);
            console.error('initDashboard: fetchJson not found after waiting; page init skipped');
        }
    }, 100);
})();
(function branchListToggleInit(){
    // restore branch list mode from localStorage and wire toggle button
    function applyMode(mode) {
        const list = document.querySelector('.branch-list');
        if (!list) return;
        list.classList.remove('grid','compact');
        if (mode === 'grid') list.classList.add('grid');
        else list.classList.add('compact');
    }

    function setup() {
        try {
            const saved = window.localStorage.getItem('branchListMode') || 'compact';
            applyMode(saved);
            const btn = document.getElementById('branchToggleBtn');
            if (btn) btn.addEventListener('click', function(){
                const cur = window.localStorage.getItem('branchListMode') || 'compact';
                const next = cur === 'compact' ? 'grid' : 'compact';
                window.localStorage.setItem('branchListMode', next);
                applyMode(next);
            });
        } catch(e) { console.error('branchListToggleInit failed', e); }
    }

    // If the page fragment was loaded after DOMContentLoaded (AJAX navigation), run setup immediately.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setup);
    } else {
        // run now so toggle works for AJAX-inserted fragments
        setup();
    }
})();
</script>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<!-- FullCalendar CSS was removed because the CDN path used returned 404. If you need FullCalendar styles, download the correct CSS file locally or replace with a working CDN path. -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Calendar setup
    var calendarEl = document.getElementById('dashboard-calendar');
    if (calendarEl) {
        if (typeof FullCalendar === 'undefined') {
            calendarEl.innerHTML = '<div class="text-muted">Calendar library not loaded. Install FullCalendar CSS/JS or check console.</div>';
            console.error('FullCalendar is not defined. Check that the script loaded correctly.');
        } else {
            var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: window.innerWidth < 768 ? 'dayGridMonth' : 'dayGridMonth',
            height: window.innerWidth < 768 ? 450 : (window.innerWidth < 992 ? 500 : 600),
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: window.innerWidth < 768 ? 'dayGridMonth' : 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            expandRows: true,
            aspectRatio: window.innerWidth < 768 ? 1.2 : 1.5,
            events: async function(fetchInfo, successCallback, failureCallback) {
                // Fetch lecture counts per day (replace with your API)
                try {
                    let data = await fetchJson('api/attendance.php?action=calendar');
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
            // Expose calendar for resize handling
            try { window._dashboardCalendar = calendar; } catch(e){}
            
            // Handle window resize for calendar
            if (!window._dashboardResizeAttached) {
                window.addEventListener('resize', function() { 
                    if (window._dashboardCalendar && typeof window._dashboardCalendar.updateSize === 'function') {
                        window._dashboardCalendar.updateSize();
                        var newHeight = window.innerWidth < 768 ? 450 : (window.innerWidth < 992 ? 500 : 600);
                        window._dashboardCalendar.setOption('height', newHeight);
                    }
                });
                window._dashboardResizeAttached = true;
            }
        }
    }

    // Birthday reminders (centralized renderer)
    var remindersEl = document.getElementById('dashboard-reminders');
    if (remindersEl) {
        window.renderDashboardReminders(remindersEl);
    }
});
</script>

