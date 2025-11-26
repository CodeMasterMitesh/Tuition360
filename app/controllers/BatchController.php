<?php
if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/controllers/BatchController.php
require_once __DIR__ . '/../../config/db.php';
class BatchController {
    public static function getAll($branch_id = null) {
        global $conn;
        $sql = "SELECT * FROM batches";
        if ($branch_id) $sql .= " WHERE branch_id = " . intval($branch_id);
        $res = mysqli_query($conn, $sql);
        $rows = [];
        while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
        return $rows;
    }
    public static function create($data) {
        global $conn;
        $stmt = mysqli_prepare($conn, "INSERT INTO batches (branch_id, course_id, title, start_date, end_date, days_of_week, time_slot, capacity, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'iisssssis', $data['branch_id'], $data['course_id'], $data['title'], $data['start_date'], $data['end_date'], $data['days_of_week'], $data['time_slot'], $data['capacity'], $data['status']);
        return mysqli_stmt_execute($stmt);
    }
    public static function delete($id) {
        global $conn;
        $id = intval($id);
        return mysqli_query($conn, "DELETE FROM batches WHERE id = $id");
    }
}
?>
