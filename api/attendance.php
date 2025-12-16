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
if ($action === 'mark_students' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'] ?? date('Y-m-d');
    $branch_id = intval($_SESSION['branch_id'] ?? ($_POST['branch_id'] ?? 0));
    $recorded_by = intval($_SESSION['user']['id'] ?? ($_POST['recorded_by'] ?? 0));
    $records = json_decode($_POST['records'] ?? '[]', true);
    $schedule_id = $_POST['schedule_id'] ?? null; // unused but accepted
    $batch_id = $_POST['batch_id'] ?? null; // unused but accepted
    if (!is_array($records) || empty($records)) {
        echo json_encode(['success' => false, 'message' => 'No records provided']);
        exit;
    }
    $stmt = mysqli_prepare($conn, "INSERT INTO attendance (branch_id, entity_type, entity_id, date, status, note, recorded_by) VALUES (?, 'student', ?, ?, ?, ?, ?)");
    if (!$stmt) { echo json_encode(['success'=>false,'message'=>'Prepare failed']); exit; }
    $ok = true;
    foreach ($records as $rec) {
        $sid = intval($rec['student_id'] ?? 0);
        $status = $rec['status'] ?? 'present';
        if (!in_array($status, ['present','absent','leave'], true)) $status = 'present';
        $note = substr($rec['note'] ?? '', 0, 255);
        mysqli_stmt_bind_param($stmt, 'iisssi', $branch_id, $sid, $date, $status, $note, $recorded_by);
        if (!mysqli_stmt_execute($stmt)) { $ok = false; break; }
    }
    echo json_encode(['success' => $ok]);
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
// Legacy staff report (GET)
if ($action === 'report' && $_SERVER['REQUEST_METHOD'] === 'GET') {
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

if ($action === 'report') {
    // Batch-wise attendance report with date and status filters
    $input = json_decode(file_get_contents('php://input'), true);
    $batchId = isset($input['batch_id']) ? $input['batch_id'] : '';
    $studentId = isset($input['student_id']) ? $input['student_id'] : 'all';
    $fromDate = $input['from_date'] ?? '';
    $toDate = $input['to_date'] ?? '';
    $statusFilter = $input['status_filter'] ?? 'all';

    if (!$fromDate || !$toDate) {
        echo json_encode(['success' => false, 'message' => 'Missing date range']);
        exit;
    }

    // Get student IDs from batch assignments
    $studentIds = [];
    
    // If specific student selected, use only that student
    if ($studentId !== 'all' && $studentId !== '') {
        $studentIds = [intval($studentId)];
    } else {
        // Get students based on batch selection
        if ($batchId === 'all' || $batchId === '') {
        // Get all students from all batch assignments
        $sql = "SELECT DISTINCT bas.student_id FROM batch_assignment_students bas";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            if (mysqli_stmt_execute($stmt)) {
                $res = mysqli_stmt_get_result($stmt);
                while ($r = mysqli_fetch_assoc($res)) {
                    $studentIds[] = intval($r['student_id']);
                }
            }
        }
    } else {
        // Specific batch
        $batchIdInt = intval($batchId);
        
        // From batch_assignment_students
        $sql = "SELECT DISTINCT bas.student_id 
                FROM batch_assignment_students bas 
                JOIN batch_assignments ba ON ba.id = bas.assignment_id 
                WHERE ba.batch_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $batchIdInt);
            if (mysqli_stmt_execute($stmt)) {
                $res = mysqli_stmt_get_result($stmt);
                while ($r = mysqli_fetch_assoc($res)) {
                    $studentIds[] = intval($r['student_id']);
                }
            }
        }
    }

    }
    
    $studentIds = array_unique($studentIds);
    
    if (empty($studentIds)) {
        echo json_encode(['success' => true, 'records' => []]);
        exit;
    }

    // Build query to get attendance with batch names
    $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
    
    // Join with batch_assignment_students and schedule_batches to get batch name and times for each student
    $sql = "SELECT a.*, s.name as student_name, b.title as batch_title, sb.start_time, sb.end_time
            FROM attendance a 
            LEFT JOIN students s ON a.entity_id = s.id 
            LEFT JOIN batch_assignment_students bas ON bas.student_id = s.id 
            LEFT JOIN batch_assignments ba ON ba.id = bas.assignment_id 
            LEFT JOIN batches b ON b.id = ba.batch_id
            LEFT JOIN schedule_batches sb ON sb.batch_id = b.id AND sb.status = 'active'
            WHERE a.entity_type = 'student' 
            AND a.entity_id IN ($placeholders) 
            AND a.date >= ? 
            AND a.date <= ?";
    
    // Apply status filter
    if ($statusFilter !== 'all') {
        $sql .= " AND a.status = ?";
    }
    
    $sql .= " ORDER BY a.date DESC, s.name ASC";

    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        $types = str_repeat('i', count($studentIds)) . 'ss';
        $params = array_merge($studentIds, [$fromDate, $toDate]);
        
        if ($statusFilter !== 'all') {
            $types .= 's';
            $params[] = $statusFilter;
        }

        // Bind parameters dynamically
        $bindParams = array_merge([$types], $params);
        $refs = [];
        foreach ($bindParams as $k => $v) {
            $refs[$k] = &$bindParams[$k];
        }
        array_unshift($refs, $stmt);
        call_user_func_array('mysqli_stmt_bind_param', $refs);

        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $records = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $records[] = $row;
            }
            echo json_encode(['success' => true, 'records' => $records]);
            exit;
        }
    }

    echo json_encode(['success' => false, 'message' => 'Database query failed']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>
