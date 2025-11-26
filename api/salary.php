<?php
require_once __DIR__ . '/init.php';
// api/salary.php
require_once __DIR__ . '/../config/db.php';
$action = $_GET['action'] ?? '';
header('Content-Type: application/json');
if ($action === 'generate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $branch_id = $_POST['branch_id'] ?? null;
    $user_id = $_POST['user_id'] ?? null;
    $salary_month = $_POST['salary_month'] ?? date('Y-m-01');
    $gross_amount = $_POST['gross_amount'] ?? 0;
    $deductions = $_POST['deductions'] ?? 0;
    $net_amount = $_POST['net_amount'] ?? 0;
    $stmt = mysqli_prepare($conn, "INSERT INTO salaries (branch_id, user_id, salary_month, gross_amount, deductions, net_amount) VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'iissdd', $branch_id, $user_id, $salary_month, $gross_amount, $deductions, $net_amount);
    $success = mysqli_stmt_execute($stmt);
    echo json_encode(['success' => $success]);
    exit;
}
if ($action === 'pay' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $salary_id = $_POST['salary_id'] ?? null;
    $paid_on = $_POST['paid_on'] ?? date('Y-m-d');
    $stmt = mysqli_prepare($conn, "UPDATE salaries SET status = 'paid', paid_on = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'si', $paid_on, $salary_id);
    $success = mysqli_stmt_execute($stmt);
    echo json_encode(['success' => $success]);
    exit;
}
if ($action === 'report') {
    $branch_id = $_GET['branch_id'] ?? null;
    $sql = "SELECT * FROM salaries WHERE 1=1";
    if ($branch_id) $sql .= " AND branch_id = " . intval($branch_id);
    $result = mysqli_query($conn, $sql);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    echo json_encode(['success' => true, 'salaries' => $rows]);
    exit;
}
if ($action === 'get') {
    $id = intval($_GET['id'] ?? 0);
    $result = mysqli_query($conn, "SELECT * FROM salaries WHERE id = $id LIMIT 1");
    $row = mysqli_fetch_assoc($result);
    echo json_encode(['success' => (bool)$row, 'data' => $row]);
    exit;
}
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $res = mysqli_query($conn, "DELETE FROM salaries WHERE id = $id");
    echo json_encode(['success' => (bool)$res]);
    exit;
}
echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>
