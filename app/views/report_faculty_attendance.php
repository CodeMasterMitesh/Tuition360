<?php
if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/views/report_faculty_attendance.php - Faculty Attendance Report
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

// Load all faculty/employees for filtering with branch_id
$faculty = [];
if ($userRole === 'super_admin') {
    $facRes = mysqli_query($conn, "SELECT id, name, role, branch_id FROM users WHERE role IN ('faculty', 'employee') ORDER BY name ASC");
    while ($f = mysqli_fetch_assoc($facRes)) {
        $faculty[] = $f;
    }
} else if ($userRole === 'branch_admin') {
    $branchId = intval($_SESSION['branch_id'] ?? 0);
    if ($branchId > 0) {
        $stmt = mysqli_prepare($conn, "SELECT id, name, role, branch_id FROM users WHERE role IN ('faculty', 'employee') AND branch_id = ? ORDER BY name ASC");
        mysqli_stmt_bind_param($stmt, 'i', $branchId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($f = mysqli_fetch_assoc($res)) {
            $faculty[] = $f;
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
    background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
    border-bottom: 2px solid #dee2e6;
}
.report-table td {
    white-space: nowrap;
    padding: 0.5rem;
    border-bottom: 1px solid #dee2e6;
}
.report-table tbody tr:hover {
    background: #f8f9fa;
}
.status-present {
    background: #d4edda;
    color: #155724;
    padding: 0.25rem 0.5rem;
    border-radius: 3px;
    font-weight: 500;
}
.status-absent {
    background: #f8d7da;
    color: #721c24;
    padding: 0.25rem 0.5rem;
    border-radius: 3px;
    font-weight: 500;
}
.status-leave {
    background: #fff3cd;
    color: #856404;
    padding: 0.25rem 0.5rem;
    border-radius: 3px;
    font-weight: 500;
}
.holiday-badge {
    background: #cfe2ff;
    color: #084298;
    padding: 0.25rem 0.5rem;
    border-radius: 3px;
    font-weight: 500;
}
.time-cell {
    font-family: 'Courier New', monospace;
    font-size: 0.75rem;
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
.report-table {
    border-collapse: collapse;
    font-size: 0.875rem;
}
.report-table td {
    border: 1px solid #e9ecef;
    padding: 0.5rem !important;
    vertical-align: middle;
    text-align: center;
}
.report-table thead th {
    border: 1px solid #dee2e6;
    padding: 0.75rem 0.5rem !important;
    font-weight: 600;
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
    margin: 2px auto;
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
.batch-info {
    font-size: 0.75rem;
    color: #666;
    margin-top: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.time-info {
    font-size: 0.7rem;
    color: #888;
    font-family: monospace;
    margin-top: 1px;
}
</style>

<div class="container-fluid dashboard-container fade-in show">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-1">
        <ol class="breadcrumb mb-0 py-2 px-3 bg-light rounded d-flex align-items-center">
            <li class="breadcrumb-item"><a href="index.php?page=reports"><i class="fas fa-chart-bar me-1"></i>Reports</a></li>
            <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-user-tie me-1"></i>Faculty Attendance</li>
            <li class="ms-auto">
                <button class="btn btn-sm btn-success" onclick="exportFacultyAttendanceReport()">
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
                <label for="facultyFilter" class="form-label">Faculty/Employee</label>
                <select id="facultyFilter" class="form-select">
                    <option value="all">All Faculty</option>
                    <?php foreach ($faculty as $fac): ?>
                        <option value="<?= $fac['id'] ?>" data-branch-id="<?= $fac['branch_id'] ?? '' ?>"><?= htmlspecialchars($fac['name']) ?></option>
                    <?php endforeach; ?>
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

            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100" onclick="loadFacultyAttendanceReport()">
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
            <span class="legend-badge bg-info text-white">H</span> Holiday (Sunday)
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
// Initialize branch filter on page load
document.addEventListener('DOMContentLoaded', function() {
    const branchFilter = document.getElementById('branchFilter');
    if (branchFilter) {
        branchFilter.addEventListener('change', filterFacultyByBranch);
    }
});

function filterFacultyByBranch() {
    const branchFilter = document.getElementById('branchFilter');
    const facultyFilter = document.getElementById('facultyFilter');
    
    if (!branchFilter || !facultyFilter) return;
    
    const selectedBranchId = branchFilter.value;
    const options = facultyFilter.querySelectorAll('option');
    let visibleCount = 0;
    
    options.forEach(option => {
        if (option.value === 'all') {
            option.style.display = '';
            return;
        }
        
        const facultyBranchId = option.getAttribute('data-branch-id');
        
        if (selectedBranchId === 'all') {
            option.style.display = '';
            visibleCount++;
        } else {
            if (facultyBranchId == selectedBranchId) {
                option.style.display = '';
                visibleCount++;
            } else {
                option.style.display = 'none';
            }
        }
    });
    
    // Reset to "All Faculty" if current selection is hidden
    if (facultyFilter.selectedOptions[0]?.style.display === 'none') {
        facultyFilter.value = 'all';
    }
}

function loadFacultyAttendanceReport() {
    const branchId = document.getElementById('branchFilter')?.value || 'all';
    const facultyId = document.getElementById('facultyFilter')?.value || 'all';
    const fromDate = document.getElementById('fromDate').value;
    const toDate = document.getElementById('toDate').value;
    
    if (!fromDate || !toDate) {
        alert('Please select date range');
        return;
    }
    
    const reportHead = document.getElementById('reportHead');
    const reportBody = document.getElementById('reportBody');
    
    reportHead.innerHTML = '<tr><th class="text-center">Loading...</th></tr>';
    reportBody.innerHTML = '<tr><td class="text-center">Please wait...</td></tr>';
    
    fetch('../api/attendance.php?action=faculty_report', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': '<?= $_SESSION['csrf_token'] ?? '' ?>'
        },
        body: JSON.stringify({
            branch_id: branchId,
            faculty_id: facultyId,
            from_date: fromDate,
            to_date: toDate,
            csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>'
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success && data.records) {
            renderFacultyReport(data.records, fromDate, toDate);
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

function renderFacultyReport(records, fromDate, toDate) {
    const reportHead = document.getElementById('reportHead');
    const reportBody = document.getElementById('reportBody');
    
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
    
    // Build unique faculty list from records
    const facultyMap = {};
    records.forEach(rec => {
        if (rec.faculty_id && !facultyMap[rec.faculty_id]) {
            facultyMap[rec.faculty_id] = rec.faculty_name;
        }
    });
    
    const facultyList = Object.entries(facultyMap).map(([id, name]) => ({ id: parseInt(id), name })).sort((a, b) => a.name.localeCompare(b.name));
    
    // Build header row with dates
    let headerHtml = '<tr><th style="background: #f8f9fa; text-align: left; padding: 0.75rem !important; min-width: 180px;">Faculty/Employee</th><th style="background: #f8f9fa; text-align: left; padding: 0.75rem !important; min-width: 200px;">Batch</th>';
    dateHeaders.forEach(header => {
        const bgColor = header.isSunday ? '#fff3cd' : '#f8f9fa';
        headerHtml += `<th class="text-center" style="background: ${bgColor}; min-width: 70px; padding: 0.5rem !important;">
            <div style="font-weight: 700; font-size: 0.875rem;">${header.day}</div>
            <div style="font-size: 0.75rem; color: #666; margin-top: 2px;">${header.month}</div>
            <div style="font-size: 0.7rem; font-weight: 600;">${header.dayName}</div>
        </th>`;
    });
    headerHtml += '</tr>';
    reportHead.innerHTML = headerHtml;
    
    // Build attendance map: key = "date_faculty_id", value = array of records
    const attendanceMap = {};
    records.forEach(rec => {
        const key = rec.date + '_' + rec.faculty_id;
        if (!attendanceMap[key]) {
            attendanceMap[key] = [];
        }
        attendanceMap[key].push(rec);
    });
    
    // Group faculty by batch for better organization
    const facultyBatchMap = {};
    records.forEach(rec => {
        if (!facultyBatchMap[rec.faculty_id]) {
            facultyBatchMap[rec.faculty_id] = {
                name: rec.faculty_name,
                batches: new Set()
            };
        }
        if (rec.batch_name) {
            facultyBatchMap[rec.faculty_id].batches.add(rec.batch_name);
        }
    });
    
    // Build body rows - one row per faculty
    let bodyHtml = '';
    facultyList.forEach((fac, facIdx) => {
        const rowBg = facIdx % 2 === 0 ? '#ffffff' : '#f8f9fa';
        const batchList = facultyBatchMap[fac.id]?.batches || new Set();
        const batchText = batchList.size > 0 ? Array.from(batchList).join(', ') : '-';
        
        let rowHtml = `<tr style="background: ${rowBg};">
            <td style="background: ${rowBg}; text-align: left; padding: 0.75rem !important; font-weight: 600; vertical-align: middle;">${escapeHtml(fac.name)}</td>
            <td style="background: ${rowBg}; text-align: left; padding: 0.75rem !important; font-size: 0.813rem; color: #666; vertical-align: middle;">${escapeHtml(batchText)}</td>`;
        
        dateHeaders.forEach(header => {
            const key = header.date + '_' + fac.id;
            const dayRecords = attendanceMap[key] || [];
            const cellBg = header.isSunday ? '#fff3cd' : rowBg;
            
            if (header.isSunday && dayRecords.length === 0) {
                // Sunday with no records - show holiday badge
                rowHtml += `<td style="background: ${cellBg};"><div class="attendance-badge badge-holiday">H</div></td>`;
            } else if (dayRecords.length === 0) {
                // No records - empty
                rowHtml += `<td style="background: ${cellBg};"></td>`;
            } else {
                // Build cell content for all records on this day
                let cellContent = '<div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">';
                
                dayRecords.forEach((rec, idx) => {
                    const statusMap = { 
                        'present': ['P', 'badge-present'], 
                        'absent': ['A', 'badge-absent'], 
                        'leave': ['L', 'badge-leave'] 
                    };
                    const [badge, badgeClass] = statusMap[rec.status] || ['-', 'badge-holiday'];
                    
                    const inTime = rec.in_time ? rec.in_time.substring(0, 5) : '';
                    const outTime = rec.out_time ? rec.out_time.substring(0, 5) : '';
                    
                    cellContent += `<div style="display: flex; flex-direction: column; align-items: center;">
                        <div class="attendance-badge ${badgeClass}">${badge}</div>
                        ${inTime ? `<div class="time-info">${inTime}</div>` : ''}
                        ${outTime ? `<div class="time-info">${outTime}</div>` : ''}
                    </div>`;
                });
                
                cellContent += '</div>';
                rowHtml += `<td style="background: ${cellBg}; padding: 0.5rem !important;">${cellContent}</td>`;
            }
        });
        
        rowHtml += '</tr>';
        bodyHtml += rowHtml;
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
    return text.replace(/[&<>"']/g, m => map[m]);
}

function exportAttendanceReport() {
    // Export CSV similar to batch report
    let csv = 'Date,Day';
    const headers = document.querySelectorAll('#reportHead th');
    for (let i = 2; i < headers.length; i++) {
        csv += ',' + headers[i].textContent.trim();
    }
    csv += '\n';
    
    document.querySelectorAll('#reportBody tr').forEach(row => {
        const cells = row.querySelectorAll('td');
        let rowData = [];
        cells.forEach(cell => {
            let text = cell.textContent.trim();
            text = `"${text.replace(/"/g, '""')}"`;
            rowData.push(text);
        });
        csv += rowData.join(',') + '\n';
    });
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `faculty-attendance-${new Date().toISOString().slice(0,10)}.csv`;
    a.click();
    window.URL.revokeObjectURL(url);
}
</script>
