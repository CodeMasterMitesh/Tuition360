<?php
// public/index.php
session_start();
define('APP_INIT', true);
require_once __DIR__ . '/config/db.php';
// Simple router for demonstration
// Simple router for demonstration
$page = $_GET['page'] ?? 'login';
switch ($page) {
    case 'login':
        require_once __DIR__ . '/app/views/login.php';
        break;
    case 'dashboard':
        require_once __DIR__ . '/app/views/dashboard.php';
        break;
    case 'branches':
        require_once __DIR__ . '/app/views/branches.php';
        break;
    case 'users':
        require_once __DIR__ . '/app/views/users.php';
        break;
    case 'courses':
        require_once __DIR__ . '/app/views/courses.php';
        break;
    case 'subjects':
        require_once __DIR__ . '/app/views/subjects.php';
        break;
    case 'students':
        require_once __DIR__ . '/app/views/students.php';
        break;
    case 'faculty':
        require_once __DIR__ . '/app/views/faculty.php';
        break;
    case 'employee':
        require_once __DIR__ . '/app/views/employee.php';
        break;
    case 'batches':
        require_once __DIR__ . '/app/views/batches.php';
        break;
    case 'attendance':
        require_once __DIR__ . '/app/views/attendance.php';
        break;
    case 'fees':
        require_once __DIR__ . '/app/views/fees.php';
        break;
    case 'salaries':
        require_once __DIR__ . '/app/views/salaries.php';
        break;
    case 'leaves':
        require_once __DIR__ . '/app/views/leaves.php';
        break;
    case 'settings':
        require_once __DIR__ . '/app/views/settings.php';
        break;
    case 'reports':
        require_once __DIR__ . '/app/views/reports.php';
        break;
    case 'company':
        require_once __DIR__ . '/app/views/company.php';
        break;
    case 'attendance_students':
        require_once __DIR__ . '/app/views/attendance_students.php';
        break;
    case 'attendance_faculty':
        require_once __DIR__ . '/app/views/attendance_faculty.php';
        break;
    case 'attendance_employee':
        require_once __DIR__ . '/app/views/attendance_employee.php';
        break;
    case 'batch_assignments':
        require_once __DIR__ . '/app/views/batch_assignments.php';
        break;
    default:
        echo 'Page not found.';
}
?>
