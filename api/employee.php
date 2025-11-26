<?php
require_once __DIR__ . '/init.php';
header('Content-Type: application/json');
require_once __DIR__ . '/../app/controllers/EmployeeController.php';

$action = $_REQUEST['action'] ?? ($_SERVER['REQUEST_METHOD']==='GET' ? 'list' : 'create');
try {
    switch ($action) {
        case 'list':
            $rows = EmployeeController::getAll();
            // Ensure dob is present for birthday reminders
            foreach ($rows as &$row) {
                if (!isset($row['dob'])) $row['dob'] = $row['dob'] ?? '';
            }
            echo json_encode(['success'=>true,'data'=>$rows]);
            break;
        case 'get':
            $id = intval($_GET['id'] ?? 0);
            $row = EmployeeController::get($id);
            echo json_encode(['success'=> (bool)$row, 'data'=>$row]);
            break;
        case 'create':
            $data = $_POST;
            $ok = EmployeeController::create($data);
            echo json_encode(['success'=>(bool)$ok]);
            break;
        case 'update':
            $id = intval($_POST['id'] ?? 0);
            $data = $_POST;
            $ok = EmployeeController::update($id, $data);
            echo json_encode(['success'=>(bool)$ok]);
            break;
        case 'delete':
            $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
            $ok = EmployeeController::delete($id);
            echo json_encode(['success'=>(bool)$ok]);
            break;
        default:
            echo json_encode(['success'=>false,'message'=>'Unknown action']);
    }
} catch (Exception $e) {
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
