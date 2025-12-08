<?php

use CampusLite\Controllers\{BatchAssignmentController, BatchController, StudentController, SubjectController, UserController};

if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/views/batch_assignments.php
$assignments = BatchAssignmentController::getAll();
$batches = BatchController::getAll();
$students = StudentController::getAll();
$subjects = SubjectController::getAll();
$users = UserController::getAll();
$batchMap = []; foreach ($batches as $b) $batchMap[$b['id']] = $b['title'] ?? $b['name'] ?? ('Batch '.$b['id']);
$userMap = []; foreach ($users as $u) $userMap[$u['id']] = $u['name'] ?? $u['email'] ?? ('User '.$u['id']);
$studentMap = []; foreach ($students as $s) $studentMap[$s['id']] = $s['name'] ?? $s['email'] ?? ('Student '.$s['id']);
$subjectMap = []; foreach ($subjects as $s) $subjectMap[$s['id']] = $s['title'] ?? $s['code'] ?? ('Subject '.$s['id']);

// Build student lookup from junction table for each assignment
// JOIN with students table to get names directly from DB
$assignmentStudents = [];
if (!empty($GLOBALS['conn'])) {
    $res = mysqli_query($GLOBALS['conn'], "
        SELECT bas.assignment_id, bas.student_id, s.name as student_name, s.email
        FROM batch_assignment_students bas
        LEFT JOIN students s ON s.id = bas.student_id
        ORDER BY bas.assignment_id, bas.student_id
    ");
    while ($r = mysqli_fetch_assoc($res)) {
        if (!isset($assignmentStudents[$r['assignment_id']])) $assignmentStudents[$r['assignment_id']] = [];
        $assignmentStudents[$r['assignment_id']][] = [
            'id' => intval($r['student_id']),
            'name' => $r['student_name'] ?? $r['email'] ?? ('Student ' . $r['student_id'])
        ];
    }
}
?>

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
                        <th>Students</th>
                        <th>Faculty / Employee</th>
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
                            <td data-label="Students">
                                <?php
                                    $role = $a['role'] ?? '';
                                    if ($role === 'student') {
                                        // Show students from junction table (now with direct DB names)
                                        $students_data = $assignmentStudents[$a['id']] ?? [];
                                        if (!empty($students_data)) {
                                            $names = [];
                                            foreach ($students_data as $stu) {
                                                $names[] = htmlspecialchars($stu['name']);
                                            }
                                            echo implode(', ', $names);
                                        } else {
                                            echo '-';
                                        }
                                    } else {
                                        echo '-';
                                    }
                                ?>
                            </td>
                            <td data-label="Faculty / Employee">
                                <?php
                                    $role = $a['role'] ?? '';
                                    if ($role === 'faculty' || $role === 'employee') {
                                        // Show assigned faculty or employee
                                        if (!empty($a['user_id'])) {
                                            echo htmlspecialchars($userMap[$a['user_id']] ?? ('#'.$a['user_id']));
                                        } else {
                                            echo '-';
                                        }
                                    } else {
                                        echo '-';
                                    }
                                ?>
                            </td>
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
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title" id="assignmentModalTitle">Add Assignment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="addAssignmentForm">
                    <input type="hidden" name="id" id="assignmentId" value="">
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <label class="form-label">Batch</label>
                            <select class="form-control" name="batch_id" id="assignmentBatch" required>
                                <option value="0">-- Select Batch --</option>
                                <?php foreach ($batches as $b): ?>
                                    <option value="<?= intval($b['id']) ?>" data-course="<?= intval($b['course_id'] ?? 0) ?>"><?= htmlspecialchars($b['title'] ?? $b['name'] ?? ('Batch '.$b['id'])) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="form-label">User</label>
                            <select class="form-control" name="user_id" id="assignmentUser">
                                <option value="0">-- Select User (for faculty/employee) --</option>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?= intval($u['id']) ?>"><?= htmlspecialchars($u['name'] ?? $u['email'] ?? ('User '.$u['id'])) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Or select multiple <strong>students</strong> below to enroll them into the batch.</small>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="form-label">Role</label>
                            <select class="form-control" name="role" id="assignmentRole">
                                <option value="faculty">Faculty</option>
                                <option value="employee">Employee</option>
                                <option value="student">Student</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-2"><label class="form-label">Students</label>
                        <div id="assignmentStudentsContainer">
                            <!-- dynamic student rows will be inserted here -->
                        </div>
                        <div class="mt-2">
                            <button type="button" id="addStudentRowBtn" class="btn btn-sm btn-outline-primary">Add Student</button>
                            <small class="text-muted ms-2">Search & add multiple students. Each added student will be submitted as <code>students_ids[]</code>.</small>
                        </div>
                    </div>
                    <div class="mb-2"><label class="form-label">Subjects (from selected batch's course)</label>
                        <select class="form-control" name="subjects[]" id="assignmentSubjects" multiple size="6" disabled></select>
                        <small class="text-muted d-block mt-2"><em>Subjects are loaded for reference but not currently saved with assignments.</em></small>
                    </div>
                    
                    <div class="mb-2"><label class="form-label">Assigned At</label>
                        <input type="datetime-local" class="form-control" name="assigned_at" id="assignmentAt">
                    </div>
                </form>
            </div>
            <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button id="assignmentSaveBtn" class="btn btn-primary" onclick="saveAssignment()">Save</button></div>
        </div>
    </div>
</div>


<script>
// inject subject map for client-side lookup
window.__subjectMap = <?= json_encode($subjectMap ?? []) ?>;
// inject student map for client-side lookup (id -> name)
window.__studentMap = <?= json_encode($studentMap ?? []) ?>;
</script>
<script src="/public/assets/js/batch_assignments.js"></script>