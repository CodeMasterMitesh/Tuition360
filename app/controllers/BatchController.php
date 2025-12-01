<?php

namespace CampusLite\Controllers;

if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/controllers/BatchController.php
require_once __DIR__ . '/../../config/db.php';

class BatchController {
    public static function getAll($branch_id = null) {
        global $conn;
        $rows = [];
        if ($branch_id) {
            $stmt = mysqli_prepare($conn, "SELECT b.*, c.title AS course_title FROM batches b LEFT JOIN courses c ON b.course_id = c.id WHERE b.branch_id = ?");
            $bid = intval($branch_id);
            mysqli_stmt_bind_param($stmt, 'i', $bid);
            if (mysqli_stmt_execute($stmt)) {
                $res = mysqli_stmt_get_result($stmt);
                while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
            }
            return $rows;
        }
        $res = mysqli_query($conn, "SELECT b.*, c.title AS course_title FROM batches b LEFT JOIN courses c ON b.course_id = c.id");
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
        $stmt = mysqli_prepare($conn, "DELETE FROM batches WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        return mysqli_stmt_execute($stmt);
    }
}
?>
