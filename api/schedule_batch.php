<?php

use CampusLite\Controllers\ScheduleBatchController;

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../app/controllers/ScheduleBatchController.php';
require_once __DIR__ . '/../config/db.php';

$action = $_REQUEST['action'] ?? ($_SERVER['REQUEST_METHOD'] === 'GET' ? 'list' : 'create');

try {
    switch ($action) {
        case 'list':
            $rows = ScheduleBatchController::all();
            send_json(true, null, $rows);
            break;
        case 'get':
            $id = intval($_GET['id'] ?? 0);
            $row = ScheduleBatchController::get($id);
            if ($row) send_json(true, null, $row);
            else send_json(false, 'Schedule not found');
            break;
        case 'create':
            $data = $_POST;
            normalize_nested($data);
            $ok = ScheduleBatchController::create($data);
            send_json((bool)$ok, $ok ? 'Schedule created' : 'Failed to create');
            break;
        case 'update':
            $id = intval($_POST['id'] ?? 0);
            $data = $_POST;
            normalize_nested($data);
            $ok = ScheduleBatchController::update($id, $data);
            send_json((bool)$ok, $ok ? 'Schedule updated' : 'Failed to update');
            break;
        case 'delete':
            $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
            $ok = ScheduleBatchController::delete($id);
            send_json((bool)$ok, $ok ? 'Schedule deleted' : 'Failed to delete');
            break;
        case 'batch_meta':
            $batchId = intval($_GET['batch_id'] ?? 0);
            require_once __DIR__ . '/../config/db.php';
            $conn = $GLOBALS['conn'];
            $out = ['subjects'=>[], 'students'=>[], 'faculties'=>[], 'employees'=>[], 'primary_faculty'=>null];
            // subjects via course->course_subjects
            $sqlSub = "SELECT s.id, s.title FROM batches b JOIN course_subjects cs ON cs.course_id=b.course_id JOIN subjects s ON s.id=cs.subject_id WHERE b.id=?";
            if ($stmt = mysqli_prepare($conn, $sqlSub)) {
                mysqli_stmt_bind_param($stmt, 'i', $batchId);
                if (mysqli_stmt_execute($stmt)) {
                    $res = mysqli_stmt_get_result($stmt);
                    while ($r = mysqli_fetch_assoc($res)) $out['subjects'][] = $r;
                }
            }
            // students via enrollments
            $sqlStu = "SELECT st.id, st.name FROM enrollments e JOIN students st ON st.id=e.student_id WHERE e.batch_id=?";
            if ($stmt = mysqli_prepare($conn, $sqlStu)) {
                mysqli_stmt_bind_param($stmt, 'i', $batchId);
                if (mysqli_stmt_execute($stmt)) {
                    $res = mysqli_stmt_get_result($stmt);
                    while ($r = mysqli_fetch_assoc($res)) $out['students'][] = $r;
                }
            }
            // faculty list via batch_assignments
            $sqlFacAll = "SELECT u.id, u.name FROM batch_assignments ba JOIN users u ON u.id=ba.user_id WHERE ba.batch_id=? AND ba.role='faculty'";
            if ($stmt = mysqli_prepare($conn, $sqlFacAll)) {
                mysqli_stmt_bind_param($stmt, 'i', $batchId);
                if (mysqli_stmt_execute($stmt)) {
                    $res = mysqli_stmt_get_result($stmt);
                    while ($r = mysqli_fetch_assoc($res)) $out['faculties'][] = $r;
                }
            }
            $out['primary_faculty'] = $out['faculties'][0] ?? null;
            // employees list via batch_assignments
            $sqlEmp = "SELECT u.id, u.name FROM batch_assignments ba JOIN users u ON u.id=ba.user_id WHERE ba.batch_id=? AND ba.role='employee'";
            if ($stmt = mysqli_prepare($conn, $sqlEmp)) {
                mysqli_stmt_bind_param($stmt, 'i', $batchId);
                if (mysqli_stmt_execute($stmt)) {
                    $res = mysqli_stmt_get_result($stmt);
                    while ($r = mysqli_fetch_assoc($res)) $out['employees'][] = $r;
                }
            }
            send_json(true, null, $out);
            break;
        default:
            send_json(false, 'Unknown action');
    }
} catch (Exception $e) {
    send_json(false, 'Server error', null, ['exception' => $e->getMessage()]);
}

function normalize_nested(&$data) {
    foreach (['subject_ids','student_ids'] as $k) {
        if (isset($data[$k])) {
            if (is_string($data[$k])) {
                $dec = json_decode($data[$k], true);
                if (is_array($dec)) $data[$k] = $dec;
            }
            if (!is_array($data[$k])) $data[$k] = [];
        }
    }
}
