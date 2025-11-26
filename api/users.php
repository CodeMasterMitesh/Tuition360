<?php
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../app/controllers/UserController.php';

$action = $_REQUEST['action'] ?? ($_SERVER['REQUEST_METHOD']==='GET' ? 'list' : 'create');
try {
    switch ($action) {
        case 'list':
            $rows = UserController::getAll();
            send_json(true, null, $rows);
            break;
        case 'create':
            $ok = UserController::create($_POST['branch_id'] ?? null, $_POST['role'] ?? 'staff', $_POST['name'] ?? '', $_POST['email'] ?? '', $_POST['password'] ?? 'password', $_POST['mobile'] ?? '', $_POST['is_part_time'] ?? 0);
            if ($ok) send_json(true, 'User created'); else send_json(false, 'Failed to create user');
            break;
        case 'get':
            $id = intval($_GET['id'] ?? 0);
            require_once __DIR__ . '/../config/db.php';
            $res = mysqli_query($conn, "SELECT * FROM users WHERE id = $id LIMIT 1");
            $row = mysqli_fetch_assoc($res);
            if ($row) send_json(true, null, $row); else send_json(false, 'User not found');
            break;
        case 'update':
            $id = intval($_POST['id'] ?? 0);
            $branch_id = $_POST['branch_id'] ?? null;
            $role = $_POST['role'] ?? 'staff';
            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $mobile = $_POST['mobile'] ?? '';
            require_once __DIR__ . '/../config/db.php';
            $stmt = mysqli_prepare($conn, "UPDATE users SET branch_id = ?, role = ?, name = ?, email = ?, mobile = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'issssi', $branch_id, $role, $name, $email, $mobile, $id);
            $ok = mysqli_stmt_execute($stmt);
            if ($ok) send_json(true, 'User updated'); else send_json(false, 'Failed to update user');
            break;
        case 'delete':
            require_once __DIR__ . '/../config/db.php';
            $id = intval($_POST['id'] ?? 0);
            $res = mysqli_query($conn, "DELETE FROM users WHERE id = $id");
            if ($res) send_json(true, 'User deleted'); else send_json(false, 'Failed to delete user');
            break;
        default:
            send_json(false, 'Unknown action');
    }
} catch(Exception $e){ send_json(false, 'Server error', null, ['exception' => $e->getMessage()]); }

