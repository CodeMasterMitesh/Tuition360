<?php
require_once __DIR__ . '/init.php';
header('Content-Type: application/json');
require_once __DIR__ . '/../app/controllers/FacultyController.php';

$action = $_REQUEST['action'] ?? ($_SERVER['REQUEST_METHOD']==='GET' ? 'list' : 'create');
try {
    switch ($action) {
        case 'list':
            $branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : null;
            $rows = FacultyController::getAll($branch_id);
            echo json_encode(['success'=>true,'data'=>$rows]);
            break;
        case 'get':
            $id = intval($_GET['id'] ?? 0);
            $row = FacultyController::get($id);
            echo json_encode(['success'=> (bool)$row, 'data'=>$row]);
            break;
        case 'create':
            $data = $_POST;
            $ok = FacultyController::create($data);
            echo json_encode(['success'=>(bool)$ok]);
            break;
        case 'update':
            $id = intval($_POST['id'] ?? 0);
            $data = $_POST;
            $ok = FacultyController::update($id, $data);
            echo json_encode(['success'=>(bool)$ok]);
            break;
        case 'delete':
            $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
            $ok = FacultyController::delete($id);
            echo json_encode(['success'=>(bool)$ok]);
            break;
        default:
            echo json_encode(['success'=>false,'message'=>'Unknown action']);
    }
} catch (Exception $e) {
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
