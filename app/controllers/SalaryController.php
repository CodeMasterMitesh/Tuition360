<?php
if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/controllers/SalaryController.php
require_once __DIR__ . '/../../config/db.php';
class SalaryController {
    public static function getAll($branch_id = null) {
        global $conn;
        $sql = "SELECT * FROM salaries";
        if ($branch_id) $sql .= " WHERE branch_id = " . intval($branch_id);
        $res = mysqli_query($conn, $sql);
        $rows = [];
        while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
        return $rows;
    }
    public static function create($data) {
        global $conn;
        $stmt = mysqli_prepare($conn, "INSERT INTO salaries (branch_id, user_id, salary_month, gross_amount, deductions, net_amount, paid_on, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'iisdddss', $data['branch_id'], $data['user_id'], $data['salary_month'], $data['gross_amount'], $data['deductions'], $data['net_amount'], $data['paid_on'], $data['status']);
        return mysqli_stmt_execute($stmt);
    }
    public static function delete($id) {
        global $conn;
        $id = intval($id);
        return mysqli_query($conn, "DELETE FROM salaries WHERE id = $id");
    }
}
?>
