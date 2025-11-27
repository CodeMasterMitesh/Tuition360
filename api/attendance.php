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
    $row = null;
    $stmt = mysqli_prepare($conn, "SELECT * FROM attendance WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    if (mysqli_stmt_execute($stmt)) {
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
    }
    echo json_encode(['success' => (bool)$row, 'data' => $row]);
    exit;
}
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $stmt = mysqli_prepare($conn, "DELETE FROM attendance WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    $res = mysqli_stmt_execute($stmt);
    echo json_encode(['success' => (bool)$res]);
    exit;
}
if ($action === 'report') {
    $branch_id = $_GET['branch_id'] ?? null;
    $from = $_GET['from'] ?? null;
    $to = $_GET['to'] ?? null;
    $rows = [];
    $where = [];
    $types = '';
    $params = [];
    if ($branch_id) {
        $where[] = 'branch_id = ?';
        $types .= 'i';
        $params[] = intval($branch_id);
    }
    if ($from) {
        $where[] = 'date >= ?';
        $types .= 's';
        $params[] = $from;
    }
    if ($to) {
        $where[] = 'date <= ?';
        $types .= 's';
        $params[] = $to;
    }
    $sql = 'SELECT * FROM attendance' . (count($where) ? ' WHERE ' . implode(' AND ', $where) : '');
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        if (!empty($params)) {
            // bind params dynamically
            $bindParams = array_merge([$types], $params);
            $refs = [];
            foreach ($bindParams as $k => $v) {
                $refs[$k] = & $bindParams[$k];
            }
            array_unshift($refs, $stmt);
            call_user_func_array('mysqli_stmt_bind_param', $refs);
        }
        if (mysqli_stmt_execute($stmt)) {
            $res = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($res)) $rows[] = $row;
        }
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
        // Fetch lecture details for this date/branch using prepared statement
        $details = [];
        $detail_sql = "SELECT a.*, b.name as branch_name, u.name as faculty_name, u2.name as employee_name FROM attendance a LEFT JOIN branches b ON a.branch_id=b.id LEFT JOIN users u ON a.entity_id=u.id AND a.entity_type='faculty' LEFT JOIN users u2 ON a.entity_id=u2.id AND a.entity_type='employee' WHERE a.date = ? AND a.branch_id = ? AND a.entity_type IN ('faculty','employee')";
        $dstmt = mysqli_prepare($conn, $detail_sql);
        if ($dstmt) {
            $d_date = $row['date'];
            $d_branch = intval($row['branch_id']);
            mysqli_stmt_bind_param($dstmt, 'si', $d_date, $d_branch);
            if (mysqli_stmt_execute($dstmt)) {
                $dres = mysqli_stmt_get_result($dstmt);
                while ($d = mysqli_fetch_assoc($dres)) $details[] = $d;
            }
        }
        $row['lectures'] = $details;
        $rows[] = $row;
    }
    echo json_encode(['success'=>true,'data'=>$rows]);
    exit;
}
echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>
