<?php
require_once __DIR__ . '/init.php';
header('Content-Type: application/json');
require_once __DIR__ . '/../app/controllers/BatchController.php';

$action = $_REQUEST['action'] ?? ($_SERVER['REQUEST_METHOD']==='GET' ? 'list' : 'create');
try {
    require_once __DIR__ . '/../config/db.php';
    switch ($action) {
        case 'list':
            $branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : null;
            $rows = BatchController::getAll($branch_id);
            echo json_encode(['success'=>true,'data'=>$rows]);
            break;
        case 'get':
            $id = intval($_GET['id'] ?? 0);
            $stmt = mysqli_prepare($conn, "SELECT * FROM batches WHERE id = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, 'i', $id);
            $row = null;
            if (mysqli_stmt_execute($stmt)) {
                $res = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($res);
            }
            echo json_encode(['success'=>(bool)$row,'data'=>$row]);
            break;
        case 'create':
            $data = [
                'branch_id' => intval($_POST['branch_id'] ?? 0),
                'course_id' => intval($_POST['course_id'] ?? 0),
                'title' => $_POST['title'] ?? '',
                'start_date' => $_POST['start_date'] ?? null,
                'end_date' => $_POST['end_date'] ?? null,
                'days_of_week' => is_array($_POST['days_of_week']) ? implode(',', $_POST['days_of_week']) : ($_POST['days_of_week'] ?? ''),
                'time_slot' => $_POST['time_slot'] ?? '',
                'capacity' => intval($_POST['capacity'] ?? 0),
                'status' => $_POST['status'] ?? 'planned'
            ];
            $ok = BatchController::create($data);
            echo json_encode(['success'=>(bool)$ok]);
            break;
        case 'update':
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid id']); break; }
            $branch_id = intval($_POST['branch_id'] ?? 0);
            $course_id = intval($_POST['course_id'] ?? 0);
            $title = $_POST['title'] ?? '';
            $start_date = $_POST['start_date'] ?? null;
            $end_date = $_POST['end_date'] ?? null;
            $days_of_week = is_array($_POST['days_of_week']) ? implode(',', $_POST['days_of_week']) : ($_POST['days_of_week'] ?? '');
            $time_slot = $_POST['time_slot'] ?? '';
            $capacity = intval($_POST['capacity'] ?? 0);
            $status = $_POST['status'] ?? 'planned';
            $stmt = mysqli_prepare($conn, "UPDATE batches SET branch_id=?, course_id=?, title=?, start_date=?, end_date=?, days_of_week=?, time_slot=?, capacity=?, status=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'iisssssisi', $branch_id, $course_id, $title, $start_date, $end_date, $days_of_week, $time_slot, $capacity, $status, $id);
            $ok = mysqli_stmt_execute($stmt);
            echo json_encode(['success'=>(bool)$ok]);
            break;
        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            $stmt = mysqli_prepare($conn, "DELETE FROM batches WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $id);
            $res = mysqli_stmt_execute($stmt);
            echo json_encode(['success'=>(bool)$res]);
            break;
        default:
            echo json_encode(['success'=>false,'message'=>'Unknown action']);
    }
} catch(Exception $e){ echo json_encode(['success'=>false,'error'=>$e->getMessage()]); }
