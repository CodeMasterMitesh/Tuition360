<?php
require_once __DIR__ . '/init.php';
// api/attendance.php
require_once __DIR__ . '/../config/db.php';
$action = $_GET['action'] ?? '';
header('Content-Type: application/json');
if ($action === 'mark' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $branch_id = $_POST['branch_id'] ?? null;
    $entity_type = $_POST['entity_type'] ?? '';
    $entity_id = $_POST['entity_id'] ?? null;
    $date = $_POST['date'] ?? date('Y-m-d');
    $in_time = $_POST['in_time'] ?? null;
    $out_time = $_POST['out_time'] ?? null;
    $status = $_POST['status'] ?? '';
    $note = $_POST['note'] ?? '';
    $recorded_by = $_POST['recorded_by'] ?? null;
    $stmt = mysqli_prepare($conn, "INSERT INTO attendance (branch_id, entity_type, entity_id, date, in_time, out_time, status, note, recorded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'isisssssi', $branch_id, $entity_type, $entity_id, $date, $in_time, $out_time, $status, $note, $recorded_by);
    $success = mysqli_stmt_execute($stmt);
    echo json_encode(['success' => $success]);
    exit;
}
if ($action === 'get') {
    $id = intval($_GET['id'] ?? 0);
    $result = mysqli_query($conn, "SELECT * FROM attendance WHERE id = $id LIMIT 1");
    $row = mysqli_fetch_assoc($result);
    echo json_encode(['success' => (bool)$row, 'data' => $row]);
    exit;
}
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $res = mysqli_query($conn, "DELETE FROM attendance WHERE id = $id");
    echo json_encode(['success' => (bool)$res]);
    exit;
}
if ($action === 'report') {
    $branch_id = $_GET['branch_id'] ?? null;
    $from = $_GET['from'] ?? null;
    $to = $_GET['to'] ?? null;
    $sql = "SELECT * FROM attendance WHERE 1=1";
    if ($branch_id) $sql .= " AND branch_id = " . intval($branch_id);
    if ($from) $sql .= " AND date >= '" . mysqli_real_escape_string($conn, $from) . "'";
    if ($to) $sql .= " AND date <= '" . mysqli_real_escape_string($conn, $to) . "'";
    $result = mysqli_query($conn, $sql);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    echo json_encode(['success' => true, 'attendance' => $rows]);
    exit;
}
if ($action === 'calendar') {
    // Return daily lecture counts and details for calendar
    $sql = "SELECT date, branch_id, COUNT(*) as count FROM attendance WHERE entity_type IN ('faculty','employee') GROUP BY date, branch_id ORDER BY date DESC";
    $result = mysqli_query($conn, $sql);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Fetch lecture details for this date/branch
        $details = [];
        $detail_sql = "SELECT a.*, b.name as branch_name, u.name as faculty_name, u2.name as employee_name FROM attendance a LEFT JOIN branches b ON a.branch_id=b.id LEFT JOIN users u ON a.entity_id=u.id AND a.entity_type='faculty' LEFT JOIN users u2 ON a.entity_id=u2.id AND a.entity_type='employee' WHERE a.date='" . mysqli_real_escape_string($conn, $row['date']) . "' AND a.branch_id=" . intval($row['branch_id']) . " AND a.entity_type IN ('faculty','employee')";
        $detail_res = mysqli_query($conn, $detail_sql);
        while ($d = mysqli_fetch_assoc($detail_res)) $details[] = $d;
        $row['lectures'] = $details;
        $rows[] = $row;
    }
    echo json_encode(['success'=>true,'data'=>$rows]);
    exit;
}
echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>
