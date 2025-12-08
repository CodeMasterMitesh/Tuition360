<?php

use CampusLite\Controllers\BatchAssignmentController;

require_once __DIR__ . '/init.php';
header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? ($_SERVER['REQUEST_METHOD']==='GET' ? 'list' : 'create');
try {
    require_once __DIR__ . '/../config/db.php';
    switch ($action) {
        case 'list':
            $page = max(1, intval($_GET['page'] ?? 1));
            $perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 0;
            $rows = BatchAssignmentController::getAll($page, $perPage);
            echo json_encode(['success'=>true,'data'=>$rows]);
            break;
        case 'get':
            $id = intval($_GET['id'] ?? 0);
            $row = BatchAssignmentController::get($id);
            echo json_encode(['success'=>(bool)$row,'data'=>$row]);
            break;
        case 'create':
            // accept multiple user_ids as a single assignment row
            $batch_id = intval($_POST['batch_id'] ?? 0);
            $role = $_POST['role'] ?? 'faculty';
            $assigned_at = $_POST['assigned_at'] ?? date('Y-m-d H:i:s');
            $students_ids = [];
            if (isset($_POST['students_ids']) && is_array($_POST['students_ids'])) {
                $students_ids = array_map('intval', $_POST['students_ids']);
            } elseif (isset($_POST['user_ids']) && is_array($_POST['user_ids'])) {
                // fallback from older name
                $students_ids = array_map('intval', $_POST['user_ids']);
            } elseif (!empty($_POST['user_id'])) {
                $students_ids = [ intval($_POST['user_id']) ];
            }
            $data = [ 'batch_id' => $batch_id, 'user_id' => (count($students_ids)?intval($students_ids[0]):0), 'students_ids' => $students_ids, 'role' => $role, 'assigned_at' => $assigned_at ];
            $ok = BatchAssignmentController::create($data);
            echo json_encode(['success'=>(bool)$ok]);
            break;
        case 'update':
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid id']); break; }
            $students_ids = [];
            if (isset($_POST['students_ids']) && is_array($_POST['students_ids'])) $students_ids = array_map('intval', $_POST['students_ids']);
            elseif (isset($_POST['user_ids']) && is_array($_POST['user_ids'])) $students_ids = array_map('intval', $_POST['user_ids']);
            $data = [
                'batch_id' => intval($_POST['batch_id'] ?? 0),
                'user_id' => intval($_POST['user_id'] ?? 0),
                'students_ids' => $students_ids,
                'role' => $_POST['role'] ?? 'faculty',
                'assigned_at' => $_POST['assigned_at'] ?? date('Y-m-d H:i:s')
            ];
            $ok = BatchAssignmentController::update($id, $data);
            echo json_encode(['success'=>(bool)$ok]);
            break;
        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            $res = BatchAssignmentController::delete($id);
            echo json_encode(['success'=>(bool)$res]);
            break;
        default:
            echo json_encode(['success'=>false,'message'=>'Unknown action']);
    }
} catch(Exception $e){ echo json_encode(['success'=>false,'error'=>$e->getMessage()]); }
?>