<?php
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
        <button class="btn btn-primary btn-action" data-bs-toggle="modal" data-bs-target="#addFacultyModal">
            <i class="fas fa-plus"></i> Add New Faculty
        </button>
    </div>
    <!-- Table Container -->
    <div class="advanced-table-container">
        <!-- Table Controls -->
        <div class="table-controls">
            <div class="table-header">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="form-control" id="searchInput" placeholder="Search faculty..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="action-buttons">
                    <button class="btn btn-success btn-action" onclick="exportToExcel()">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </button>
                    <button class="btn btn-secondary btn-action" onclick="printTable()">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <button class="btn btn-info btn-action" onclick="refreshTable()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>
        </div>
        <!-- Table -->
        <div class="table-responsive position-relative" id="tableContainer">
            <table class="table data-table" id="faculty-table">
                <thead>
                    <tr>
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
                            <td colspan="7">
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
                                <td><?= htmlspecialchars($f['id'] ?? '') ?></td>
                                <td><?= htmlspecialchars($f['name'] ?? '') ?></td>
                                <td><?= htmlspecialchars($f['email'] ?? '') ?></td>
                                <td><?= htmlspecialchars($f['phone'] ?? '') ?></td>
                                <td><?= htmlspecialchars($f['branch'] ?? '') ?></td>
                                <td>
                                    <?php if (isset($f['status'])): ?>
                                        <span class="status-badge <?= $f['status'] === 'active' ? 'status-active' : 'status-inactive' ?>">
                                            <?= ucfirst($f['status']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge status-inactive">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
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
                <h5 class="modal-title">Add New Faculty</h5>
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
                        <input type="tel" class="form-control" name="phone" required>
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
                <button type="button" class="btn btn-primary" onclick="saveFaculty()">Save Faculty</button>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
<script>
    // Client-side search functionality with debounce
    let searchTimeout;
    document.getElementById('searchInput').addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const searchValue = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#faculty-table tbody tr');
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(searchValue) ? '' : 'none';
            });
        }, 200);
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
    async function editFaculty(id){ CRUD.showLoading('tableContainer'); try{ const res = await CRUD.get(`api/faculty.php?action=get&id=${encodeURIComponent(id)}`); if(res.success && res.data){ const f = res.data; document.getElementById('facultyId').value = f.id||''; document.querySelector('#addFacultyForm [name="name"]').value = f.name||''; document.querySelector('#addFacultyForm [name="email"]').value = f.email||''; document.querySelector('#addFacultyForm [name="phone"]').value = f.mobile||f.phone||''; document.getElementById('facultyBranch').value = f.branch_id||0; const modalEl = document.getElementById('addFacultyModal'); const modal = bootstrap.Modal.getOrCreateInstance(modalEl); modal.show(); } else alert('Faculty not found'); }catch(e){ alert('Failed: '+e.message);} finally{ CRUD.hideLoading(); } }
    async function viewFaculty(id){ await editFaculty(id); const form=document.getElementById('addFacultyForm'); Array.from(form.elements).forEach(el=>el.disabled=true); const saveBtn=document.querySelector('#addFacultyModal .btn-primary'); if(saveBtn) saveBtn.style.display='none'; document.querySelector('#addFacultyModal .modal-title').innerText='View Faculty'; }
    async function deleteFaculty(id){ if(!confirm('Delete faculty '+id+'?')) return; CRUD.showLoading('tableContainer'); try{ const p=new URLSearchParams(); p.append('id', id); const res = await CRUD.post('api/faculty.php?action=delete', p); if(res.success) refreshTable(); else alert('Delete failed'); }catch(e){ alert('Delete failed: '+e.message);} finally{ CRUD.hideLoading(); } }
    async function saveFaculty(){ const form=document.getElementById('addFacultyForm'); const params=new FormData(form); if(!params.get('name')){ alert('Name required'); return;} CRUD.showLoading('tableContainer'); try{ const id=params.get('id'); const action = id ? 'update' : 'create'; const res = await CRUD.post('api/faculty.php?action='+action, params); if(res.success){ const modalEl=document.getElementById('addFacultyModal'); const modal=bootstrap.Modal.getOrCreateInstance(modalEl); modal.hide(); refreshTable(); } else alert('Save failed: '+(res.message||res.error||'Unknown')); }catch(e){ alert('Request failed: '+e.message);} finally{ CRUD.hideLoading(); } }
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'f') {
            e.preventDefault();
            document.getElementById('searchInput').focus();
        }
        if (e.ctrlKey && e.key === 'n') {
            e.preventDefault();
            document.querySelector('[data-bs-target="#addFacultyModal"]').click();
        }
    });
</script>
