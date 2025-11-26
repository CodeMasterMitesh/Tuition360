<?php
require_once __DIR__ . '/init.php';
header('Content-Type: application/json');
require_once __DIR__ . '/../app/controllers/CourseController.php';

$action = $_REQUEST['action'] ?? ($_SERVER['REQUEST_METHOD']==='GET' ? 'list' : 'create');
try {
    switch ($action) {
        case 'list':
            $rows = CourseController::getAll();
            echo json_encode(['success'=>true,'data'=>$rows]);
            break;
        case 'get_subjects':
            $id = intval($_GET['id'] ?? 0);
            require_once __DIR__ . '/../config/db.php';
            $subjects = [];
            $res = mysqli_query($conn, "SELECT subject_id FROM course_subjects WHERE course_id = $id ORDER BY sequence, id");
            while ($row = mysqli_fetch_assoc($res)) $subjects[] = $row;
            echo json_encode(['success'=>true,'data'=>$subjects]);
            break;
        case 'create':
            $ok = CourseController::create(intval($_POST['branch_id'] ?? 0), $_POST['title'] ?? '', $_POST['description'] ?? '', floatval($_POST['total_fee'] ?? 0), intval($_POST['duration_months'] ?? 0));
            echo json_encode(['success'=>(bool)$ok]);
            break;
        case 'get':
            $id = intval($_GET['id'] ?? 0);
            $rows = CourseController::getAll();
            $found = null;
            foreach ($rows as $r) if ($r['id'] == $id) $found = $r;
            echo json_encode(['success' => (bool)$found, 'data' => $found]);
            break;
        case 'update':
            $id = intval($_POST['id'] ?? 0);
            require_once __DIR__ . '/../config/db.php';
            $branch_id = intval($_POST['branch_id'] ?? 0);
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            $total_fee = floatval($_POST['total_fee'] ?? 0);
            $duration = intval($_POST['duration_months'] ?? 0);
            $stmt = mysqli_prepare($conn, "UPDATE courses SET branch_id = ?, title = ?, description = ?, total_fee = ?, duration_months = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'issdii', $branch_id, $title, $description, $total_fee, $duration, $id);
            $ok = mysqli_stmt_execute($stmt);

            // Update subjects mapping
            $subjects = isset($_POST['subjects']) ? $_POST['subjects'] : (isset($_POST['subjects']) ? $_POST['subjects'] : []);
            if (!is_array($subjects)) {
                $subjects = [$subjects];
            }
            // Remove old mappings
            mysqli_query($conn, "DELETE FROM course_subjects WHERE course_id = $id");
            // Insert new mappings
            $seq = 1;
            foreach ($subjects as $subj_id) {
                $subj_id = intval($subj_id);
                if ($subj_id > 0) {
                    mysqli_query($conn, "INSERT INTO course_subjects (course_id, subject_id, sequence) VALUES ($id, $subj_id, $seq)");
                    $seq++;
                }
            }

            echo json_encode(['success' => (bool)$ok]);
            break;
        case 'delete':
            require_once __DIR__ . '/../config/db.php';
            $id = intval($_POST['id'] ?? 0);
            $res = mysqli_query($conn, "DELETE FROM courses WHERE id = $id");
            echo json_encode(['success'=>(bool)$res]);
            break;
        default:
            echo json_encode(['success'=>false,'message'=>'Unknown action']);
    }
} catch(Exception $e){ echo json_encode(['success'=>false,'error'=>$e->getMessage()]); }

