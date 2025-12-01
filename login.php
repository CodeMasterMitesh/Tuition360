<?php
// Public login entrypoint. Loads the login view while protecting internal includes.
require_once __DIR__ . '/config/session.php';
start_secure_session();
define('APP_INIT', true);
require_once __DIR__ . '/vendor/autoload.php';
// include DB if views expect it
require_once __DIR__ . '/config/db.php';
// If already logged in, redirect to main index
if (!empty($_SESSION['user']['id']) || !empty($_SESSION['user_id']) || !empty($_SESSION['udata'])) {
    header('Location: index.php');
    exit;
}

// Include the login view
require_once __DIR__ . '/app/views/login.php';

?>
