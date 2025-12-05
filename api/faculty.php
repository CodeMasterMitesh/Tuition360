<?php

use CampusLite\Controllers\FacultyController;

require_once __DIR__ . '/init.php';
header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? ($_SERVER['REQUEST_METHOD'] === 'GET' ? 'list' : 'create');

try {
    switch ($action) {
        case 'list':
            $branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : null;
            $rows = FacultyController::getAll($branch_id);
            echo json_encode(['success' => true, 'data' => $rows]);
            break;
        case 'get':
            $id = intval($_GET['id'] ?? 0);
            $row = FacultyController::get($id);
            echo json_encode(['success' => (bool) $row, 'data' => $row]);
            break;
        case 'create':
            $data = $_POST;
            FacultyUploadHelper::handleUploads($data, null);
            foreach (['education','employment'] as $k) {
                if (isset($data[$k]) && is_string($data[$k])) {
                    $dec = json_decode($data[$k], true);
                    if (is_array($dec)) $data[$k] = $dec;
                }
            }
            $ok = FacultyController::create($data);
            echo json_encode(['success' => (bool) $ok]);
            break;
        case 'update':
            $id = intval($_POST['id'] ?? 0);
            $data = $_POST;
            $existing = FacultyController::get($id);
            FacultyUploadHelper::handleUploads($data, $existing);
            foreach (['education','employment'] as $k) {
                if (isset($data[$k]) && is_string($data[$k])) {
                    $dec = json_decode($data[$k], true);
                    if (is_array($dec)) $data[$k] = $dec;
                }
            }
            $ok = FacultyController::update($id, $data);
            echo json_encode(['success' => (bool) $ok]);
            break;
        case 'delete-photo':
            $id = intval($_POST['id'] ?? 0);
            $row = FacultyController::get($id);
            if ($row && !empty($row['profile_photo'])) {
                $path = __DIR__ . '/../public/uploads/faculty/' . basename($row['profile_photo']);
                if (is_file($path)) @unlink($path);
                $_POST = ['profile_photo' => null];
                FacultyController::update($id, $_POST);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No photo']);
            }
            break;
        case 'delete':
            $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
            $ok = FacultyController::delete($id);
            echo json_encode(['success' => (bool) $ok]);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

class FacultyUploadHelper {
    public static function handleUploads(&$data, $existing) {
        $dir = __DIR__ . '/../public/uploads/faculty';
        if (!is_dir($dir)) @mkdir($dir, 0777, true);
        if (!empty($_FILES['profile_photo']['name'])) {
            $ext = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
            $ext = $ext ? strtolower($ext) : 'jpg';
            $safe = uniqid('fac_') . '.' . $ext;
            $dest = $dir . '/' . $safe;
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $dest)) {
                $data['profile_photo'] = $safe;
            }
        } else if ($existing && isset($existing['profile_photo'])) {
            $data['profile_photo'] = $existing['profile_photo'];
        }
        foreach (['aadhar_card','pan_card','passport'] as $doc) {
            if (!empty($_FILES[$doc]['name'])) {
                $ext = pathinfo($_FILES[$doc]['name'], PATHINFO_EXTENSION);
                if ($ext === '') $ext = 'bin';
                $safe = uniqid($doc . '_') . '.' . strtolower($ext);
                $dest = $dir . '/' . $safe;
                if (move_uploaded_file($_FILES[$doc]['tmp_name'], $dest)) {
                    $data[$doc] = $safe;
                }
            } else if ($existing && isset($existing[$doc])) {
                $data[$doc] = $existing[$doc];
            }
        }
    }
}
