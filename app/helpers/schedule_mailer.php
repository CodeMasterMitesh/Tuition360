<?php
// app/helpers/schedule_mailer.php
// Sends schedule batch notifications to selected students

require_once __DIR__ . '/mail.php';
require_once __DIR__ . '/../controllers/ScheduleBatchController.php';
require_once __DIR__ . '/../../config/db.php';

/**
 * Send schedule notification emails to given student IDs
 * @param int $scheduleId
 * @param array $studentIds
 * @return array ['sent'=>int,'failed'=>int]
 */
function send_schedule_batch_notifications(int $scheduleId, array $studentIds = []): array {
    global $conn;
    $summary = ['sent' => 0, 'failed' => 0];
    if (empty($studentIds)) return $summary;

    $schedule = \CampusLite\Controllers\ScheduleBatchController::get($scheduleId);
    if (!$schedule) return $summary;

    $subjectIds = json_decode($schedule['subject_ids'] ?? '[]', true) ?: [];
    $subjects = fetch_subjects_by_ids($subjectIds);
    $students = fetch_students_by_ids($studentIds);
    if (empty($students)) return $summary;

    $batchTitle = $schedule['batch_title'] ?? 'Batch #' . $schedule['batch_id'];
    $facultyName = $schedule['faculty_name'] ?? 'Faculty';
    $timing = format_schedule_timing($schedule);

    $template = __DIR__ . '/../views/partials/emails/schedule_batch_email.php';

    foreach ($students as $stu) {
        if (empty($stu['email'])) { $summary['failed']++; continue; }
        $html = render_template($template, [
            'student_name' => $stu['name'] ?? 'Student',
            'batch_title' => $batchTitle,
            'faculty_name' => $facultyName,
            'timing' => $timing,
            'recurrence' => ucfirst($schedule['recurrence'] ?? 'daily'),
            'subjects' => implode(', ', array_column($subjects, 'title')),
            'notes' => $schedule['notes'] ?? ''
        ]);

        $ok = send_mail_message([
            'to' => [['email' => $stu['email'], 'name' => $stu['name'] ?? '']],
            'subject' => 'Class Schedule: ' . $batchTitle,
            'html' => $html,
            'alt' => strip_tags($html)
        ]);

        if ($ok) $summary['sent']++; else $summary['failed']++;
    }
    return $summary;
}

function fetch_subjects_by_ids(array $ids): array {
    global $conn;
    $out = [];
    $ids = array_filter(array_map('intval', $ids));
    if (empty($ids)) return $out;
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $sql = "SELECT id, title FROM subjects WHERE id IN ($placeholders)";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, $types, ...$ids);
        if (mysqli_stmt_execute($stmt)) {
            $res = mysqli_stmt_get_result($stmt);
            while ($r = mysqli_fetch_assoc($res)) $out[] = $r;
        }
    }
    return $out;
}

function fetch_students_by_ids(array $ids): array {
    global $conn;
    $out = [];
    $ids = array_filter(array_map('intval', $ids));
    if (empty($ids)) return $out;
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $sql = "SELECT id, name, email FROM students WHERE id IN ($placeholders)";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, $types, ...$ids);
        if (mysqli_stmt_execute($stmt)) {
            $res = mysqli_stmt_get_result($stmt);
            while ($r = mysqli_fetch_assoc($res)) $out[] = $r;
        }
    }
    return $out;
}

function format_schedule_timing(array $schedule): string {
    $parts = [];
    
    // Format date range
    if (!empty($schedule['start_date']) && $schedule['start_date'] !== '0000-00-00') {
        $startDate = date('M d, Y', strtotime($schedule['start_date']));
        if (!empty($schedule['end_date']) && $schedule['end_date'] !== '0000-00-00') {
            $endDate = date('M d, Y', strtotime($schedule['end_date']));
            $parts[] = "$startDate to $endDate";
        } else {
            $parts[] = "Starting $startDate";
        }
    }
    
    // Format time range
    if (!empty($schedule['start_time']) && $schedule['start_time'] !== '00:00:00') {
        $startTime = date('g:i A', strtotime($schedule['start_time']));
        if (!empty($schedule['end_time']) && $schedule['end_time'] !== '00:00:00') {
            $endTime = date('g:i A', strtotime($schedule['end_time']));
            $parts[] = "$startTime - $endTime";
        } else {
            $parts[] = "at $startTime";
        }
    }
    
    return !empty($parts) ? implode(', ', $parts) : 'Schedule details to be confirmed';
}
