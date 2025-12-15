<?php
if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }

$currentUser = $_SESSION['user'] ?? null;
$studentId = $currentUser['id'] ?? 0;
$studentName = $currentUser['name'] ?? 'Student';
$studentEmail = $currentUser['email'] ?? '';

// Fetch student-specific data
$sql = "SELECT * FROM students WHERE id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $studentId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$studentData = mysqli_fetch_assoc($result);

// Get enrolled batches via batch_assignments
$enrolledBatches = [];
$sql = "SELECT DISTINCT b.id, b.title, b.course_id, c.name as course_name, b.branch_id
        FROM batch_assignments ba
        JOIN batch_assignment_students bas ON bas.assignment_id = ba.id
        JOIN batches b ON b.id = ba.batch_id
        LEFT JOIN courses c ON c.id = b.course_id
        WHERE bas.student_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $studentId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $enrolledBatches[] = $row;
}

// Get recent attendance
$recentAttendance = [];
$sql = "SELECT a.*, b.title as batch_title 
        FROM attendance a
        LEFT JOIN batches b ON b.id = a.batch_id
        WHERE a.student_id = ? 
        ORDER BY a.date DESC LIMIT 10";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $studentId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $recentAttendance[] = $row;
}

// Calculate attendance stats
$totalClasses = count($recentAttendance);
$presentCount = count(array_filter($recentAttendance, fn($a) => ($a['status'] ?? '') === 'present'));
$attendancePercent = $totalClasses > 0 ? round(($presentCount / $totalClasses) * 100, 1) : 0;

// Get upcoming schedules
$upcomingSchedules = [];
$sql = "SELECT sb.*, b.title as batch_title
        FROM schedule_batches sb
        JOIN batches b ON b.id = sb.batch_id
        WHERE sb.status = 'active' 
        AND JSON_CONTAINS(sb.student_ids, ?)
        ORDER BY sb.start_date DESC LIMIT 5";
$stmt = mysqli_prepare($conn, $sql);
$studentIdJson = json_encode($studentId);
mysqli_stmt_bind_param($stmt, 's', $studentIdJson);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $upcomingSchedules[] = $row;
}
?>

<div class="container-fluid dashboard-container fade-in">
    <div class="row mb-4">
        <div class="col-12">
            <h3 class="mb-1">Welcome, <?= htmlspecialchars($studentName) ?>!</h3>
            <p class="text-muted">Your Student Portal</p>
        </div>
    </div>

    <div class="row g-3">
        <!-- Profile Card -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($studentName) ?>&size=120&background=6f42c1&color=fff" 
                         alt="Profile" class="rounded-circle mb-3">
                    <h5 class="card-title"><?= htmlspecialchars($studentName) ?></h5>
                    <p class="text-muted">Student</p>
                    <?php if ($studentData): ?>
                        <div class="text-start mt-3">
                            <p class="mb-1"><i class="fas fa-envelope me-2"></i><?= htmlspecialchars($studentData['email'] ?? 'N/A') ?></p>
                            <p class="mb-1"><i class="fas fa-phone me-2"></i><?= htmlspecialchars($studentData['mobile'] ?? 'N/A') ?></p>
                            <p class="mb-1"><i class="fas fa-calendar me-2"></i>Joined: <?= htmlspecialchars($studentData['registration_date'] ?? 'N/A') ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="col-md-8">
            <div class="row g-3">
                <div class="col-sm-6">
                    <div class="card text-bg-primary">
                        <div class="card-body">
                            <h6 class="card-title"><i class="fas fa-layer-group me-2"></i>Enrolled Batches</h6>
                            <h3 class="mb-0"><?= count($enrolledBatches) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="card text-bg-success">
                        <div class="card-body">
                            <h6 class="card-title"><i class="fas fa-percentage me-2"></i>Attendance</h6>
                            <h3 class="mb-0"><?= $attendancePercent ?>%</h3>
                            <small>Based on <?= $totalClasses ?> classes</small>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="card text-bg-info">
                        <div class="card-body">
                            <h6 class="card-title"><i class="fas fa-check-circle me-2"></i>Classes Attended</h6>
                            <h3 class="mb-0"><?= $presentCount ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="card text-bg-warning text-dark">
                        <div class="card-body">
                            <h6 class="card-title"><i class="fas fa-calendar-alt me-2"></i>Upcoming Classes</h6>
                            <h3 class="mb-0"><?= count($upcomingSchedules) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enrolled Batches -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">My Batches</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($enrolledBatches)): ?>
                        <p class="text-muted text-center">You are not enrolled in any batches yet</p>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($enrolledBatches as $batch): ?>
                                <div class="col-md-6">
                                    <div class="card border">
                                        <div class="card-body">
                                            <h6 class="card-title"><?= htmlspecialchars($batch['title'] ?? 'Batch') ?></h6>
                                            <p class="text-muted mb-0"><i class="fas fa-book me-2"></i><?= htmlspecialchars($batch['course_name'] ?? 'N/A') ?></p>
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

    <!-- Recent Attendance -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Recent Attendance</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recentAttendance)): ?>
                        <p class="text-muted text-center">No attendance records found</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Batch</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentAttendance as $att): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($att['date'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($att['batch_title'] ?? 'N/A') ?></td>
                                            <td>
                                                <?php
                                                $status = $att['status'] ?? 'absent';
                                                $badgeClass = $status === 'present' ? 'bg-success' : 'bg-danger';
                                                ?>
                                                <span class="badge <?= $badgeClass ?>"><?= ucfirst($status) ?></span>
                                            </td>
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
