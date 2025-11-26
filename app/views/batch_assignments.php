<?php
// app/views/batch_assignments.php
$assignments = [];
$controllerFile = __DIR__ . '/../controllers/BatchAssignmentController.php';
if (file_exists($controllerFile)) {
    require_once $controllerFile;
    $cls = 'BatchAssignmentController';
    if (class_exists($cls) && method_exists($cls, 'getAll')) {
        $assignments = $cls::getAll();
    }
}
// load batches and users for selects
$batchFile = __DIR__ . '/../controllers/BatchController.php';
$batches = [];
if (file_exists($batchFile)) { require_once $batchFile; if (class_exists('BatchController') && method_exists('BatchController','getAll')) $batches = BatchController::getAll(); }
$userFile = __DIR__ . '/../controllers/UserController.php';
$users = [];
if (file_exists($userFile)) { require_once $userFile; if (class_exists('UserController') && method_exists('UserController','getAll')) $users = UserController::getAll(); }
$batchMap = []; foreach ($batches as $b) $batchMap[$b['id']] = $b['title'] ?? $b['name'] ?? ('Batch '.$b['id']);
$userMap = []; foreach ($users as $u) $userMap[$u['id']] = $u['name'] ?? $u['email'] ?? ('User '.$u['id']);
?>
<?php include __DIR__ . '/partials/nav.php'; ?>
<div class="container-fluid dashboard-container fade-in">
    <?php
    $page_icon = 'fas fa-tasks';
    $page_title = 'Batch Assignments';
    $show_actions = true;
    $action_buttons = [
        ['label'=>'Export Excel','class'=>'btn-primary','onclick'=>'exportToExcel()','icon'=>'fas fa-file-excel'],
        ['id' => 'delete-selected-assignments-header', 'label' => 'Delete Selected', 'class' => 'btn-danger', 'onclick' => "deleteSelectedAssignments()", 'icon' => 'fas fa-trash']
    ];
    $add_button = ['label'=>'Add Assignment','onclick'=>"showAddModal('addAssignmentModal','addAssignmentForm')"];
    include __DIR__ . '/partials/page-header.php';
    ?>
    <div class="advanced-table-container">
        <div class="table-responsive table-compact" id="tableContainer">
            <table class="table data-table" id="assignments-table">
                <thead>
                    <tr>
                        <th width="40" class="text-center"><input type="checkbox" id="select-all-assignments"></th>
                        <th width="80">ID</th>
                        <th>Batch</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Assigned At</th>
                        <th width="150" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($assignments)): ?>
                        <tr>
                            <td>
                                <div class="empty-state"><i class="fas fa-inbox"></i><h4>No assignments</h4><p>No batch assignments found</p></div>
                            </td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    <?php else: foreach ($assignments as $a): ?>
                        <tr>
                            <td class="text-center" data-label="Select"><input type="checkbox" class="row-select" data-id="<?= htmlspecialchars($a['id']) ?>"></td>
                            <td data-label="ID"><?= htmlspecialchars($a['id']) ?></td>
                            <td data-label="Batch"><?= htmlspecialchars($batchMap[$a['batch_id']] ?? ('#'.$a['batch_id'])) ?></td>
                            <td data-label="User"><?= htmlspecialchars($userMap[$a['user_id']] ?? ('#'.$a['user_id'])) ?></td>
                            <td data-label="Role"><?= htmlspecialchars(ucfirst($a['role'] ?? '')) ?></td>
                            <td data-label="Assigned At"><?= htmlspecialchars($a['assigned_at'] ?? '') ?></td>
                            <td>
                                <div class="table-actions">
                                    <button class="btn btn-sm btn-outline-primary btn-table" onclick="editAssignment(<?= $a['id'] ?>)"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm btn-outline-danger btn-table" onclick="deleteAssignment(<?= $a['id'] ?>)"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="addAssignmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Add Assignment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="addAssignmentForm">
                    <input type="hidden" name="id" id="assignmentId" value="">
                    <div class="mb-2"><label class="form-label">Batch</label>
                        <select class="form-control" name="batch_id" id="assignmentBatch" required>
                            <option value="0">-- Select Batch --</option>
                            <?php foreach ($batches as $b): ?>
                                <option value="<?= intval($b['id']) ?>"><?= htmlspecialchars($b['title'] ?? $b['name'] ?? ('Batch '.$b['id'])) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2"><label class="form-label">User</label>
                        <select class="form-control" name="user_id" id="assignmentUser" required>
                            <option value="0">-- Select User --</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= intval($u['id']) ?>"><?= htmlspecialchars($u['name'] ?? $u['email'] ?? ('User '.$u['id'])) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2"><label class="form-label">Role</label>
                        <select class="form-control" name="role">
                            <option value="faculty">Faculty</option>
                            <option value="employee">Employee</option>
                        </select>
                    </div>
                    <div class="mb-2"><label class="form-label">Assigned At</label>
                        <input type="datetime-local" class="form-control" name="assigned_at" id="assignmentAt">
                    </div>
                </form>
            </div>
            <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" onclick="saveAssignment()">Save</button></div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
<script src="../../../public/assets/js/batch_assignments.js"></script>