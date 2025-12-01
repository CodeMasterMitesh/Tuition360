<?php

namespace CampusLite\Controllers;

if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/controllers/CompanyController.php
require_once __DIR__ . '/../../config/db.php';

class CompanyController {
    public static function getAll() {
        global $conn;
        $res = mysqli_query($conn, "SELECT * FROM company");
        $rows = [];
        while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
        return $rows;
    }
    public static function get($id) {
        global $conn;
        $id = intval($id);
        $res = mysqli_query($conn, "SELECT * FROM company WHERE id = $id LIMIT 1");
        return mysqli_fetch_assoc($res) ?: null;
    }
    public static function save($data) {
        global $conn;
        // if id exists update else insert
        if (!empty($data['id'])) {
            $stmt = mysqli_prepare($conn, "UPDATE company SET name=?, address=?, phone=?, email=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'ssssi', $data['name'], $data['address'], $data['phone'], $data['email'], $data['id']);
            return mysqli_stmt_execute($stmt);
        }
        $stmt = mysqli_prepare($conn, "INSERT INTO company (name, address, phone, email) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'ssss', $data['name'], $data['address'], $data['phone'], $data['email']);
        return mysqli_stmt_execute($stmt);
    }
}
?>
