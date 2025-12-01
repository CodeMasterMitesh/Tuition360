<?php

use CampusLite\Controllers\{BranchController, FacultyController};

if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/views/faculty.php
$faculty = FacultyController::getAll();
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$totalFaculty = count($faculty);
$totalPages = 1;
$branches = BranchController::getAll();
// build branch id => name map for display
$branchMap = [];
foreach ($branches as $b) {
    $branchMap[intval($b['id'])] = $b['name'];
}
?>

<div class="container-fluid dashboard-container fade-in">
    <?php
    $page_icon = 'fas fa-chalkboard-teacher';
    $page_title = 'Faculty';
    $show_actions = true;
    $action_buttons = [
        ['id' => 'delete-selected-faculty-header', 'label' => 'Delete Selected', 'class' => 'btn-danger', 'onclick' => "deleteSelectedFaculty()", 'icon' => 'fas fa-trash'],
        ['id' => 'export-faculty-header', 'label' => 'Export Excel', 'class' => 'btn-primary', 'onclick' => "exportToExcel()", 'icon' => 'fas fa-file-excel']
    ];
    $add_button = ['label' => 'Add New Faculty', 'modal' => 'addFacultyModal', 'form' => 'addFacultyForm'];
    include __DIR__ . '/partials/page-header.php';
    ?>
    <!-- Table Container -->
    <div class="advanced-table-container">
        <!-- table-controls removed (search/actions removed) -->
        <!-- Table -->
        <div class="table-responsive table-compact" id="tableContainer">
            <table class="table data-table" id="faculty-table">
                <thead>
                    <tr>
                        <th width="40" class="text-center"><input type="checkbox" id="select-all-faculty"></th>
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
                    <?php if (empty($faculty)): ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <h4>No faculty found</h4>
                                    <p>No faculty match your search criteria</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFacultyModal">
                                        <i class="fas fa-plus"></i> Add First Faculty
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($faculty as $f): ?>
                            <tr>
                                <td class="text-center" data-label="Select"><input type="checkbox" class="row-select" data-id="<?= htmlspecialchars($f['id'] ?? '') ?>"></td>
                                <td data-label="ID"><?= htmlspecialchars($f['id'] ?? '') ?></td>
                                <td data-label="Name"><?= htmlspecialchars($f['name'] ?? '') ?></td>
                                <td data-label="Email"><?= htmlspecialchars($f['email'] ?? '') ?></td>
                                <td data-label="Phone"><?= htmlspecialchars($f['mobile'] ?? ($f['phone'] ?? '')) ?></td>
                                <td data-label="Branch"><?= htmlspecialchars($branchMap[intval($f['branch_id'] ?? 0)] ?? ($f['branch'] ?? '')) ?></td>
                                <td data-label="Status">
                                    <?php if (isset($f['status'])): ?>
                                        <span class="status-badge <?= $f['status'] === 'active' ? 'status-active' : 'status-inactive' ?>">
                                            <?= ucfirst($f['status']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge status-inactive">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Actions">
                                    <div class="table-actions">
                                        <button class="btn btn-sm btn-outline-primary btn-table" onclick="editFaculty(<?= $f['id'] ?? 0 ?>)" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-info btn-table" onclick="viewFaculty(<?= $f['id'] ?? 0 ?>)" title="View">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger btn-table" onclick="deleteFaculty(<?= $f['id'] ?? 0 ?>)" title="Delete">
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
            <div class="d-flex justify-content-end gap-2 mt-2">
                <button class="btn btn-danger" id="delete-selected-faculty-header" style="display:none;" onclick="deleteSelectedFaculty()"><i class="fas fa-trash"></i> Delete Selected</button>
            </div>
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination-container">
            <div class="pagination-info">
                Showing <?= count($faculty) ?> of <?= $totalFaculty ?> faculty
            </div>
            <nav aria-label="Page navigation">
                <ul class="pagination mb-0">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=faculty&page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=faculty&page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=faculty&page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">
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
<!-- Add Faculty Modal -->
<div class="modal fade" id="addFacultyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="facultyModalTitle">Add New Faculty</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addFacultyForm">
                    <input type="hidden" name="id" id="facultyId" value="">
                    <div class="mb-3">
                        <label class="form-label">Faculty Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="tel" class="form-control" name="mobile" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" placeholder="Optional: enter a password to set">
                        <div class="form-text">Leave blank to generate or use default password.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Branch</label>
                        <select class="form-control" name="branch_id" id="facultyBranch" required>
                            <option value="0">-- Select Branch --</option>
                            <?php foreach ($branches as $b): ?>
                                <option value="<?= intval($b['id']) ?>"><?= htmlspecialchars($b['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="saveFacultyBtn" class="btn btn-primary" onclick="saveFaculty()">Save Faculty</button>
            </div>
        </div>
    </div>
</div>

<!-- faculty script loaded from public/assets/js/faculty.js -->
<script src="/public/assets/js/faculty.js"></script>
