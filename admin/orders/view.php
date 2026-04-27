<?php
/**
 * Order View - Detailed Order Information
 */

require_once '../includes/auth.php';
requirePermission('orders');
$pageTitle = __('order_details', 'Order Details');

// Get order ID
$orderId = (int)($_GET['id'] ?? 0);
if (!$orderId) {
    $_SESSION['error'] = __('order_id_not_provided', 'Order ID not provided');
    header('Location: index.php');
    exit;
}

// Initialize classes
$order = new Order($db);
require_once INCLUDES_PATH . 'Payment.php';
$paymentObj = new Payment($db);

// Get order details
$orderDetails = $order->getById($orderId);
if (!$orderDetails) {
    $_SESSION['error'] = __('order_not_found', 'Order not found');
    header('Location: index.php');
    exit;
}

// Mark order as viewed by admin (for sidebar badge count)
// Note: Database wrapper swallows PDO exceptions, so we must check return values.
$col = $db->fetchOne("SHOW COLUMNS FROM orders LIKE 'admin_viewed_at'");
if (!$col) {
    $db->query("ALTER TABLE orders ADD COLUMN admin_viewed_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at");
    $col = $db->fetchOne("SHOW COLUMNS FROM orders LIKE 'admin_viewed_at'");
}
if ($col) {
    $db->update('orders', ['admin_viewed_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $orderId]);
}

// Get order items
$orderItems = $orderDetails['items'] ?? [];

// Get order status history
$statusHistory = $order->getStatusHistory($orderId);
$invoiceService = new Invoice($db);
$invoiceInfo = $invoiceService->getInfoForOrder($orderId);

// Handle payment status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment'])) {
    $paymentStatus = $_POST['payment_status'] ?? '';
    
    if ($paymentStatus) {
        $result = $order->updatePaymentStatus($orderId, $paymentStatus);
        
        if ($result) {
            $_SESSION['success'] = __('payment_status_updated_successfully', 'Payment status updated successfully');
            
            // Trigger confirmation email if paid
            if ($paymentStatus === 'paid') {
                try {
                    require_once INCLUDES_PATH . 'Email.php';
                    $mailer = new Email($db);
                    $mailer->orderConfirmation($orderId);
                    
                    // Also auto-confirm the order if it was pending
                    if (($orderDetails['order_status'] ?? 'pending') === 'pending') {
                        $order->updateStatus($orderId, 'confirmed', 'System: Status updated to confirmed because payment was marked as paid.');
                    }
                } catch (Exception $e) {
                    error_log("Failed to send payment confirmation email: " . $e->getMessage());
                }
            }
            
            // Refresh order details
            $orderDetails = $order->getById($orderId);
            $statusHistory = $order->getStatusHistory($orderId);
        } else {
            $_SESSION['error'] = __('failed_to_update_payment_status', 'Failed to update payment status');
        }
    }
    header("Location: view.php?id=$orderId");
    exit;
}

// Handle Manual Payment Sync
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync_payment'])) {
    $paymentMethod = $orderDetails['payment_method'] ?? '';
    
    if ($paymentMethod === 'networks') {
        $result = $paymentObj->verifyNetworksPayment($orderId);
        if ($result['success']) {
            $_SESSION['success'] = __('payment_synced_successfully', 'Payment synced successfully: ') . $result['message'];
        } else {
            $_SESSION['error'] = __('payment_sync_failed', 'Payment sync failed: ') . $result['message'];
        }
    } else {
        $_SESSION['error'] = __('sync_not_supported', 'Manual sync is not supported for this payment method.');
    }
    header("Location: view.php?id=$orderId");
    exit;
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = $_POST['status'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $shippingMethodId = $_POST['shipping_method_id'] ?? '';
    
    error_log("DEBUG: Order status update - Order ID: $orderId, New Status: $newStatus, Payment Status: " . ($orderDetails['payment_status'] ?? 'unknown'));
    
    if ($newStatus) {
        // If status is being changed to shipped, capture shipping method and date
        if ($newStatus === 'shipped' && !$shippingMethodId) {
            $_SESSION['error'] = __('shipping_method_required_for_shipped', 'Please select a shipping method before marking the order as shipped.');
            header("Location: view.php?id=$orderId");
            exit;
        }
        elseif ($newStatus === 'shipped' && $shippingMethodId) {
            error_log("DEBUG: Calling updateStatusWithShipping");
            $result = $order->updateStatusWithShipping($orderId, $newStatus, $notes, $shippingMethodId);
        }
        // If status is being changed to delivered, handle delivery logic
        elseif ($newStatus === 'delivered') {
            error_log("DEBUG: Calling updateStatusWithDelivery");
            $result = $order->updateStatusWithDelivery($orderId, $newStatus, $notes);
            error_log("DEBUG: updateStatusWithDelivery result: " . print_r($result, true));
            if (!$result['success']) {
                error_log("DEBUG: updateStatusWithDelivery failed: " . $result['message']);
                $_SESSION['error'] = $result['message'];
                header("Location: view.php?id=$orderId");
                exit;
            }
        }
        else {
            error_log("DEBUG: Calling updateStatus");
            $result = $order->updateStatus($orderId, $newStatus, $notes);
        }
        
        error_log("DEBUG: Final result: " . print_r($result, true));
        
        if ($result) {
            $_SESSION['success'] = __('order_status_updated_successfully', 'Order status updated successfully');
            
            // Trigger email notifications
            try {
                require_once INCLUDES_PATH . 'Email.php';
                $mailer = new Email($db);
                if ($newStatus === 'confirmed') {
                    $mailer->orderConfirmation($orderId);
                } else {
                    // Notify about status update (Shipped, Delivered, Cancelled, etc.)
                    $trackingNumber = $orderDetails['tracking_number'] ?? '';
                    $mailer->orderStatusUpdate($orderId, $newStatus, $trackingNumber);
                }
            } catch (Exception $e) {
                error_log("Failed to send order status email: " . $e->getMessage());
            }

            // Refresh order details
            $orderDetails = $order->getById($orderId);
            $statusHistory = $order->getStatusHistory($orderId);
            $invoiceInfo = $invoiceService->getInfoForOrder($orderId);
        } else {
            $_SESSION['error'] = $order->getLastError() ?: __('failed_to_update_order_status', 'Failed to update order status');
        }
    }
    header("Location: view.php?id=$orderId");
    exit;
}

// Handle tracking number update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_tracking'])) {
    $trackingNumber = $_POST['tracking_number'] ?? '';
    
    if ($trackingNumber) {
        $result = $order->addTrackingNumber($orderId, $trackingNumber);
        if ($result) {
            $_SESSION['success'] = __('tracking_number_added_successfully', 'Tracking number added successfully');
            
            // Trigger email notification with tracking info
            try {
                require_once INCLUDES_PATH . 'Email.php';
                $mailer = new Email($db);
                $mailer->orderStatusUpdate($orderId, $orderDetails['order_status'], $trackingNumber);
            } catch (Exception $e) {
                error_log("Failed to send tracking update email: " . $e->getMessage());
            }

            // Refresh order details
            $orderDetails = $order->getById($orderId);
        } else {
            $_SESSION['error'] = __('failed_to_add_tracking_number', 'Failed to add tracking number');
        }
    }
    header("Location: view.php?id=$orderId");
    exit;
}

// Handle Zajil Auto Shipment Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_zajil_shipment'])) {
    $apiKey = $siteSettings['zajil_api_key'] ?? (defined('ZAJIL_API_KEY') ? ZAJIL_API_KEY : '');
    $customerCode = $siteSettings['zajil_customer_code'] ?? (defined('ZAJIL_CUSTOMER_CODE') ? ZAJIL_CUSTOMER_CODE : '');
    $isTestMode = ($siteSettings['zajil_test_mode'] ?? '1') == '1';
    
    if (empty($apiKey) || empty($customerCode)) {
        $_SESSION['error'] = "Zajil Error: API Key or Customer Code is missing. Please configure them in Shipping Settings.";
        header("Location: view.php?id=$orderId");
        exit;
    }
    
    $zajil = new Zajil($apiKey, $customerCode, $isTestMode);
    
    $destName = trim(($orderDetails['shipping_first_name'] ?? '') . ' ' . ($orderDetails['shipping_last_name'] ?? ''));
    if (empty($destName)) {
        $destName = trim(($orderDetails['billing_first_name'] ?? '') . ' ' . ($orderDetails['billing_last_name'] ?? 'Guest'));
    }

    $destPhone = $orderDetails['shipping_phone'] ?? $orderDetails['billing_phone'] ?? '971500000000';
    $destAddress1 = $orderDetails['shipping_address_line1'] ?? $orderDetails['billing_address_line1'] ?? 'N/A';
    $destAddress2 = $orderDetails['shipping_address_line2'] ?? $orderDetails['billing_address_line2'] ?? '';
    $destCity = $orderDetails['shipping_city'] ?? $orderDetails['billing_city'] ?? 'DXB'; // Better to map to DXB/AUH/SHJ etc.
    
    // Attempt mapping city name to Zajil Code
    $cityMap = ['dubai' => 'DXB', 'abu dhabi' => 'AUH', 'sharjah' => 'SHJ', 'ajman' => 'AJM', 'fujairah' => 'FUJ', 'al ain' => 'AIN', 'ras al khaimah' => 'RAK', 'umm al quwain' => 'UAQ'];
    $normalizedCity = strtolower($destCity);
    $destCityCode = $cityMap[$normalizedCity] ?? 'DXB'; // default to DXB if unknown

    $storePhone = $siteSettings['store_phone'] ?? '971500000000';
    $storeAddress = $siteSettings['store_address'] ?? 'Our Store Address';
    $storeCity = $siteSettings['store_city'] ?? 'DXB';
    $storeArea = $siteSettings['store_area'] ?? 'AL GARHOUD';

    $orderData = [
        'order_number' => $orderDetails['order_number'],
        'weight' => 1.0, // Default 1kg
        'pieces' => 1,
        'cod_amount' => ($orderDetails['payment_method'] === 'cod' && $orderDetails['payment_status'] !== 'paid') ? $orderDetails['total_amount'] : 0,
        'description' => "Order #" . $orderDetails['order_number'],
        'origin' => [
            'name' => SITE_NAME,
            'phone' => $storePhone,
            'address_line_1' => $storeAddress,
            'city' => $storeCity,
            'area' => $storeArea,
        ],
        'destination' => [
            'name' => $destName,
            'phone' => $destPhone,
            'address_line_1' => $destAddress1,
            'address_line_2' => $destAddress2,
            'city' => $destCityCode,
        ]
    ];
    
    $response = $zajil->createDomesticShipment($orderData);
    
    if ($response['success'] ?? false) {
        $trackingNumber = $response['referenceNumber'];
        $order->addTrackingNumber($orderId, $trackingNumber);
        
        if ($orderDetails['order_status'] !== 'shipped') {
             $order->updateStatus($orderId, 'shipped', 'System: Auto shipped via Zajil. AWB: ' . $trackingNumber);
        }
        $_SESSION['success'] = "Zajil shipment created successfully! AWB: " . $trackingNumber;
    } else {
        $_SESSION['error'] = "Zajil Error: " . ($response['message'] ?? 'Unknown error. Check error logs.');
        error_log("Zajil API Error Response: " . print_r($response, true));
    }
    
    header("Location: view.php?id=$orderId");
    exit;
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-shopping-bag me-2"></i>
                    <?php echo __('order_details', 'Order Details'); ?>: #<?php echo htmlspecialchars($orderDetails['order_number']); ?>
                </h1>
                <div class="d-flex gap-2">
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> <?php echo __('back_to_orders', 'Back to Orders'); ?>
                    </a>
                    <a href="edit.php?id=<?php echo $orderId; ?>" class="btn btn-primary">
                        <i class="fas fa-edit me-1"></i> <?php echo __('edit_order', 'Edit Order'); ?>
                    </a>
                    <a href="invoice.php?id=<?php echo $orderId; ?>" class="btn btn-outline-primary" target="_blank">
                        <i class="fas fa-file-pdf me-1"></i> <?php echo __('view_invoice', 'View Invoice'); ?>
                    </a>
                    <button type="button" class="btn btn-danger" onclick="window.print()">
                        <i class="fas fa-print me-1"></i> <?php echo __('print', 'Print'); ?>
                    </button>
                </div>
            </div>

            <div class="alert <?php echo !empty($invoiceInfo['exists']) ? 'alert-success' : 'alert-warning'; ?> d-flex justify-content-between align-items-center">
                <div>
                    <strong><?php echo __('invoice', 'Invoice'); ?>:</strong>
                    <?php if (!empty($invoiceInfo['exists'])): ?>
                        <?php echo htmlspecialchars($invoiceInfo['invoice_number'] ?? ''); ?>
                        <?php if (!empty($invoiceInfo['invoice_generated_at'])): ?>
                            <span class="ms-2 text-muted"><?php echo __('generated', 'Generated'); ?> <?php echo htmlspecialchars($invoiceInfo['invoice_generated_at']); ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php echo __('invoice_not_generated_yet', 'Invoice PDF has not been generated yet. It will be created automatically when the order is marked as prepared/processing.'); ?>
                    <?php endif; ?>
                </div>
                <div class="d-flex gap-2">
                    <a href="invoice.php?id=<?php echo $orderId; ?>&regenerate=1" class="btn btn-sm btn-outline-secondary" target="_blank">
                        <i class="fas fa-sync-alt me-1"></i> <?php echo __('regenerate_invoice', 'Regenerate Invoice'); ?>
                    </a>
                    <?php if (!empty($invoiceInfo['exists'])): ?>
                        <a href="invoice.php?id=<?php echo $orderId; ?>&download=1" class="btn btn-sm btn-primary">
                            <i class="fas fa-download me-1"></i> <?php echo __('download_invoice', 'Download Invoice'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Order Status Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title"><?php echo __('order_status', 'Order Status'); ?></h6>
                            <span class="badge bg-<?php echo getStatusColor($orderDetails['order_status']); ?> fs-6">
                                <?php echo ucfirst($orderDetails['order_status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title"><?php echo __('payment_status', 'Payment Status'); ?></h6>
                            <span class="badge bg-<?php echo getPaymentStatusColor($orderDetails['payment_status']); ?> fs-6">
                                <?php echo ucfirst($orderDetails['payment_status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php if ($orderDetails['order_status'] === 'shipped' || $orderDetails['order_status'] === 'delivered'): ?>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title"><?php echo __('shipping_info', 'Shipping Info'); ?></h6>
                            <?php if (!empty($orderDetails['shipping_method_name'])): ?>
                                <p class="mb-1"><strong><?php echo __('method', 'Method'); ?>:</strong><br>
                                    <small><?php echo htmlspecialchars($orderDetails['shipping_method_name']); ?></small>
                                </p>
                            <?php endif; ?>
                            <?php if (!empty($orderDetails['shipping_date'])): ?>
                                <p class="mb-0"><strong><?php echo __('date', 'Date'); ?>:</strong><br>
                                    <small><?php echo date('M d, Y', strtotime($orderDetails['shipping_date'])); ?></small>
                                </p>
                            <?php endif; ?>
                            <?php if ($orderDetails['order_status'] === 'delivered' && !empty($orderDetails['delivery_date'])): ?>
                                <p class="mb-0"><strong><?php echo __('delivery_date', 'Delivery Date'); ?>:</strong><br>
                                    <small><?php echo date('M d, Y', strtotime($orderDetails['delivery_date'])); ?></small>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title"><?php echo __('payment_info', 'Payment Info'); ?></h6>
                            <?php if (!empty($orderDetails['payment_date'])): ?>
                                <p class="mb-1"><strong><?php echo __('payment_date', 'Payment Date'); ?>:</strong><br>
                                    <small><?php echo date('M d, Y', strtotime($orderDetails['payment_date'])); ?></small>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title"><?php echo __('order_date', 'Order Date'); ?></h6>
                            <p class="mb-0"><?php echo date('M d, Y H:i', strtotime($orderDetails['created_at'] . ' UTC')); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title"><?php echo __('total_amount', 'Total Amount'); ?></h6>
                            <h5 class="mb-0 text-primary"><?php echo $currency->format($orderDetails['total_amount']); ?></h5>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Customer Information -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-user me-2"></i><?php echo __('customer_information', 'Customer Information'); ?>
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php if ($orderDetails['user_id']): ?>
                                <!-- Registered User Information -->
                                <p class="mb-1">
                                    <strong><?php echo __('account_name', 'Account Name'); ?>:</strong> 
                                    <?php echo htmlspecialchars($orderDetails['user_first_name'] . ' ' . $orderDetails['user_last_name']); ?>
                                </p>
                                <?php if (!empty($orderDetails['user_email'])): ?>
                                <p class="mb-1">
                                    <strong><?php echo __('account_email', 'Account Email'); ?>:</strong> 
                                    <?php echo htmlspecialchars($orderDetails['user_email']); ?>
                                </p>
                                <?php endif; ?>
                                <p class="mb-1 mt-2">
                                    <strong><?php echo __('account_type', 'Account Type'); ?>:</strong> 
                                    <span class="badge bg-success"><?php echo __('registered_user', 'Registered User'); ?></span>
                                </p>
                                
                                <hr>
                                
                                <!-- Billing Information -->
                                <h6 class="text-muted mb-2"><?php echo __('billing_information', 'Billing Information'); ?></h6>
                            <?php else: ?>
                                <!-- Guest Checkout -->
                                <p class="mb-1 mt-2">
                                    <strong><?php echo __('account_type', 'Account Type'); ?>:</strong> 
                                    <span class="badge bg-secondary"><?php echo __('guest_checkout', 'Guest Checkout'); ?></span>
                                </p>
                                <hr>
                            <?php endif; ?>
                            
                            <!-- Billing Information (shown for all orders) -->
                            <?php 
                            // Try to get billing address from individual fields first, then JSON as fallback
                            $billingData = null;
                            
                            // Fallback: when billing empty, use shipping
                            if (empty(trim($orderDetails['billing_first_name'] ?? '')) && !empty(trim($orderDetails['shipping_first_name'] ?? ''))) {
                                $orderDetails['billing_first_name'] = $orderDetails['shipping_first_name'];
                                $orderDetails['billing_last_name'] = $orderDetails['shipping_last_name'];
                                $orderDetails['billing_email'] = $orderDetails['billing_email'] ?? $orderDetails['guest_email'] ?? $orderDetails['user_email'] ?? '';
                                $orderDetails['billing_phone'] = $orderDetails['billing_phone'] ?? $orderDetails['shipping_phone'] ?? '';
                                $orderDetails['billing_address_line1'] = $orderDetails['billing_address_line1'] ?? $orderDetails['shipping_address_line1'] ?? '';
                                $orderDetails['billing_city'] = $orderDetails['billing_city'] ?? $orderDetails['shipping_city'] ?? '';
                                $orderDetails['billing_country'] = $orderDetails['billing_country'] ?? $orderDetails['shipping_country'] ?? '';
                            }
                            // First try individual fields
                            if (!empty($orderDetails['billing_first_name'])) {
                                $billingData = [
                                    'first_name' => $orderDetails['billing_first_name'],
                                    'last_name' => $orderDetails['billing_last_name'],
                                    'company' => $orderDetails['billing_company'],
                                    'address_line1' => $orderDetails['billing_address_line1'],
                                    'address_line2' => $orderDetails['billing_address_line2'],
                                    'city' => $orderDetails['billing_city'],
                                    'state' => $orderDetails['billing_state'],
                                    'postal_code' => $orderDetails['billing_postal_code'],
                                    'country' => $orderDetails['billing_country'],
                                    'phone' => $orderDetails['billing_phone'],
                                    'email' => $orderDetails['billing_email']
                                ];
                            }
                            // Fallback to JSON if individual fields are empty
                            elseif (!empty($orderDetails['billing_address'])) {
                                $billingData = json_decode($orderDetails['billing_address'], true);
                            }
                            
                            // Check if billing is same as shipping
                            $shippingData = null;
                            
                            // First try individual shipping fields
                            if (!empty($orderDetails['shipping_first_name'])) {
                                $shippingData = [
                                    'first_name' => $orderDetails['shipping_first_name'],
                                    'last_name' => $orderDetails['shipping_last_name'],
                                    'company' => $orderDetails['shipping_company'],
                                    'address_line1' => $orderDetails['shipping_address_line1'],
                                    'address_line2' => $orderDetails['shipping_address_line2'],
                                    'city' => $orderDetails['shipping_city'],
                                    'state' => $orderDetails['shipping_state'],
                                    'postal_code' => $orderDetails['shipping_postal_code'],
                                    'country' => $orderDetails['shipping_country'],
                                    'phone' => $orderDetails['shipping_phone']
                                ];
                            }
                            // Fallback to JSON if individual fields are empty
                            elseif (!empty($orderDetails['shipping_address'])) {
                                $shippingData = json_decode($orderDetails['shipping_address'], true);
                            }
                            
                            $isSameAsShipping = false;
                            if ($billingData && $shippingData) {
                                $isSameAsShipping = (
                                    $billingData['first_name'] === $shippingData['first_name'] &&
                                    $billingData['last_name'] === $shippingData['last_name'] &&
                                    $billingData['address_line1'] === $shippingData['address_line1'] &&
                                    $billingData['city'] === $shippingData['city'] &&
                                    $billingData['country'] === $shippingData['country']
                                );
                            }
                            
                            if ($isSameAsShipping && $billingData):
                            ?>
                                <div class="alert alert-info py-2">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <?php echo __('same_as_shipping_address', 'Same as shipping address'); ?>
                                </div>
                            <?php elseif ($billingData): ?>
                                <p class="mb-1">
                                    <strong><?php echo __('billing_name', 'Billing Name'); ?>:</strong> 
                                    <?php echo htmlspecialchars($billingData['first_name'] . ' ' . $billingData['last_name']); ?>
                                </p>
                                <?php if (!empty($billingData['company'])): ?>
                                <p class="mb-1">
                                    <strong><?php echo __('billing_company', 'Billing Company'); ?>:</strong> 
                                    <?php echo htmlspecialchars($billingData['company']); ?>
                                </p>
                                <?php endif; ?>
                                <p class="mb-1">
                                    <strong><?php echo __('billing_address', 'Billing Address'); ?>:</strong> 
                                    <?php echo htmlspecialchars($billingData['address_line1']); ?>
                                </p>
                                <?php if (!empty($billingData['address_line2'])): ?>
                                <p class="mb-1">
                                    <strong><?php echo __('billing_address2', 'Billing Address 2'); ?>:</strong> 
                                    <?php echo htmlspecialchars($billingData['address_line2']); ?>
                                </p>
                                <?php endif; ?>
                                <p class="mb-1">
                                    <strong><?php echo __('billing_city_state_postal', 'City, State, Postal'); ?>:</strong> 
                                    <?php echo htmlspecialchars($billingData['city'] . ', ' . $billingData['state'] . ' ' . $billingData['postal_code']); ?>
                                </p>
                                <p class="mb-1">
                                    <strong><?php echo __('billing_country', 'Billing Country'); ?>:</strong> 
                                    <?php echo htmlspecialchars($billingData['country']); ?>
                                </p>
                                <?php if (!empty($billingData['phone'])): ?>
                                <p class="mb-1">
                                    <strong><?php echo __('billing_phone', 'Billing Phone'); ?>:</strong> 
                                    <?php echo htmlspecialchars($billingData['phone']); ?>
                                </p>
                                <?php endif; ?>
                                <?php if (!empty($billingData['email'])): ?>
                                <p class="mb-1">
                                    <strong><?php echo __('billing_email', 'Billing Email'); ?>:</strong> 
                                    <?php echo htmlspecialchars($billingData['email']); ?>
                                </p>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="mb-1">
                                    <strong><?php echo __('customer', 'Customer'); ?>:</strong> 
                                    <?php echo htmlspecialchars($orderDetails['guest_email'] ?? __('guest', 'Guest')); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Shipping Information -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-truck me-2"></i><?php echo __('shipping_information', 'Shipping Information'); ?>
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php 
                            // Try to get shipping address from individual fields first, then JSON as fallback
                            $shippingData = null;
                            
                            // First try individual fields
                            if (!empty($orderDetails['shipping_first_name'])) {
                                $shippingData = [
                                    'first_name' => $orderDetails['shipping_first_name'],
                                    'last_name' => $orderDetails['shipping_last_name'],
                                    'company' => $orderDetails['shipping_company'],
                                    'address_line1' => $orderDetails['shipping_address_line1'],
                                    'address_line2' => $orderDetails['shipping_address_line2'],
                                    'city' => $orderDetails['shipping_city'],
                                    'state' => $orderDetails['shipping_state'],
                                    'postal_code' => $orderDetails['shipping_postal_code'],
                                    'country' => $orderDetails['shipping_country'],
                                    'phone' => $orderDetails['shipping_phone']
                                ];
                            }
                            // Fallback to JSON if individual fields are empty
                            elseif (!empty($orderDetails['shipping_address'])) {
                                $shippingData = json_decode($orderDetails['shipping_address'], true);
                            }
                            
                            if ($shippingData):
                            ?>
                                <p class="mb-1">
                                    <strong>Name:</strong> 
                                    <?php echo htmlspecialchars($shippingData['first_name'] . ' ' . $shippingData['last_name']); ?>
                                </p>
                                <?php if (!empty($shippingData['company'])): ?>
                                <p class="mb-1">
                                    <strong><?php echo __('company', 'Company'); ?>:</strong> 
                                    <?php echo htmlspecialchars($shippingData['company']); ?>
                                </p>
                                <?php endif; ?>
                                <p class="mb-1">
                                    <strong><?php echo __('address', 'Address'); ?>:</strong> 
                                    <?php echo htmlspecialchars($shippingData['address_line1']); ?>
                                </p>
                                <?php if (!empty($shippingData['address_line2'])): ?>
                                <p class="mb-1">
                                    <strong><?php echo __('address2', 'Address 2'); ?>:</strong> 
                                    <?php echo htmlspecialchars($shippingData['address_line2']); ?>
                                </p>
                                <?php endif; ?>
                                <p class="mb-1">
                                    <strong><?php echo __('city_state_postal', 'City, State, Postal'); ?>:</strong> 
                                    <?php echo htmlspecialchars($shippingData['city'] . ', ' . $shippingData['state'] . ' ' . $shippingData['postal_code']); ?>
                                </p>
                                <p class="mb-1">
                                    <strong><?php echo __('country', 'Country'); ?>:</strong> 
                                    <?php echo htmlspecialchars($shippingData['country']); ?>
                                </p>
                                <?php if (!empty($shippingData['phone'])): ?>
                                <p class="mb-1">
                                    <strong>Phone:</strong> 
                                    <?php echo htmlspecialchars($shippingData['phone']); ?>
                                </p>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="text-muted"><?php echo __('no_shipping_address', 'No shipping address available'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-box me-2"></i><?php echo __('order_items', 'Order Items'); ?>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><?php echo __('product', 'Product'); ?></th>
                                    <th><?php echo __('sku', 'SKU'); ?></th>
                                    <th><?php echo __('price', 'Price'); ?></th>
                                    <th><?php echo __('quantity', 'Quantity'); ?></th>
                                    <th><?php echo __('total', 'Total'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orderItems as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php 
                                                $productImage = $item['product_image'] ?? '';
                                                $hasImage = !empty($productImage) && file_exists('../../assets/uploads/products/' . $productImage);
                                                ?>
                                                <?php if ($hasImage): ?>
                                                    <img src="<?php echo UPLOADS_URL; ?>products/<?php echo htmlspecialchars($productImage); ?>" 
                                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                                         class="img-thumbnail me-2" style="width: 40px; height: 40px; object-fit: cover;"
                                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                <?php endif; ?>
                                                <div class="img-thumbnail me-2 d-flex align-items-center justify-content-center bg-light text-muted" 
                                                     style="width: 40px; height: 40px; font-size: 8px; <?php echo $hasImage ? 'display: none;' : ''; ?>">
                                                    <i class="fas fa-image"></i>
                                                </div>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                                    <?php if (!empty($item['product_options'])): ?>
                                                        <br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($item['product_options']); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['product_sku'] ?? '-'); ?></td>
                                        <td><?php echo $currency->format($item['product_price']); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td><strong><?php echo $currency->format($item['total_price']); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-calculator me-2"></i><?php echo __('order_summary', 'Order Summary'); ?>
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span><?php echo __('subtotal', 'Subtotal'); ?>:</span>
                                <span><?php echo $currency->format($orderDetails['subtotal']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span><?php echo __('tax', 'Tax'); ?>:</span>
                                <span><?php echo $currency->format($orderDetails['tax_amount']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span><?php echo __('shipping', 'Shipping'); ?>:</span>
                                <span><?php echo $currency->format($orderDetails['shipping_cost']); ?></span>
                            </div>
                            <?php if ($orderDetails['discount_amount'] > 0): ?>
                                <div class="d-flex justify-content-between mb-2 text-success">
                                    <span><?php echo __('discount', 'Discount'); ?>:</span>
                                    <span>-<?php echo $currency->format($orderDetails['discount_amount']); ?></span>
                                </div>
                            <?php endif; ?>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <strong><?php echo __('total', 'Total'); ?>:</strong>
                                <strong><?php echo $currency->format($orderDetails['total_amount']); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-cog me-2"></i><?php echo __('order_actions', 'Order Actions'); ?>
                            </h6>
                        </div>
                        <div class="card-body">
                            <!-- Update Status -->
                            <form method="POST" class="mb-3">
                                <input type="hidden" name="update_status" value="1">
                                <div class="mb-3">
                                    <label class="form-label"><?php echo __('update_status', 'Update Status'); ?></label>
                                    <select name="status" class="form-select">
                                        <option value="pending" <?php echo $orderDetails['order_status'] === 'pending' ? 'selected' : ''; ?>><?php echo __('pending', 'Pending'); ?></option>
                                        <option value="confirmed" <?php echo $orderDetails['order_status'] === 'confirmed' ? 'selected' : ''; ?>><?php echo __('confirmed', 'Confirmed'); ?></option>
                                        <option value="processing" <?php echo $orderDetails['order_status'] === 'processing' ? 'selected' : ''; ?>><?php echo __('processing', 'Processing'); ?></option>
                                        <option value="shipped" <?php echo $orderDetails['order_status'] === 'shipped' ? 'selected' : ''; ?>><?php echo __('shipped', 'Shipped'); ?></option>
                                        <option value="delivered" <?php echo $orderDetails['order_status'] === 'delivered' ? 'selected' : ''; ?>><?php echo __('delivered', 'Delivered'); ?></option>
                                        <option value="cancelled" <?php echo $orderDetails['order_status'] === 'cancelled' ? 'selected' : ''; ?>><?php echo __('cancelled', 'Cancelled'); ?></option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label"><?php echo __('notes', 'Notes'); ?></label>
                                    <textarea name="notes" class="form-control" rows="3" 
                                              placeholder="<?php echo __('add_status_notes', 'Add notes about this status change...'); ?>"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> <?php echo __('update_status', 'Update Status'); ?>
                                </button>
                            </form>

                            <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const statusSelect = document.querySelector('select[name="status"]');
                                const shippingMethodDiv = document.createElement('div');
                                shippingMethodDiv.id = 'shipping-method-container';
                                shippingMethodDiv.className = 'mb-3';
                                shippingMethodDiv.style.display = 'none';
                                shippingMethodDiv.innerHTML = `
                                    <label class="form-label"><?php echo __('shipping_method', 'Shipping Method'); ?></label>
                                    <select name="shipping_method_id" class="form-select" required>
                                        <option value=""><?php echo __('select_shipping_method', 'Select shipping method'); ?></option>
                                        <?php
                                        $shippingMethods = $db->fetchAll("SELECT id, name FROM shipping_methods WHERE is_active = 1 ORDER BY sort_order, name");
                                        foreach ($shippingMethods as $method):
                                        ?>
                                            <option value="<?php echo $method['id']; ?>"><?php echo htmlspecialchars($method['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                `;
                                
                                // Insert after the status select
                                statusSelect.parentNode.insertBefore(shippingMethodDiv, statusSelect.parentNode.children[1]);
                                
                                statusSelect.addEventListener('change', function() {
                                    if (this.value === 'shipped') {
                                        shippingMethodDiv.style.display = 'block';
                                        shippingMethodDiv.querySelector('select').setAttribute('required', 'required');
                                    } else {
                                        shippingMethodDiv.style.display = 'none';
                                        shippingMethodDiv.querySelector('select').removeAttribute('required');
                                    }
                                });
                            });
                            </script>

                            <!-- Update Payment Status -->
                            <form method="POST" class="mb-3">
                                <input type="hidden" name="update_payment" value="1">
                                <div class="mb-3">
                                    <label class="form-label"><?php echo __('payment_status', 'Payment Status'); ?></label>
                                    <select name="payment_status" class="form-select">
                                        <option value="pending" <?php echo $orderDetails['payment_status'] === 'pending' ? 'selected' : ''; ?>><?php echo __('pending', 'Pending'); ?></option>
                                        <option value="paid" <?php echo $orderDetails['payment_status'] === 'paid' ? 'selected' : ''; ?>><?php echo __('paid', 'Paid'); ?></option>
                                        <option value="failed" <?php echo $orderDetails['payment_status'] === 'failed' ? 'selected' : ''; ?>><?php echo __('failed', 'Failed'); ?></option>
                                        <option value="refunded" <?php echo $orderDetails['payment_status'] === 'refunded' ? 'selected' : ''; ?>><?php echo __('refunded', 'Refunded'); ?></option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-credit-card me-1"></i> <?php echo __('update_payment', 'Update Payment'); ?>
                                </button>
                            </form>

                            <!-- Sync Payment with Gateway -->
                            <?php if ($orderDetails['payment_method'] === 'networks' && $orderDetails['payment_status'] === 'pending'): ?>
                                <hr>
                                <form method="POST">
                                    <input type="hidden" name="sync_payment" value="1">
                                    <p class="small text-muted mb-2">
                                        <i class="fas fa-info-circle me-1"></i> 
                                        <?php echo __('sync_payment_info', 'If the customer paid but the status is still pending, click below to check the real-time status from the gateway.'); ?>
                                    </p>
                                    <button type="submit" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-sync-alt me-1"></i> <?php echo __('sync_with_gateway', 'Sync with Gateway'); ?>
                                    </button>
                                </form>
                            <?php endif; ?>

                            <!-- Add Tracking Number -->
                            <?php if ($orderDetails['order_status'] === 'shipped' || $orderDetails['order_status'] === 'delivered' || $orderDetails['order_status'] === 'processing'): ?>
                                <hr>
                                <form method="POST">
                                    <input type="hidden" name="add_tracking" value="1">
                                    <div class="mb-3">
                                        <label class="form-label"><?php echo __('tracking_number', 'Tracking Number'); ?></label>
                                        <input type="text" name="tracking_number" class="form-control" 
                                               value="<?php echo htmlspecialchars($orderDetails['tracking_number'] ?? ''); ?>"
                                               placeholder="<?php echo __('enter_tracking_number', 'Enter tracking number...'); ?>">
                                    </div>
                                    <button type="submit" class="btn btn-info w-100 mb-2">
                                        <i class="fas fa-truck me-1"></i> <?php echo __('add_tracking', 'Save Tracking'); ?>
                                    </button>
                                </form>
                                
                                <?php if (empty($orderDetails['tracking_number'])): ?>
                                <form method="POST" onsubmit="return confirm('Create a new shipment via Zajil? This will register the parcel with Zajil.');">
                                    <input type="hidden" name="create_zajil_shipment" value="1">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-shipping-fast me-1"></i> Create Zajil Shipment (Auto)
                                    </button>
                                </form>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if ($orderDetails['tracking_number']): ?>
                                <div class="alert alert-info">
                                    <strong><?php echo __('current_tracking_number', 'Current Tracking Number'); ?>:</strong> 
                                    <?php echo htmlspecialchars($orderDetails['tracking_number']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status History -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-history me-2"></i><?php echo __('status_history', 'Status History'); ?>
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($statusHistory)): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th><?php echo __('status', 'Status'); ?></th>
                                        <th><?php echo __('date', 'Date'); ?></th>
                                        <th><?php echo __('notes', 'Notes'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($statusHistory as $history): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-<?php echo getStatusColor($history['status']); ?>">
                                                    <?php echo ucfirst($history['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y H:i', strtotime($history['created_at'] . ' UTC')); ?></td>
                                            <td><?php echo htmlspecialchars($history['comment'] ?? ''); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted"><?php echo __('no_status_history', 'No status history available'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<?php
// Helper functions
function getStatusColor($status) {
    $colors = [
        'pending' => 'warning',
        'confirmed' => 'info',
        'processing' => 'primary',
        'shipped' => 'success',
        'delivered' => 'success',
        'cancelled' => 'danger'
    ];
    return $colors[$status] ?? 'secondary';
}

function getPaymentStatusColor($status) {
    $colors = [
        'pending' => 'warning',
        'paid' => 'success',
        'failed' => 'danger',
        'refunded' => 'info'
    ];
    return $colors[$status] ?? 'secondary';
}
?>
