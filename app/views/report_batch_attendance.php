<?php
if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/views/report_batch_attendance.php - Admin Batch-wise Attendance Report
require_once __DIR__ . '/../../config/db.php';

$userRole = $_SESSION['user']['role'] ?? '';
$userId = intval($_SESSION['user']['id'] ?? 0);

// Load all branches for super_admin, or only user's branch for branch_admin
$branches = [];
if ($userRole === 'super_admin') {
    $branchRes = mysqli_query($conn, "SELECT id, name FROM branches ORDER BY name ASC");
    while ($b = mysqli_fetch_assoc($branchRes)) {
        $branches[] = $b;
    }
} else if ($userRole === 'branch_admin') {
    $branchId = intval($_SESSION['branch_id'] ?? 0);
    if ($branchId > 0) {
        $stmt = mysqli_prepare($conn, "SELECT id, name FROM branches WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $branchId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($b = mysqli_fetch_assoc($res)) {
            $branches[] = $b;
        }
    }
}

// Load all batches across all branches (or filtered by branch if branch_admin)
$batches = [];
if ($userRole === 'super_admin') {
    $batchRes = mysqli_query($conn, "SELECT b.id, b.title, b.branch_id, br.name as branch_name FROM batches b LEFT JOIN branches br ON br.id = b.branch_id ORDER BY b.title ASC");
    while ($batch = mysqli_fetch_assoc($batchRes)) {
        $batches[] = $batch;
    }
} else if ($userRole === 'branch_admin') {
    $branchId = intval($_SESSION['branch_id'] ?? 0);
    if ($branchId > 0) {
        $stmt = mysqli_prepare($conn, "SELECT b.id, b.title, b.branch_id, br.name as branch_name FROM batches b LEFT JOIN branches br ON br.id = b.branch_id WHERE b.branch_id = ? ORDER BY b.title ASC");
        mysqli_stmt_bind_param($stmt, 'i', $branchId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($batch = mysqli_fetch_assoc($res)) {
            $batches[] = $batch;
        }
    }
}

// Load all students with batch info for report filtering
$reportStudents = [];
$seenPairs = [];

if ($userRole === 'super_admin') {
    $reportSql = "SELECT DISTINCT 
                  s.id as student_id, 
                  s.name as student_name, 
                  ba.batch_id, 
                  b.title as batch_title,
                  b.branch_id,
                  br.name as branch_name
                  FROM students s
                  JOIN batch_assignment_students bas ON bas.student_id = s.id
                  JOIN batch_assignments ba ON ba.id = bas.assignment_id
                  JOIN batches b ON b.id = ba.batch_id
                  LEFT JOIN branches br ON br.id = b.branch_id
                  WHERE (s.status = 1 OR s.status = 'active' OR s.status IS NOT NULL)
                  ORDER BY s.name ASC, b.title ASC";
} else {
    $branchId = intval($_SESSION['branch_id'] ?? 0);
    $reportSql = "SELECT DISTINCT 
                  s.id as student_id, 
                  s.name as student_name, 
                  ba.batch_id, 
                  b.title as batch_title,
                  b.branch_id,
                  br.name as branch_name
                  FROM students s
                  JOIN batch_assignment_students bas ON bas.student_id = s.id
                  JOIN batch_assignments ba ON ba.id = bas.assignment_id
                  JOIN batches b ON b.id = ba.batch_id
                  LEFT JOIN branches br ON br.id = b.branch_id
                  WHERE (s.status = 1 OR s.status = 'active' OR s.status IS NOT NULL)
                  AND b.branch_id = $branchId
                  ORDER BY s.name ASC, b.title ASC";
}

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
                    'batch_title' => $row['batch_title'] ?? 'Unknown',
                    'branch_id' => (int)($row['branch_id'] ?? 0),
                    'branch_name' => $row['branch_name'] ?? 'Unknown'
                ];
            }
        }
    }
}
?>

<style>
.breadcrumb {
    font-size: 0.875rem;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 1px solid #dee2e6;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.breadcrumb-item a {
    text-decoration: none;
    color: #495057;
    transition: color 0.2s;
}
.breadcrumb-item a:hover {
    color: #0d6efd;
}
.breadcrumb-item.active {
    color: #0d6efd;
    font-weight: 500;
}
.report-filters {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    padding: 1.25rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    border: 1px solid #e9ecef;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
.report-filters .form-label {
    font-size: 0.813rem;
    font-weight: 500;
    color: #495057;
    margin-bottom: 0.375rem;
}
.report-filters .form-select,
.report-filters .form-control {
    font-size: 0.875rem;
    padding: 0.5rem 0.75rem;
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
}
.report-table td {
    white-space: nowrap;
    padding: 0.5rem;
}
.legend-item {
    display: inline-flex;
    align-items: center;
    margin-right: 1rem;
    font-size: 0.813rem;
}
.legend-badge {
    display: inline-block;
    width: 22px;
    height: 22px;
    border-radius: 4px;
    text-align: center;
    line-height: 22px;
    margin-right: 0.375rem;
    font-weight: 600;
    font-size: 0.75rem;
}
</style>

<div class="container-fluid dashboard-container fade-in show">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-1">
        <ol class="breadcrumb mb-0 py-2 px-3 bg-light rounded d-flex align-items-center">
            <li class="breadcrumb-item"><a href="index.php?page=reports"><i class="fas fa-chart-bar me-1"></i>Reports</a></li>
            <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-calendar-check me-1"></i>Batch Attendance</li>
            <li class="ms-auto">
                <button class="btn btn-sm btn-success" onclick="exportAttendanceReport()">
                    <i class="fas fa-file-export me-1"></i>Export
                </button>
            </li>
        </ol>
    </nav>

    <!-- Filters -->
    <div class="report-filters">
        <div class="row g-3">
            <?php if ($userRole === 'super_admin' && count($branches) > 1): ?>
            <div class="col-md-2">
                <label for="branchFilter" class="form-label">Branch</label>
                <select id="branchFilter" class="form-select">
                    <option value="all">All Branches</option>
                    <?php foreach ($branches as $branch): ?>
                        <option value="<?= $branch['id'] ?>"><?= htmlspecialchars($branch['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div class="col-md-2">
                <label for="batchFilter" class="form-label">Batch</label>
                <select id="batchFilter" class="form-select">
                    <option value="all" data-branch-id="all">All Batches</option>
                    <?php foreach ($batches as $batch): ?>
                        <option value="<?= $batch['id'] ?>" 
                                data-branch-id="<?= $batch['branch_id'] ?? '' ?>"
                                data-branch-name="<?= htmlspecialchars($batch['branch_name'] ?? '') ?>">
                            <?= htmlspecialchars($batch['title']) ?>
                            <?php if ($userRole === 'super_admin'): ?>
                                (<?= htmlspecialchars($batch['branch_name'] ?? 'N/A') ?>)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label for="studentFilter" class="form-label">Student</label>
                <select id="studentFilter" class="form-select">
                    <option value="all">All Students</option>
                </select>
            </div>

            <div class="col-md-2">
                <label for="fromDate" class="form-label">From Date</label>
                <input type="date" id="fromDate" class="form-control" value="<?= date('Y-m-01') ?>">
            </div>

            <div class="col-md-2">
                <label for="toDate" class="form-label">To Date</label>
                <input type="date" id="toDate" class="form-control" value="<?= date('Y-m-d') ?>">
            </div>

            <div class="col-md-2">
                <label for="statusFilter" class="form-label">Status</label>
                <select id="statusFilter" class="form-select">
                    <option value="all">All Status</option>
                    <option value="present">Present Only</option>
                    <option value="absent">Absent Only</option>
                    <option value="leave">Leave Only</option>
                </select>
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-primary w-100" onclick="loadAttendanceReport()">
                    <i class="fas fa-search me-2"></i>Generate Report
                </button>
            </div>
        </div>
    </div>

    <!-- Legend -->
    <div class="mb-3">
        <div class="legend-item">
            <span class="legend-badge bg-success text-white">P</span> Present
        </div>
        <div class="legend-item">
            <span class="legend-badge bg-danger text-white">A</span> Absent
        </div>
        <div class="legend-item">
            <span class="legend-badge bg-warning text-dark">L</span> Leave
        </div>
        <div class="legend-item">
            <span class="legend-badge bg-secondary text-white">-</span> No Data
        </div>
    </div>

    <!-- Report Table -->
    <div class="card">
        <div class="card-body">
            <div class="report-table-container">
                <table class="table table-bordered table-hover report-table">
                    <thead id="reportHead">
                        <tr><th class="text-center">Loading...</th></tr>
                    </thead>
                    <tbody id="reportBody">
                        <tr><td class="text-center text-muted">Click "Generate Report" to load data</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize student data from PHP
window.reportStudentData = <?= json_encode($reportStudents ?? []) ?>;
window.userRole = '<?= $userRole ?>';

// console.log('Report Students Data:', window.reportStudentData);
// console.log('User Role:', window.userRole);

// Update student filter based on batch and branch selection
function updateStudentFilter() {
    const batchId = document.getElementById('batchFilter').value;
    const branchId = document.getElementById('branchFilter')?.value || 'all';
    const studentFilter = document.getElementById('studentFilter');
    
    const students = window.reportStudentData || [];
    
    // console.log('Batch ID:', batchId, 'Branch ID:', branchId);
    // console.log('Total students in data:', students.length);
    
    // Clear current options
    studentFilter.innerHTML = '<option value="all">All Students</option>';
    
    // Filter students based on selection
    let filteredStudents = students;
    
    // First filter by branch (if not "all" and super_admin)
    if (branchId !== 'all' && window.userRole === 'super_admin') {
        filteredStudents = filteredStudents.filter(s => s.branch_id == branchId);
        // console.log('After branch filter:', filteredStudents.length, 'Branch ID:', branchId);
    }
    
    // Then filter by batch (if not "all")
    if (batchId !== 'all') {
        filteredStudents = filteredStudents.filter(s => s.batch_id == batchId);
        // console.log('After batch filter:', filteredStudents.length);
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
    
    // console.log('Filtered unique students:', uniqueStudents.length);
    
    // Populate dropdown
    uniqueStudents.forEach(student => {
        const option = document.createElement('option');
        option.value = student.student_id;
        option.textContent = student.student_name;
        studentFilter.appendChild(option);
    });
}

// Initialize filters when page loads
document.addEventListener('DOMContentLoaded', function() {
    updateStudentFilter();
    
    // Attach change listeners
    document.getElementById('batchFilter').addEventListener('change', updateStudentFilter);
    if (document.getElementById('branchFilter')) {
        document.getElementById('branchFilter').addEventListener('change', function() {
            // First filter batches by branch
            filterBatchesByBranch();
            // Then update students based on new batch list
            updateStudentFilter();
        });
    }
});

// Filter batches based on branch selection (for super_admin)
function filterBatchesByBranch() {
    if (window.userRole !== 'super_admin') return;
    
    const branchFilter = document.getElementById('branchFilter');
    const batchFilter = document.getElementById('batchFilter');
    const selectedBranchId = branchFilter.value;
    
    // console.log('Filtering batches for branch ID:', selectedBranchId);
    
    const allOptions = Array.from(batchFilter.options);
    let visibleBatches = 0;
    
    allOptions.forEach(option => {
        if (option.value === 'all') {
            option.style.display = '';
            return;
        }
        
        if (selectedBranchId === 'all') {
            // Show all batches when "All Branches" is selected
            option.style.display = '';
            visibleBatches++;
        } else {
            // Show only batches from selected branch using branch_id
            const batchBranchId = option.getAttribute('data-branch-id');
            
            // console.log('Batch:', option.textContent, 'Branch ID:', batchBranchId, 'Selected:', selectedBranchId);
            
            if (batchBranchId == selectedBranchId) {
                option.style.display = '';
                visibleBatches++;
            } else {
                option.style.display = 'none';
            }
        }
    });
    
    // console.log('Visible batches:', visibleBatches);
    
    // Reset batch selection to "All Batches" if current selection is hidden
    if (batchFilter.selectedOptions[0]?.style.display === 'none') {
        batchFilter.value = 'all';
    }
}

function loadAttendanceReport() {
    const batchId = document.getElementById('batchFilter').value;
    const studentId = document.getElementById('studentFilter').value;
    const fromDate = document.getElementById('fromDate').value;
    const toDate = document.getElementById('toDate').value;
    const statusFilter = document.getElementById('statusFilter').value;
    
    if (!fromDate || !toDate) {
        alert('Please select date range');
        return;
    }
    
    const reportHead = document.getElementById('reportHead');
    const reportBody = document.getElementById('reportBody');
    
    reportHead.innerHTML = '<tr><th class="text-center">Loading...</th></tr>';
    reportBody.innerHTML = '<tr><td class="text-center">Please wait...</td></tr>';
    
    fetch('../api/attendance.php?action=report', {
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
    .then(res => res.json())
    .then(data => {
        if (data.success && data.records) {
            renderReport(data.records, fromDate, toDate, statusFilter);
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

function renderReport(records, fromDate, toDate, statusFilter) {
    const reportHead = document.getElementById('reportHead');
    const reportBody = document.getElementById('reportBody');
    
    // Generate date range
    const start = new Date(fromDate);
    const end = new Date(toDate);
    const dates = [];
    const dateHeaders = [];
    
    for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
        const dateStr = d.toISOString().split('T')[0];
        dates.push(dateStr);
        
        const day = d.getDate();
        const month = d.toLocaleDateString('en-US', { month: 'short' });
        const dayName = d.toLocaleDateString('en-US', { weekday: 'short' });
        const isSunday = d.getDay() === 0;
        
        dateHeaders.push({ day, month, dayName, isSunday });
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
            let cellClass = isSunday ? 'bg-secondary-subtle' : '';
            
            if (attendance) {
                if (statusFilter === 'all' || statusFilter === attendance) {
                    switch (attendance) {
                        case 'present':
                            cellContent = '<span class="badge bg-success">P</span>';
                            break;
                        case 'absent':
                            cellContent = '<span class="badge bg-danger">A</span>';
                            break;
                        case 'leave':
                            cellContent = '<span class="badge bg-warning text-dark">L</span>';
                            break;
                        default:
                            cellContent = '-';
                    }
                } else {
                    cellContent = '-';
                    cellClass += ' text-muted';
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
}

function exportAttendanceReport() {
    alert('Export functionality will be implemented soon!');
}
</script>
