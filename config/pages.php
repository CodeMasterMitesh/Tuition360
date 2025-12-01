<?php
// config/pages.php - whitelist of routable pages with layout + role metadata
$roles = [
    'all' => ['super_admin', 'branch_admin', 'faculty', 'employee'],
    'staff' => ['super_admin', 'branch_admin', 'faculty'],
    'admin' => ['super_admin', 'branch_admin'],
    'super' => ['super_admin'],
];

return [
    'login' => [
        'view' => __DIR__ . '/../app/views/login.php',
        'title' => 'Login',
        'layout' => null,
        'roles' => [],
    ],
    'dashboard' => [
        'view' => __DIR__ . '/../app/views/dashboard.php',
        'title' => 'Dashboard',
        'roles' => $roles['all'],
    ],
    'branches' => [
        'view' => __DIR__ . '/../app/views/branches.php',
        'title' => 'Branches',
        'roles' => $roles['admin'],
    ],
    'users' => [
        'view' => __DIR__ . '/../app/views/users.php',
        'title' => 'Users',
        'roles' => $roles['super'],
    ],
    'courses' => [
        'view' => __DIR__ . '/../app/views/courses.php',
        'title' => 'Courses',
        'roles' => $roles['admin'],
    ],
    'subjects' => [
        'view' => __DIR__ . '/../app/views/subjects.php',
        'title' => 'Subjects',
        'roles' => $roles['admin'],
    ],
    'students' => [
        'view' => __DIR__ . '/../app/views/students.php',
        'title' => 'Students',
        'roles' => $roles['staff'],
    ],
    'faculty' => [
        'view' => __DIR__ . '/../app/views/faculty.php',
        'title' => 'Faculty',
        'roles' => $roles['admin'],
    ],
    'employee' => [
        'view' => __DIR__ . '/../app/views/employee.php',
        'title' => 'Employees',
        'roles' => $roles['admin'],
    ],
    'batches' => [
        'view' => __DIR__ . '/../app/views/batches.php',
        'title' => 'Batches',
        'roles' => $roles['staff'],
    ],
    'batch_assignments' => [
        'view' => __DIR__ . '/../app/views/batch_assignments.php',
        'title' => 'Batch Assignments',
        'roles' => $roles['admin'],
    ],
    'attendance' => [
        'view' => __DIR__ . '/../app/views/attendance.php',
        'title' => 'Attendance',
        'roles' => $roles['staff'],
    ],
    'attendance_students' => [
        'view' => __DIR__ . '/../app/views/attendance_students.php',
        'title' => 'Student Attendance',
        'roles' => $roles['staff'],
    ],
    'attendance_faculty' => [
        'view' => __DIR__ . '/../app/views/attendance_faculty.php',
        'title' => 'Faculty Attendance',
        'roles' => $roles['admin'],
    ],
    'attendance_employee' => [
        'view' => __DIR__ . '/../app/views/attendance_employee.php',
        'title' => 'Employee Attendance',
        'roles' => $roles['all'],
    ],
    'fees' => [
        'view' => __DIR__ . '/../app/views/fees.php',
        'title' => 'Fees',
        'roles' => $roles['admin'],
    ],
    'salaries' => [
        'view' => __DIR__ . '/../app/views/salaries.php',
        'title' => 'Salaries',
        'roles' => $roles['super'],
    ],
    'leaves' => [
        'view' => __DIR__ . '/../app/views/leaves.php',
        'title' => 'Leaves',
        'roles' => $roles['all'],
    ],
    'settings' => [
        'view' => __DIR__ . '/../app/views/settings.php',
        'title' => 'Settings',
        'roles' => $roles['admin'],
    ],
    'reports' => [
        'view' => __DIR__ . '/../app/views/reports.php',
        'title' => 'Reports',
        'roles' => $roles['admin'],
    ],
    'company' => [
        'view' => __DIR__ . '/../app/views/company.php',
        'title' => 'Company',
        'roles' => $roles['super'],
    ],
    'logout' => [
        'view' => __DIR__ . '/../app/views/logout.php',
        'title' => 'Logout',
        'layout' => null,
        'roles' => $roles['all'],
    ],
];
