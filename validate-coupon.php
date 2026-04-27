<?php
require_once 'config.php';
require_once 'includes/CouponManager.php';

header('Content-Type: application/json');

// Initialize coupon manager
$couponManager = new CouponManager($db, $user ?? null);

// Get coupon code and amount from POST
$couponCode = $_POST['coupon_code'] ?? '';
$amount = floatval($_POST['amount'] ?? 0);

if (empty($couponCode) || $amount <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request parameters'
    ]);
    exit;
}

try {
    // Get currency details for proper discount calculation
    $displayCurr = $currency->getCurrentCode();
    $defaultCurr = defined('DEFAULT_CURRENCY') ? DEFAULT_CURRENCY : 'AED';
    $rateToDisplay = ($displayCurr !== $defaultCurr) ? (float)$currency->convert(1, $defaultCurr) : 1.0;
    
    // Validate and apply coupon
    $result = $couponManager->applyCoupon($couponCode, $amount, $displayCurr, $rateToDisplay);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Coupon applied successfully!',
            'coupon_code' => $result['coupon']['code'],
            'coupon_type' => $result['coupon']['type'],
            'coupon_value' => $result['coupon']['value'],
            'description' => $result['coupon']['description'] ?? 'Discount applied',
            'discount' => $result['discount'],
            'discount_base' => $result['discount_base'],
            'final_amount' => $result['final_amount'],
            'final_amount_base' => $result['final_amount_base']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $result['message'] ?? 'Invalid coupon code'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error validating coupon: ' . $e->getMessage()
    ]);
}
?>
