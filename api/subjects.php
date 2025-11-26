<?php
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../app/controllers/SubjectController.php';

$action = $_REQUEST['action'] ?? ($_SERVER['REQUEST_METHOD']==='GET' ? 'list' : 'create');
try {
    switch ($action) {
        case 'list':
            $rows = SubjectController::getAll();
            send_json(true, null, $rows);
            break;
        case 'get':
            $id = intval($_GET['id'] ?? 0);
            $rows = SubjectController::getAll();
            $found = null;
            foreach ($rows as $r) {
                if (isset($r['id']) && intval($r['id']) === $id) { $found = $r; break; }
            }
            if ($found) send_json(true, null, $found); else send_json(false, 'Subject not found');
            break;
        case 'create':
            $ok = SubjectController::create($_POST['title'] ?? '', $_POST['description'] ?? '');
            if ($ok) send_json(true, 'Subject created'); else send_json(false, 'Failed to create subject');
            break;
        case 'update':
            $id = intval($_POST['id'] ?? 0);
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            require_once __DIR__ . '/../config/db.php';
            $stmt = mysqli_prepare($conn, "UPDATE subjects SET title = ?, description = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'ssi', $title, $description, $id);
            $ok = mysqli_stmt_execute($stmt);
            if ($ok) send_json(true, 'Subject updated'); else send_json(false, 'Failed to update subject');
            break;
        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            require_once __DIR__ . '/../config/db.php';
            $res = mysqli_query($conn, "DELETE FROM subjects WHERE id = $id");
            if ($res) send_json(true, 'Subject deleted'); else send_json(false, 'Failed to delete subject');
            break;
        default:
            send_json(false, 'Unknown action');
    }
} catch(Exception $e){ send_json(false, 'Server error', null, ['exception' => $e->getMessage()]); }

