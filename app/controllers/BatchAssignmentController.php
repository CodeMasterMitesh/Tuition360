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
                        // decode JSON fields
                        if (isset($r['subjects']) && $r['subjects'] !== null) {
                            $decoded = json_decode($r['subjects'], true);
                            $r['subjects'] = is_array($decoded) ? $decoded : [];
                        } else { $r['subjects'] = []; }
                        if (isset($r['students_ids']) && $r['students_ids'] !== null) {
                            $decoded = json_decode($r['students_ids'], true);
                            $r['students_ids'] = is_array($decoded) ? $decoded : [];
                        } else { $r['students_ids'] = []; }
                        $rows[] = $r;
                    }
                }
            }
            return $rows;
        }

        $res = mysqli_query($conn, "SELECT * FROM batch_assignments ORDER BY assigned_at DESC");
        while ($r = mysqli_fetch_assoc($res)) {
            // decode JSON fields
            if (isset($r['subjects']) && $r['subjects'] !== null) {
                $decoded = json_decode($r['subjects'], true);
                $r['subjects'] = is_array($decoded) ? $decoded : [];
            } else { $r['subjects'] = []; }
            if (isset($r['students_ids']) && $r['students_ids'] !== null) {
                $decoded = json_decode($r['students_ids'], true);
                $r['students_ids'] = is_array($decoded) ? $decoded : [];
            } else { $r['students_ids'] = []; }
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
            if (isset($row['subjects']) && $row['subjects'] !== null) {
                $decoded = json_decode($row['subjects'], true);
                $row['subjects'] = is_array($decoded) ? $decoded : [];
            } else { $row['subjects'] = []; }
            if (isset($row['students_ids']) && $row['students_ids'] !== null) {
                $decoded = json_decode($row['students_ids'], true);
                $row['students_ids'] = is_array($decoded) ? $decoded : [];
            } else { $row['students_ids'] = []; }
            // if students_ids empty, try reading from normalized junction table
            if (empty($row['students_ids'])) {
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
        }
        return $row;
    }
    public static function create($data) {
        global $conn;
        $subjectsJson = null;
        if (!empty($data['subjects'])) {
            if (is_array($data['subjects'])) $subjectsJson = json_encode(array_values($data['subjects'])); else $subjectsJson = json_encode([$data['subjects']]);
        }
        $studentsIdsJson = null;
        if (!empty($data['students_ids'])) {
            if (is_array($data['students_ids'])) $studentsIdsJson = json_encode(array_values($data['students_ids'])); else $studentsIdsJson = json_encode([$data['students_ids']]);
        }
        $user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;
        $stmt = mysqli_prepare($conn, "INSERT INTO batch_assignments (batch_id, user_id, students_ids, role, subjects, assigned_at) VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'iissss', $data['batch_id'], $user_id, $studentsIdsJson, $data['role'], $subjectsJson, $data['assigned_at']);
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
        $subjectsJson = null;
        if (!empty($data['subjects'])) {
            if (is_array($data['subjects'])) $subjectsJson = json_encode(array_values($data['subjects'])); else $subjectsJson = json_encode([$data['subjects']]);
        }
        $studentsIdsJson = null;
        if (!empty($data['students_ids'])) {
            if (is_array($data['students_ids'])) $studentsIdsJson = json_encode(array_values($data['students_ids'])); else $studentsIdsJson = json_encode([$data['students_ids']]);
        }
        $user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;
        $stmt = mysqli_prepare($conn, "UPDATE batch_assignments SET batch_id=?, user_id=?, students_ids=?, role=?, subjects=?, assigned_at=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'iissssi', $data['batch_id'], $user_id, $studentsIdsJson, $data['role'], $subjectsJson, $data['assigned_at'], $id);
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