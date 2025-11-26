<?php
if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/views/subjects.php
$subjects = [];
$controllerFile = __DIR__ . '/../controllers/SubjectController.php';
if (file_exists($controllerFile)) {
    require_once $controllerFile;
    $cls = 'SubjectController';
    if (class_exists($cls) && method_exists($cls, 'getAll')) {
        $subjects = $cls::getAll();
    }
}
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$total = count($subjects);
$totalPages = 1;
?>
<?php include __DIR__ . '/partials/nav.php'; ?>
<div class="container-fluid dashboard-container fade-in">
    <?php
    $page_icon = 'fas fa-book';
    $page_title = 'Subjects';
    $show_actions = true;
    $action_buttons = [
        ['id' => 'delete-selected-subjects-header', 'label' => 'Delete Selected', 'class' => 'btn-danger', 'onclick' => "deleteSelectedSubjects()", 'icon' => 'fas fa-trash']
    ];
    $add_button = ['label' => 'Add New Subject', 'onclick' => "showAddModal('addSubjectModal','addSubjectForm')"];
    include __DIR__ . '/partials/page-header.php';
    ?>
    <div class="advanced-table-container">
        <!-- table-controls removed (search/actions moved or not needed) -->
        <div class="table-responsive table-compact" id="tableContainer">
            <table class="table data-table" id="subjects-table">
                <thead>
                    <tr>
                        <th width="40" class="text-center"><input type="checkbox" id="select-all-subjects"></th>
                        <th width="80">ID</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th width="150" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if (empty($subjects)): ?>
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <h4>No subjects found</h4>
                                    <p>No subjects match your search criteria</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubjectModal"><i class="fas fa-plus"></i> Add First Subject</button>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($subjects as $s): ?>
                            <tr>
                                <td class="text-center"><input type="checkbox" class="row-select" data-id="<?= htmlspecialchars($s['id'] ?? '') ?>"></td>
                                <td><?= htmlspecialchars($s['id'] ?? '') ?></td>
                                <td><?= htmlspecialchars($s['title'] ?? '') ?></td>
                                <td><?= htmlspecialchars($s['description'] ?? '') ?></td>
                                <td>
                                    <div class="table-actions">
                                        <button class="btn btn-sm btn-outline-primary btn-table" onclick="editSubject(<?= $s['id'] ?? 0 ?>)"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-sm btn-outline-danger btn-table" onclick="deleteSubject(<?= $s['id'] ?? 0 ?>)"><i class="fas fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages > 1): ?>
            <div class="pagination-container">
                <div class="pagination-info">Showing <?= count($subjects) ?> of <?= $total ?> subjects</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Subject Modal -->
<div class="modal fade" id="addSubjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Subject</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addSubjectForm">
                    <input type="hidden" name="id" id="subjectId" value="">
                    <div class="mb-3"><label class="form-label">Title</label><input type="text" class="form-control" name="title" required></div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3"></textarea></div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" onclick="saveSubject()">Save Subject</button></div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
<script src="../../../public/assets/js/subjects.js"></script>