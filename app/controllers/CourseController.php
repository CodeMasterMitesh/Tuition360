<?php
if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/controllers/CourseController.php
require_once __DIR__ . '/../../config/db.php';
class CourseController {
    public static function getAll($branch_id = null) {
        global $conn;
        $sql = "SELECT * FROM courses";
        if ($branch_id) {
            $sql .= " WHERE branch_id = " . intval($branch_id);
        }
        $result = mysqli_query($conn, $sql);
        $courses = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $courses[] = $row;
        }
        return $courses;
    }
    public static function create($branch_id, $title, $description, $total_fee, $duration_months) {
        global $conn;
        $stmt = mysqli_prepare($conn, "INSERT INTO courses (branch_id, title, description, total_fee, duration_months) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'issdi', $branch_id, $title, $description, $total_fee, $duration_months);
        return mysqli_stmt_execute($stmt);
    }
}
?>
