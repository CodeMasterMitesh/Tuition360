<?php

namespace CampusLite\Controllers;

if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/controllers/UserController.php
require_once __DIR__ . '/../../config/db.php';
if (!isset($GLOBALS['conn']) || !($GLOBALS['conn'] instanceof \mysqli)) {
    $GLOBALS['conn'] = \db_conn();
}

class UserController {
    public static function getAll() {
        global $conn;
        $result = mysqli_query($conn, "SELECT * FROM users");
        $users = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }
        return $users;
    }
    public static function create($branch_id, $role, $name, $email, $password, $mobile, $is_part_time) {
        global $conn;
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "INSERT INTO users (branch_id, role, name, email, password, mobile, is_part_time) VALUES (?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'isssssi', $branch_id, $role, $name, $email, $hashed_password, $mobile, $is_part_time);
        return mysqli_stmt_execute($stmt);
    }
}
?>
