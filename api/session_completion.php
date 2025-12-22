<?php

use CampusLite\Controllers\{SessionCompletionController, SessionAttachmentController};

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../config/db.php';

// Auto-load controllers
require_once __DIR__ . '/../app/controllers/SessionCompletionController.php';
require_once __DIR__ . '/../app/controllers/SessionAttachmentController.php';

// Normalize user context from session for downstream usage
if (!isset($GLOBALS['user']) || !is_array($GLOBALS['user'])) {
    $GLOBALS['user'] = [];
}
if (!empty($_SESSION['user']) && is_array($_SESSION['user'])) {
    $GLOBALS['user'] = array_merge($GLOBALS['user'], $_SESSION['user']);
}
if (empty($GLOBALS['user']['id']) && !empty($_SESSION['user_id'])) {
    $GLOBALS['user']['id'] = (int)$_SESSION['user_id'];
}
if (empty($GLOBALS['user']['role']) && !empty($_SESSION['role'])) {
    $GLOBALS['user']['role'] = $_SESSION['role'];
}

$action = $_REQUEST['action'] ?? ($_SERVER['REQUEST_METHOD'] === 'GET' ? 'list' : 'create');

try {
    switch ($action) {
        case 'list':
            // List user's session completions (for faculty/employee dashboard)
            // Admin sees all, faculty/employee see only their own
            $userId = intval($GLOBALS['user']['id'] ?? 0);
            $userRole = $GLOBALS['user']['role'] ?? '';
            $isAdmin = in_array($userRole, ['super_admin', 'branch_admin']);
            
            $rows = $isAdmin ? SessionCompletionController::getAll() : SessionCompletionController::getUserCompletions($userId);
            send_json(true, null, $rows);
            break;

        case 'get':
            $id = intval($_GET['id'] ?? 0);
            $row = SessionCompletionController::get($id);
            if ($row) {
                // Load attachments and notes
                $row['attachments'] = SessionAttachmentController::getByCompletion($id);
                $row['notes'] = SessionCompletionController::getNotes($id);
                send_json(true, null, $row);
            } else {
                send_json(false, 'Session not found');
            }
            break;

        case 'get_by_occurrence':
            // Get all completions for an occurrence_id
            $occurrenceId = intval($_GET['occurrence_id'] ?? 0);
            if (!$occurrenceId) {
                send_json(false, 'Missing occurrence_id');
                break;
            }
            
            $rows = [];
            $sql = "SELECT sc.*, 
                    b.title AS batch_title,
                    b.id AS batch_id,
                    u.name AS completed_by_name,
                    sbo.session_date,
                    sbo.start_time AS scheduled_start,
                    sbo.end_time AS scheduled_end,
                    uf.name AS faculty_name,
                    ue.name AS employee_name
                    FROM session_completions sc
                    LEFT JOIN schedule_batch_occurrences sbo ON sbo.id = sc.occurrence_id
                    LEFT JOIN schedule_batches sb ON sb.id = sc.schedule_id
                    LEFT JOIN batches b ON b.id = sc.batch_id
                    LEFT JOIN users u ON u.id = sc.completed_by
                    LEFT JOIN users uf ON uf.id = sc.faculty_id
                    LEFT JOIN users ue ON ue.id = sc.employee_id
                    WHERE sc.occurrence_id = ?
                    ORDER BY sc.created_at DESC";
            
            if ($stmt = mysqli_prepare($GLOBALS['conn'], $sql)) {
                mysqli_stmt_bind_param($stmt, 'i', $occurrenceId);
                if (mysqli_stmt_execute($stmt)) {
                    $res = mysqli_stmt_get_result($stmt);
                    while ($r = mysqli_fetch_assoc($res)) {
                        $r['attachments'] = SessionAttachmentController::getByCompletion($r['id']);
                        $r['comments'] = SessionCompletionController::getNotes($r['id']);
                        $rows[] = $r;
                    }
                }
                mysqli_stmt_close($stmt);
            }
            send_json(true, null, $rows);
            break;

        case 'create':
            $data = $_POST;
            // Resolve user who is completing the session
            $userId = intval($GLOBALS['user']['id'] ?? 0);
            if (!$userId && !empty($_SESSION['user_id'])) {
                $userId = (int)$_SESSION['user_id'];
            }
            // Allow explicit override from POST (e.g., dashboard JS) if session is missing
            $postedUserId = intval($_POST['completed_by'] ?? 0);
            if (!$userId && $postedUserId) {
                $userId = $postedUserId;
            }
            if (!$userId) {
                send_json(false, 'Missing user context for completion');
                break;
            }

            $data['completed_by'] = $userId;

            // Default faculty/employee IDs if not explicitly provided
            $role = $GLOBALS['user']['role'] ?? ($_SESSION['role'] ?? '');
            if (empty($data['faculty_id']) && $role === 'faculty') {
                $data['faculty_id'] = $userId;
            }
            if (empty($data['employee_id']) && $role === 'employee') {
                $data['employee_id'] = $userId;
            }

            $completionId = SessionCompletionController::create($data);
            if ($completionId) {
                send_json(true, 'Session created', ['id' => $completionId]);
            } else {
                send_json(false, 'Failed to create session');
            }
            break;

        case 'update':
            $id = intval($_POST['id'] ?? 0);
            $data = $_POST;
            $ok = SessionCompletionController::update($id, $data);
            send_json((bool)$ok, $ok ? 'Session updated' : 'Failed to update');
            break;

        case 'complete':
            // Mark session as completed
            $id = intval($_POST['id'] ?? 0);
            $data = [
                'actual_start_time' => $_POST['actual_start_time'] ?? null,
                'actual_end_time' => $_POST['actual_end_time'] ?? null,
                'completion_code' => $_POST['completion_code'] ?? null,
                'notes' => $_POST['notes'] ?? null,
                'status' => 'completed',
                'completed_at' => date('Y-m-d H:i:s')
            ];
            $ok = SessionCompletionController::update($id, $data);
            send_json((bool)$ok, $ok ? 'Session marked completed' : 'Failed to complete');
            break;

        case 'cancel':
            $id = intval($_POST['id'] ?? 0);
            $data = ['status' => 'cancelled'];
            $ok = SessionCompletionController::update($id, $data);
            send_json((bool)$ok, $ok ? 'Session cancelled' : 'Failed to cancel');
            break;

        case 'add_note':
            $completionId = intval($_POST['completion_id'] ?? 0);
            $noteText = $_POST['note_text'] ?? '';
            $noteType = $_POST['note_type'] ?? 'general';
            $userId = intval($_SESSION['user']['id'] ?? ($GLOBALS['user']['id'] ?? 0));

            if (!$completionId || !$noteText) {
                send_json(false, 'Missing required fields');
                break;
            }

            $noteId = SessionCompletionController::addNote($completionId, $userId, $noteText, $noteType);
            if ($noteId) {
                send_json(true, 'Note added', ['note_id' => $noteId]);
            } else {
                send_json(false, 'Failed to add note');
            }
            break;

        case 'delete_note':
            $noteId = intval($_POST['note_id'] ?? 0);
            $ok = SessionCompletionController::deleteNote($noteId);
            send_json((bool)$ok, $ok ? 'Note deleted' : 'Failed to delete');
            break;

        case 'upload_attachment':
            $completionId = intval($_POST['completion_id'] ?? 0);
            $description = $_POST['description'] ?? '';
            $userId = intval($_SESSION['user']['id'] ?? ($GLOBALS['user']['id'] ?? 0));

            if (!$completionId || empty($_FILES['file'] ?? null)) {
                send_json(false, 'Missing file or completion_id');
                break;
            }

            $file = $_FILES['file'];
            $attachmentId = SessionAttachmentController::upload($completionId, $file, $description, $userId);
            if ($attachmentId) {
                send_json(true, 'Attachment uploaded', ['attachment_id' => $attachmentId]);
            } else {
                send_json(false, 'File upload failed');
            }
            break;

        case 'delete_attachment':
            $attachmentId = intval($_POST['attachment_id'] ?? 0);
            $ok = SessionAttachmentController::delete($attachmentId);
            send_json((bool)$ok, $ok ? 'Attachment deleted' : 'Failed to delete');
            break;

        case 'occurrences':
            // List upcoming/pending occurrences for current user
            // Admin sees all, faculty/employee see only their own
            $userId = intval($GLOBALS['user']['id'] ?? 0);
            $userRole = $GLOBALS['user']['role'] ?? '';
            $isAdmin = in_array($userRole, ['super_admin', 'branch_admin']);
            $scope = $_GET['scope'] ?? 'all';
            $today = date('Y-m-d');
            
            $rows = $isAdmin ? SessionCompletionController::getAllPendingOccurrences() : SessionCompletionController::getPendingOccurrences($userId);

            // Optional date-based filtering for dashboard views
            if (is_array($rows)) {
                if ($scope === 'today') {
                    $rows = array_values(array_filter($rows, function($r) use ($today) {
                        return isset($r['session_date']) && $r['session_date'] === $today;
                    }));
                } elseif ($scope === 'upcoming') {
                    $rows = array_values(array_filter($rows, function($r) use ($today) {
                        return isset($r['session_date']) && $r['session_date'] >= $today;
                    }));
                }
            }
            send_json(true, null, $rows);
            break;

        default:
            send_json(false, 'Unknown action');
    }
} catch (Exception $e) {
    send_json(false, 'Server error', null, ['exception' => $e->getMessage()]);
}
?>
