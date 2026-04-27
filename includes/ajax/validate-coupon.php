<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files (config.php already creates $db and starts session)
require_once '../../config.php';
require_once '../../includes/CouponManager.php';

try {
    // Use $db from config.php, get user from session
    $user = $_SESSION['user'] ?? null;

    // Initialize coupon manager
    $couponManager = new CouponManager($db, $user);

    // Get request data
    $couponCode = $_POST['coupon_code'] ?? '';
    $totalAmount = (float)($_POST['total_amount'] ?? 0);

    // Validate input
    if (empty($couponCode) || $totalAmount <= 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid request parameters'
        ]);
        exit;
    }

    // Apply coupon with active currency exchange rate
    $exchangeRate = (float)($_POST['exchange_rate'] ?? 1.0);
    if ($exchangeRate <= 0) $exchangeRate = 1.0;
    $result = $couponManager->applyCoupon($couponCode, $totalAmount, $exchangeRate);

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;

} catch (Exception $e) {
    // Return error response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
    exit;
}
