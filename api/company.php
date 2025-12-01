<?php

use CampusLite\Controllers\CompanyController;

require_once __DIR__ . '/init.php';
header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? ($_SERVER['REQUEST_METHOD']==='GET' ? 'list' : 'save');
try {
    switch ($action) {
        case 'list':
            $rows = CompanyController::getAll();
            echo json_encode(['success'=>true,'data'=>$rows]);
            break;
        case 'get':
            $id = intval($_GET['id'] ?? 0);
            $row = CompanyController::get($id);
            echo json_encode(['success'=> (bool)$row, 'data'=>$row]);
            break;
        case 'save':
            $data = $_POST;
            $ok = CompanyController::save($data);
            echo json_encode(['success'=>(bool)$ok]);
            break;
        default:
            echo json_encode(['success'=>false,'message'=>'Unknown action']);
    }
} catch(Exception $e) { echo json_encode(['success'=>false,'error'=>$e->getMessage()]); }
