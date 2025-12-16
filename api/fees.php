<?php
require_once __DIR__ . '/init.php';
// api/fees.php
require_once __DIR__ . '/../config/db.php';
$action = $_GET['action'] ?? '';
header('Content-Type: application/json');
if ($action === 'pay' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $branch_id = intval($_POST['branch_id'] ?? 0);
    $student_id = intval($_POST['student_id'] ?? 0);
    $batch_id = intval($_POST['batch_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
    $payment_mode = $_POST['payment_mode'] ?? 'cash';
    $receipt_no = $_POST['receipt_no'] ?? '';
    $created_by = intval($_POST['created_by'] ?? 0);

    // Resolve batch assignment for this student/batch (acts as foreign key for fees.enrollment_id)
    $assignSql = "SELECT ba.id AS assignment_id FROM batch_assignments ba
                  JOIN batch_assignment_students bas ON bas.assignment_id = ba.id
                  WHERE ba.batch_id = ? AND bas.student_id = ? LIMIT 1";
    $assignStmt = mysqli_prepare($conn, $assignSql);
    if (!$assignStmt) { echo json_encode(['success'=>false,'message'=>'Assignment lookup failed']); exit; }
    mysqli_stmt_bind_param($assignStmt, 'ii', $batch_id, $student_id);
    mysqli_stmt_execute($assignStmt);
    $assignRes = mysqli_stmt_get_result($assignStmt);
    $assignRow = mysqli_fetch_assoc($assignRes);
    if (!$assignRow) {
        echo json_encode(['success'=>false,'message'=>'No batch assignment found for student']);
        exit;
    }
    $assignment_id = intval($assignRow['assignment_id']);

    $stmt = mysqli_prepare($conn, "INSERT INTO fees (branch_id, student_id, enrollment_id, amount, payment_date, payment_mode, receipt_no, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'iiidsssi', $branch_id, $student_id, $assignment_id, $amount, $payment_date, $payment_mode, $receipt_no, $created_by);
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
    $rows = [];

    $baseSql = "SELECT ba.id AS assignment_id, s.id AS student_id, s.name AS student_name, b.id AS batch_id, b.title AS batch_title, c.title AS course_title, c.total_fee, COALESCE(SUM(f.amount),0) AS paid, (c.total_fee - COALESCE(SUM(f.amount),0)) AS outstanding
                FROM batch_assignments ba
                JOIN batch_assignment_students bas ON bas.assignment_id = ba.id
                JOIN students s ON s.id = bas.student_id
                JOIN batches b ON b.id = ba.batch_id
                JOIN courses c ON c.id = b.course_id
                LEFT JOIN fees f ON f.enrollment_id = ba.id AND f.student_id = s.id
                WHERE 1=1";

    $params = [];
    $types = '';
    if ($branch_id) {
        $baseSql .= " AND s.branch_id = ?";
        $types .= 'i';
        $params[] = intval($branch_id);
    }

    $baseSql .= " GROUP BY ba.id, s.id HAVING outstanding > 0";

    if ($types) {
        $stmt = mysqli_prepare($conn, $baseSql);
        if ($stmt) {
            $bindParams = array_merge([$types], $params);
            $refs = [];
            foreach ($bindParams as $k => $v) { $refs[$k] = &$bindParams[$k]; }
            array_unshift($refs, $stmt);
            call_user_func_array('mysqli_stmt_bind_param', $refs);
            if (mysqli_stmt_execute($stmt)) {
                $res = mysqli_stmt_get_result($stmt);
                while ($row = mysqli_fetch_assoc($res)) $rows[] = $row;
            }
        }
    } else {
        $res = mysqli_query($conn, $baseSql);
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) $rows[] = $row;
        }
    }

    echo json_encode(['success' => true, 'outstanding' => $rows]);
    exit;
}
echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>
