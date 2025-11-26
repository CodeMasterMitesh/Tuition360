<?php
if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/controllers/StudentController.php
require_once __DIR__ . '/../../config/db.php';
class StudentController {
    public static function getAll($branch_id = null) {
        global $conn;
        $sql = "SELECT * FROM students";
        if ($branch_id) $sql .= " WHERE branch_id = " . intval($branch_id);
        $res = mysqli_query($conn, $sql);
        $rows = [];
        while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
        return $rows;
    }
    public static function get($id) {
        global $conn;
        $id = intval($id);
        $res = mysqli_query($conn, "SELECT * FROM students WHERE id = $id LIMIT 1");
        return mysqli_fetch_assoc($res) ?: null;
    }
    public static function create($data) {
        global $conn;
        // Insert student
        $stmt = mysqli_prepare($conn, "INSERT INTO students (branch_id, name, email, mobile, dob, education, college_name, father_name, address, pincode, state, city, area, registration_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $registration_date = $data['registration_date'] ?? date('Y-m-d');
        $status = ($data['status'] === 'active' || $data['status'] == 1) ? 1 : 0;
        mysqli_stmt_bind_param($stmt, 'issssssssssssssi',
            $data['branch_id'],
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
            $registration_date,
            $status
        );
        $ok = mysqli_stmt_execute($stmt);
        if ($ok) {
            $student_id = mysqli_insert_id($conn);
            // Handle course mapping
            if (!empty($data['courses']) && is_array($data['courses'])) {
                foreach ($data['courses'] as $course_id) {
                    $course_id = intval($course_id);
                    mysqli_query($conn, "INSERT INTO student_courses (student_id, course_id) VALUES ($student_id, $course_id)");
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
            mysqli_query($conn, "DELETE FROM student_courses WHERE student_id = $id");
            if (!empty($data['courses']) && is_array($data['courses'])) {
                foreach ($data['courses'] as $course_id) {
                    $course_id = intval($course_id);
                    mysqli_query($conn, "INSERT INTO student_courses (student_id, course_id) VALUES ($id, $course_id)");
                }
            }
        }
        return $ok;
    }
    public static function delete($id) {
        global $conn;
        $id = intval($id);
        return mysqli_query($conn, "DELETE FROM students WHERE id = $id");
    }
}
?>
