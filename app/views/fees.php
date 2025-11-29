<?php
if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/views/fees.php
$fees = [];
$controllerFile = __DIR__ . '/../controllers/FeeController.php';
if (file_exists($controllerFile)) {
    require_once $controllerFile;
    $cls = 'FeeController';
    if (class_exists($cls) && method_exists($cls, 'getAll')) {
        $fees = $cls::getAll();
    }
}
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$total = count($fees);
$totalPages = 1;
?>
<?php include __DIR__ . '/partials/nav.php'; ?>
<div class="container-fluid dashboard-container fade-in">
    <div class="breadcrumb-container d-flex justify-content-between align-items-center">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=dashboard"><i class="fas fa-home"></i> Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-hand-holding-usd"></i> Fees</li>
            </ol>
        </nav>
        <button class="btn btn-primary btn-action" data-bs-toggle="modal" data-bs-target="#addFeeModal"><i class="fas fa-plus"></i> Collect Fee</button>
    </div>
    <div class="advanced-table-container">
        <!-- table-controls removed (search/actions removed) -->
        <div class="table-responsive table-compact" id="tableContainer">
            <table class="table data-table" id="fees-table">
                <thead>
                    <tr>
                        <th width="80">ID</th>
                        <th>Student</th>
                        <th>Course</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th width="150" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if (empty($fees)): ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <h4>No fee records</h4>
                                    <p>No fees match your search criteria</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFeeModal"><i class="fas fa-plus"></i> Add Fee</button>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($fees as $f): ?>
                            <tr>
                                <td><?= htmlspecialchars($f['id'] ?? '') ?></td>
                                <td><?= htmlspecialchars($f['student'] ?? '') ?></td>
                                <td><?= htmlspecialchars($f['course'] ?? '') ?></td>
                                <td><?= htmlspecialchars($f['amount'] ?? '') ?></td>
                                <td><?= htmlspecialchars($f['date'] ?? '') ?></td>
                                <td>
                                    <div class="table-actions">
                                        <button class="btn btn-sm btn-outline-info btn-table" onclick="viewFee(<?= $f['id'] ?? 0 ?>)"><i class="fas fa-eye"></i></button>
                                        <button class="btn btn-sm btn-outline-danger btn-table" onclick="deleteFee(<?= $f['id'] ?? 0 ?>)"><i class="fas fa-trash"></i></button>
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

<!-- Add Fee Modal -->
<div class="modal fade" id="addFeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Record Fee</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                        <form id="addFeeForm">
                            <input type="hidden" name="id" id="feeId" value="">
                            <input type="hidden" name="branch_id" id="feeBranchId" value="0">
                            <div class="mb-3"><label class="form-label">Student</label><input class="form-control" name="student" required></div>
                            <div class="mb-3"><label class="form-label">Course</label><input class="form-control" name="course" required></div>
                            <div class="mb-3"><label class="form-label">Amount</label><input type="number" class="form-control" name="amount" required></div>
                            <div class="mb-3"><label class="form-label">Date</label><input type="date" class="form-control" name="date" required></div>
                        </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" onclick="saveFee()">Save Fee</button></div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    try {
        const table = $('#fees-table');
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
        $('#fees-table thead').on('keyup change','tr.filters input', function(){ const idx=$(this).closest('th').index(); const val=$(this).val(); if(dataTable.column(idx).search()!==val) dataTable.column(idx).search(val).draw(); });
    } catch(e){}
    document.querySelector('.dashboard-container').classList.add('show');
});
</script>
