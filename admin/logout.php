<?php
/**
 * Admin Logout
 */

session_start();

// Load configuration (root config.php - supports live/local, provides $db)
require_once dirname(__DIR__) . '/config.php';

// Log activity if user is logged in
if (isset($_SESSION['admin_id'])) {
    $db->insert('activity_log', [
        'user_id' => $_SESSION['admin_id'],
        'action' => 'admin_logout',
        'description' => 'Admin logged out',
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
}

// Destroy session
$_SESSION = [];

// Clear session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'] ?? '/',
        $params['domain'] ?? '',
        $params['secure'] ?? false,
        $params['httponly'] ?? true
    );
}

session_destroy();

// Redirect to login page
header('Location: login.php');
exit;
