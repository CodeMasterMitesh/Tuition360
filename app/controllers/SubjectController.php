<?php

namespace CampusLite\Controllers;

if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/controllers/SubjectController.php
require_once __DIR__ . '/../../config/db.php';
if (!isset($GLOBALS['conn']) || !($GLOBALS['conn'] instanceof \mysqli)) {
    $GLOBALS['conn'] = \db_conn();
}

class SubjectController {
    public static function getAll() {
        global $conn;
        $result = mysqli_query($conn, "SELECT * FROM subjects");
        $subjects = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $subjects[] = $row;
        }
        return $subjects;
    }
    public static function create($title, $description) {
        global $conn;
        $stmt = mysqli_prepare($conn, "INSERT INTO subjects (title, description) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, 'ss', $title, $description);
        return mysqli_stmt_execute($stmt);
    }
}
?>
