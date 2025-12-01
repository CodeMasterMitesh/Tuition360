<?php
// api/init.php - centralized session start and auth enforcement for API endpoints
// Mark that the application is initialized so controllers/views included by APIs allow access
if (!defined('APP_INIT')) define('APP_INIT', true);
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/session.php';
start_secure_session();

// Ensure a CSRF token exists for this session
if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        // fallback
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
}

// list of API scripts that are allowed to be public (no session required)
$publicApis = [
    'auth.php', // login/register endpoints
];

$currentScript = basename($_SERVER['SCRIPT_FILENAME']);
if (!in_array($currentScript, $publicApis)) {
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['status' => false, 'message' => 'Unauthorized']);
        exit;
    }
}

// For non-GET requests, require a matching CSRF token (except for explicitly public APIs)
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && !in_array($currentScript, $publicApis)) {
    // Try header first (X-CSRF-Token), then POST field 'csrf_token', then JSON body
    $csrf = null;
    if (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'];
    elseif (!empty($_POST['csrf_token'])) $csrf = $_POST['csrf_token'];
    else {
        $raw = file_get_contents('php://input');
        if ($raw) {
            $d = json_decode($raw, true);
            if (is_array($d) && !empty($d['csrf_token'])) $csrf = $d['csrf_token'];
        }
    }
    if (empty($csrf) || !hash_equals($_SESSION['csrf_token'], (string)$csrf)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
}
