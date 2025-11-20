<?php
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
echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>
