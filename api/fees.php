<?php
require_once __DIR__ . '/init.php';
// api/fees.php
require_once __DIR__ . '/../config/db.php';
$action = $_GET['action'] ?? '';
header('Content-Type: application/json');
if ($action === 'pay' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $branch_id = $_POST['branch_id'] ?? null;
    $student_id = $_POST['student_id'] ?? null;
    $enrollment_id = $_POST['enrollment_id'] ?? null;
    $amount = $_POST['amount'] ?? 0;
    $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
    $payment_mode = $_POST['payment_mode'] ?? 'cash';
    $receipt_no = $_POST['receipt_no'] ?? '';
    $created_by = $_POST['created_by'] ?? null;
    $stmt = mysqli_prepare($conn, "INSERT INTO fees (branch_id, student_id, enrollment_id, amount, payment_date, payment_mode, receipt_no, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'iiidsssi', $branch_id, $student_id, $enrollment_id, $amount, $payment_date, $payment_mode, $receipt_no, $created_by);
    $success = mysqli_stmt_execute($stmt);
    echo json_encode(['success' => $success]);
    exit;
}
if ($action === 'get') {
    $id = intval($_GET['id'] ?? 0);
    $result = mysqli_query($conn, "SELECT * FROM fees WHERE id = $id LIMIT 1");
    $row = mysqli_fetch_assoc($result);
    echo json_encode(['success' => (bool)$row, 'data' => $row]);
    exit;
}
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $res = mysqli_query($conn, "DELETE FROM fees WHERE id = $id");
    echo json_encode(['success' => (bool)$res]);
    exit;
}
if ($action === 'outstanding') {
    $branch_id = $_GET['branch_id'] ?? null;
    $sql = "SELECT e.id AS enrollment_id, s.name AS student_name, c.title AS course_title, c.total_fee, IFNULL(SUM(f.amount),0) AS paid, (c.total_fee - IFNULL(SUM(f.amount),0)) AS outstanding FROM enrollments e JOIN students s ON e.student_id = s.id JOIN batches b ON e.batch_id = b.id JOIN courses c ON b.course_id = c.id LEFT JOIN fees f ON f.enrollment_id = e.id WHERE 1=1";
    if ($branch_id) $sql .= " AND s.branch_id = " . intval($branch_id);
    $sql .= " GROUP BY e.id HAVING outstanding > 0";
    $result = mysqli_query($conn, $sql);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    echo json_encode(['success' => true, 'outstanding' => $rows]);
    exit;
}
echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>
