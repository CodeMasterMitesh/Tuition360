<?php
if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/views/faculty.php
$faculty = [];
$controllerFile = __DIR__ . '/../controllers/FacultyController.php';
if (file_exists($controllerFile)) {
    require_once $controllerFile;
    $cls = 'FacultyController';
    if (class_exists($cls) && method_exists($cls, 'getAll')) {
        $faculty = $cls::getAll();
        // echo '<pre>'.print_r($faculty,true).'</pre>';
    }
}
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$totalFaculty = count($faculty);
$totalPages = 1;
// load branches
$branchFile = __DIR__ . '/../controllers/BranchController.php';
$branches = [];
if (file_exists($branchFile)) {
    require_once $branchFile;
    if (class_exists('BranchController') && method_exists('BranchController','getAll')) $branches = BranchController::getAll();
}
// build branch id => name map for display
$branchMap = [];
foreach ($branches as $b) {
    $branchMap[intval($b['id'])] = $b['name'];
}
?>
<?php include __DIR__ . '/partials/nav.php'; ?>
<div class="container-fluid dashboard-container fade-in">
    <!-- Breadcrumbs -->
    <div class="breadcrumb-container d-flex justify-content-between align-items-center">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=dashboard"><i class="fas fa-home"></i> Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-chalkboard-teacher"></i> Faculty</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-danger btn-action" id="delete-selected-faculty" style="display:none; margin-right:0.5rem;">
                <i class="fas fa-trash"></i> Delete Selected
            </button>
            <button class="btn btn-primary btn-action" data-bs-toggle="modal" data-bs-target="#addFacultyModal" onclick="prepareAddFaculty()">
                <i class="fas fa-plus"></i> Add New Faculty
            </button>
        </div>
    </div>
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
<?php include __DIR__ . '/partials/footer.php'; ?>
<script>
    // Initialize DataTables with per-column filters (faculty)
    document.addEventListener('DOMContentLoaded', function() {
        try {
            const table = $('#faculty-table');
            const thead = table.find('thead');
            const filterRow = $('<tr>').addClass('filters');
            thead.find('tr').first().children().each(function() {
                const th = $('<th>');
                if ($(this).find('input[type="checkbox"]').length) {
                    th.html('');
                } else if ($(this).text().trim() === 'Actions') th.html(''); else th.html('<input type="text" class="form-control form-control-sm" placeholder="Search">');
                filterRow.append(th);
            });
            thead.append(filterRow);

            const dataTable = table.DataTable({ dom: 'lrtip', orderCellsTop:true, fixedHeader:true, pageLength:10, lengthMenu:[10,25,50,100], responsive:true, columnDefs:[{orderable:false, targets:[0,-1]}] });
            $('#faculty-table thead').on('keyup change', 'tr.filters input', function(){ const idx = $(this).closest('th').index(); const val = $(this).val(); if (dataTable.column(idx).search() !== val) dataTable.column(idx).search(val).draw(); });
        } catch(e) {}
        document.querySelector('.dashboard-container').classList.add('show');
    });
    // Smooth fade-in effect for page content
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelector('.dashboard-container').classList.add('show');
    });
    // Export to Excel
    function exportToExcel() {
        showLoading();
        setTimeout(() => {
            window.location.href = '?page=faculty&export=excel';
            hideLoading();
        }, 1000);
    }
    // Print table
    function printTable() {
        const table = document.getElementById('faculty-table').cloneNode(true);
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Faculty Report</title>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f8f9fa; }
                    </style>
                </head>
                <body>
                    <h2>Faculty Report</h2>
                    ${table.outerHTML}
                    <p>Generated on: ${new Date().toLocaleDateString()}</p>
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
    }
    // Refresh table
    function refreshTable() {
        showLoading();
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    }
    // Loading states
    function showLoading() {
        const container = document.getElementById('tableContainer');
        const overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.innerHTML = `
            <div class="spinner-border text-primary spinner" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        `;
        container.style.position = 'relative';
        container.appendChild(overlay);
    }
    function hideLoading() {
        const overlay = document.querySelector('.loading-overlay');
        if (overlay) overlay.remove();
    }
    // Faculty management functions
    
    function prepareAddFaculty() {
        // Clear form for adding new faculty
        try {
            const form = document.getElementById('addFacultyForm');
            form.reset();
            const idEl = document.getElementById('facultyId');
            if (idEl) idEl.value = '';
            const title = document.getElementById('facultyModalTitle');
            if (title) title.innerText = 'Add New Faculty';
            const saveBtn = document.getElementById('saveFacultyBtn');
            if (saveBtn) saveBtn.innerText = 'Save Faculty';
        } catch (e) { /* ignore */ }
    }

    async function editFaculty(id){
        CRUD.showLoading('tableContainer');
        try {
            const res = await CRUD.get(`api/faculty.php?action=get&id=${encodeURIComponent(id)}`);
            if (res.success && res.data) {
                const f = res.data;
                document.getElementById('facultyId').value = f.id || '';
                document.querySelector('#addFacultyForm [name="name"]').value = f.name || '';
                document.querySelector('#addFacultyForm [name="email"]').value = f.email || '';
                document.querySelector('#addFacultyForm [name="mobile"]').value = f.mobile || f.phone || '';
                document.getElementById('facultyBranch').value = f.branch_id || 0;
                // set modal to edit mode
                const title = document.getElementById('facultyModalTitle');
                if (title) title.innerText = 'Edit Faculty';
                const saveBtn = document.getElementById('saveFacultyBtn');
                if (saveBtn) saveBtn.innerText = 'Update Faculty';
                const modalEl = document.getElementById('addFacultyModal');
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            } else {
                alert('Faculty not found');
            }
        } catch (e) { alert('Failed: ' + e.message); }
        finally { CRUD.hideLoading(); }
    }
    async function viewFaculty(id){ await editFaculty(id); const form=document.getElementById('addFacultyForm'); Array.from(form.elements).forEach(el=>el.disabled=true); const saveBtn=document.querySelector('#addFacultyModal .btn-primary'); if(saveBtn) saveBtn.style.display='none'; document.querySelector('#addFacultyModal .modal-title').innerText='View Faculty'; }
    async function deleteFaculty(id){ if(!confirm('Delete faculty '+id+'?')) return; CRUD.showLoading('tableContainer'); try{ const p=new URLSearchParams(); p.append('id', id); const res = await CRUD.post('api/faculty.php?action=delete', p); if(res.success) refreshTable(); else alert('Delete failed'); }catch(e){ alert('Delete failed: '+e.message);} finally{ CRUD.hideLoading(); } }
    async function saveFaculty(){ const form=document.getElementById('addFacultyForm'); const params=new FormData(form); if(!params.get('name')){ alert('Name required'); return;} CRUD.showLoading('tableContainer'); try{ const id=params.get('id'); const action = id ? 'update' : 'create'; const res = await CRUD.post('api/faculty.php?action='+action, params); if(res.success){ const modalEl=document.getElementById('addFacultyModal'); const modal=bootstrap.Modal.getOrCreateInstance(modalEl); modal.hide(); refreshTable(); } else alert('Save failed: '+(res.message||res.error||'Unknown')); }catch(e){ alert('Request failed: '+e.message);} finally{ CRUD.hideLoading(); } }
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'f') {
            e.preventDefault();
            const si = document.getElementById('searchInput'); if (si) si.focus();
        }
        if (e.ctrlKey && e.key === 'n') {
            e.preventDefault();
            document.querySelector('[data-bs-target="#addFacultyModal"]').click();
        }
    });
</script>
    <script>
    // No additional DataTables buttons to append
    </script>
