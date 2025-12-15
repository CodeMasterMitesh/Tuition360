<?php
// public/index.php — single entry point for authenticated pages
require_once __DIR__ . '/config/session.php';
start_secure_session();
define('APP_INIT', true);
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/db.php';

// Ensure each session has a CSRF token for view rendering
if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
}

$pagesConfig = [];
$pagesFile = __DIR__ . '/config/pages.php';
if (file_exists($pagesFile)) {
    $pagesConfig = require $pagesFile;
}

$defaultPage = 'dashboard';
// Check user type and route to appropriate dashboard
$userType = $_SESSION['user_type'] ?? 'admin';
if ($userType === 'student') {
    $defaultPage = 'dashboard_student';
} elseif ($userType === 'employee_faculty') {
    $defaultPage = 'dashboard_employee';
}

// For GET requests with explicit page, use that
// Otherwise determine page based on last_page or user type
if (isset($_GET['page'])) {
    $pageKey = $_GET['page'];
} else {
    // Use last_page if available (set by auth after login), otherwise use default for user type
    $pageKey = $_SESSION['last_page'] ?? $defaultPage;
}

// Basic auth guard: allow login page without active session
$isAuthenticated = !empty($_SESSION['user']['id']) || !empty($_SESSION['user_id']) || !empty($_SESSION['udata']);
if ($pageKey !== 'login' && !$isAuthenticated) {
    header('Location: login.php');
    exit;
}

$pageConfig = $pagesConfig[$pageKey] ?? null;
if ($pageConfig === null) {
    $pageKey = $defaultPage;
    $pageConfig = $pagesConfig[$pageKey] ?? null;
}
if (is_string($pageConfig)) {
    $pageConfig = ['view' => $pageConfig];
}

$viewFile = $pageConfig['view'] ?? null;
if (!$viewFile || !file_exists($viewFile)) {
    http_response_code(404);
    echo 'Page not found.';
    exit;
}

// Some pages (login/logout) render their own layout, so allow opt-out
$layoutName = $pageConfig['layout'] ?? 'master';

// Render target view; content is injected into the master layout if enabled
$pageTitle = $pageConfig['title'] ?? ucwords(str_replace('_', ' ', $pageKey));
$currentUser = $_SESSION['user'] ?? null;
$userRole = strtolower($currentUser['role'] ?? ($_SESSION['role'] ?? ''));

$allowedRoles = $pageConfig['roles'] ?? [];
if (!empty($allowedRoles) && $pageKey !== 'login' && $layoutName !== null) {
    if ($userRole === '' || !in_array($userRole, $allowedRoles, true)) {
        // Debug log to trace role mismatch issues
        error_log('PERMISSION_DENIED page=' . $pageKey . ' role=' . $userRole . ' allowed=' . json_encode($allowedRoles) . ' session=' . json_encode($_SESSION));
        http_response_code(403);
        echo 'You do not have permission to view this page.';
        exit;
    }
}

// Persist last visited page in session so full refresh at /index.php renders the same view
try {
    $_SESSION['last_page'] = $pageKey;
} catch (Exception $e) {
}

ob_start();
$__pageConfig = $pageConfig;
require $viewFile;
$contentForLayout = ob_get_clean();

if ($layoutName === null) {
    echo $contentForLayout;
    exit;
}

$layoutFile = __DIR__ . '/app/views/layouts/' . $layoutName . '.php';
if (!file_exists($layoutFile)) {
    echo $contentForLayout;
    exit;
}

require $layoutFile;
