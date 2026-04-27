<?php
/**
 * Subdirectory Config Wrapper
 * Redirects to the root config.php to maintain consistency
 */

$rootConfig = dirname(__DIR__) . '/config.php';

if (file_exists($rootConfig)) {
    require_once $rootConfig;
} else {
    // Fallback if root config is missing (unlikely)
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'u744799766_cat_eye');
    define('BASE_URL', 'http://localhost/CatEyes/');
}

// Ensure all essential constants are defined (legacy support)
if (!defined('SITE_URL')) define('SITE_URL', BASE_URL);
if (!defined('ADMIN_URL')) define('ADMIN_URL', BASE_URL . 'admin/');
if (!defined('SESSION_NAME')) define('SESSION_NAME', 'ECOMMERCE_SESSION');
if (!defined('SESSION_LIFETIME')) define('SESSION_LIFETIME', 86400);
