<?php

namespace CampusLite\Controllers;

if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/controllers/AttendanceController.php
require_once __DIR__ . '/../../config/db.php';

class AttendanceController {
    // $entity_type can be 'student', 'faculty', 'employee' or null for all
    public static function getAll($entity_type = null, $branch_id = null) {
        global $conn;
        $rows = [];
        // Use prepared statements when filters are provided
        if ($entity_type && $branch_id) {
            $stmt = mysqli_prepare($conn, "SELECT * FROM attendance WHERE entity_type = ? AND branch_id = ?");
            $bid = intval($branch_id);
            mysqli_stmt_bind_param($stmt, 'si', $entity_type, $bid);
            if (mysqli_stmt_execute($stmt)) {
                $res = mysqli_stmt_get_result($stmt);
                while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
            }
            return $rows;
        }
        if ($entity_type) {
            $stmt = mysqli_prepare($conn, "SELECT * FROM attendance WHERE entity_type = ?");
            mysqli_stmt_bind_param($stmt, 's', $entity_type);
            if (mysqli_stmt_execute($stmt)) {
                $res = mysqli_stmt_get_result($stmt);
                while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
            }
            return $rows;
        }
        if ($branch_id) {
            $stmt = mysqli_prepare($conn, "SELECT * FROM attendance WHERE branch_id = ?");
            $bid = intval($branch_id);
            mysqli_stmt_bind_param($stmt, 'i', $bid);
            if (mysqli_stmt_execute($stmt)) {
                $res = mysqli_stmt_get_result($stmt);
                while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
            }
            return $rows;
        }
        $res = mysqli_query($conn, "SELECT * FROM attendance");
        while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
        return $rows;
    }
    public static function record($data) {
        global $conn;
        $stmt = mysqli_prepare($conn, "INSERT INTO attendance (branch_id, entity_type, entity_id, date, in_time, out_time, status, note, recorded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'isisssssi', $data['branch_id'] ?? null, $data['entity_type'] ?? '', $data['entity_id'] ?? null, $data['date'] ?? date('Y-m-d'), $data['in_time'] ?? null, $data['out_time'] ?? null, $data['status'] ?? '', $data['note'] ?? '', $data['recorded_by'] ?? null);
        return mysqli_stmt_execute($stmt);
    }
    public static function delete($id) {
        global $conn;
        $id = intval($id);
        $stmt = mysqli_prepare($conn, "DELETE FROM attendance WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        return mysqli_stmt_execute($stmt);
    }
}
?>
