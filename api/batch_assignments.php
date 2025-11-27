<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../app/controllers/BatchAssignmentController.php';
$action = $_REQUEST['action'] ?? ($_SERVER['REQUEST_METHOD']==='GET' ? 'list' : 'create');
try {
    require_once __DIR__ . '/../config/db.php';
    switch ($action) {
        case 'list':
            $rows = BatchAssignmentController::getAll();
            echo json_encode(['success'=>true,'data'=>$rows]);
            break;
        case 'get':
            $id = intval($_GET['id'] ?? 0);
            $row = BatchAssignmentController::get($id);
            echo json_encode(['success'=>(bool)$row,'data'=>$row]);
            break;
        case 'create':
            // support multiple user_ids (students) or a single user_id
            $batch_id = intval($_POST['batch_id'] ?? 0);
            $role = $_POST['role'] ?? 'faculty';
            $assigned_at = $_POST['assigned_at'] ?? date('Y-m-d H:i:s');
            $subjects = isset($_POST['subjects']) ? $_POST['subjects'] : null;
            $user_ids = [];
            if (isset($_POST['user_ids']) && is_array($_POST['user_ids'])) {
                $user_ids = array_map('intval', $_POST['user_ids']);
            } elseif (!empty($_POST['user_id'])) {
                $user_ids = [ intval($_POST['user_id']) ];
            }
            $ok_any = false;
            foreach ($user_ids as $uid) {
                $data = [ 'batch_id' => $batch_id, 'user_id' => $uid, 'role' => $role, 'subjects' => $subjects, 'assigned_at' => $assigned_at ];
                $ok = BatchAssignmentController::create($data);
                if ($ok) $ok_any = true;
            }
            echo json_encode(['success'=>$ok_any]);
            break;
        case 'update':
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid id']); break; }
            $data = [
                'batch_id' => intval($_POST['batch_id'] ?? 0),
                'user_id' => intval($_POST['user_id'] ?? 0),
                'role' => $_POST['role'] ?? 'faculty',
                'subjects' => isset($_POST['subjects']) ? $_POST['subjects'] : null,
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