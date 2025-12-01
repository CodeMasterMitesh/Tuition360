<?php

use CampusLite\Controllers\UserController;

if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/views/users.php
$users = UserController::getAll();
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$total = count($users);
$totalPages = 1;
?>

<div class="container-fluid dashboard-container fade-in">
    <?php
    $page_icon = 'fas fa-users';
    $page_title = 'Users';
    $show_actions = true;
    $action_buttons = [
        ['label' => 'Export Excel', 'class' => 'btn-primary', 'onclick' => 'exportToExcel()', 'icon' => 'fas fa-file-excel'],
        ['id' => 'delete-selected-users-header', 'label' => 'Delete Selected', 'class' => 'btn-danger', 'onclick' => "deleteSelectedUsers()", 'icon' => 'fas fa-trash']
    ];
    $add_button = ['label' => 'Add New User', 'modal' => 'addUserModal', 'form' => 'addUserForm'];
    include __DIR__ . '/partials/page-header.php';
    ?>
    <div class="advanced-table-container">
        <!-- table-controls removed (no search/actions required) -->
        <div class="table-responsive table-compact" id="tableContainer">
            <table class="table data-table" id="users-table">
                <thead>
                    <tr>
                        <th width="40" class="text-center"><input type="checkbox" id="select-all-users"></th>
                        <th width="80">ID</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Branch</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th width="150" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <h4>No users found</h4>
                                    <p>No users match your search criteria</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="fas fa-plus"></i> Add First User</button>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td class="text-center" data-label="Select"><input type="checkbox" class="row-select" data-id="<?= htmlspecialchars($u['id'] ?? '') ?>"></td>
                                <td data-label="ID"><?= htmlspecialchars($u['id'] ?? '') ?></td>
                                <td data-label="Name"><?= htmlspecialchars($u['name'] ?? '') ?></td>
                                <td data-label="Role"><?= htmlspecialchars($u['role'] ?? '') ?></td>
                                <td data-label="Branch"><?= htmlspecialchars($u['branch_id'] ?? '') ?></td>
                                <td data-label="Email"><?= htmlspecialchars($u['email'] ?? '') ?></td>
                                <td data-label="Mobile"><?= htmlspecialchars($u['mobile'] ?? '') ?></td>
                                <td data-label="Actions">
                                    <div class="table-actions">
                                        <button class="btn btn-sm btn-outline-primary btn-table" onclick="editUser(<?= $u['id'] ?? 0 ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-sm btn-outline-info btn-table" onclick="viewUser(<?= $u['id'] ?? 0 ?>)" title="View"><i class="fas fa-eye"></i></button>
                                        <button class="btn btn-sm btn-outline-danger btn-table" onclick="deleteUser(<?= $u['id'] ?? 0 ?>)" title="Delete"><i class="fas fa-trash"></i></button>
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

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Add New User</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <form id="addUserForm">
                    <input type="hidden" name="id" id="userId" value="">
                    <div class="mb-3"><label class="form-label">Name</label><input class="form-control" name="name" required></div>
                    <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" name="email" required></div>
                    <div class="mb-3"><label class="form-label">Mobile</label><input class="form-control" name="mobile"></div>
                    <div class="mb-3"><label class="form-label">Role</label><select class="form-control" name="role"><option>admin</option><option>staff</option></select></div>
                    <div class="mb-3"><label class="form-label">Branch</label><input class="form-control" name="branch_id"></div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" onclick="saveUser()">Save User</button></div>
        </div>
    </div>
</div>

<script src="/public/assets/js/users.js"></script>
