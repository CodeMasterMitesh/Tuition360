<?php
if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/controllers/FeeController.php
require_once __DIR__ . '/../../config/db.php';
class FeeController {
    public static function getAll($branch_id = null) {
        global $conn;
        $sql = "SELECT * FROM fees";
        if ($branch_id) $sql .= " WHERE branch_id = " . intval($branch_id);
        $res = mysqli_query($conn, $sql);
        $rows = [];
        while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
        return $rows;
    }
    public static function create($data) {
        global $conn;
        $stmt = mysqli_prepare($conn, "INSERT INTO fees (branch_id, student_id, enrollment_id, amount, payment_date, payment_mode, receipt_no, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'iiidsssi', $data['branch_id'], $data['student_id'], $data['enrollment_id'], $data['amount'], $data['payment_date'], $data['payment_mode'], $data['receipt_no'], $data['created_by']);
        return mysqli_stmt_execute($stmt);
    }
    public static function delete($id) {
        global $conn;
        $id = intval($id);
        return mysqli_query($conn, "DELETE FROM fees WHERE id = $id");
    }
}
?>
