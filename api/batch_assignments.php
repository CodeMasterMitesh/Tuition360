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
            $data = [
                'batch_id' => intval($_POST['batch_id'] ?? 0),
                'user_id' => intval($_POST['user_id'] ?? 0),
                'role' => $_POST['role'] ?? 'faculty',
                'assigned_at' => $_POST['assigned_at'] ?? date('Y-m-d H:i:s')
            ];
            $ok = BatchAssignmentController::create($data);
            echo json_encode(['success'=>(bool)$ok]);
            break;
        case 'update':
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid id']); break; }
            $data = [
                'batch_id' => intval($_POST['batch_id'] ?? 0),
                'user_id' => intval($_POST['user_id'] ?? 0),
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