<?php
// app/controllers/BatchAssignmentController.php
require_once __DIR__ . '/../../config/db.php';
class BatchAssignmentController {
    public static function getAll() {
        global $conn;
        $sql = "SELECT * FROM batch_assignments ORDER BY assigned_at DESC";
        $res = mysqli_query($conn, $sql);
        $rows = [];
        while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
        return $rows;
    }
    public static function get($id) {
        global $conn;
        $id = intval($id);
        $res = mysqli_query($conn, "SELECT * FROM batch_assignments WHERE id = $id LIMIT 1");
        return mysqli_fetch_assoc($res);
    }
    public static function create($data) {
        global $conn;
        $stmt = mysqli_prepare($conn, "INSERT INTO batch_assignments (batch_id, user_id, role, assigned_at) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'iiss', $data['batch_id'], $data['user_id'], $data['role'], $data['assigned_at']);
        return mysqli_stmt_execute($stmt);
    }
    public static function update($id, $data) {
        global $conn;
        $stmt = mysqli_prepare($conn, "UPDATE batch_assignments SET batch_id=?, user_id=?, role=?, assigned_at=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'iissi', $data['batch_id'], $data['user_id'], $data['role'], $data['assigned_at'], $id);
        return mysqli_stmt_execute($stmt);
    }
    public static function delete($id) {
        global $conn;
        $id = intval($id);
        return mysqli_query($conn, "DELETE FROM batch_assignments WHERE id = $id");
    }
}
?>