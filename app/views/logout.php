<?php
if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// Logout page: destroy session and redirect to login
require_once __DIR__ . '/../../config/session.php';
start_secure_session();
// Unset all session values
$_SESSION = [];
// Destroy session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'], $params['secure'], $params['httponly']
    );
}
// Finally destroy the session
session_destroy();
// Redirect to the login page (standalone)
header('Location: ' . dirname($_SERVER['SCRIPT_NAME']) . '/../../login.php');
exit;
?>