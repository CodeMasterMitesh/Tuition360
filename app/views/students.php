<?php
if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/views/students.php
$students = [];
$controllerFile = __DIR__ . '/../controllers/StudentController.php';
if (file_exists($controllerFile)) {
    require_once $controllerFile;
    $cls = 'StudentController';
    if (class_exists($cls) && method_exists($cls, 'getAll')) {
        $students = $cls::getAll();
    }
}
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$totalStudents = count($students);
$totalPages = 1;
// load branches for dropdown
$branchFile = __DIR__ . '/../controllers/BranchController.php';
$branches = [];
if (file_exists($branchFile)) {
    require_once $branchFile;
    if (class_exists('BranchController') && method_exists('BranchController', 'getAll')) $branches = BranchController::getAll();
}
// build branch id -> name map
$branchMap = [];
foreach ($branches as $b) {
    $branchMap[$b['id']] = $b['name'];
}
?>
<?php include __DIR__ . '/partials/nav.php'; ?>
<div class="container-fluid dashboard-container fade-in">
    <?php
    $page_icon = 'fas fa-user-graduate';
    $page_title = 'Students';
    $show_actions = true;
    $action_buttons = [
        ['label' => 'Export Excel', 'class' => 'btn-primary', 'onclick' => 'exportToExcel()', 'icon' => 'fas fa-file-excel'],
        ['id' => 'delete-selected-students-header', 'label' => 'Delete Selected', 'class' => 'btn-danger', 'onclick' => "deleteSelectedStudents()", 'icon' => 'fas fa-trash'],
    ];
    $add_button = ['label' => 'Add New Student', 'onclick' => "showAddModal('addStudentModal','addStudentForm')"];
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
<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i> Add New Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addStudentForm">
                    <input type="hidden" name="id" id="studentId" value="">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Student Name</label>
                            <input type="text" class="form-control soft-input" name="name" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control soft-input" name="email" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control soft-input" name="mobile" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" class="form-control soft-input" name="dob">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Education</label>
                            <select class="form-control soft-input" name="education">
                                <option value="">-- Select Education --</option>
                                <option value="10th">10th</option>
                                <option value="12th">12th</option>
                                <option value="Diploma">Diploma</option>
                                <option value="Graduate">Graduate</option>
                                <option value="Post Graduate">Post Graduate</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">College Name</label>
                            <input type="text" class="form-control soft-input" name="college_name">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Father Name</label>
                            <input type="text" class="form-control soft-input" name="father_name">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Address</label>
                            <textarea class="form-control soft-input" name="address"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Pincode</label>
                            <input type="text" class="form-control soft-input" name="pincode">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">State</label>
                            <input type="text" class="form-control soft-input" name="state">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">City</label>
                            <input type="text" class="form-control soft-input" name="city">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Area</label>
                            <input type="text" class="form-control soft-input" name="area">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Branch</label>
                            <select class="form-control soft-input" name="branch_id" id="studentBranch" required>
                                <option value="0">-- Select Branch --</option>
                                <?php foreach ($branches as $b): ?>
                                    <option value="<?= intval($b['id']) ?>"><?= htmlspecialchars($b['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select class="form-control soft-input" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Courses</label>
                            <div id="courses-dynamic" class="row g-2">
                                <div class="course-row col-md-4 mb-2 d-flex align-items-center">
                                    <select class="form-control soft-input" name="courses[]">
                                        <?php 
                                        require_once __DIR__ . '/../controllers/CourseController.php';
                                        $courses = CourseController::getAll();
                                        foreach ($courses as $course): ?>
                                            <option value="<?= intval($course['id']) ?>"><?= htmlspecialchars($course['title']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-sm btn-outline-danger ms-2 delete-course-btn" title="Remove"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addCourseDropdown"><i class="fas fa-plus"></i> Add More</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveStudent()">Save Student</button>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
<script src="../../../public/assets/js/students.js"></script>