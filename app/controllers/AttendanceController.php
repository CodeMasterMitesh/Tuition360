<?php

namespace CampusLite\Controllers;

if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/controllers/AttendanceController.php
require_once __DIR__ . '/../../config/db.php';
if (!isset($GLOBALS['conn']) || !($GLOBALS['conn'] instanceof \mysqli)) {
    $GLOBALS['conn'] = \db_conn();
}

class AttendanceController {
    // $entity_type can be 'student', 'faculty', 'employee' or null for all
    public static function getAll($entity_type = null, $branch_id = null) {
        global $conn;
        $rows = [];
        
        // Build query based on entity type
        if ($entity_type === 'student') {
            // Join with students and batches for student attendance
                $sql = "SELECT a.*, s.name as student_name, s.email as student_email, 
                    b.title as batch_title, br.name as branch_name
                    FROM attendance a 
                    LEFT JOIN students s ON a.entity_id = s.id AND a.entity_type = 'student'
                    LEFT JOIN batch_assignment_students bas ON bas.student_id = s.id
                    LEFT JOIN batch_assignments ba ON ba.id = bas.assignment_id
                    LEFT JOIN batches b ON b.id = ba.batch_id
                    LEFT JOIN branches br ON br.id = a.branch_id
                    WHERE a.entity_type = 'student'";
            
            if ($branch_id) {
                $sql .= " AND a.branch_id = ?";
                $stmt = mysqli_prepare($conn, $sql . " ORDER BY a.date DESC, s.name ASC");
                $bid = intval($branch_id);
                mysqli_stmt_bind_param($stmt, 'i', $bid);
            } else {
                $stmt = mysqli_prepare($conn, $sql . " ORDER BY a.date DESC, s.name ASC");
            }
            
            if ($stmt && mysqli_stmt_execute($stmt)) {
                $res = mysqli_stmt_get_result($stmt);
                while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
            }
            return $rows;
        }
        
        // For faculty/employee
        if ($entity_type === 'faculty' || $entity_type === 'employee') {
            $sql = "SELECT a.*, u.name as user_name, br.name as branch_name
                    FROM attendance a 
                    LEFT JOIN users u ON a.entity_id = u.id
                    LEFT JOIN branches br ON br.id = a.branch_id
                    WHERE a.entity_type = ?";
            
            if ($branch_id) {
                $sql .= " AND a.branch_id = ?";
                $stmt = mysqli_prepare($conn, $sql . " ORDER BY a.date DESC");
                $bid = intval($branch_id);
                mysqli_stmt_bind_param($stmt, 'si', $entity_type, $bid);
            } else {
                $stmt = mysqli_prepare($conn, $sql . " ORDER BY a.date DESC");
                mysqli_stmt_bind_param($stmt, 's', $entity_type);
            }
            
            if ($stmt && mysqli_stmt_execute($stmt)) {
                $res = mysqli_stmt_get_result($stmt);
                while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
            }
            return $rows;
        }
        
        // All attendance (no entity type filter)
        if ($branch_id) {
            $stmt = mysqli_prepare($conn, "SELECT a.*, br.name as branch_name FROM attendance a LEFT JOIN branches br ON br.id = a.branch_id WHERE a.branch_id = ? ORDER BY a.date DESC");
            $bid = intval($branch_id);
            mysqli_stmt_bind_param($stmt, 'i', $bid);
            if (mysqli_stmt_execute($stmt)) {
                $res = mysqli_stmt_get_result($stmt);
                while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
            }
            return $rows;
        }
        
        $res = mysqli_query($conn, "SELECT a.*, br.name as branch_name FROM attendance a LEFT JOIN branches br ON br.id = a.branch_id ORDER BY a.date DESC");
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
