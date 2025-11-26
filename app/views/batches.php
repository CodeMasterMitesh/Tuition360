<?php
if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/views/batches.php
$batches = [];
$controllerFile = __DIR__ . '/../controllers/BatchController.php';
if (file_exists($controllerFile)) {
    require_once $controllerFile;
    $cls = 'BatchController';
    if (class_exists($cls) && method_exists($cls, 'getAll')) {
        $batches = $cls::getAll();
    }
}
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$totalBatches = count($batches);
$totalPages = 1;
// load branches and courses
$branchFile = __DIR__ . '/../controllers/BranchController.php';
$branches = [];
if (file_exists($branchFile)) { require_once $branchFile; if (class_exists('BranchController') && method_exists('BranchController','getAll')) $branches = BranchController::getAll(); }
$courseFile = __DIR__ . '/../controllers/CourseController.php';
$courses = [];
if (file_exists($courseFile)) { require_once $courseFile; if (class_exists('CourseController') && method_exists('CourseController','getAll')) $courses = CourseController::getAll(); }
$courseMap = [];
foreach ($courses as $c) { $courseMap[$c['id']] = $c['title'] ?? $c['name'] ?? ''; }
?>
<?php include __DIR__ . '/partials/nav.php'; ?>
<div class="container-fluid dashboard-container fade-in">
    <?php
    $page_icon = 'fas fa-layer-group';
    $page_title = 'Batches';
    $show_actions = true;
    $action_buttons = [
        ['label' => 'Export Excel', 'class' => 'btn-primary', 'onclick' => 'exportToExcel()', 'icon' => 'fas fa-file-excel'],
        ['id' => 'delete-selected-batches-header', 'label' => 'Delete Selected', 'class' => 'btn-danger', 'onclick' => "deleteSelectedBatches()", 'icon' => 'fas fa-trash']
    ];
    $add_button = ['label' => 'Add New Batch', 'onclick' => "showAddModal('addBatchModal','addBatchForm')"];
    include __DIR__ . '/partials/page-header.php';
    ?>
    <!-- Table Container -->
    <div class="advanced-table-container">
        <!-- Table Controls removed (search/actions removed) -->
        <!-- Table -->
        <div class="table-responsive table-compact" id="tableContainer">
            <table class="table data-table" id="batches-table">
                <thead>
                    <tr>
                        <th width="40" class="text-center"><input type="checkbox" id="select-all-batches"></th>
                        <th width="80">ID</th>
                        <th>Name</th>
                        <th>Course</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th width="150" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if (empty($batches)): ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <h4>No batches found</h4>
                                    <p>No batches match your search criteria</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBatchModal">
                                        <i class="fas fa-plus"></i> Add First Batch
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($batches as $batch): ?>
                            <tr>
                                <td class="text-center"><input type="checkbox" class="row-select" data-id="<?= htmlspecialchars($batch['id'] ?? '') ?>"></td>
                                <td><?= htmlspecialchars($batch['id'] ?? '') ?></td>
                                <td><?= htmlspecialchars($batch['title'] ?? $batch['name'] ?? '') ?></td>
                                <td>
                                    <?php
                                    $cid = $batch['course_id'] ?? $batch['course'] ?? null;
                                    $courseName = '';
                                    if ($cid && isset($courseMap[$cid])) $courseName = $courseMap[$cid];
                                    elseif (!empty($batch['course'])) $courseName = $batch['course'];
                                    ?>
                                    <?= htmlspecialchars($courseName) ?>
                                </td>
                                <td><?= htmlspecialchars($batch['start_date'] ?? '') ?></td>
                                <td><?= htmlspecialchars($batch['end_date'] ?? '') ?></td>
                                <td>
                                    <?php if (!empty($batch['status'])): 
                                        $st = $batch['status'];
                                        $activeStates = ['running'];
                                        $cls = in_array($st, $activeStates) ? 'status-active' : 'status-inactive';
                                    ?>
                                        <span class="status-badge <?= $cls ?>"><?= htmlspecialchars(ucfirst($st)) ?></span>
                                    <?php else: ?>
                                        <span class="status-badge status-inactive">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <button class="btn btn-sm btn-outline-primary btn-table" onclick="editBatch(<?= $batch['id'] ?? 0 ?>)" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-info btn-table" onclick="viewBatch(<?= $batch['id'] ?? 0 ?>)" title="View">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger btn-table" onclick="deleteBatch(<?= $batch['id'] ?? 0 ?>)" title="Delete">
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
                Showing <?= count($batches) ?> of <?= $totalBatches ?> batches
            </div>
            <nav aria-label="Page navigation">
                <ul class="pagination mb-0">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=batches&page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=batches&page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=batches&page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">
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
<!-- Add Batch Modal -->
<div class="modal fade" id="addBatchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Batch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addBatchForm">
                    <input type="hidden" name="id" id="batchId" value="">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Branch</label>
                        <select class="form-control" name="branch_id" id="batchBranch">
                            <option value="0">-- Select Branch --</option>
                            <?php foreach ($branches as $br): ?>
                                <option value="<?= intval($br['id']) ?>"><?= htmlspecialchars($br['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Course</label>
                        <select class="form-control" name="course_id" id="batchCourse" required>
                            <option value="0">-- Select Course --</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= intval($c['id']) ?>"><?= htmlspecialchars($c['title'] ?? $c['name'] ?? 'Course') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Capacity</label>
                            <input type="number" class="form-control" name="capacity" min="1" value="30">
                        </div>
                    </div>
                    <div class="mb-3 mt-2">
                        <label class="form-label">Days of Week</label>
                        <div class="d-flex gap-2 flex-wrap">
                            <?php $days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun']; foreach ($days as $d): ?>
                                <div class="form-check">
                                    <input class="form-check-input day-checkbox" type="checkbox" value="<?= $d ?>" id="day-<?= $d ?>">
                                    <label class="form-check-label" for="day-<?= $d ?>"><?= $d ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="days_of_week" id="daysOfWeek">
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Time Slot</label>
                            <input type="text" class="form-control" name="time_slot" placeholder="e.g. 6:00PM - 8:00PM">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-control" name="status">
                                <option value="planned">Planned</option>
                                <option value="running">Running</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="saveBatchBtn" class="btn btn-primary" onclick="saveBatch()">Save Batch</button>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
<script src="../../../public/assets/js/batches.js"></script>
