<?php
// Include configuration (handles session_start with correct session name)
require_once '../config.php';

// Clear session variables
$_SESSION = array();

// Destroy the session
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

// Clear session cookie from browser
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirect to login page with success message
header('Location: login.php?logout=success');
exit();
?>
