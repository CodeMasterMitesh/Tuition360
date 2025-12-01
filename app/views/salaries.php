<?php

use CampusLite\Controllers\SalaryController;

if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/views/salaries.php
$salaries = SalaryController::getAll();
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$total = count($salaries);
$totalPages = 1;
?>

<div class="container-fluid dashboard-container fade-in">
    <div class="breadcrumb-container d-flex justify-content-between align-items-center">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=dashboard"><i class="fas fa-home"></i> Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-money-check-alt"></i> Salaries</li>
            </ol>
        </nav>
        <button class="btn btn-primary btn-action" data-bs-toggle="modal" data-bs-target="#generateSalaryModal"><i class="fas fa-plus"></i> Generate Salary</button>
    </div>
    <div class="advanced-table-container">
        <!-- table-controls removed (search/actions removed) -->
        <div class="table-responsive table-compact" id="tableContainer">
            <table class="table data-table" id="salaries-table">
                <thead>
                    <tr>
                        <th width="80">ID</th>
                        <th>Employee</th>
                        <th>Month</th>
                        <th>Gross</th>
                        <th>Net</th>
                        <th width="150" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if (empty($salaries)): ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <h4>No salary records</h4>
                                    <p>No salaries match your search criteria</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#generateSalaryModal"><i class="fas fa-plus"></i> Generate Salary</button>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($salaries as $s): ?>
                            <tr>
                                <td><?= htmlspecialchars($s['id'] ?? '') ?></td>
                                <td><?= htmlspecialchars($s['employee'] ?? '') ?></td>
                                <td><?= htmlspecialchars($s['month'] ?? '') ?></td>
                                <td><?= htmlspecialchars($s['gross'] ?? '') ?></td>
                                <td><?= htmlspecialchars($s['net'] ?? '') ?></td>
                                <td>
                                    <div class="table-actions">
                                        <button class="btn btn-sm btn-outline-info btn-table" onclick="viewSalary(<?= $s['id'] ?? 0 ?>)"><i class="fas fa-eye"></i></button>
                                        <button class="btn btn-sm btn-outline-danger btn-table" onclick="deleteSalary(<?= $s['id'] ?? 0 ?>)"><i class="fas fa-trash"></i></button>
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

<!-- Generate Salary Modal -->
<div class="modal fade" id="generateSalaryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Generate Salary</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <form id="generateSalaryForm">
                    <input type="hidden" name="id" id="salaryId" value="">
                    <div class="mb-3"><label class="form-label">Month</label><input type="month" class="form-control" name="month" required></div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" onclick="generateSalary()">Generate</button></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    try {
        const table = $('#salaries-table');
        const thead = table.find('thead');
        const filterRow = $('<tr>').addClass('filters');
        thead.find('tr').first().children().each(function(){ const th=$('<th>'); if($(this).text().trim()==='Actions') th.html(''); else th.html('<input type="text" class="form-control form-control-sm" placeholder="Search">'); filterRow.append(th); });
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
        $('#salaries-table thead').on('keyup change','tr.filters input', function(){ const idx=$(this).closest('th').index(); const val=$(this).val(); if(dataTable.column(idx).search()!==val) dataTable.column(idx).search(val).draw(); });
    } catch(e){}
    document.querySelector('.dashboard-container').classList.add('show');
});
</script>
