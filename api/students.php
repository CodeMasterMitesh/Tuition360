<?php
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../app/controllers/StudentController.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? ($method === 'GET' ? 'list' : 'create');

try {
    switch ($action) {
        case 'list':
            $branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : null;
            $rows = StudentController::getAll($branch_id);
            send_json(true, null, $rows);
            break;
        case 'search':
            $q = trim($_GET['q'] ?? '');
            require_once __DIR__ . '/../config/db.php';
            $out = [];
            if ($q !== '') {
                $like = '%' . $q . '%';
                $stmt = mysqli_prepare($conn, "SELECT id,name,email,mobile FROM students WHERE name LIKE ? OR email LIKE ? OR mobile LIKE ? LIMIT 50");
                mysqli_stmt_bind_param($stmt, 'sss', $like, $like, $like);
                if (mysqli_stmt_execute($stmt)) {
                    $res = mysqli_stmt_get_result($stmt);
                    while ($r = mysqli_fetch_assoc($res)) $out[] = $r;
                }
            }
            send_json(true, null, $out);
            break;
        case 'get_courses':
            $id = intval($_GET['id'] ?? 0);
            require_once __DIR__ . '/../config/db.php';
            $courses = [];
            $stmt = mysqli_prepare($conn, "SELECT course_id FROM student_courses WHERE student_id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $id);
            if (mysqli_stmt_execute($stmt)) {
                $res = mysqli_stmt_get_result($stmt);
                while ($row = mysqli_fetch_assoc($res)) $courses[] = $row;
            }
            send_json(true, null, $courses);
            break;
        case 'get':
            $id = intval($_GET['id'] ?? 0);
            $row = StudentController::get($id);
            if ($row) send_json(true, null, $row);
            else send_json(false, 'Student not found');
            break;
        case 'create':
            $data = $_POST;
            $ok = StudentController::create($data);
            if ($ok) send_json(true, 'Student created');
            else send_json(false, 'Failed to create student');
            break;
        case 'update':
            $id = intval($_POST['id'] ?? 0);
            $data = $_POST;
            $ok = StudentController::update($id, $data);
            if ($ok) send_json(true, 'Student updated');
            else send_json(false, 'Failed to update student');
            break;
        case 'delete':
            $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
            $ok = StudentController::delete($id);
            if ($ok) send_json(true, 'Student deleted');
            else send_json(false, 'Failed to delete student');
            break;
        default:
            send_json(false, 'Unknown action');
    }
} catch (Exception $e) {
    send_json(false, 'Server error', null, ['exception' => $e->getMessage()]);
}
