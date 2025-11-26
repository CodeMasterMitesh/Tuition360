<?php
if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/views/leaves.php
$leaves = [];
$controllerFile = __DIR__ . '/../controllers/LeaveController.php';
if (file_exists($controllerFile)) {
    require_once $controllerFile;
    $cls = 'LeaveController';
    if (class_exists($cls) && method_exists($cls, 'getAll')) {
        $leaves = $cls::getAll();
    }
}
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$total = count($leaves);
$totalPages = 1;
?>
<?php include __DIR__ . '/partials/nav.php'; ?>
<div class="container-fluid dashboard-container fade-in">
    <div class="breadcrumb-container d-flex justify-content-between align-items-center">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=dashboard"><i class="fas fa-home"></i> Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-calendar-alt"></i> Leaves</li>
            </ol>
        </nav>
        <button class="btn btn-primary btn-action" data-bs-toggle="modal" data-bs-target="#addLeaveModal"><i class="fas fa-plus"></i> Apply Leave</button>
    </div>
    <div class="advanced-table-container">
        <!-- table-controls removed (search/actions removed) -->
        <div class="table-responsive table-compact" id="tableContainer">
            <table class="table data-table" id="leaves-table">
                <thead>
                    <tr>
                        <th width="80">ID</th>
                        <th>Employee</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th width="150" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if (empty($leaves)): ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <h4>No leave records</h4>
                                    <p>No leaves match your search criteria</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLeaveModal"><i class="fas fa-plus"></i> Apply Leave</button>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($leaves as $l): ?>
                            <tr>
                                <td><?= htmlspecialchars($l['id'] ?? '') ?></td>
                                <td><?= htmlspecialchars($l['employee'] ?? '') ?></td>
                                <td><?= htmlspecialchars($l['from_date'] ?? '') ?></td>
                                <td><?= htmlspecialchars($l['to_date'] ?? '') ?></td>
                                <td><?= htmlspecialchars($l['type'] ?? '') ?></td>
                                <td><span class="status-badge <?= ($l['status'] ?? '') === 'approved' ? 'status-active' : 'status-inactive' ?>"><?= htmlspecialchars($l['status'] ?? 'pending') ?></span></td>
                                <td>
                                    <div class="table-actions">
                                        <button class="btn btn-sm btn-outline-info btn-table" onclick="viewLeave(<?= $l['id'] ?? 0 ?>)"><i class="fas fa-eye"></i></button>
                                        <button class="btn btn-sm btn-outline-danger btn-table" onclick="deleteLeave(<?= $l['id'] ?? 0 ?>)"><i class="fas fa-trash"></i></button>
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

<!-- Add Leave Modal -->
<div class="modal fade" id="addLeaveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Apply Leave</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <form id="addLeaveForm">
                    <input type="hidden" name="id" id="leaveId" value="">
                    <input type="hidden" name="branch_id" id="leaveBranchId" value="0">
                    <div class="mb-3"><label class="form-label">Employee</label><input class="form-control" name="employee" required></div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">From</label><input type="date" class="form-control" name="from_date" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">To</label><input type="date" class="form-control" name="to_date" required></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Type</label><select class="form-control" name="type"><option>Casual</option><option>Sick</option><option>Other</option></select></div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" onclick="saveLeave()">Apply</button></div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    try {
        const table = $('#leaves-table');
        const thead = table.find('thead');
        const filterRow = $('<tr>').addClass('filters');
        thead.find('tr').first().children().each(function(){ const th=$('<th>'); if($(this).text().trim()==='Actions') th.html(''); else th.html('<input type="text" class="form-control form-control-sm" placeholder="Search">'); filterRow.append(th); });
        thead.append(filterRow);
        const dataTable = table.DataTable({ dom: 'lrtip', orderCellsTop:true, fixedHeader:true, pageLength:10, lengthMenu:[10,25,50,100], responsive:true, columnDefs:[{orderable:false, targets:-1}] });
        $('#leaves-table thead').on('keyup change','tr.filters input', function(){ const idx=$(this).closest('th').index(); const val=$(this).val(); if(dataTable.column(idx).search()!==val) dataTable.column(idx).search(val).draw(); });
    } catch(e){}
    document.querySelector('.dashboard-container').classList.add('show');
});
</script>
