<?php
/**
 * Application Initialization
 * Loads all required files and initializes core systems
 */

// Load configuration first
require_once dirname(__DIR__) . '/config/config.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
    
    // Regenerate session ID periodically for security
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > SESSION_LIFETIME) {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

// Load helper functions
require_once INCLUDES_PATH . 'functions.php';

// Load core classes
require_once INCLUDES_PATH . 'Database.php';
require_once INCLUDES_PATH . 'Language.php';
require_once INCLUDES_PATH . 'Currency.php';
require_once INCLUDES_PATH . 'Cart.php';
require_once INCLUDES_PATH . 'Product.php';
require_once INCLUDES_PATH . 'Order.php';
require_once INCLUDES_PATH . 'Invoice.php';
require_once INCLUDES_PATH . 'User.php';
require_once INCLUDES_PATH . 'Email.php';

// Initialize database connection
$db = new Database();

// Initialize language system
$lang = new Language($db);

// Initialize currency system
$currency = new Currency($db);

// Initialize cart (if not in admin)
if (!defined('ADMIN_AREA')) {
    $cart = new Cart($db);
}

// Set language direction for HTML
$dir = $lang->getDirection();
$langCode = $lang->getCurrentCode();

// Helper function for translations
function __($key, $default = '') {
    global $lang;
    return $lang->get($key, $default);
}

// Handle language change
if (isset($_GET['change_lang'])) {
    $lang->changeLanguage($_GET['change_lang']);
}

// Handle currency change
if (isset($_GET['change_currency'])) {
    $currency->changeCurrency($_GET['change_currency']);
    redirect($_SERVER['PHP_SELF']);
}

// Get site settings
$siteSettings = [];
$settingsQuery = "SELECT `key`, `value` FROM site_settings 
                  WHERE language_id IS NULL OR language_id = :lang_id";
$settings = $db->fetchAll($settingsQuery, ['lang_id' => $lang->getCurrentId()]);

foreach ($settings as $setting) {
    $siteSettings[$setting['key']] = $setting['value'];
}

// Define site name
define('SITE_NAME', $siteSettings['site_name'] ?? __('site_name'));

// Error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    $error = "[" . date('Y-m-d H:i:s') . "] ";
    $error .= "Error [$errno]: $errstr in $errfile on line $errline\n";
    
    if (LOG_ERRORS) {
        error_log($error, 3, ERROR_LOG_FILE);
    }
    
    if (DISPLAY_ERRORS) {
        echo "<strong>Error:</strong> $errstr in <strong>$errfile</strong> on line <strong>$errline</strong><br>";
    }
    
    return true;
});

// Exception handler
set_exception_handler(function($exception) {
    $error = "[" . date('Y-m-d H:i:s') . "] ";
    $error .= "Exception: " . $exception->getMessage() . "\n";
    $error .= "File: " . $exception->getFile() . " Line: " . $exception->getLine() . "\n";
    $error .= "Trace: " . $exception->getTraceAsString() . "\n";
    
    if (LOG_ERRORS) {
        error_log($error, 3, ERROR_LOG_FILE);
    }
    
    if (DISPLAY_ERRORS) {
        echo "<h1>An error occurred</h1>";
        echo "<p>" . $exception->getMessage() . "</p>";
        echo "<pre>" . $exception->getTraceAsString() . "</pre>";
    } else {
        echo "<h1>An error occurred</h1>";
        echo "<p>Please try again later.</p>";
    }
});
