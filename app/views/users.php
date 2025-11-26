<?php
if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/views/users.php
$users = [];
$controllerFile = __DIR__ . '/../controllers/UserController.php';
if (file_exists($controllerFile)) {
    require_once $controllerFile;
    $cls = 'UserController';
    if (class_exists($cls) && method_exists($cls, 'getAll')) {
        $users = $cls::getAll();
    }
}
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$total = count($users);
$totalPages = 1;
?>
<?php include __DIR__ . '/partials/nav.php'; ?>
<div class="container-fluid dashboard-container fade-in">
    <div class="breadcrumb-container d-flex justify-content-between align-items-center">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=dashboard"><i class="fas fa-home"></i> Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-users"></i> Users</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-danger btn-action" id="delete-selected-users" style="display:none; margin-right:0.5rem;">
                <i class="fas fa-trash"></i> Delete Selected
            </button>
            <button class="btn btn-primary btn-action" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="fas fa-plus"></i> Add New User</button>
        </div>
    </div>
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
                                <td class="text-center"><input type="checkbox" class="row-select" data-id="<?= htmlspecialchars($u['id'] ?? '') ?>"></td>
                                <td><?= htmlspecialchars($u['id'] ?? '') ?></td>
                                <td><?= htmlspecialchars($u['name'] ?? '') ?></td>
                                <td><?= htmlspecialchars($u['role'] ?? '') ?></td>
                                <td><?= htmlspecialchars($u['branch_id'] ?? '') ?></td>
                                <td><?= htmlspecialchars($u['email'] ?? '') ?></td>
                                <td><?= htmlspecialchars($u['mobile'] ?? '') ?></td>
                                <td>
                                    <div class="table-actions">
                                        <button class="btn btn-sm btn-outline-primary btn-table" onclick="editUser(<?= $u['id'] ?? 0 ?>)"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-sm btn-outline-info btn-table" onclick="viewUser(<?= $u['id'] ?? 0 ?>)"><i class="fas fa-eye"></i></button>
                                        <button class="btn btn-sm btn-outline-danger btn-table" onclick="deleteUser(<?= $u['id'] ?? 0 ?>)"><i class="fas fa-trash"></i></button>
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
<?php include __DIR__ . '/partials/footer.php'; ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelector('.dashboard-container').classList.add('show');

        // Initialize DataTable with per-column filters
        try {
            const table = $('#users-table');
            const thead = table.find('thead');
            const filterRow = $('<tr>').addClass('filters');
            thead.find('tr').first().children().each(function() {
                const th = $('<th>');
                if ($(this).find('input[type="checkbox"]').length) {
                    th.html('');
                } else if ($(this).text().trim() === 'Actions') {
                    th.html('');
                } else {
                    th.html('<input type="text" class="form-control form-control-sm" placeholder="Search">');
                }
                filterRow.append(th);
            });
            thead.append(filterRow);

            const dataTable = table.DataTable({
                dom: 'lrtip',
                orderCellsTop: true,
                fixedHeader: true,
                pageLength: 10,
                lengthMenu: [10,25,50,100],
                responsive: true,
                columnDefs: [{ orderable: false, targets: [0, -1] }]
            });

            $('#users-table thead').on('keyup change', 'tr.filters input', function() {
                const idx = $(this).closest('th').index();
                const val = $(this).val();
                if (dataTable.column(idx).search() !== val) dataTable.column(idx).search(val).draw();
            });
        } catch (e) { /* ignore if DataTables not available */ }
    });
    function exportToExcel(){ CRUD.showLoading('tableContainer'); setTimeout(()=>{ window.location.href='?page=users&export=excel'; CRUD.hideLoading(); },800);} function printTable(){ const table=document.getElementById('users-table').cloneNode(true); const w=window.open('','_blank'); w.document.write(`<html><head><title>Users</title></head><body><h2>Users</h2>${table.outerHTML}</body></html>`); w.document.close(); w.print(); }
    function refreshTable(){ CRUD.showLoading('tableContainer'); setTimeout(()=>location.reload(),600);} 
    async function editUser(id){ CRUD.showLoading('tableContainer'); try{ const res=await CRUD.get(`api/users.php?action=get&id=${encodeURIComponent(id)}`); if(res.success&&res.data){ const u=res.data; document.getElementById('userId').value=u.id||''; document.querySelector('#addUserForm [name="name"]').value=u.name||''; document.querySelector('#addUserForm [name="email"]').value=u.email||''; document.querySelector('#addUserForm [name="mobile"]').value=u.mobile||''; document.querySelector('#addUserForm [name="role"]').value=u.role||'staff'; document.querySelector('#addUserForm [name="branch_id"]').value=u.branch_id||''; const modalEl=document.getElementById('addUserModal'); const modal=bootstrap.Modal.getOrCreateInstance(modalEl); modal.show(); } else { CRUD.toastError('User not found'); } }catch(e){ CRUD.toastError('Failed: '+e.message);} finally{ CRUD.hideLoading(); } }
    async function viewUser(id){ await editUser(id); // reuse, then set view-only
        const form=document.getElementById('addUserForm'); Array.from(form.elements).forEach(el=>el.disabled=true); const saveBtn=document.querySelector('#addUserModal .btn-primary'); if(saveBtn) saveBtn.style.display='none'; document.querySelector('#addUserModal .modal-title').innerText='View User';
    }
    async function deleteUser(id){ if(!confirm('Delete user '+id+'?')) return; CRUD.showLoading('tableContainer'); try{ const p=new URLSearchParams(); p.append('id', id); const res=await CRUD.post('api/users.php?action=delete', p); if(res.success){ CRUD.toastSuccess(res.message || 'Deleted'); refreshTable(); } else CRUD.toastError('Delete failed'); }catch(e){ CRUD.toastError('Delete failed: '+e.message);} finally{ CRUD.hideLoading(); } }
    async function saveUser(){ const form=document.getElementById('addUserForm'); const params=new FormData(form); if(!params.get('name')||!params.get('email')){ CRUD.toastError('Name and email required'); return;} const modalEl=document.getElementById('addUserModal'); CRUD.modalLoadingStart(modalEl); try{ const id=params.get('id'); const action = id ? 'update' : 'create'; const res=await CRUD.post('api/users.php?action='+action, params); if(res.success){ const modal=bootstrap.Modal.getOrCreateInstance(modalEl); modal.hide(); CRUD.toastSuccess(res.message||'Saved'); refreshTable(); } else CRUD.toastError('Save failed: '+(res.message||res.error||'Unknown')); }catch(e){ CRUD.toastError('Request failed: '+e.message);} finally{ CRUD.modalLoadingStop(modalEl); } }
</script>
<script>
// If DataTables + Buttons are initialized elsewhere, move Buttons into header-right
// No DataTables Buttons to append for users table
</script>
