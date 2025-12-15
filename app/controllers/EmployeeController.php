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
        $row = mysqli_fetch_assoc($res) ?: null;
        if (!$row) return null;
        // Attach education and employment details
        $edus = [];
        if ($q = mysqli_query($conn, "SELECT degree, institute, from_date, to_date, grade, specialization FROM employee_education WHERE employee_id = $id ORDER BY id")) {
            while ($r = mysqli_fetch_assoc($q)) $edus[] = $r;
        }
        $emps = [];
        if ($q = mysqli_query($conn, "SELECT organisation, designation, from_date, to_date, annual_ctc FROM employee_employment WHERE employee_id = $id ORDER BY id")) {
            while ($r = mysqli_fetch_assoc($q)) $emps[] = $r;
        }
        $row['education'] = $edus;
        $row['employment'] = $emps;
        return $row;
    }
    
    public static function getByUserId($userId) {
        global $conn;
        $userId = intval($userId);
        $res = mysqli_query($conn, "SELECT * FROM users WHERE id = $userId AND role IN ('employee', 'faculty') LIMIT 1");
        return mysqli_fetch_assoc($res) ?: null;
    }
    public static function create($data) {
        global $conn;
        $hashed = isset($data['password']) ? password_hash($data['password'], PASSWORD_DEFAULT) : null;
        $sql = "INSERT INTO users (branch_id, role, name, email, password, mobile, is_part_time, status, profile_photo, dob, gender, marital_status, joining_date, resign_date, in_time, out_time, address, area, city, pincode, state, country, aadhar_card, pan_card, passport)
            VALUES (?, 'employee', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        // Prepare variables for bind_param (must be pass-by-reference)
        $branch_id = $data['branch_id'];
        $name = $data['name'];
        $email = $data['email'];
        $password = $hashed;
        $mobile = $data['mobile'];
        $is_part_time = isset($data['is_part_time']) ? intval($data['is_part_time']) : 0;
        $status = isset($data['status']) ? intval($data['status']) : 1;
        $profile_photo = $data['profile_photo'] ?? null;
        $dob = $data['dob'] ?? null;
        $gender = $data['gender'] ?? null;
        $marital_status = $data['marital_status'] ?? null;
        $joining_date = $data['joining_date'] ?? null;
        $resign_date = $data['resign_date'] ?? null;
        $in_time = $data['in_time'] ?? null;
        $out_time = $data['out_time'] ?? null;
        $address = $data['address'] ?? null;
        $area = $data['area'] ?? null;
        $city = $data['city'] ?? null;
        $pincode = $data['pincode'] ?? null;
        $state = $data['state'] ?? null;
        $country = $data['country'] ?? null;
        $aadhar_card = $data['aadhar_card'] ?? null;
        $pan_card = $data['pan_card'] ?? null;
        $passport = $data['passport'] ?? null;
        // Types: i (branch) + ssss (name,email,password,mobile) + ii (is_part_time,status) + 17s (remaining string fields)
        mysqli_stmt_bind_param($stmt, 'issssii' . str_repeat('s', 17),
            $branch_id, $name, $email, $password, $mobile, $is_part_time, $status,
            $profile_photo, $dob, $gender, $marital_status,
            $joining_date, $resign_date, $in_time, $out_time,
            $address, $area, $city, $pincode, $state, $country,
            $aadhar_card, $pan_card, $passport
        );
        $ok = mysqli_stmt_execute($stmt);
        if (!$ok) return false;
        $id = mysqli_insert_id($conn);
        self::upsertEducation($id, $data['education'] ?? []);
        self::upsertEmployment($id, $data['employment'] ?? []);
        return true;
    }
    public static function update($id, $data) {
        global $conn;
        // debug($data);
        // exit;
        // Normalize nested arrays if provided as JSON strings
        if (isset($data['education']) && is_string($data['education'])) {
            $dec = json_decode($data['education'], true);
            if (is_array($dec)) $data['education'] = $dec; else $data['education'] = [];
        }
        if (isset($data['employment']) && is_string($data['employment'])) {
            $dec = json_decode($data['employment'], true);
            if (is_array($dec)) $data['employment'] = $dec; else $data['employment'] = [];
        }
        $fields = "name=?, email=?, mobile=?, is_part_time=?, status=?, profile_photo=?, dob=?, gender=?, marital_status=?, joining_date=?, resign_date=?, in_time=?, out_time=?, address=?, area=?, city=?, pincode=?, state=?, country=?, aadhar_card=?, pan_card=?, passport=?";
        $sql = "UPDATE users SET $fields WHERE id=? AND role='employee'";
        $stmt = mysqli_prepare($conn, $sql);
        // Prepare variables for bind_param
        $name = $data['name'];
        $email = $data['email'];
        $mobile = $data['mobile'];
        $is_part_time = isset($data['is_part_time']) ? intval($data['is_part_time']) : 0;
        $status = isset($data['status']) ? intval($data['status']) : 1;
        $profile_photo = $data['profile_photo'] ?? null;
        $dob = $data['dob'] ?? null;
        $gender = $data['gender'] ?? null;
        $marital_status = $data['marital_status'] ?? null;
        $joining_date = $data['joining_date'] ?? null;
        $resign_date = $data['resign_date'] ?? null;
        $in_time = $data['in_time'] ?? null;
        $out_time = $data['out_time'] ?? null;
        $address = $data['address'] ?? null;
        $area = $data['area'] ?? null;
        $city = $data['city'] ?? null;
        $pincode = $data['pincode'] ?? null;
        $state = $data['state'] ?? null;
        $country = $data['country'] ?? null;
        $aadhar_card = $data['aadhar_card'] ?? null;
        $pan_card = $data['pan_card'] ?? null;
        $passport = $data['passport'] ?? null;
        $user_id = $id;
        mysqli_stmt_bind_param($stmt, 'sssii' . str_repeat('s', 17) . 'i',
            $name, $email, $mobile, $is_part_time, $status,
            $profile_photo, $dob, $gender, $marital_status,
            $joining_date, $resign_date, $in_time, $out_time,
            $address, $area, $city, $pincode, $state, $country,
            $aadhar_card, $pan_card, $passport, $user_id
        );
        $ok = mysqli_stmt_execute($stmt);
        if (!$ok) return false;
        self::upsertEducation($id, $data['education'] ?? []);
        self::upsertEmployment($id, $data['employment'] ?? []);
        return true;
    }
    public static function delete($id) {
        global $conn;
        $id = intval($id);
        // delete profile photo file if exists
        $row = self::get($id);
        if ($row && !empty($row['profile_photo'])) {
            $path = __DIR__ . '/../../public/uploads/employees/' . basename($row['profile_photo']);
            if (is_file($path)) @unlink($path);
        }
        $ok = mysqli_query($conn, "DELETE FROM users WHERE id = $id AND role='employee'");
        if ($ok) {
            mysqli_query($conn, "DELETE FROM employee_education WHERE employee_id = $id");
            mysqli_query($conn, "DELETE FROM employee_employment WHERE employee_id = $id");
        }
        return $ok;
    }

    private static function upsertEducation($employeeId, $items) {
        global $conn;
        mysqli_query($conn, "DELETE FROM employee_education WHERE employee_id=" . intval($employeeId));
        if (!is_array($items)) return;
        foreach ($items as $it) {
            $stmt = mysqli_prepare($conn, "INSERT INTO employee_education (employee_id, degree, institute, from_date, to_date, grade, specialization) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $deg = $it['degree'] ?? '';
            $inst = $it['institute'] ?? '';
            $from = $it['from_date'] ?? null;
            $to = $it['to_date'] ?? null;
            $grade = $it['grade'] ?? null;
            $spec = $it['specialization'] ?? null;
            mysqli_stmt_bind_param($stmt, 'issssss', $employeeId, $deg, $inst, $from, $to, $grade, $spec);
            mysqli_stmt_execute($stmt);
        }
    }
    private static function upsertEmployment($employeeId, $items) {
        global $conn;
        mysqli_query($conn, "DELETE FROM employee_employment WHERE employee_id=" . intval($employeeId));
        if (!is_array($items)) return;
        foreach ($items as $it) {
            $stmt = mysqli_prepare($conn, "INSERT INTO employee_employment (employee_id, organisation, designation, from_date, to_date, annual_ctc) VALUES (?, ?, ?, ?, ?, ?)");
            $org = $it['organisation'] ?? '';
            $des = $it['designation'] ?? '';
            $from = $it['from_date'] ?? null;
            $to = $it['to_date'] ?? null;
            $ctc = $it['annual_ctc'] ?? null;
            mysqli_stmt_bind_param($stmt, 'issssd', $employeeId, $org, $des, $from, $to, $ctc);
            mysqli_stmt_execute($stmt);
        }
    }
}
?>
