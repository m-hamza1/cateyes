<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once '../../config.php';
require_once '../../includes/MultiServiceBooking.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // Initialize multi-service booking
    $multiServiceBooking = new MultiServiceBooking($db);

    // Get cart item ID from POST
    $cartItemId = $_POST['cart_item_id'] ?? '';

    if (empty($cartItemId)) {
        echo json_encode(['success' => false, 'error' => 'Cart item ID not provided']);
        exit;
    }

    // Remove item from cart
    $result = $multiServiceBooking->removeServiceFromCart($cartItemId);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to remove item from cart']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>
