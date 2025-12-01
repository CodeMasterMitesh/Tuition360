<?php

use CampusLite\Controllers\{BranchController, EmployeeController};

if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/views/employee.php
$employees = EmployeeController::getAll();
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$totalEmployees = count($employees);
$totalPages = 1;
$branches = BranchController::getAll();
?>

<div class="container-fluid dashboard-container fade-in">
    <!-- Breadcrumbs -->
    <div class="breadcrumb-container d-flex justify-content-between align-items-center">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=dashboard"><i class="fas fa-home"></i> Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-user-tie"></i> Employee</li>
            </ol>
        </nav>
        <button class="btn btn-primary btn-action" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
            <i class="fas fa-plus"></i> Add New Employee
        </button>
    </div>
    <!-- Table Container -->
    <div class="advanced-table-container">
        <!-- Table Controls -->
        <!-- table-controls removed (search/actions removed) -->
        <!-- Table -->
        <div class="table-responsive table-compact" id="tableContainer">
            <table class="table data-table" id="employee-table">
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
                    <?php if (empty($employees)): ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <h4>No employees found</h4>
                                    <p>No employees match your search criteria</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                                        <i class="fas fa-plus"></i> Add First Employee
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($employees as $employee): ?>
                            <tr>
                                <td><?= htmlspecialchars($employee['id'] ?? '') ?></td>
                                <td><?= htmlspecialchars($employee['name'] ?? '') ?></td>
                                <td><?= htmlspecialchars($employee['email'] ?? '') ?></td>
                                <td><?= htmlspecialchars($employee['phone'] ?? '') ?></td>
                                <td><?= htmlspecialchars($employee['branch'] ?? '') ?></td>
                                <td>
                                    <?php if (isset($employee['status'])): ?>
                                        <span class="status-badge <?= $employee['status'] === 'active' ? 'status-active' : 'status-inactive' ?>">
                                            <?= ucfirst($employee['status']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge status-inactive">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <button class="btn btn-sm btn-outline-primary btn-table" onclick="editEmployee(<?= $employee['id'] ?? 0 ?>)" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-info btn-table" onclick="viewEmployee(<?= $employee['id'] ?? 0 ?>)" title="View">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger btn-table" onclick="deleteEmployee(<?= $employee['id'] ?? 0 ?>)" title="Delete">
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
                Showing <?= count($employees) ?> of <?= $totalEmployees ?> employees
            </div>
            <nav aria-label="Page navigation">
                <ul class="pagination mb-0">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=employee&page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=employee&page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=employee&page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">
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
<!-- Add Employee Modal -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addEmployeeForm">
                    <input type="hidden" name="id" id="employeeId" value="">
                    <div class="mb-3">
                        <label class="form-label">Employee Name</label>
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
                        <label class="form-label">Branch</label>
                        <select class="form-control" name="branch_id" id="employeeBranch" required>
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
                <button type="button" class="btn btn-primary" onclick="saveEmployee()">Save Employee</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    try {
        const table = $('#employee-table');
        const thead = table.find('thead');
        const filterRow = $('<tr>').addClass('filters');
        thead.find('tr').first().children().each(function() {
            const th = $('<th>');
            if ($(this).text().trim() === 'Actions') th.html(''); else th.html('<input type="text" class="form-control form-control-sm" placeholder="Search">');
            filterRow.append(th);
        });
        thead.append(filterRow);
        const dataTable = table.DataTable({ 
            dom: 'lrtip', 
            orderCellsTop:true, 
            fixedHeader:true, 
            pageLength:10, 
            lengthMenu:[10,25,50,100], 
            responsive: {
                details: {
                    type: 'column',
                    target: 'tr'
                }
            },
            columnDefs:[
                {orderable:false, targets:-1},
                {responsivePriority: 1, targets: 0},
                {responsivePriority: 2, targets: -1}
            ]
        });
        $('#employee-table thead').on('keyup change','tr.filters input', function(){ const idx=$(this).closest('th').index(); const val=$(this).val(); if(dataTable.column(idx).search()!==val) dataTable.column(idx).search(val).draw(); });
    } catch(e){}
    document.querySelector('.dashboard-container').classList.add('show');
});
</script>
    // Export to Excel
    function exportToExcel() {
        showLoading();
        setTimeout(() => {
            window.location.href = '?page=employee&export=excel';
            hideLoading();
        }, 1000);
    }
    // Print table
    function printTable() {
        const table = document.getElementById('employee-table').cloneNode(true);
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Employee Report</title>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f8f9fa; }
                    </style>
                </head>
                <body>
                    <h2>Employee Report</h2>
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
    // Employee management functions
    async function editEmployee(id){ CRUD.showLoading('tableContainer'); try{ const res=await CRUD.get(`api/employee.php?action=get&id=${encodeURIComponent(id)}`); if(res.success&&res.data){ const e=res.data; document.getElementById('employeeId').value=e.id||''; document.querySelector('#addEmployeeForm [name="name"]').value=e.name||''; document.querySelector('#addEmployeeForm [name="email"]').value=e.email||''; document.querySelector('#addEmployeeForm [name="phone"]').value=e.mobile||e.phone||''; document.getElementById('employeeBranch').value=e.branch_id||0; const modalEl=document.getElementById('addEmployeeModal'); const modal=bootstrap.Modal.getOrCreateInstance(modalEl); modal.show(); } else alert('Employee not found'); }catch(e){ alert('Failed: '+e.message);} finally{ CRUD.hideLoading(); } }
    async function viewEmployee(id){ await editEmployee(id); const form=document.getElementById('addEmployeeForm'); Array.from(form.elements).forEach(el=>el.disabled=true); const saveBtn=document.querySelector('#addEmployeeModal .btn-primary'); if(saveBtn) saveBtn.style.display='none'; document.querySelector('#addEmployeeModal .modal-title').innerText='View Employee'; }
    async function deleteEmployee(id){ if(!confirm('Delete employee '+id+'?')) return; CRUD.showLoading('tableContainer'); try{ const p=new URLSearchParams(); p.append('id', id); const res=await CRUD.post('api/employee.php?action=delete', p); if(res.success) refreshTable(); else alert('Delete failed'); }catch(e){ alert('Delete failed: '+e.message);} finally{ CRUD.hideLoading(); } }
    async function saveEmployee(){ const form=document.getElementById('addEmployeeForm'); const params=new FormData(form); if(!params.get('name')){ alert('Name required'); return;} CRUD.showLoading('tableContainer'); try{ const id=params.get('id'); const action = id ? 'update' : 'create'; const res=await CRUD.post('api/employee.php?action='+action, params); if(res.success){ const modalEl=document.getElementById('addEmployeeModal'); const modal=bootstrap.Modal.getOrCreateInstance(modalEl); modal.hide(); refreshTable(); } else alert('Save failed: '+(res.message||res.error||'Unknown')); }catch(e){ alert('Request failed: '+e.message);} finally{ CRUD.hideLoading(); } }
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'f') {
            e.preventDefault();
            const si = document.getElementById('searchInput'); if (si) si.focus();
        }
        if (e.ctrlKey && e.key === 'n') {
            e.preventDefault();
            document.querySelector('[data-bs-target="#addEmployeeModal"]').click();
        }
    });
</script>
