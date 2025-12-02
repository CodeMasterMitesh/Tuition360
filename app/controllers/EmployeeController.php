<?php

namespace CampusLite\Controllers;

if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/controllers/EmployeeController.php
require_once __DIR__ . '/../../config/db.php';
if (!isset($GLOBALS['conn']) || !($GLOBALS['conn'] instanceof \mysqli)) {
    $GLOBALS['conn'] = \db_conn();
}

class EmployeeController {
    public static function getAll($branch_id = null) {
        global $conn;
        $sql = "SELECT * FROM users WHERE role = 'employee'";
        if ($branch_id) $sql .= " AND branch_id = " . intval($branch_id);
        $res = mysqli_query($conn, $sql);
        $rows = [];
        while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
        return $rows;
    }
    public static function get($id) {
        global $conn;
        $id = intval($id);
        $res = mysqli_query($conn, "SELECT * FROM users WHERE id = $id AND role='employee' LIMIT 1");
        return mysqli_fetch_assoc($res) ?: null;
    }
    public static function create($data) {
        global $conn;
        $hashed = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "INSERT INTO users (branch_id, role, name, email, password, mobile, is_part_time, status) VALUES (?, 'employee', ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'issssii', $data['branch_id'], $data['name'], $data['email'], $hashed, $data['mobile'], $data['is_part_time'], $data['status']);
        return mysqli_stmt_execute($stmt);
    }
    public static function update($id, $data) {
        global $conn;
        $stmt = mysqli_prepare($conn, "UPDATE users SET name=?, email=?, mobile=?, is_part_time=?, status=? WHERE id=? AND role='employee'");
        mysqli_stmt_bind_param($stmt, 'sssiii', $data['name'], $data['email'], $data['mobile'], $data['is_part_time'], $data['status'], $id);
        return mysqli_stmt_execute($stmt);
    }
    public static function delete($id) {
        global $conn;
        $id = intval($id);
        return mysqli_query($conn, "DELETE FROM users WHERE id = $id AND role='employee'");
    }
}
?>
