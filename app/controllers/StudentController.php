<?php
if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/controllers/StudentController.php
require_once __DIR__ . '/../../config/db.php';
class StudentController {
    public static function getAll($branch_id = null) {
        global $conn;
        $rows = [];
        // try cached map for full student list when branch_id not provided
        if (empty($branch_id)) {
            // load cache helper if available
            $map = null;
            if (file_exists(__DIR__ . '/../helpers/cache.php')) require_once __DIR__ . '/../helpers/cache.php';
            if (function_exists('cache_get')) {
                $map = cache_get('students_all_v1');
                if (is_array($map)) return $map;
            }
        }
        if ($branch_id !== null && $branch_id !== false && $branch_id !== '') {
            $bid = intval($branch_id);
            $stmt = mysqli_prepare($conn, "SELECT * FROM students WHERE branch_id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'i', $bid);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
                return $rows;
            }
        }
        $res = mysqli_query($conn, "SELECT * FROM students");
        while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
        // populate cache for full list
        if (empty($branch_id) && function_exists('cache_set')) {
            cache_set('students_all_v1', $rows, 60);
        }
        return $rows;
    }
    public static function get($id) {
        global $conn;
        $id = intval($id);
        $row = null;
        $stmt = mysqli_prepare($conn, "SELECT * FROM students WHERE id = ? LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $id);
            if (mysqli_stmt_execute($stmt)) {
                $res = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($res) ?: null;
            }
        }
        return $row;
    }
    public static function create($data) {
        global $conn;
        // Insert student
        $stmt = mysqli_prepare($conn, "INSERT INTO students (branch_id, name, email, mobile, dob, education, college_name, father_name, address, pincode, state, city, area, registration_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            error_log('StudentController::create prepare failed: ' . mysqli_error($conn));
            return false;
        }
        // local variables (bind_param requires variables passed by reference)
        $branch_id = isset($data['branch_id']) ? intval($data['branch_id']) : 0;
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $mobile = $data['mobile'] ?? '';
        $dob = $data['dob'] ?? null;
        $education = $data['education'] ?? '';
        $college_name = $data['college_name'] ?? '';
        $father_name = $data['father_name'] ?? '';
        $address = $data['address'] ?? '';
        $pincode = $data['pincode'] ?? '';
        $state = $data['state'] ?? '';
        $city = $data['city'] ?? '';
        $area = $data['area'] ?? '';
        $registration_date = $data['registration_date'] ?? date('Y-m-d');
        $status = ($data['status'] === 'active' || $data['status'] == 1) ? 1 : 0;

        // bind: 15 params, types: i (branch), then 13 strings, then i (status)
        $bindTypes = 'isssssssssssssi';
        mysqli_stmt_bind_param($stmt, $bindTypes,
            $branch_id,
            $name,
            $email,
            $mobile,
            $dob,
            $education,
            $college_name,
            $father_name,
            $address,
            $pincode,
            $state,
            $city,
            $area,
            $registration_date,
            $status
        );
        $ok = mysqli_stmt_execute($stmt);
        if ($ok) {
            $student_id = mysqli_insert_id($conn);
            // Handle course mapping
            if (!empty($data['courses']) && is_array($data['courses'])) {
                $insStmt = mysqli_prepare($conn, "INSERT INTO student_courses (student_id, course_id) VALUES (?, ?)");
                foreach ($data['courses'] as $course_id) {
                    $course_id = intval($course_id);
                    if ($course_id <= 0) continue;
                    mysqli_stmt_bind_param($insStmt, 'ii', $student_id, $course_id);
                    mysqli_stmt_execute($insStmt);
                }
            }
        }
        return $ok;
    }
    public static function update($id, $data) {
        global $conn;
        $status = ($data['status'] === 'active' || $data['status'] == 1) ? 1 : 0;
        $stmt = mysqli_prepare($conn, "UPDATE students SET name=?, email=?, mobile=?, dob=?, education=?, college_name=?, father_name=?, address=?, pincode=?, state=?, city=?, area=?, status=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'ssssssssssssii',
            $data['name'],
            $data['email'],
            $data['mobile'],
            $data['dob'],
            $data['education'],
            $data['college_name'],
            $data['father_name'],
            $data['address'],
            $data['pincode'],
            $data['state'],
            $data['city'],
            $data['area'],
            $status,
            $id
        );
        $ok = mysqli_stmt_execute($stmt);
        if ($ok) {
            // Update course mapping: remove old, insert new
            $delStmt = mysqli_prepare($conn, "DELETE FROM student_courses WHERE student_id = ?");
            mysqli_stmt_bind_param($delStmt, 'i', $id);
            mysqli_stmt_execute($delStmt);
            if (!empty($data['courses']) && is_array($data['courses'])) {
                $insStmt = mysqli_prepare($conn, "INSERT INTO student_courses (student_id, course_id) VALUES (?, ?)");
                foreach ($data['courses'] as $course_id) {
                    $course_id = intval($course_id);
                    if ($course_id <= 0) continue;
                    mysqli_stmt_bind_param($insStmt, 'ii', $id, $course_id);
                    mysqli_stmt_execute($insStmt);
                }
            }
        }
        return $ok;
    }
    public static function delete($id) {
        global $conn;
        $id = intval($id);
        $stmt = mysqli_prepare($conn, "DELETE FROM students WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        return mysqli_stmt_execute($stmt);
    }
}
?>
