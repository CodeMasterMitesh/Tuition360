<?php
// api/auth.php
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/../config/db.php';
$action = $_GET['action'] ?? '';
// helper to detect AJAX requests
function is_ajax_request() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $loginRole = $_POST['role'] ?? '';
    
    $user = null;
    $userType = null;
    
    // Check based on selected role
    if ($loginRole === 'student') {
        // Login as student
        $sql = "SELECT id, name, email, branch_id, 'student' as role, status FROM students WHERE email = ? AND status = 1 LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        if ($user) {
            // Students don't have password in schema, so we'll use email as temp password or skip verification
            // For now, allow login without password check for students
            $userType = 'student';
        }
    } elseif ($loginRole === 'employee_faculty') {
        // Login as employee or faculty from users table
        $sql = "SELECT * FROM users WHERE email = ? AND status = 1 AND role IN ('employee', 'faculty') LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        // Allow login with password verification OR if password is empty (for testing/initial setup)
        if ($user && (empty($user['password']) || password_verify($password, $user['password']))) {
            // Both employee and faculty use employee_faculty dashboard
            $userType = 'employee_faculty';
        } else {
            $user = null;
        }
    } elseif ($loginRole === 'admin') {
        // Login as admin
        $sql = "SELECT * FROM users WHERE email = ? AND status = 1 AND role IN ('super_admin', 'branch_admin') LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        if ($user && password_verify($password, $user['password'])) {
            $userType = 'admin';
        } else {
            $user = null;
        }
    } else {
        // Invalid role selection
        $user = null;
        $userType = null;
    }
    
    if ($user) {
        // regenerate session id to prevent fixation
        if (function_exists('session_regenerate_id')) session_regenerate_id(true);
        // store full user details in session except password
        if (isset($user['password'])) unset($user['password']);
        $_SESSION['user'] = $user;
        $_SESSION['user_type'] = $userType;
        // compatibility keys used elsewhere in app
        $_SESSION['user_id'] = $user['id'] ?? ($_SESSION['user_id'] ?? null);
        $_SESSION['role'] = $user['role'] ?? ($_SESSION['role'] ?? null);
        $_SESSION['branch_id'] = $user['branch_id'] ?? ($_SESSION['branch_id'] ?? null);
        
        // Set redirect page
        $redirectPage = 'dashboard';
        if ($userType === 'student') {
            $redirectPage = 'dashboard_student';
        } elseif ($userType === 'employee_faculty') {
            $redirectPage = 'dashboard_employee';
        }
        $_SESSION['last_page'] = $redirectPage;
        session_write_close(); // Ensure session is written
        
        if (is_ajax_request()) {
            header('Content-Type: application/json');
            echo json_encode(['status' => true, 'redirect' => 'index.php']);
            exit;
        }
        // Non-AJAX behavior
        header('Location: ../index.php');
        exit;
    } else {
        if (is_ajax_request()) {
            header('Content-Type: application/json');
            echo json_encode(['status' => false, 'message' => 'Invalid credentials']);
            exit;
        }
        echo '<script>alert("Invalid credentials");window.location.href="../login.php";</script>';
        exit;
    }
}
?>
