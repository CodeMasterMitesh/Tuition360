<?php

namespace CampusLite\Controllers;

if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/controllers/CourseController.php
require_once __DIR__ . '/../../config/db.php';

class CourseController {
    public static function getAll($branch_id = null) {
        global $conn;
        $courses = [];
        // We'll return course metadata including total capacity across batches and enrolled count
        if ($branch_id) {
            $sql = "SELECT c.*, IFNULL(SUM(b.capacity),0) AS total_capacity, IFNULL(SUM(IFNULL(ec.enrolled,0)),0) AS enrolled_count
                    FROM courses c
                    LEFT JOIN batches b ON b.course_id = c.id
                    LEFT JOIN (
                        SELECT batch_id, COUNT(*) AS enrolled FROM enrollments GROUP BY batch_id
                    ) ec ON ec.batch_id = b.id
                    WHERE c.branch_id = ?
                    GROUP BY c.id";
            $stmt = mysqli_prepare($conn, $sql);
            $bid = intval($branch_id);
            mysqli_stmt_bind_param($stmt, 'i', $bid);
            if (mysqli_stmt_execute($stmt)) {
                $res = mysqli_stmt_get_result($stmt);
                while ($row = mysqli_fetch_assoc($res)) $courses[] = $row;
            }
            return $courses;
        }
        $sql = "SELECT c.*, IFNULL(SUM(b.capacity),0) AS total_capacity, IFNULL(SUM(IFNULL(ec.enrolled,0)),0) AS enrolled_count
                FROM courses c
                LEFT JOIN batches b ON b.course_id = c.id
                LEFT JOIN (
                    SELECT batch_id, COUNT(*) AS enrolled FROM enrollments GROUP BY batch_id
                ) ec ON ec.batch_id = b.id
                GROUP BY c.id";
        $result = mysqli_query($conn, $sql);
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
