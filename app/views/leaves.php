<?php

use CampusLite\Controllers\LeaveController;

if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/views/leaves.php
$currentUser = $_SESSION['user'] ?? null;
$currentBranch = $_SESSION['branch_id'] ?? ($currentUser['branch_id'] ?? 0);
$userId = $currentUser['id'] ?? 0;
$userRole = strtolower($currentUser['role'] ?? 'employee');
$isAdmin = in_array($userRole, ['super_admin', 'branch_admin'], true);

// Admins see all; staff see own
$leaves = LeaveController::getAll($isAdmin ? null : $userId);

// preload user names for display
$userNames = [];
$nameRes = mysqli_query($conn, "SELECT id, name, role FROM users");
if ($nameRes) {
    while ($row = mysqli_fetch_assoc($nameRes)) {
        $userNames[$row['id']] = $row['name'] . (isset($row['role']) ? ' (' . $row['role'] . ')' : '');
    }
}
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$total = count($leaves);
$totalPages = 1;
?>

<div class="container-fluid dashboard-container fade-in">
    <?php
    $page_icon = 'fas fa-calendar-alt';
    $page_title = 'Leaves';
    $show_actions = true;
    $add_button = ['label' => 'Apply Leave', 'modal' => 'addLeaveModal', 'form' => 'addLeaveForm'];
    include __DIR__ . '/partials/page-header.php';
    ?>
    <div class="advanced-table-container">
        <!-- table-controls removed (search/actions removed) -->
        <div class="table-responsive table-compact" id="tableContainer">
            <table class="table data-table" id="leaves-table">
                <thead>
                    <tr>
                        <th width="80">ID</th>
                        <th>Staff</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th width="150" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if (empty($leaves)): ?>
                        <tr>
                            <td>
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <h4>No leave records</h4>
                                    <p>No leaves match your search criteria</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLeaveModal"><i class="fas fa-plus"></i> Apply Leave</button>
                                </div>
                            </td><td></td><td></td><td></td><td></td><td></td><td></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($leaves as $l): ?>
                            <tr>
                                <td><?= htmlspecialchars($l['id'] ?? '') ?></td>
                                <td><?= htmlspecialchars($userNames[$l['user_id'] ?? 0] ?? ('User #' . ($l['user_id'] ?? ''))) ?></td>
                                <td><?= htmlspecialchars($l['from_date'] ?? '') ?></td>
                                <td><?= htmlspecialchars($l['to_date'] ?? '') ?></td>
                                <td><?= htmlspecialchars($l['type'] ?? '') ?></td>
                                <td><span class="status-badge <?= ($l['status'] ?? '') === 'approved' ? 'status-active' : 'status-inactive' ?>"><?= htmlspecialchars($l['status'] ?? 'applied') ?></span></td>
                                <td>
                                    <div class="table-actions">
                                        <button class="btn btn-sm btn-outline-info btn-table" onclick="viewLeave(<?= $l['id'] ?? 0 ?>)" title="View"><i class="fas fa-eye"></i></button>
                                        <?php 
                                        $leaveStatus = $l['status'] ?? 'applied';
                                        if ($isAdmin && $leaveStatus === 'applied'): 
                                        ?>
                                            <button class="btn btn-sm btn-outline-success btn-table" onclick="decideLeave(<?= $l['id'] ?? 0 ?>, 'approved')" title="Approve"><i class="fas fa-check"></i></button>
                                            <button class="btn btn-sm btn-outline-warning btn-table" onclick="decideLeave(<?= $l['id'] ?? 0 ?>, 'rejected')" title="Reject"><i class="fas fa-times"></i></button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-outline-danger btn-table" onclick="deleteLeave(<?= $l['id'] ?? 0 ?>)" title="Delete"><i class="fas fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Leave Modal -->
<div class="modal fade" id="addLeaveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Apply Leave</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <form id="addLeaveForm">
                    <input type="hidden" name="id" id="leaveId" value="">
                    <input type="hidden" name="branch_id" id="leaveBranchId" value="<?= htmlspecialchars($currentBranch) ?>">
                    <?php if ($isAdmin): ?>
                        <div class="mb-3">
                            <label class="form-label">Employee/Faculty</label>
                            <select class="form-control" name="user_id" id="leaveUserId" required>
                                <option value="">-- Select Staff --</option>
                                <?php
                                $staffRes = mysqli_query($conn, "SELECT id, name, role FROM users WHERE role IN ('employee','faculty') ORDER BY name");
                                if ($staffRes) {
                                    while ($staff = mysqli_fetch_assoc($staffRes)) {
                                        echo '<option value="' . htmlspecialchars($staff['id']) . '">' . htmlspecialchars($staff['name']) . ' (' . htmlspecialchars($staff['role']) . ')</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="user_id" id="leaveUserId" value="<?= htmlspecialchars($userId) ?>">
                        <div class="mb-3"><label class="form-label"><?= $userRole === 'faculty' ? 'Faculty' : 'Employee' ?></label><input class="form-control" name="staff_name" id="leaveStaffName" value="<?= htmlspecialchars($currentUser['name'] ?? 'User') ?>" readonly></div>
                    <?php endif; ?>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">From</label><input type="date" class="form-control" name="from_date" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">To</label><input type="date" class="form-control" name="to_date" required></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Type</label><select class="form-control" name="leave_type"><option value="Casual">Casual</option><option value="Sick">Sick</option><option value="Other">Other</option></select></div>
                    <div class="mb-3"><label class="form-label">Reason</label><textarea class="form-control" name="reason" rows="3" placeholder="Add your reason" required></textarea></div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" onclick="saveLeave()">Apply</button></div>
        </div>
    </div>
</div>

<script src="/public/assets/js/leaves.js"></script>
<script>
window.CURRENT_USER_ID = <?= json_encode($userId) ?>;
window.CURRENT_USER_NAME = <?= json_encode($currentUser['name'] ?? 'User') ?>;
window.CURRENT_BRANCH_ID = <?= json_encode($currentBranch) ?>;
window.IS_ADMIN = <?= json_encode($isAdmin) ?>;
</script>
