
<?php
if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
require_once __DIR__ . '/../controllers/CourseController.php';
require_once __DIR__ . '/../controllers/BranchController.php';
require_once __DIR__ . '/../controllers/SubjectController.php';
$courses = CourseController::getAll();
$branches = BranchController::getAll();
$branchMap = [];
foreach ($branches as $b) {
    $branchMap[$b['id']] = $b['name'];
}
$subjects = SubjectController::getAll();
?>
<?php include __DIR__ . '/partials/nav.php'; ?>
<div class="container-fluid dashboard-container fade-in">
    <?php
    // Use shared page header partial for consistency
    $page_icon = 'fas fa-book';
    $page_title = 'Courses';
    $show_actions = false;
    $add_button = ['label' => 'Add New Course', 'onclick' => "showAddModal('addCourseModal','addCourseForm')"]; 
    include __DIR__ . '/partials/page-header.php';
    ?>
    <div class="advanced-table-container">
        <div class="table-responsive table-compact" id="tableContainer">
            <table class="table data-table" id="courses-table">
                <thead>
                    <tr>
                        <th width="40" class="text-center"><input type="checkbox" id="select-all-courses"></th>
                        <th width="80">ID</th>
                        <th>Branch</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Total Fee</th>
                        <th>Duration (months)</th>
                        <th width="150" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if (empty($courses)): ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <h4>No courses found</h4>
                                    <p>No courses match your search criteria</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCourseModal">
                                        <i class="fas fa-plus"></i> Add First Course
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($courses as $course): ?>
                            <tr>
                                <td class="text-center"><input type="checkbox" class="row-select" data-id="<?= htmlspecialchars($course['id'] ?? '') ?>"></td>
                                <td><?= htmlspecialchars($course['id'] ?? '') ?></td>
                                <td>
                                    <?php
                                    $bid = $course['branch_id'] ?? $course['branch'] ?? null;
                                    $branchName = '';
                                    if ($bid && isset($branchMap[$bid])) $branchName = $branchMap[$bid];
                                    elseif (!empty($course['branch'])) $branchName = $course['branch'];
                                    ?>
                                    <?= htmlspecialchars($branchName) ?>
                                </td>
                                <td><?= htmlspecialchars($course['title'] ?? '') ?></td>
                                <td><?= htmlspecialchars($course['description'] ?? '') ?></td>
                                <td><?= htmlspecialchars($course['total_fee'] ?? '') ?></td>
                                <td><?= htmlspecialchars($course['duration_months'] ?? '') ?></td>
                                <td>
                                    <div class="table-actions">
                                        <button class="btn btn-sm btn-outline-primary btn-table" onclick="editCourse(<?= $course['id'] ?? 0 ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-sm btn-outline-info btn-table" onclick="viewCourse(<?= $course['id'] ?? 0 ?>)" title="View"><i class="fas fa-eye"></i></button>
                                        <button class="btn btn-sm btn-outline-danger btn-table" onclick="deleteCourse(<?= $course['id'] ?? 0 ?>)" title="Delete"><i class="fas fa-trash"></i></button>
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

<!-- Add/Edit Course Modal -->
<div class="modal fade" id="addCourseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-book me-2"></i> Add/Edit Course</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addCourseForm">
                    <input type="hidden" name="id" id="courseId" value="">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Branch</label>
                            <select class="form-control" name="branch_id" required>
                                <option value="0">-- Select Branch --</option>
                                <?php foreach ($branches as $b): ?>
                                    <option value="<?= intval($b['id']) ?>"><?= htmlspecialchars($b['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Total Fee</label>
                            <input type="number" class="form-control" name="total_fee" min="0" step="0.01">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Duration (months)</label>
                            <input type="number" class="form-control" name="duration_months" min="0">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description"></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Subjects</label>
                            <div id="subjects-dynamic" class="row g-2">
                                <div class="subject-row col-md-4 mb-2 d-flex align-items-center">
                                    <select class="form-control" name="subjects[]">
                                        <?php foreach ($subjects as $subject): ?>
                                            <option value="<?= intval($subject['id']) ?>"><?= htmlspecialchars($subject['title']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-sm btn-outline-danger ms-2 delete-subject-btn" title="Remove"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addSubjectDropdown"><i class="fas fa-plus"></i> Add More</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveCourse()">Save Course</button>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
<script src="../../../public/assets/js/courses.js"></script>
