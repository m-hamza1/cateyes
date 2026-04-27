<?php
/**
 * Admin Authentication Check
 * Include this file in all admin pages to ensure user is logged in as admin
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define admin area constant
define('ADMIN_AREA', true);

// Prevent browser caching in admin area (keeps sidebar badge counts accurate)
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

// Load core config (creates $db, $lang, $currency, $settings automatically)
require_once dirname(dirname(__DIR__)) . '/config.php';


// Check if user is logged in and has access to admin area
$allowed_roles = ['admin', 'editor', 'sales_manager', 'shop_manager', 'sales'];
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_role']) || !in_array($_SESSION['admin_role'], $allowed_roles)) {
    // Basic role check failed, redirect to login
    header('Location: ' . ADMIN_URL . 'login.php');
    exit;
}

// Get admin user data and their permissions
$admin = $db->fetchOne(
    "SELECT u.*, r.permissions AS role_permissions
     FROM users u
     LEFT JOIN admin_roles r ON u.admin_role_id = r.id
     WHERE u.id = :id AND u.is_active = 1",
    ['id' => $_SESSION['admin_id']]
);

if (!$admin || !in_array($admin['role'], $allowed_roles)) {
    // User no longer exists, is inactive, or role changed
    session_destroy();
    header('Location: ' . ADMIN_URL . 'login.php');
    exit;
}

// Sync session with database role/permissions
$_SESSION['admin_id'] = $admin['id'];
$_SESSION['admin_role'] = $admin['role'];
$_SESSION['admin_role_id'] = $admin['admin_role_id'] ?? null;

// Prefer role permissions when a role is assigned; fallback to per-user permissions.
$perms = [];
if (!empty($admin['role_permissions'])) {
    $perms = json_decode($admin['role_permissions'], true) ?: [];
} elseif (!empty($admin['permissions'])) {
    $perms = json_decode($admin['permissions'], true) ?: [];
}
$_SESSION['admin_permissions'] = $perms;

// Update last activity
$_SESSION['last_activity'] = time();

// Handle language change
if (isset($_GET['change_lang'])) {
    $lang->changeLanguage($_GET['change_lang']);
    // Redirect to remove the query parameter
    $redirect_url = strtok($_SERVER['REQUEST_URI'], '?');
    header('Location: ' . $redirect_url);
    exit;
}

// Set timezone from settings (GMT+4 for Asia/Dubai)
$tz = $settings->get('timezone', DEFAULT_TIMEZONE);
date_default_timezone_set($tz ?: DEFAULT_TIMEZONE);
// Admin currency is handled automatically by the Currency class based on ADMIN_AREA constant

// Helper function for translations (only if not already defined)
if (!function_exists('__')) {
    function __($key, $default = '') {
        global $lang;
        return $lang ? $lang->get($key, $default) : $default;
    }
}

// Helper function to build current URL with parameter (only if not already defined)
if (!function_exists('buildCurrentUrlWithParam')) {
    function buildCurrentUrlWithParam($param, $value) {
        $current_url = $_SERVER['REQUEST_URI'];
        $parsed_url = parse_url($current_url);
        $query = [];
        
        if (isset($parsed_url['query'])) {
            parse_str($parsed_url['query'], $query);
        }
        
        // Remove existing parameter and add new one
        unset($query[$param]);
        $query[$param] = $value;
        
        // Build new query string
        $new_query = http_build_query($query);
        
        // Build final URL
        $new_url = $parsed_url['path'];
        if ($new_query) {
            $new_url .= '?' . $new_query;
        }
        
        return $new_url;
    }
}
