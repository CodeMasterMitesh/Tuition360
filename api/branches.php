<?php

use CampusLite\Controllers\BranchController;

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/helpers.php';

$action = $_REQUEST['action'] ?? ($_SERVER['REQUEST_METHOD']==='GET' ? 'list' : 'create');
try {
    switch ($action) {
        case 'list':
            $rows = BranchController::getAll();
            send_json(true, null, $rows);
            break;
        case 'create':
            $ok = BranchController::create($_POST['name'] ?? '', $_POST['address'] ?? '');
            if ($ok) send_json(true, 'Branch created'); else send_json(false, 'Failed to create branch');
            break;
        case 'delete':
            require_once __DIR__ . '/../config/db.php';
            $id = intval($_POST['id'] ?? 0);
            $stmt = mysqli_prepare($conn, "DELETE FROM branches WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $id);
            $res = mysqli_stmt_execute($stmt);
            if ($res && mysqli_stmt_affected_rows($stmt) > 0) send_json(true, 'Branch deleted'); else send_json(false, 'Failed to delete branch');
            break;
        default:
            send_json(false, 'Unknown action');
    }
} catch(Exception $e){ send_json(false, 'Server error', null, ['exception' => $e->getMessage()]); }

