<?php

namespace CampusLite\Controllers;

if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/controllers/FacultyController.php
require_once __DIR__ . '/../../config/db.php';
if (!isset($GLOBALS['conn']) || !($GLOBALS['conn'] instanceof \mysqli)) {
    $GLOBALS['conn'] = \db_conn();
}

class FacultyController {
    public static function getAll($branch_id = null) {
        global $conn;
        $sql = "SELECT * FROM users WHERE role = 'faculty'";
        if ($branch_id) $sql .= " AND branch_id = " . intval($branch_id);
        $res = mysqli_query($conn, $sql);
        $rows = [];
        while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
        return $rows;
    }
    public static function get($id) {
        global $conn;
        $id = intval($id);
        $res = mysqli_query($conn, "SELECT * FROM users WHERE id = $id AND role='faculty' LIMIT 1");
        return mysqli_fetch_assoc($res) ?: null;
    }
    public static function create($data) {
        global $conn;
        // defensive: ensure expected keys exist
        $branch_id = intval($data['branch_id'] ?? 0);
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $password_raw = $data['password'] ?? 'password';
        $hashed = password_hash($password_raw, PASSWORD_DEFAULT);
        $mobile = $data['mobile'] ?? '';
        $is_part_time = intval($data['is_part_time'] ?? 0);
        $status = intval($data['status'] ?? 1);

        $stmt = mysqli_prepare($conn, "INSERT INTO users (branch_id, role, name, email, password, mobile, is_part_time, status) VALUES (?, 'faculty', ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'issssii', $branch_id, $name, $email, $hashed, $mobile, $is_part_time, $status);
        return mysqli_stmt_execute($stmt);
    }
    public static function update($id, $data) {
        global $conn;
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $mobile = $data['mobile'] ?? '';
        $is_part_time = intval($data['is_part_time'] ?? 0);
        $status = intval($data['status'] ?? 1);

        // If password provided and non-empty, update it as well
        if (!empty($data['password'])) {
            $password_raw = $data['password'];
            $hashed = password_hash($password_raw, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "UPDATE users SET name=?, email=?, mobile=?, is_part_time=?, status=?, password=? WHERE id=? AND role='faculty'");
            mysqli_stmt_bind_param($stmt, 'sssiisi', $name, $email, $mobile, $is_part_time, $status, $hashed, $id);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE users SET name=?, email=?, mobile=?, is_part_time=?, status=? WHERE id=? AND role='faculty'");
            mysqli_stmt_bind_param($stmt, 'sssiii', $name, $email, $mobile, $is_part_time, $status, $id);
        }

        return mysqli_stmt_execute($stmt);
    }
    public static function delete($id) {
        global $conn;
        $id = intval($id);
        return mysqli_query($conn, "DELETE FROM users WHERE id = $id AND role='faculty'");
    }
}
?>
