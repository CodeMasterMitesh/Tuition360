<?php

use CampusLite\Controllers\{BranchController, StudentController};

if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/views/students.php
$students = StudentController::getAll();
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$totalStudents = count($students);
$totalPages = 1;
$branches = BranchController::getAll();
// build branch id -> name map
$branchMap = [];
foreach ($branches as $b) {
    $branchMap[$b['id']] = $b['name'];
}
?>

<div class="container-fluid dashboard-container fade-in">
    <?php
    $page_icon = 'fas fa-user-graduate';
    $page_title = 'Students';
    $show_actions = true;
    $action_buttons = [
        ['label' => 'Export Excel', 'class' => 'btn-primary', 'onclick' => 'exportToExcel()', 'icon' => 'fas fa-file-excel'],
        ['id' => 'delete-selected-students-header', 'label' => 'Delete Selected', 'class' => 'btn-danger', 'onclick' => "deleteSelectedStudents()", 'icon' => 'fas fa-trash'],
    ];
    // Use declarative modal attributes so nav-ajax and common handlers can manage it
    $add_button = ['label' => 'Add New Student', 'modal' => 'addStudentModal', 'form' => 'addStudentForm'];
    include __DIR__ . '/partials/page-header.php';
    ?>
    <!-- Table Container -->
    <div class="advanced-table-container">
        <!-- Table -->
        <div class="table-responsive table-compact" id="tableContainer">
            <table class="table data-table" id="students-table">
                <thead>
                    <tr>
                        <th width="40" class="text-center"><input type="checkbox" id="select-all-students"></th>
                        <th width="80">ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Branch</th>
                        <th>Status</th>
                        <th width="150" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <h4>No students found</h4>
                                    <p>No students match your search criteria</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                                        <i class="fas fa-plus"></i> Add First Student
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td class="text-center" data-label="Select"><input type="checkbox" class="row-select" data-id="<?= htmlspecialchars($student['id'] ?? '') ?>"></td>
                                <td data-label="ID"><?= htmlspecialchars($student['id'] ?? '') ?></td>
                                <td data-label="Name"><?= htmlspecialchars($student['name'] ?? '') ?></td>
                                <td data-label="Email"><?= htmlspecialchars($student['email'] ?? '') ?></td>
                                <td data-label="Phone"><?= htmlspecialchars($student['mobile'] ?? $student['phone'] ?? '') ?></td>
                                <td data-label="Branch">
                                    <?php
                                    $bid = $student['branch_id'] ?? $student['branch'] ?? null;
                                    $branchName = '';
                                    if ($bid && isset($branchMap[$bid])) $branchName = $branchMap[$bid];
                                    elseif ($student['branch'] ?? false) $branchName = $student['branch'];
                                    ?>
                                    <?= htmlspecialchars($branchName) ?>
                                </td>
                                <td>
                                    <?php
                                    $st = $student['status'] ?? null;
                                    $statusLabel = 'N/A';
                                    $isActive = false;
                                    if ($st === 'active' || $st === '1' || $st === 1) {
                                        $statusLabel = 'Active';
                                        $isActive = true;
                                    } elseif ($st === 'inactive' || $st === '0' || $st === 0) {
                                        $statusLabel = 'Inactive';
                                        $isActive = false;
                                    } elseif (is_string($st) && $st !== '') {
                                        $statusLabel = ucfirst($st);
                                    }
                                    ?>
                                    <span class="status-badge <?= $isActive ? 'status-active' : 'status-inactive' ?>"><?= htmlspecialchars($statusLabel) ?></span>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <button class="btn btn-sm btn-outline-primary btn-table" onclick="editStudent(<?= $student['id'] ?? 0 ?>)" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-info btn-table" onclick="viewStudent(<?= $student['id'] ?? 0 ?>)" title="View">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger btn-table" onclick="deleteStudent(<?= $student['id'] ?? 0 ?>)" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination-container">
                <div class="pagination-info">
                    Showing <?= count($students) ?> of <?= $totalStudents ?> students
                </div>
                <nav aria-label="Page navigation">
                    <ul class="pagination mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=students&page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=students&page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=students&page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>
<!-- Student modal moved to shared partial (partials/modals.php) -->
<?php // branch/course options will be populated by page init when the shared modal is shown ?>

<script src="/public/assets/js/students.js"></script>