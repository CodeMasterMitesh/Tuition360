<?php

namespace CampusLite\Controllers;

if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }

require_once __DIR__ . '/../../config/db.php';
if (!isset($GLOBALS['conn']) || !($GLOBALS['conn'] instanceof \mysqli)) {
    $GLOBALS['conn'] = \db_conn();
}

class ScheduleBatchController {
    public static function all() {
        global $conn;
        $rows = [];
        $sql = "SELECT sb.*, b.title AS batch_title, b.branch_id AS batch_branch_id,
                (SELECT name FROM users u JOIN batch_assignments ba ON ba.user_id=u.id AND ba.role='faculty' WHERE ba.batch_id=sb.batch_id LIMIT 1) AS faculty_name
                FROM schedule_batches sb
                JOIN batches b ON b.id = sb.batch_id
                ORDER BY sb.created_at DESC";
        if ($res = mysqli_query($conn, $sql)) {
            while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
        }
        return $rows;
    }

    public static function get($id) {
        global $conn;
        $id = intval($id);
        $sql = "SELECT sb.*, 
                COALESCE(b.title, 'N/A') AS batch_title, 
                b.course_id, 
                b.branch_id AS batch_branch_id,
                (SELECT name FROM users u JOIN batch_assignments ba ON ba.user_id=u.id AND ba.role='faculty' WHERE ba.batch_id=sb.batch_id LIMIT 1) AS faculty_name
                FROM schedule_batches sb 
                LEFT JOIN batches b ON b.id = sb.batch_id 
                WHERE sb.id = ? LIMIT 1";
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
        error_log("ScheduleBatchController::get($id) failed: " . mysqli_error($conn));
        return null;
    }

    public static function create($data) {
        global $conn;
        $sql = "INSERT INTO schedule_batches (branch_id, batch_id, faculty_id, recurrence, start_date, end_date, day_of_week, weekdays, day_of_month, start_time, end_time, subject_ids, student_ids, notes, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        $branch_id = intval($data['branch_id'] ?? 0);
        $batch_id = intval($data['batch_id'] ?? 0);
        
        // Handle faculty_ids array - store as JSON or take first faculty as primary
        $faculty_id = null;
        if (isset($data['faculty_ids']) && is_array($data['faculty_ids']) && !empty($data['faculty_ids'])) {
            $faculty_id = intval($data['faculty_ids'][0]); // Store first faculty as primary
        } elseif (isset($data['faculty_id']) && $data['faculty_id'] !== '') {
            $faculty_id = intval($data['faculty_id']);
        }
        
        $recurrence = $data['recurrence'] ?? 'daily';
        $start_date = !empty($data['start_date']) ? $data['start_date'] : null;
        $end_date = !empty($data['end_date']) ? $data['end_date'] : null;
        $day_of_week = isset($data['day_of_week']) && $data['day_of_week'] !== '' ? intval($data['day_of_week']) : null;
        // Support multiple weekdays via 'weekdays' array
        $weekdays = [];
        if (isset($data['weekdays'])) {
            if (is_string($data['weekdays'])) {
                $dec = json_decode($data['weekdays'], true);
                if (is_array($dec)) $weekdays = $dec;
            } elseif (is_array($data['weekdays'])) {
                $weekdays = array_values(array_unique(array_map('intval', $data['weekdays'])));
            }
        }
        $weekdaysJson = !empty($weekdays) ? json_encode($weekdays) : null;
        $day_of_month = isset($data['day_of_month']) && $data['day_of_month'] !== '' ? intval($data['day_of_month']) : null;
        $start_time = $data['start_time'] ?? null;
        $end_time = $data['end_time'] ?? null;
        $subject_ids = isset($data['subject_ids']) ? json_encode($data['subject_ids']) : null;
        $student_ids = isset($data['student_ids']) ? json_encode($data['student_ids']) : null;
        $notes = $data['notes'] ?? null;
        $status = ($data['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
        mysqli_stmt_bind_param($stmt, 'iiisssisissssss',
            $branch_id, $batch_id, $faculty_id,
            $recurrence, $start_date, $end_date,
            $day_of_week, $weekdaysJson, $day_of_month,
            $start_time, $end_time,
            $subject_ids, $student_ids, $notes, $status
        );
        $ok = mysqli_stmt_execute($stmt);
        if ($ok) {
            $scheduleId = mysqli_insert_id($conn);
            // Generate occurrences for the schedule
            self::regenerateOccurrences($scheduleId);
            return $scheduleId;
        }
        return false;
    }

    public static function update($id, $data) {
        global $conn;
        $sql = "UPDATE schedule_batches SET branch_id=?, batch_id=?, faculty_id=?, recurrence=?, start_date=?, end_date=?, day_of_week=?, weekdays=?, day_of_month=?, start_time=?, end_time=?, subject_ids=?, student_ids=?, notes=?, status=? WHERE id=?";
        $stmt = mysqli_prepare($conn, $sql);
        $branch_id = intval($data['branch_id'] ?? 0);
        $batch_id = intval($data['batch_id'] ?? 0);
        
        // Handle faculty_ids array - store as JSON or take first faculty as primary
        $faculty_id = null;
        if (isset($data['faculty_ids']) && is_array($data['faculty_ids']) && !empty($data['faculty_ids'])) {
            $faculty_id = intval($data['faculty_ids'][0]); // Store first faculty as primary
        } elseif (isset($data['faculty_id']) && $data['faculty_id'] !== '') {
            $faculty_id = intval($data['faculty_id']);
        }
        
        $recurrence = $data['recurrence'] ?? 'daily';
        $start_date = !empty($data['start_date']) ? $data['start_date'] : null;
        $end_date = !empty($data['end_date']) ? $data['end_date'] : null;
        $day_of_week = isset($data['day_of_week']) && $data['day_of_week'] !== '' ? intval($data['day_of_week']) : null;
        // Support multiple weekdays via 'weekdays' array
        $weekdays = [];
        if (isset($data['weekdays'])) {
            if (is_string($data['weekdays'])) {
                $dec = json_decode($data['weekdays'], true);
                if (is_array($dec)) $weekdays = $dec;
            } elseif (is_array($data['weekdays'])) {
                $weekdays = array_values(array_unique(array_map('intval', $data['weekdays'])));
            }
        }
        $weekdaysJson = !empty($weekdays) ? json_encode($weekdays) : null;
        $day_of_month = isset($data['day_of_month']) && $data['day_of_month'] !== '' ? intval($data['day_of_month']) : null;
        $start_time = $data['start_time'] ?? null;
        $end_time = $data['end_time'] ?? null;
        $subject_ids = isset($data['subject_ids']) ? json_encode($data['subject_ids']) : null;
        $student_ids = isset($data['student_ids']) ? json_encode($data['student_ids']) : null;
        $notes = $data['notes'] ?? null;
        $status = ($data['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
        $id = intval($id);
        mysqli_stmt_bind_param($stmt, 'iiisssisissssssi',
            $branch_id, $batch_id, $faculty_id,
            $recurrence, $start_date, $end_date,
            $day_of_week, $weekdaysJson, $day_of_month,
            $start_time, $end_time,
            $subject_ids, $student_ids, $notes, $status, $id
        );
        $ok = mysqli_stmt_execute($stmt);
        if ($ok) {
            // Regenerate occurrences after update
            self::regenerateOccurrences($id);
        }
        return $ok;
    }

    public static function delete($id) {
        global $conn;
        // Deleting schedule will cascade delete occurrences via FK
        $stmt = mysqli_prepare($conn, "DELETE FROM schedule_batches WHERE id=?");
        $id = intval($id);
        mysqli_stmt_bind_param($stmt, 'i', $id);
        return mysqli_stmt_execute($stmt);
    }

    /**
     * Regenerate occurrences for a schedule_id based on its meta
     */
    private static function regenerateOccurrences(int $scheduleId): void {
        global $conn;
        $schedule = self::get($scheduleId);
        if (!$schedule) return;

        // Delete existing occurrences
        $del = mysqli_prepare($conn, "DELETE FROM schedule_batch_occurrences WHERE schedule_id = ?");
        mysqli_stmt_bind_param($del, 'i', $scheduleId);
        mysqli_stmt_execute($del);
        mysqli_stmt_close($del);

        $recurrence = $schedule['recurrence'] ?? 'daily';
        $startDate = $schedule['start_date'] ?? null;
        $endDate = $schedule['end_date'] ?? null;
        $startTime = $schedule['start_time'] ?? null;
        $endTime = $schedule['end_time'] ?? null;
        $dayOfWeek = isset($schedule['day_of_week']) ? intval($schedule['day_of_week']) : null;
        $dayOfMonth = isset($schedule['day_of_month']) ? intval($schedule['day_of_month']) : null;
        $weekdays = [];
        if (!empty($schedule['weekdays'])) {
            $dec = json_decode($schedule['weekdays'], true);
            if (is_array($dec)) $weekdays = array_values(array_unique(array_map('intval', $dec)));
        }

        if (!$startDate || !$endDate) return;
        $startTs = strtotime($startDate);
        $endTs = strtotime($endDate);
        if ($startTs === false || $endTs === false || $startTs > $endTs) return;

        $ins = mysqli_prepare($conn, "INSERT INTO schedule_batch_occurrences (schedule_id, session_date, start_time, end_time) VALUES (?,?,?,?)");
        $dateList = [];

        if ($recurrence === 'daily') {
            for ($ts = $startTs; $ts <= $endTs; $ts = strtotime('+1 day', $ts)) {
                $dateList[] = date('Y-m-d', $ts);
            }
        } elseif ($recurrence === 'weekly') {
            $days = !empty($weekdays) ? $weekdays : (isset($dayOfWeek) ? [$dayOfWeek] : []);
            if (!empty($days)) {
                // Iterate through each day and add matching dates
                for ($ts = $startTs; $ts <= $endTs; $ts = strtotime('+1 day', $ts)) {
                    $dow = intval(date('w', $ts)); // 0=Sun..6=Sat
                    if (in_array($dow, $days, true)) {
                        $dateList[] = date('Y-m-d', $ts);
                    }
                }
            }
        } elseif ($recurrence === 'monthly') {
            if (!empty($dayOfMonth)) {
                // Walk months from start to end
                $cur = strtotime(date('Y-m-01', $startTs));
                while ($cur !== false && $cur <= $endTs) {
                    $y = intval(date('Y', $cur));
                    $m = intval(date('n', $cur));
                    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $m, $y);
                    if ($dayOfMonth <= $daysInMonth) {
                        $dt = sprintf('%04d-%02d-%02d', $y, $m, $dayOfMonth);
                        $dtTs = strtotime($dt);
                        if ($dtTs !== false && $dtTs >= $startTs && $dtTs <= $endTs) {
                            $dateList[] = date('Y-m-d', $dtTs);
                        }
                    }
                    $cur = strtotime('+1 month', $cur);
                }
            }
        }

        foreach ($dateList as $d) {
            mysqli_stmt_bind_param($ins, 'isss', $scheduleId, $d, $startTime, $endTime);
            mysqli_stmt_execute($ins);
        }
        mysqli_stmt_close($ins);
    }
}

?>
