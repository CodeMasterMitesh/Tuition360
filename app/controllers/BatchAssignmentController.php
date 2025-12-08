<?php

namespace CampusLite\Controllers;

if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/controllers/BatchAssignmentController.php
require_once __DIR__ . '/../../config/db.php';
if (!isset($GLOBALS['conn']) || !($GLOBALS['conn'] instanceof \mysqli)) {
    $GLOBALS['conn'] = \db_conn();
}

class BatchAssignmentController {
    public static function getAll($page = 1, $perPage = 0) {
        global $conn;
        $rows = [];
        // pagination support: if perPage > 0, apply LIMIT/OFFSET
        if ($perPage && intval($perPage) > 0) {
            $page = max(1, intval($page));
            $perPage = intval($perPage);
            $offset = ($page - 1) * $perPage;
            $stmt = mysqli_prepare($conn, "SELECT * FROM batch_assignments ORDER BY assigned_at DESC LIMIT ? OFFSET ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'ii', $perPage, $offset);
                if (mysqli_stmt_execute($stmt)) {
                    $res = mysqli_stmt_get_result($stmt);
                    while ($r = mysqli_fetch_assoc($res)) {
                        $rows[] = $r;
                    }
                }
            }
            return $rows;
        }

        $res = mysqli_query($conn, "SELECT * FROM batch_assignments ORDER BY assigned_at DESC");
        while ($r = mysqli_fetch_assoc($res)) {
            $rows[] = $r;
        }
        return $rows;
    }
    public static function get($id) {
        global $conn;
        $id = intval($id);
        $row = null;
        $stmt = mysqli_prepare($conn, "SELECT * FROM batch_assignments WHERE id = ? LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $id);
            if (mysqli_stmt_execute($stmt)) {
                $res = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($res) ?: null;
            }
        }
        if ($row) {
            // fetch students from junction table
            $row['students_ids'] = [];
            $sidStmt = mysqli_prepare($conn, "SELECT student_id FROM batch_assignment_students WHERE assignment_id = ?");
            if ($sidStmt) {
                mysqli_stmt_bind_param($sidStmt, 'i', $row['id']);
                if (mysqli_stmt_execute($sidStmt)) {
                    $resS = mysqli_stmt_get_result($sidStmt);
                    $sids = [];
                    while ($rr = mysqli_fetch_assoc($resS)) $sids[] = intval($rr['student_id']);
                    $row['students_ids'] = $sids;
                }
            }
        }
        return $row;
    }
    public static function create($data) {
        global $conn;
        $user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;
        $batch_id = isset($data['batch_id']) ? intval($data['batch_id']) : 0;
        $role = isset($data['role']) ? $data['role'] : 'faculty';
        $assigned_at = isset($data['assigned_at']) ? $data['assigned_at'] : date('Y-m-d H:i:s');
        
        $stmt = mysqli_prepare($conn, "INSERT INTO batch_assignments (batch_id, user_id, role, assigned_at) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'iiss', $batch_id, $user_id, $role, $assigned_at);
        $ok = mysqli_stmt_execute($stmt);
        if ($ok) {
            $assignment_id = mysqli_insert_id($conn);
            // populate normalized junction table if student ids provided
            if (!empty($data['students_ids']) && is_array($data['students_ids'])) {
                $ins = mysqli_prepare($conn, "INSERT INTO batch_assignment_students (assignment_id, student_id) VALUES (?, ?)");
                if ($ins) {
                    foreach ($data['students_ids'] as $sid) {
                        $sid = intval($sid);
                        mysqli_stmt_bind_param($ins, 'ii', $assignment_id, $sid);
                        mysqli_stmt_execute($ins);
                    }
                }
            }
        }
        return $ok;
    }
    public static function update($id, $data) {
        global $conn;
        $id = intval($id);
        $batch_id = isset($data['batch_id']) ? intval($data['batch_id']) : 0;
        $user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;
        $role = isset($data['role']) ? $data['role'] : 'faculty';
        $assigned_at = isset($data['assigned_at']) ? $data['assigned_at'] : date('Y-m-d H:i:s');
        
        $stmt = mysqli_prepare($conn, "UPDATE batch_assignments SET batch_id=?, user_id=?, role=?, assigned_at=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'iissi', $batch_id, $user_id, $role, $assigned_at, $id);
        $ok = mysqli_stmt_execute($stmt);
        if ($ok) {
            // refresh normalized junction table entries for this assignment
            $del = mysqli_prepare($conn, "DELETE FROM batch_assignment_students WHERE assignment_id = ?");
            if ($del) {
                mysqli_stmt_bind_param($del, 'i', $id);
                mysqli_stmt_execute($del);
            }
            if (!empty($data['students_ids']) && is_array($data['students_ids'])) {
                $ins = mysqli_prepare($conn, "INSERT INTO batch_assignment_students (assignment_id, student_id) VALUES (?, ?)");
                if ($ins) {
                    foreach ($data['students_ids'] as $sid) {
                        $sid = intval($sid);
                        mysqli_stmt_bind_param($ins, 'ii', $id, $sid);
                        mysqli_stmt_execute($ins);
                    }
                }
            }
        }
        return $ok;
    }
    public static function delete($id) {
        global $conn;
        $id = intval($id);
        $stmt = mysqli_prepare($conn, "DELETE FROM batch_assignments WHERE id = ?");
        if (!$stmt) return false;
        mysqli_stmt_bind_param($stmt, 'i', $id);
        return mysqli_stmt_execute($stmt);
    }
}
?>