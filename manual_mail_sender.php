<?php
/**
 * Manual Email Trigger Script
 * This script allows you to test the email notifications for a specific order.
 */

require_once 'config.php';
require_once INCLUDES_PATH . 'Email.php';

// Check if user is logged in as admin (basic security)
if (!isset($_SESSION['admin_role']) && !isset($_SESSION['user_id'])) {
    // For local testing purposes, we'll allow it if accessed from localhost
    $isLocal = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']);
    if (!$isLocal) {
        die('Access Denied. Please login as admin first.');
    }
}

// Get the latest order ID for testing if none provided
$latestOrder = $db->fetchOne("SELECT id FROM orders ORDER BY id DESC LIMIT 1");
$orderId = $_GET['id'] ?? ($latestOrder['id'] ?? 0);

if (!$orderId) {
    die('No orders found in the database to test with.');
}

// Get order details
$order = $db->fetchOne("SELECT order_number, guest_email, billing_email FROM orders WHERE id = :id", ['id' => $orderId]);
$dbRecipient = !empty($order['billing_email']) ? $order['billing_email'] : ($order['guest_email'] ?? '');

$action = $_GET['action'] ?? '';
$status = $_GET['status'] ?? 'confirmed';
$customEmail = $_GET['custom_email'] ?? '';
$emailLog = '';

if ($action === 'send') {
    try {
        $mailer = new Email($db);
        $result = false;
        
        // Use custom email if provided, otherwise use from DB
        $recipient = !empty($customEmail) ? $customEmail : $dbRecipient;
        
        if (empty($recipient)) {
            $emailLog = "<span style='color:red;'>FAILED: Recipient email is empty. Please enter an email address manually below.</span>";
        } else {
            // Since orderConfirmation and orderStatusUpdate use the ID to fetch from DB, 
            // we'll need to briefly mock the email in the object if we want to use a custom one for testing.
            // But for simplicity, we'll just check if it's confirmation or status update.
            
            switch ($status) {
                case 'confirmation':
                    $result = $mailer->orderConfirmation($orderId, $recipient);
                    $emailLog = "Sent Order Confirmation email for Order #$orderId to $recipient";
                    break;
                case 'shipped':
                case 'delivered':
                case 'cancelled':
                case 'processing':
                    $result = $mailer->orderStatusUpdate($orderId, $status, 'TEST-TRACK-123', $recipient);
                    $emailLog = "Sent Status Update ($status) email for Order #$orderId to $recipient";
                    break;
                default:
                    $emailLog = "Invalid status selected.";
            }

            if ($result) {
                $emailLog .= " - <span style='color:green;font-weight:bold;'>SUCCESS!</span> Check your inbox.";
            } else {
                $emailLog .= " - <span style='color:red;font-weight:bold;'>FAILED.</span> Possible reasons: SMTP settings incorrect, or recipient email still empty. Check logs/error.log.";
            }
        }
    } catch (Exception $e) {
        $emailLog = "Error: " . $e->getMessage();
    }
}

$displayRecipient = !empty($customEmail) ? $customEmail : (!empty($dbRecipient) ? $dbRecipient : '<span style="color:red">EMPTY (Please enter manually below)</span>');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manual Email Trigger</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; padding: 40px 0; }
        .card { border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border:none; }
        .test-btn { min-width: 150px; margin: 5px; }
        .status-badge { font-size: 0.9rem; padding: 5px 12px; border-radius: 20px; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-9">
            <div class="card p-4">
                <h3 class="mb-4 text-center">Order Email Tester</h3>
                
                <?php if ($emailLog): ?>
                    <div class="alert alert-light border-start border-4 border-info shadow-sm mb-4">
                        <?php echo $emailLog; ?>
                    </div>
                <?php endif; ?>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5>Testing Order: <span class="text-primary">#<?php echo htmlspecialchars($order['order_number'] ?? 'N/A'); ?></span></h5>
                        <p class="text-muted mb-0">Target Recipient: <strong><?php echo $displayRecipient; ?></strong></p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <form action="" method="GET" class="d-inline-flex gap-2 w-100">
                            <input type="hidden" name="id" value="<?php echo $orderId; ?>">
                            <input type="email" name="custom_email" class="form-control form-control-sm" placeholder="Enter manual email to test..." value="<?php echo htmlspecialchars($customEmail); ?>">
                            <button type="submit" class="btn btn-sm btn-secondary">Set Email</button>
                        </form>
                        <small class="text-muted mt-1 d-block italic">Use this to test if DB email is empty or incorrect.</small>
                    </div>
                </div>

                <hr>

                <div class="mb-5 text-center">
                    <h6 class="mb-3">Choose Email Template to Send:</h6>
                    <div class="d-flex flex-wrap justify-content-center">
                        <a href="?id=<?php echo $orderId; ?>&action=send&status=confirmation&custom_email=<?php echo urlencode($customEmail); ?>" class="btn btn-success test-btn">
                            <i class="fas fa-check-circle me-1"></i> Order Confirmation
                        </a>
                        <a href="?id=<?php echo $orderId; ?>&action=send&status=processing&custom_email=<?php echo urlencode($customEmail); ?>" class="btn btn-primary test-btn">
                            <i class="fas fa-cog me-1"></i> Status: Processing
                        </a>
                        <a href="?id=<?php echo $orderId; ?>&action=send&status=shipped&custom_email=<?php echo urlencode($customEmail); ?>" class="btn btn-info test-btn">
                            <i class="fas fa-truck me-1"></i> Status: Shipped
                        </a>
                        <a href="?id=<?php echo $orderId; ?>&action=send&status=delivered&custom_email=<?php echo urlencode($customEmail); ?>" class="btn btn-dark test-btn">
                            <i class="fas fa-home me-1"></i> Status: Delivered
                        </a>
                        <a href="?id=<?php echo $orderId; ?>&action=send&status=cancelled&custom_email=<?php echo urlencode($customEmail); ?>" class="btn btn-danger test-btn">
                            <i class="fas fa-times-circle me-1"></i> Status: Cancelled
                        </a>
                    </div>
                </div>

                <div class="mt-4 p-4 bg-light rounded italic shadow-sm">
                    <p class="h6"><i class="fas fa-info-circle me-2"></i><strong>Why did it fail?</strong></p>
                    <ul class="mb-0 mt-2 small">
                        <li><strong>Recipient Email Empty:</strong> Database mein Order #<?php echo $orderId; ?> ke saath koi email save nahi hui. Upar diye gaye box mein apni email daal kar test karein.</li>
                        <li><strong>SMTP Settings:</strong> Localhost (XAMPP) par Gmail/SMTP set hona zaroori hai. Admin Dashboard > Settings > Mail check karein.</li>
                        <li><strong>Logs:</strong> Agar "FAILED" aata hai, to `logs/error.log` file check karein wahan exact error milega (e.g., Auth failure, Connection timeout).</li>
                    </ul>
                </div>
                
                <div class="mt-4 text-center">
                    <a href="admin/orders/view.php?id=<?php echo $orderId; ?>" class="btn btn-link text-decoration-none">← Go to Admin Order View</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>
