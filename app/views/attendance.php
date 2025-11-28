<?php
if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/views/attendance.php
$attendance = [];
$controllerFile = __DIR__ . '/../controllers/AttendanceController.php';
if (file_exists($controllerFile)) {
    require_once $controllerFile;
    $cls = 'AttendanceController';
    if (class_exists($cls) && method_exists($cls, 'getAll')) {
        $attendance = $cls::getAll();
    }
}
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$totalAttendance = count($attendance);
$totalPages = 1;
?>
<?php include __DIR__ . '/partials/nav.php'; ?>
<div class="container-fluid dashboard-container fade-in">
    <?php
    $page_icon = 'fas fa-calendar-check';
    $page_title = 'Attendance';
    $show_actions = true;
    $action_buttons = [
        ['label' => 'Export Excel', 'class' => 'btn-primary', 'onclick' => "exportToExcel('attendance')", 'icon' => 'fas fa-file-excel'],
    ];
    $add_button = ['label' => 'Mark Attendance', 'modal' => 'addAttendanceModal', 'form' => 'addAttendanceForm'];
    include __DIR__ . '/partials/page-header.php';
    ?>
    <!-- Table Container -->
    <div class="advanced-table-container">
        <!-- table-controls removed (search/actions removed) -->
        <!-- Table -->
        <div class="table-responsive" id="tableContainer">
            <table class="table data-table" id="attendance-table">
                <thead>
                    <tr>
                        <th width="80">ID</th>
                        <th>Student</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th width="150" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if (empty($attendance)): ?>
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <h4>No attendance records found</h4>
                                    <p>No attendance records match your search criteria</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAttendanceModal">
                                        <i class="fas fa-plus"></i> Mark First Attendance
                                    </button>
                                <?php include __DIR__ . '/partials/footer.php'; ?>
                                <script src="/public/assets/js/attendance.js"></script>
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
    // Attendance management functions
    async function editAttendance(id) {
        showLoading();
        try {
            const res = await CRUD.get(`api/attendance.php?action=get&id=${encodeURIComponent(id)}`);
            if (res.success && res.data) {
                const a = res.data;
                document.getElementById('attendanceId').value = a.id || '';
                document.querySelector('#addAttendanceForm [name="student"]').value = a.student || '';
                document.querySelector('#addAttendanceForm [name="date"]').value = a.date || '';
                document.querySelector('#addAttendanceForm [name="status"]').value = a.status || 'present';
                document.getElementById('attendanceBranchId').value = a.branch_id ?? 0;
                setModalModeAttendance('edit');
                const modalEl = document.getElementById('addAttendanceModal');
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            } else {
                alert('Record not found');
            }
        } catch (e) { alert('Failed to load record: ' + e.message); }
        finally { CRUD.hideLoading(); }
    }

    async function viewAttendance(id) {
        showLoading();
        try {
            const res = await CRUD.get(`api/attendance.php?action=get&id=${encodeURIComponent(id)}`);
            if (res.success && res.data) {
                const a = res.data;
                document.getElementById('attendanceId').value = a.id || '';
                document.querySelector('#addAttendanceForm [name="student"]').value = a.student || '';
                document.querySelector('#addAttendanceForm [name="date"]').value = a.date || '';
                document.querySelector('#addAttendanceForm [name="status"]').value = a.status || 'present';
                document.getElementById('attendanceBranchId').value = a.branch_id ?? 0;
                setModalModeAttendance('view');
                const modalEl = document.getElementById('addAttendanceModal');
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            } else {
                alert('Record not found');
            }
        } catch (e) { alert('Failed to load record: ' + e.message); }
        finally { CRUD.hideLoading(); }
    }

    async function deleteAttendance(id) {
        if (!confirm('Are you sure you want to delete this attendance record?')) return;
        CRUD.showLoading('tableContainer');
        try {
            const params = new URLSearchParams(); params.append('id', id);
            const res = await CRUD.post('api/attendance.php?action=delete', params);
            if (res.success) refreshTable(); else alert('Delete failed');
        } catch (e) { alert('Delete request failed: ' + e.message); }
        finally { CRUD.hideLoading(); }
    }

    function setModalModeAttendance(mode) {
        const form = document.getElementById('addAttendanceForm');
        const saveBtn = document.querySelector('#addAttendanceModal .btn-primary');
        if (mode === 'view') {
            Array.from(form.elements).forEach(el => el.disabled = true);
            saveBtn.style.display = 'none';
            document.querySelector('#addAttendanceModal .modal-title').innerText = 'View Attendance';
        } else {
            Array.from(form.elements).forEach(el => el.disabled = false);
            saveBtn.style.display = '';
            document.querySelector('#addAttendanceModal .modal-title').innerText = mode === 'edit' ? 'Edit Attendance' : 'Mark Attendance';
        }
    }

    async function saveAttendance() {
        const form = document.getElementById('addAttendanceForm');
        const params = new FormData(form);
        if (!params.get('student')) { alert('Student is required'); return; }
        CRUD.showLoading('tableContainer');
        try {
            const res = await CRUD.post('api/attendance.php?action=mark', params);
            if (res.success) {
                const modalEl = document.getElementById('addAttendanceModal');
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.hide();
                refreshTable();
            } else {
                alert('Save failed: ' + (res.message || res.error || 'Unknown'));
            }
        } catch (e) { alert('Save request failed: ' + e.message); }
        finally { CRUD.hideLoading(); }
    }
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'f') {
            e.preventDefault();
            const si = document.getElementById('searchInput'); if (si) si.focus();
        }
        if (e.ctrlKey && e.key === 'n') {
            e.preventDefault();
            document.querySelector('[data-bs-target="#addAttendanceModal"]').click();
        }
    });
</script>
