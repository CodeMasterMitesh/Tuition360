<?php
// api/init.php - start session and enforce authentication for API endpoints
session_start();

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
