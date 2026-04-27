<?php
// Safe session start with security hardening
if (session_status() === PHP_SESSION_NONE) {
    // Session Configuration
    if (!defined('SESSION_NAME')) define('SESSION_NAME', 'ECOMMERCE_SESSION');
    if (!defined('SESSION_LIFETIME')) define('SESSION_LIFETIME', 86400); // 24 hours
    
    session_name(SESSION_NAME);
    
    // Secure session settings
    ini_set('session.cookie_httponly', 1);      
    ini_set('session.cookie_samesite', 'Lax');  
    ini_set('session.use_strict_mode', 1);      
    ini_set('session.use_only_cookies', 1);     
    
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    
    session_start();
    
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > SESSION_LIFETIME) {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

// Master Site Enable/Disable logic
$maintenanceFile = dirname(__FILE__) . '/maintenance-mode.php';
if (file_exists($maintenanceFile)) {
    require_once $maintenanceFile;
}
if (!defined('SITE_ENABLED')) {
    define('SITE_ENABLED', true); 
}

// Environment Detection
$localConfigFile = dirname(__FILE__) . '/local-config.php';
$hostingerFile = dirname(__FILE__) . '/hostinger-config.php';

$isLocal = (PHP_SAPI === 'cli') || (isset($_SERVER['HTTP_HOST']) && (
    strpos($_SERVER['HTTP_HOST'], 'localhost') !== false ||
    strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false
));
define('IS_LOCAL', $isLocal);

$isLive = !$isLocal && (isset($_SERVER['HTTP_HOST']) && (
    strpos($_SERVER['HTTP_HOST'], 'cateyesbeauty.com') !== false ||
    strpos($_SERVER['HTTP_HOST'], 'vertexvaluestore.com') !== false ||
    strpos($_SERVER['HTTP_HOST'], 'test100.sbs') !== false ||
    strpos($_SERVER['HTTP_HOST'], 'bidssync.com') !== false
));

// Priority 1: Load environment specific config file
if (file_exists($hostingerFile)) {
    // We always want to know the live URL for emails even when on localhost
    require_once $hostingerFile;
}

if (file_exists($localConfigFile) && $isLocal) {
    require_once $localConfigFile;
    if (!defined('DB_HOST')) define('DB_HOST', defined('LOCAL_DB_HOST') ? LOCAL_DB_HOST : 'localhost');
    if (!defined('DB_NAME')) define('DB_NAME', LOCAL_DB_NAME);
    if (!defined('DB_USER')) define('DB_USER', LOCAL_DB_USER);
    if (!defined('DB_PASS')) define('DB_PASS', LOCAL_DB_PASS);
    if (!defined('BASE_URL')) {
        $url = rtrim(LOCAL_SITE_URL, '/');
        // Ensure spaces are encoded for URLs
        $encodedUrl = str_replace(' ', '%20', $url);
        define('BASE_URL', $encodedUrl . '/');
    }
} elseif ($isLive) {
    if (!defined('DB_HOST')) define('DB_HOST', defined('HOSTINGER_DB_HOST') ? HOSTINGER_DB_HOST : 'localhost');
    if (!defined('DB_NAME')) define('DB_NAME', HOSTINGER_DB_NAME);
    if (!defined('DB_USER')) define('DB_USER', HOSTINGER_DB_USER);
    if (!defined('DB_PASS')) define('DB_PASS', HOSTINGER_DB_PASS);
    if (!defined('BASE_URL')) define('BASE_URL', rtrim(HOSTINGER_SITE_URL, '/') . '/');
    if (defined('HOSTINGER_SITE_NAME')) define('SITE_NAME_OVERRIDE', HOSTINGER_SITE_NAME);
} 
// Priority 2: Fallbacks for known domains
elseif ($isLive && strpos($_SERVER['HTTP_HOST'] ?? '', 'cateyesbeauty.com') !== false) {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'u138580725_cateyes');
    define('DB_USER', 'u138580725_cateyes');
    define('DB_PASS', 'Cateyesbeauty.com@1122');
    define('BASE_URL', 'https://cateyesbeauty.com/');
} elseif ($isLive && strpos($_SERVER['HTTP_HOST'] ?? '', 'vertexvaluestore.com') !== false) {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'u744799766_cat_eye');
    define('DB_USER', 'u744799766_cat_eye');
    define('DB_PASS', 'Cat_eyes@19');
    define('BASE_URL', 'https://vertexvaluestore.com/');
} 
// Priority 3: Default Local
else {
    if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
    if (!defined('DB_NAME')) define('DB_NAME', 'u744799766_cat_eye');
    if (!defined('DB_USER')) define('DB_USER', 'root');
    if (!defined('DB_PASS')) define('DB_PASS', '');
    
    $folderName = basename(BASE_PATH);
    $localHost = (($_SERVER['HTTP_HOST'] ?? 'localhost') === 'localhost:8080')
        ? 'http://localhost:8080/' . rawurlencode($folderName) . '/'
        : 'http://localhost/' . rawurlencode($folderName) . '/';
    if (!defined('BASE_URL')) define('BASE_URL', $localHost);
}

// Common Constants
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');
if (!defined('SITE_NAME')) define('SITE_NAME', defined('SITE_NAME_OVERRIDE') ? SITE_NAME_OVERRIDE : 'Cat Eye Beauty');
if (!defined('SITE_URL')) define('SITE_URL', BASE_URL);

// Error logging
if (!defined('LOG_ERRORS')) define('LOG_ERRORS', true);
if (!defined('ERROR_LOG_FILE')) define('ERROR_LOG_FILE', dirname(__FILE__) . '/logs/error.log');
if (!defined('DISPLAY_ERRORS')) define('DISPLAY_ERRORS', $isLocal); 

if (LOG_ERRORS) {
    ini_set('log_errors', 1);
    ini_set('error_log', ERROR_LOG_FILE);
}

// Security
if (!defined('HASH_ALGO')) define('HASH_ALGO', 'sha256');
if (!defined('ENCRYPTION_KEY')) define('ENCRYPTION_KEY', 'cateyes-secure-key-2026');
if (!defined('PASSWORD_HASH_COST')) define('PASSWORD_HASH_COST', 12);

// SMTP
if (!defined('SMTP_HOST')) define('SMTP_HOST', 'smtp.hostinger.com');
if (!defined('SMTP_PORT')) define('SMTP_PORT', 465); 
if (!defined('SMTP_USERNAME')) define('SMTP_USERNAME', 'info@cateyesbeauty.com');
if (!defined('SMTP_PASSWORD')) define('SMTP_PASSWORD', 'Cateyesbeauty.com@1122');
if (!defined('SMTP_FROM_EMAIL')) define('SMTP_FROM_EMAIL', 'info@cateyesbeauty.com');
if (!defined('SMTP_FROM_NAME')) define('SMTP_FROM_NAME', SITE_NAME);
if (!defined('DISABLE_EMAILS')) define('DISABLE_EMAILS', false); 

// Payment
if (!defined('PAYPAL_MODE')) define('PAYPAL_MODE', 'sandbox');
if (!defined('STRIPE_MODE')) define('STRIPE_MODE', 'test');

// Files
if (!defined('MAX_FILE_SIZE')) define('MAX_FILE_SIZE', 5242880); 
if (!defined('ALLOWED_IMAGE_TYPES')) define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// Defaults
if (!defined('DEFAULT_LANGUAGE')) define('DEFAULT_LANGUAGE', 'en');
if (!defined('DEFAULT_CURRENCY')) define('DEFAULT_CURRENCY', 'AED');
if (!defined('DEFAULT_TIMEZONE')) define('DEFAULT_TIMEZONE', 'Asia/Dubai');
if (!defined('LANGUAGES_PATH')) define('LANGUAGES_PATH', dirname(__FILE__) . '/languages/');

// Pagination
if (!defined('ITEMS_PER_PAGE')) define('ITEMS_PER_PAGE', 12);
if (!defined('ADMIN_ITEMS_PER_PAGE')) define('ADMIN_ITEMS_PER_PAGE', 20);

// Paths
if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__FILE__) . '/');
if (!defined('ADMIN_URL')) define('ADMIN_URL', BASE_URL . 'admin/');
if (!defined('ASSETS_URL')) define('ASSETS_URL', BASE_URL . 'assets/');
if (!defined('UPLOADS_URL')) define('UPLOADS_URL', ASSETS_URL . 'uploads/');
if (!defined('INCLUDES_PATH')) define('INCLUDES_PATH', BASE_PATH . 'includes/');
if (!defined('CONFIG_PATH')) define('CONFIG_PATH', BASE_PATH . 'config/');
if (!defined('UPLOADS_PATH')) define('UPLOADS_PATH', BASE_PATH . 'assets/uploads/');

// Zajil API Configuration
if (!defined('ZAJIL_API_KEY')) define('ZAJIL_API_KEY', 'YOUR_ZAJIL_API_KEY_HERE');
if (!defined('ZAJIL_CUSTOMER_CODE')) define('ZAJIL_CUSTOMER_CODE', 'YOUR_CUSTOMER_CODE_HERE');
if (!defined('ZAJIL_TEST_MODE')) define('ZAJIL_TEST_MODE', true); // Set to false for production

// Includes
require_once BASE_PATH . 'includes/functions.php';
require_once BASE_PATH . 'includes/Database.php';
require_once BASE_PATH . 'includes/Language.php';
require_once BASE_PATH . 'includes/Currency.php';
require_once BASE_PATH . 'includes/Settings.php';
require_once BASE_PATH . 'includes/User.php';
require_once BASE_PATH . 'includes/Product.php';
require_once BASE_PATH . 'includes/Cart.php';
require_once BASE_PATH . 'includes/Wishlist.php';
require_once BASE_PATH . 'includes/Coupon.php';
require_once BASE_PATH . 'includes/Shipping.php';
require_once BASE_PATH . 'includes/Payment.php';
require_once BASE_PATH . 'includes/Order.php';
require_once BASE_PATH . 'includes/Invoice.php';
require_once BASE_PATH . 'includes/Zajil.php';

// Init
$db = new Database();
$lang = new Language($db);
$currency = new Currency($db);
$settings = new Settings($db);

require_once BASE_PATH . 'includes/language-switch.php';

if (!function_exists('__')) {
    function __($key, $default = '') {
        global $lang;
        return $lang ? $lang->get($key, $default) : $default;
    }
}

if (!function_exists('buildCurrentUrlWithParam')) {
    function buildCurrentUrlWithParam($param, $value) {
        $current_url = $_SERVER['REQUEST_URI'] ?? '';
        $parsed_url = parse_url($current_url);
        $query = [];
        if (isset($parsed_url['query'])) {
            parse_str($parsed_url['query'], $query);
        }
        unset($query[$param]);
        $query[$param] = $value;
        $new_query = http_build_query($query);
        $new_url = $parsed_url['path'] ?? '';
        if ($new_query) $new_url .= '?' . $new_query;
        return $new_url;
    }
}

// Dir setup
if ($isLocal) {
    $directories = [BASE_PATH . 'logs', UPLOADS_PATH . 'products', UPLOADS_PATH . 'banners', UPLOADS_PATH . 'brands', UPLOADS_PATH . 'blogs', UPLOADS_PATH . 'services', UPLOADS_PATH . 'temp'];
    foreach ($directories as $dir) { if (!file_exists($dir)) @mkdir($dir, 0755, true); }
}

$user = null;
if (isset($_SESSION['user_id'])) {
    $user = $db->fetchOne("SELECT * FROM users WHERE id = :id", ['id' => $_SESSION['user_id']]);
}

$cart = new Cart($db);
$cart_items = $cart->getItems();
$cart_count = $cart->getCount();
$_SESSION['cart'] = $cart_items; 
$cart_subtotal = $cart->getSubtotal();

$wishlist_count = 0;
if ($user) $wishlist_count = $db->count('wishlists', 'user_id = :id', ['id' => $user['id']]);

$service_cart_count = 0;
if (isset($_SESSION['multi_service_booking_cart'])) $service_cart_count = count($_SESSION['multi_service_booking_cart']);

$tz = $settings->get('timezone', DEFAULT_TIMEZONE);
date_default_timezone_set($tz ?: DEFAULT_TIMEZONE);
