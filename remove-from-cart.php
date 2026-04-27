<?php
require_once 'config.php';
require_once 'includes/MultiServiceBooking.php';

// Initialize multi-service booking
$multiServiceBooking = new MultiServiceBooking($db);

// Get item ID from URL
$itemId = $_GET['id'] ?? '';

if (empty($itemId)) {
    $_SESSION['error'] = 'Invalid item ID';
    header('Location: ' . BASE_URL . 'service-payment.php');
    exit;
}

try {
    // Remove item from cart
    $result = $multiServiceBooking->removeServiceFromCart($itemId);
    
    if ($result) {
        $_SESSION['success'] = 'Service removed from cart successfully';
    } else {
        $_SESSION['error'] = 'Failed to remove service from cart';
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'Error removing service: ' . $e->getMessage();
}

// Redirect back to payment page
header('Location: ' . BASE_URL . 'service-payment.php');
exit;
?>
