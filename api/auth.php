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
    // echo '<pre>';
    // print_r($_POST);
    // echo '</pre>';
    // exit;
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $sql = "SELECT * FROM users WHERE email = ? AND status = 1 LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    if ($user && password_verify($password, $user['password'])) {
        // regenerate session id to prevent fixation
        if (function_exists('session_regenerate_id')) session_regenerate_id(true);
        // store full user details in session except password
        if (isset($user['password'])) unset($user['password']);
        $_SESSION['user'] = $user;
        // compatibility keys used elsewhere in app
        $_SESSION['user_id'] = $user['id'] ?? ($_SESSION['user_id'] ?? null);
        $_SESSION['role'] = $user['role'] ?? ($_SESSION['role'] ?? null);
        $_SESSION['branch_id'] = $user['branch_id'] ?? ($_SESSION['branch_id'] ?? null);
        if (is_ajax_request()) {
            header('Content-Type: application/json');
            echo json_encode(['status' => true, 'redirect' => 'index.php']);
            exit;
        }
        // Non-AJAX behavior: redirect to main index (dashboard will load)
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
