<?php
/**
 * Payment Return Page
 * N-Genius / Networks International redirects user back here after payment
 * This page auto-verifies the payment with the gateway and updates the order status
 */
require_once 'config.php';
require_once INCLUDES_PATH . 'Payment.php';
require_once INCLUDES_PATH . 'Settings.php';

$orderId   = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$bookingId = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
$gateway   = $_GET['gateway'] ?? '';
$status    = $_GET['status'] ?? '';
$type      = $bookingId ? 'booking' : 'order';
$id        = $bookingId ?: $orderId;

if (!$id || !$gateway) {
    header('Location: index.php');
    exit;
}

// Fetch the record
if ($type === 'booking') {
    $record = $db->fetchOne("SELECT * FROM service_bookings WHERE id = :id", ['id' => $id]);
} else {
    $record = $db->fetchOne("SELECT * FROM orders WHERE id = :id", ['id' => $id]);
}

if (!$record) {
    header('Location: index.php');
    exit;
}

$payment    = new Payment($db);
$isSuccess  = false;
$errorMessage = '';

if ($gateway === 'networks') {
    $result     = $payment->verifyNetworksPayment($id, $type);
    $isSuccess  = $result['success'] && ($result['message'] === 'Payment verified successfully');
    $errorMessage = $result['message'] ?? 'Payment failed';
} elseif ($gateway === 'tabby') {
    if ($status === 'success') {
        $paymentRecord = $db->fetchOne("SELECT id FROM payments WHERE " . ($type === 'booking' ? 'booking_id' : 'order_id') . " = :id AND payment_method = 'tabby'", ['id' => $id]);
        if ($paymentRecord) {
            $payment->updateStatus($paymentRecord['id'], 'completed');
            if ($type === 'booking') {
                $db->update('service_bookings', ['status' => 'confirmed', 'payment_status' => 'paid'], 'id = :id', ['id' => $id]);
                // Update other bookings if multi-service
                $db->update('service_bookings', ['status' => 'confirmed', 'payment_status' => 'paid'], 'payment_id = :pid', ['pid' => $paymentRecord['id']]);
            } else {
                $db->update('orders', ['order_status' => 'confirmed', 'payment_status' => 'paid'], 'id = :id', ['id' => $id]);
            }
            $isSuccess = true;
        } else {
            $errorMessage = 'Payment record not found';
        }
    } else {
        $errorMessage = 'Payment cancelled or failed';
    }
}

if ($isSuccess) {
    // Payment confirmed — send emails
    try {
        require_once INCLUDES_PATH . 'Email.php';
        $mailer = new Email($db);
        if ($type === 'booking') {
            require_once INCLUDES_PATH . 'ServiceBooking.php';
            $sb = new ServiceBooking($db);
            
            // Get all bookings linked to this payment
            $pid_query = ($gateway === 'tabby') ? "payment_method = 'tabby'" : "payment_method = 'networks'";
            $paymentRec = $db->fetchOne("SELECT id FROM payments WHERE " . ($type === 'booking' ? 'booking_id' : 'order_id') . " = :id AND {$pid_query} ORDER BY id DESC LIMIT 1", ['id' => $id]);
            
            if ($paymentRec) {
                $bookings = $db->fetchAll("SELECT id FROM service_bookings WHERE payment_id = :pid", ['pid' => $paymentRec['id']]);
                $bookingIds = array_column($bookings, 'id');
                
                if (count($bookingIds) > 1) {
                    $sb->sendMultiBookingConfirmation($bookingIds);
                    $sb->notifyAdminMultiBooking($bookingIds);
                } else {
                    $sb->sendConfirmationEmail(['id' => $id]);
                    $sb->notifyAdminNewBooking($id);
                }
            }
        } else {
            $mailer->orderConfirmation($id);
            $mailer->notifyAdminNewOrder($id);
        }
    } catch (Exception $e) {
        error_log("Email error after return: " . $e->getMessage());
    }

    // Clear session
    unset($_SESSION['checkout']);
    unset($_SESSION['service_cart']);

    $refNumber = ($type === 'booking') ? $record['booking_number'] : $record['order_number'];
    $pageTitle   = 'Payment Successful';
    include 'includes/header.php';
    ?>
    <div class="container py-5 text-center">
        <div class="card shadow-sm border-0 rounded-4 mx-auto" style="max-width:540px;">
            <div class="card-body py-5 px-4">
                <div class="mb-4">
                    <span style="font-size:4rem;">✅</span>
                </div>
                <h1 class="h3 fw-bold text-success mb-2">Payment Successful!</h1>
                <p class="text-muted mb-1">Thank you for your <?php echo ($type === 'booking' ? 'booking' : 'order'); ?>.</p>
                <p class="mb-4"><strong><?php echo ($type === 'booking' ? 'Booking' : 'Order'); ?> #<?php echo htmlspecialchars($refNumber); ?></strong> has been confirmed.</p>
                
                <?php if ($type === 'order'): ?>
                <a href="order-tracking.php?order_id=<?php echo $id; ?>" class="btn btn-primary me-2">
                    <i class="fas fa-box me-1"></i> Track Order
                </a>
                <?php else: ?>
                <a href="my-bookings.php" class="btn btn-primary me-2">
                    <i class="fas fa-calendar-check me-1"></i> View Bookings
                </a>
                <?php endif; ?>
                
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-home me-1"></i> Home
                </a>
            </div>
        </div>
    </div>
    <?php
    include 'includes/footer.php';

} else {
    // Failure logic
    $isPending = str_contains($errorMessage, 'Pending') || str_contains($errorMessage, 'Incomplete');

    if (!$isPending) {
        // Only cancel if it's an online payment that definitely failed
        if ($type === 'booking') {
            $paymentRec = $db->fetchOne("SELECT id FROM payments WHERE booking_id = :bid AND payment_method = :pm ORDER BY id DESC LIMIT 1", ['bid' => $id, 'pm' => $gateway]);
            if ($paymentRec) {
                $db->update('service_bookings', ['status' => 'cancelled', 'payment_status' => 'failed'], 'payment_id = :pid', ['pid' => $paymentRec['id']]);
            }
            $db->update('service_bookings', ['status' => 'cancelled', 'payment_status' => 'failed'], 'id = :id', ['id' => $id]);
        } else {
            $db->update('orders', ['order_status' => 'cancelled', 'payment_status' => 'failed'], 'id = :id', ['id' => $id]);
        }
        
        $db->update('payments',
            ['status' => 'failed'],
            ($type === 'booking' ? 'booking_id' : 'order_id') . ' = :id AND payment_method = :pm',
            ['id' => $id, 'pm' => $gateway]
        );
    }

    $pageTitle = $isPending ? 'Payment Pending' : 'Payment Failed';
    include 'includes/header.php';
    ?>
    <div class="container py-5 text-center">
        <div class="card shadow-sm border-0 rounded-4 mx-auto" style="max-width:540px;">
            <div class="card-body py-5 px-4">
                <div class="mb-4">
                    <span style="font-size:4rem;"><?php echo $isPending ? '⏳' : '❌'; ?></span>
                </div>
                <h1 class="h3 fw-bold <?php echo $isPending ? 'text-warning' : 'text-danger'; ?> mb-2">
                    <?php echo $isPending ? 'Payment Pending' : 'Payment Failed'; ?>
                </h1>
                <p class="text-muted mb-4">
                    <?php if ($isPending): ?>
                        Your payment is still being processed. We will update you via email once confirmed.
                    <?php else: ?>
                        Your payment could not be completed. Error: <?php echo htmlspecialchars($errorMessage); ?>
                    <?php endif; ?>
                </p>
                <a href="<?php echo ($type === 'booking' ? 'servicescheckout.php' : 'checkout.php'); ?>" class="btn btn-primary me-2">
                    <i class="fas fa-redo me-1"></i> Try Again
                </a>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-home me-1"></i> Home
                </a>
            </div>
        </div>
    </div>
    <?php
    include 'includes/footer.php';
}
?>
