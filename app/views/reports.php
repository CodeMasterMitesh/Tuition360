<?php
if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/views/reports.php

$userRole = $_SESSION['user']['role'] ?? '';
$branchId = intval($_SESSION['branch_id'] ?? 1);

$reports = [
    [
        'title' => 'Batch-wise Attendance Report',
        'description' => 'View attendance records in horizontal matrix format by batch and date range',
        'icon' => 'fa-calendar-check',
        'color' => 'primary',
        'link' => 'index.php?page=report_batch_attendance',
        'type' => 'internal'
    ],
    [
        'title' => 'Faculty Attendance Report',
        'description' => 'View faculty and employee attendance with in-time, out-time, batch and holiday indicators',
        'icon' => 'fa-user-tie',
        'color' => 'info',
        'link' => 'index.php?page=report_faculty_attendance',
        'type' => 'internal'
    ],
    [
        'title' => 'Outstanding Fees Report',
        'description' => 'View students with pending fee payments by batch and course',
        'icon' => 'fa-money-bill-wave',
        'color' => 'success',
        'link' => "../api/fees.php?action=outstanding&branch_id=$branchId",
        'type' => 'api'
    ],
    [
        'title' => 'Salary Report',
        'description' => 'View salary payments and pending salaries for faculty and employees',
        'icon' => 'fa-file-invoice-dollar',
        'color' => 'warning',
        'link' => "../api/salary.php?action=report&branch_id=$branchId",
        'type' => 'api',
        'roles' => ['super_admin']
    ],
];
?>

<style>
.report-card {
    transition: all 0.3s ease;
    cursor: pointer;
    border: 2px solid transparent;
}
.report-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.1);
    border-color: var(--bs-primary);
}
.report-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}
</style>

<div class="container-fluid dashboard-container fade-in show">
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h3 class="mb-1"><i class="fas fa-chart-bar me-2"></i>Reports</h3>
            <p class="text-muted">Access various reports and analytics</p>
        </div>
    </div>

    <div class="row g-4">
        <?php foreach ($reports as $report): ?>
            <?php 
            // Check role access if specified
            if (isset($report['roles']) && !in_array($userRole, $report['roles'])) {
                continue;
            }
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="card report-card h-100" onclick="openReport('<?= $report['link'] ?>', '<?= $report['type'] ?>')">
                    <div class="card-body text-center">
                        <div class="report-icon text-<?= $report['color'] ?>">
                            <i class="fas <?= $report['icon'] ?>"></i>
                        </div>
                        <h5 class="card-title"><?= htmlspecialchars($report['title']) ?></h5>
                        <p class="card-text text-muted"><?= htmlspecialchars($report['description']) ?></p>
                        <button class="btn btn-<?= $report['color'] ?> mt-2">
                            <i class="fas fa-eye me-2"></i>View Report
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function openReport(link, type) {
    if (type === 'internal') {
        window.location.href = link;
    } else if (type === 'api') {
        window.open(link, '_blank');
    }
}
</script>

