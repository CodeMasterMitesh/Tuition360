<?php
// app/controllers/AttendanceController.php
require_once __DIR__ . '/../../config/db.php';
class AttendanceController {
    // $entity_type can be 'student', 'faculty', 'employee' or null for all
    public static function getAll($entity_type = null, $branch_id = null) {
        global $conn;
        $sql = "SELECT * FROM attendance WHERE 1=1";
        if ($entity_type) {
            $sql .= " AND entity_type = '" . mysqli_real_escape_string($conn, $entity_type) . "'";
        }
        if ($branch_id) {
            $sql .= " AND branch_id = " . intval($branch_id);
        }
        $res = mysqli_query($conn, $sql);
        $rows = [];
        while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
        return $rows;
    }
    public static function record($data) {
        global $conn;
        $stmt = mysqli_prepare($conn, "INSERT INTO attendance (branch_id, entity_type, entity_id, date, in_time, out_time, status, note, recorded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'isisssssi', $data['branch_id'] ?? null, $data['entity_type'] ?? '', $data['entity_id'] ?? null, $data['date'] ?? date('Y-m-d'), $data['in_time'] ?? null, $data['out_time'] ?? null, $data['status'] ?? '', $data['note'] ?? '', $data['recorded_by'] ?? null);
        return mysqli_stmt_execute($stmt);
    }
    public static function delete($id) {
        global $conn;
        $id = intval($id);
        return mysqli_query($conn, "DELETE FROM attendance WHERE id = $id");
    }
}
?>
