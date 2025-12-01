<?php

namespace CampusLite\Controllers;

if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/controllers/LeaveController.php
require_once __DIR__ . '/../../config/db.php';

class LeaveController {
    public static function getAll($user_id = null) {
        global $conn;
        $sql = "SELECT * FROM leaves";
        if ($user_id) $sql .= " WHERE user_id = " . intval($user_id);
        $res = mysqli_query($conn, $sql);
        $rows = [];
        while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
        return $rows;
    }
    public static function create($data) {
        global $conn;
        $stmt = mysqli_prepare($conn, "INSERT INTO leaves (user_id, branch_id, leave_type, from_date, to_date, reason, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'iisssss', $data['user_id'], $data['branch_id'], $data['leave_type'], $data['from_date'], $data['to_date'], $data['reason'], $data['status']);
        return mysqli_stmt_execute($stmt);
    }
    public static function delete($id) {
        global $conn;
        $id = intval($id);
        return mysqli_query($conn, "DELETE FROM leaves WHERE id = $id");
    }
}
?>
