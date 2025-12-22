<?php

namespace CampusLite\Controllers;

if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }

require_once __DIR__ . '/../../config/db.php';
if (!isset($GLOBALS['conn']) || !($GLOBALS['conn'] instanceof \mysqli)) {
    $GLOBALS['conn'] = \db_conn();
}

class SessionCompletionController {

    /**
     * Get a single session completion by ID
     */
    public static function get(int $id) {
        global $conn;
        $sql = "SELECT sc.*, 
                sb.title AS batch_title,
                b.id AS batch_id,
                u.name AS faculty_name,
                sbo.session_date
                FROM session_completions sc
                LEFT JOIN schedule_batches sb ON sb.id = sc.schedule_id
                LEFT JOIN batches b ON b.id = sc.batch_id
                LEFT JOIN users u ON u.id = sc.faculty_id OR u.id = sc.employee_id
                LEFT JOIN schedule_batch_occurrences sbo ON sbo.id = sc.occurrence_id
                WHERE sc.id = ? LIMIT 1";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, 'i', $id);
            if (mysqli_stmt_execute($stmt)) {
                $res = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($res);
                mysqli_stmt_close($stmt);
                return $row ?: null;
            }
            mysqli_stmt_close($stmt);
        }
        return null;
    }

    /**
     * Get all session completions (for admin view)
     */
    public static function getAll() {
        global $conn;
        $rows = [];
        $sql = "SELECT sc.*, 
                sb.title AS batch_title,
                b.id AS batch_id,
                u.name AS completed_by_name,
                sbo.session_date,
                sbo.start_time AS scheduled_start,
                sbo.end_time AS scheduled_end,
                (SELECT COUNT(*) FROM session_attachments sa WHERE sa.completion_id = sc.id) AS attachment_count,
                (SELECT COUNT(*) FROM session_notes sn WHERE sn.completion_id = sc.id) AS note_count,
                uf.name AS faculty_name,
                ue.name AS employee_name
                FROM session_completions sc
                LEFT JOIN schedule_batch_occurrences sbo ON sbo.id = sc.occurrence_id
                LEFT JOIN schedule_batches sb ON sb.id = sc.schedule_id
                LEFT JOIN batches b ON b.id = sc.batch_id
                LEFT JOIN users u ON u.id = sc.completed_by
                LEFT JOIN users uf ON uf.id = sc.faculty_id
                LEFT JOIN users ue ON ue.id = sc.employee_id
                ORDER BY sbo.session_date DESC, sbo.start_time DESC";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            if (mysqli_stmt_execute($stmt)) {
                $res = mysqli_stmt_get_result($stmt);
                while ($r = mysqli_fetch_assoc($res)) {
                    $rows[] = $r;
                }
            }
            mysqli_stmt_close($stmt);
        }
        return $rows;
    }

    /**
     * Get user's session completions (for dashboard)
     */
    public static function getUserCompletions(int $userId = 0) {
        // Get user ID from session if not provided
        if (!$userId && isset($_SESSION['user_id'])) {
            $userId = intval($_SESSION['user_id']);
        }
        if (!$userId) {
            return [];
        }
        
        global $conn;
        $rows = [];
        $sql = "SELECT sc.*, 
                sb.title AS batch_title,
                b.id AS batch_id,
                sbo.session_date,
                sbo.start_time AS scheduled_start,
                sbo.end_time AS scheduled_end,
                (SELECT COUNT(*) FROM session_attachments sa WHERE sa.completion_id = sc.id) AS attachment_count,
                (SELECT COUNT(*) FROM session_notes sn WHERE sn.completion_id = sc.id) AS note_count
                FROM session_completions sc
                LEFT JOIN schedule_batch_occurrences sbo ON sbo.id = sc.occurrence_id
                LEFT JOIN schedule_batches sb ON sb.id = sc.schedule_id
                LEFT JOIN batches b ON b.id = sc.batch_id
                WHERE sc.faculty_id = ? OR sc.employee_id = ?
                ORDER BY sbo.session_date DESC, sbo.start_time DESC";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, 'ii', $userId, $userId);
            if (mysqli_stmt_execute($stmt)) {
                $res = mysqli_stmt_get_result($stmt);
                while ($r = mysqli_fetch_assoc($res)) {
                    $rows[] = $r;
                }
            }
            mysqli_stmt_close($stmt);
        }
        return $rows;
    }

    /**
     * Get all pending/upcoming occurrences (for admin view)
     */
    public static function getAllPendingOccurrences() {
        global $conn;
        $rows = [];
        $sql = "SELECT sbo.id AS occurrence_id,
                sbo.schedule_id,
                sbo.session_date,
                sbo.start_time,
                sbo.end_time,
                sb.id AS schedule_id,
                sb.batch_id,
                b.title AS batch_title,
                b.branch_id,
                COUNT(DISTINCT sc.id) AS completion_count,
                MAX(sc.status) AS latest_status,
                MAX(sc.completed_at) AS last_completed
                FROM schedule_batch_occurrences sbo
                LEFT JOIN schedule_batches sb ON sb.id = sbo.schedule_id
                LEFT JOIN batches b ON b.id = sb.batch_id
                LEFT JOIN session_completions sc ON sc.occurrence_id = sbo.id
                GROUP BY sbo.id
                ORDER BY sbo.session_date DESC, sbo.start_time DESC";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            if (mysqli_stmt_execute($stmt)) {
                $res = mysqli_stmt_get_result($stmt);
                while ($r = mysqli_fetch_assoc($res)) {
                    $rows[] = $r;
                }
            }
            mysqli_stmt_close($stmt);
        }
        return $rows;
    }

    /**
     * Get pending/upcoming occurrences for a user
     */
    public static function getPendingOccurrences(int $userId = 0) {
        // Get user ID from session if not provided
        if (!$userId && isset($_SESSION['user_id'])) {
            $userId = intval($_SESSION['user_id']);
        }
        if (!$userId) {
            return [];
        }
        
        global $conn;
        $rows = [];
        $sql = "SELECT sbo.id AS occurrence_id,
                sbo.schedule_id,
                sbo.session_date,
                sbo.start_time,
                sbo.end_time,
                sb.batch_id,
                b.title AS batch_title,
                b.branch_id,
                COUNT(DISTINCT sc.id) AS completion_count,
                MAX(sc.status) AS latest_status,
                MAX(sc.completed_at) AS last_completed
                FROM schedule_batch_occurrences sbo
                JOIN schedule_batches sb ON sb.id = sbo.schedule_id
                JOIN batches b ON b.id = sb.batch_id
                LEFT JOIN session_completions sc ON sc.occurrence_id = sbo.id
                LEFT JOIN batch_assignments bax ON bax.batch_id = sb.batch_id AND bax.user_id = ? AND bax.role IN ('faculty','employee')
                WHERE (sb.faculty_id = ? OR bax.id IS NOT NULL)
                GROUP BY sbo.id
                ORDER BY sbo.session_date DESC, sbo.start_time DESC";
        // echo $sql;
        // exit;
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, 'ii', $userId, $userId);
            if (mysqli_stmt_execute($stmt)) {
                $res = mysqli_stmt_get_result($stmt);
                while ($r = mysqli_fetch_assoc($res)) {
                    $rows[] = $r;
                }
            }
            mysqli_stmt_close($stmt);
        }

        // // Fallback: if nothing found (e.g., faculty_id not set on schedule), show all occurrences for visibility
        // if (empty($rows)) {
        //     $rows = self::getAllPendingOccurrences();
        // }
        return $rows;
    }

    /**
     * Create a session completion entry
     */
    public static function create(array $data) {
        global $conn;
        $sql = "INSERT INTO session_completions (occurrence_id, schedule_id, batch_id, faculty_id, employee_id, 
                                                  actual_start_time, actual_end_time, completion_code, notes, status, completed_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $sql);
        $occurrenceId = intval($data['occurrence_id'] ?? 0);
        $scheduleId = intval($data['schedule_id'] ?? 0);
        $batchId = intval($data['batch_id'] ?? 0);
        $facultyId = intval($data['faculty_id'] ?? 0) ?: null;
        $employeeId = intval($data['employee_id'] ?? 0) ?: null;
        $actualStart = $data['actual_start_time'] ?? null;
        $actualEnd = $data['actual_end_time'] ?? null;
        $code = $data['completion_code'] ?? null;
        $notes = $data['notes'] ?? null;
        $status = $data['status'] ?? 'pending';
        $completedBy = intval($data['completed_by'] ?? 0) ?: null;

        mysqli_stmt_bind_param($stmt, 'iiiiiissssi',
            $occurrenceId, $scheduleId, $batchId, $facultyId, $employeeId,
            $actualStart, $actualEnd, $code, $notes, $status, $completedBy
        );

        if (mysqli_stmt_execute($stmt)) {
            $id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            return $id;
        }
        mysqli_stmt_close($stmt);
        return false;
    }

    /**
     * Update a session completion
     */
    public static function update(int $id, array $data) {
        global $conn;
        $updates = [];
        $types = '';
        $values = [];

        if (isset($data['actual_start_time'])) { $updates[] = 'actual_start_time = ?'; $types .= 's'; $values[] = $data['actual_start_time']; }
        if (isset($data['actual_end_time'])) { $updates[] = 'actual_end_time = ?'; $types .= 's'; $values[] = $data['actual_end_time']; }
        if (isset($data['completion_code'])) { $updates[] = 'completion_code = ?'; $types .= 's'; $values[] = $data['completion_code']; }
        if (isset($data['notes'])) { $updates[] = 'notes = ?'; $types .= 's'; $values[] = $data['notes']; }
        if (isset($data['status'])) { $updates[] = 'status = ?'; $types .= 's'; $values[] = $data['status']; }
        if (isset($data['completed_at'])) { $updates[] = 'completed_at = ?'; $types .= 's'; $values[] = $data['completed_at']; }

        if (empty($updates)) return false;

        $sql = "UPDATE session_completions SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = ?";
        $types .= 'i';
        $values[] = $id;

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$values);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    /**
     * Add a note to a session
     */
    public static function addNote(int $completionId, int $authorId, string $noteText, string $noteType = 'general') {
        global $conn;
        $sql = "INSERT INTO session_notes (completion_id, author_id, note_text, note_type) VALUES (?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'iiss', $completionId, $authorId, $noteText, $noteType);
        
        if (mysqli_stmt_execute($stmt)) {
            $id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            return $id;
        }
        mysqli_stmt_close($stmt);
        return false;
    }

    /**
     * Get notes for a session
     */
    public static function getNotes(int $completionId) {
        global $conn;
        $rows = [];
        $sql = "SELECT sn.*, u.name AS author_name, u.email 
                FROM session_notes sn
                LEFT JOIN users u ON u.id = sn.author_id
                WHERE sn.completion_id = ?
                ORDER BY sn.created_at DESC";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, 'i', $completionId);
            if (mysqli_stmt_execute($stmt)) {
                $res = mysqli_stmt_get_result($stmt);
                while ($r = mysqli_fetch_assoc($res)) {
                    $rows[] = $r;
                }
            }
            mysqli_stmt_close($stmt);
        }
        return $rows;
    }

    /**
     * Delete a note
     */
    public static function deleteNote(int $noteId) {
        global $conn;
        $stmt = mysqli_prepare($conn, "DELETE FROM session_notes WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $noteId);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }
}
?>
