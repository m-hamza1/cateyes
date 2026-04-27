<?php
require_once 'config.php';

// Get order number from URL or POST
$orderNumber = $_GET['number'] ?? $_POST['order_number'] ?? '';

$order = null;
$error = '';

// Handle Payment Gateway Callbacks
$gateway = $_GET['gateway'] ?? '';
$status = $_GET['status'] ?? '';
$orderId = (int)($_GET['order_id'] ?? 0);

if ($gateway && $orderId) {
    $payment = new Payment($db);
    $success = false;

    if ($gateway === 'networks') {
        $verifyResult = $payment->verifyNetworksPayment($orderId);
        if ($verifyResult['success']) {
            $success = true;
        } else {
            // DELETE ORDER IF PAYMENT FAILED
            $db->query("DELETE FROM order_items WHERE order_id = :id", ['id' => $orderId]);
            $db->query("DELETE FROM payments WHERE order_id = :id", ['id' => $orderId]);
            $db->query("DELETE FROM orders WHERE id = :id", ['id' => $orderId]);
            $error = __('Payment failed or was cancelled. Your order has been removed. Please try again.');
        }
    } elseif ($gateway === 'tabby' && $status === 'success') {
        $paymentRecord = $db->fetchOne("SELECT id FROM payments WHERE order_id = :id AND payment_method = 'tabby'", ['id' => $orderId]);
        if ($paymentRecord) {
            $payment->updateStatus($paymentRecord['id'], 'completed');
            $db->update('orders', ['order_status' => 'confirmed', 'payment_status' => 'paid'], 'id = :id', ['id' => $orderId]);
            $success = true;
        }
    } elseif ($gateway === 'tabby' && $status !== 'success') {
        // DELETE ORDER IF TABBY FAILED
        $db->query("DELETE FROM order_items WHERE order_id = :id", ['id' => $orderId]);
        $db->query("DELETE FROM payments WHERE order_id = :id", ['id' => $orderId]);
        $db->query("DELETE FROM orders WHERE id = :id", ['id' => $orderId]);
        $error = __('Tabby payment was not successful. Your order has been removed.');
    }

    if ($success) {
        $orderClass = new Order($db);
        $orderData = $orderClass->getById($orderId);
        if ($orderData) {
            $_SESSION['order_success'] = [
                'order_id' => $orderId,
                'order_number' => $orderData['order_number'],
                'payment_method' => $gateway
            ];
            
            try {
                require_once INCLUDES_PATH . 'Email.php';
                $mailer = new Email($db);
                $mailer->orderConfirmation($orderId);
                $mailer->notifyAdminNewOrder($orderId);
            } catch (Exception $e) {}

            header('Location: order-success.php');
            exit;
        }
    }
}

if ($orderNumber) {
    $orderClass = new Order($db);
    $order = $orderClass->getByOrderNumber($orderNumber);
    
    if (!$order) {
        $error = __('Order not found. Please check your order number and try again.');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['order_number'])) {
    $orderNumber = $_POST['order_number'];
    header('Location: order-tracking.php?number=' . urlencode($orderNumber));
    exit;
}

$pageTitle = 'Order Tracking';
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="h3 mb-4 text-center"><?php echo __('Order Tracking'); ?></h1>
            
            <!-- Search Form -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="post">
                        <div class="input-group">
                            <input type="text" name="order_number" class="form-control" 
                                   placeholder="<?php echo __('Enter your order number (e.g., ORD-20250111-1234)'); ?>"
                                   value="<?php echo htmlspecialchars($orderNumber); ?>" required>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> <?php echo __('Track Order'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($order): ?>
                <!-- Order Details -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="card-title mb-1"><?php echo __('Order'); ?> #<?php echo htmlspecialchars($order['order_number']); ?></h5>
                                <?php $tz = $settings->get('timezone', 'Asia/Dubai'); $dt = new DateTime($order['created_at'], new DateTimeZone('UTC')); $dt->setTimezone(new DateTimeZone($tz ?: 'Asia/Dubai')); ?>
                                <p class="text-muted mb-0"><?php echo __('Placed on'); ?> <?php echo $dt->format('F j, Y, g:i a'); ?></p>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-<?php echo getStatusColor($order['order_status']); ?> fs-6">
                                    <?php echo __(ucfirst($order['order_status'])); ?>
                                </span>
                                <br>
                                <small class="text-muted"><?php echo __('Payment'); ?>: <?php echo __(ucfirst($order['payment_status'])); ?></small>
                            </div>
                        </div>
                        
                        <!-- Order Status Timeline -->
                        <div class="order-timeline mb-4">
                            <?php
                            $statusHistory = (new Order($db))->getStatusHistory($order['id']);
                            $allStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered'];
                            $currentStatusIndex = array_search($order['order_status'], $allStatuses);
                            ?>
                            
                            <div class="timeline">
                                <?php foreach ($allStatuses as $index => $status): ?>
                                    <div class="timeline-item <?php echo $index <= $currentStatusIndex ? 'completed' : 'pending'; ?>">
                                        <div class="timeline-marker">
                                            <i class="fas <?php echo getStatusIcon($status); ?>"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <h6><?php echo getStatusTitle($status); ?></h6>
                                            <p class="text-muted small"><?php echo getStatusDescription($status); ?></p>
                                            <?php if ($index <= $currentStatusIndex): ?>
                                                <small class="text-success">
                                                    <?php 
                                                    $statusDate = null;
                                                    foreach ($statusHistory as $history) {
                                                        if ($history['status'] === $status) {
                                                            $statusDate = $history['created_at'];
                                                            break;
                                                        }
                                                    }
                                                    if ($statusDate) { $d = new DateTime($statusDate, new DateTimeZone('UTC')); $d->setTimezone(new DateTimeZone($settings->get('timezone', 'Asia/Dubai') ?: 'Asia/Dubai')); echo $d->format('M j, Y g:i a'); }
                                                    ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Tracking Information -->
                        <?php if ($order['tracking_number']): ?>
                            <div class="alert alert-info">
                                <h6><i class="fas fa-truck"></i> <?php echo __('Tracking Information'); ?></h6>
                                <p class="mb-2">
                                    <strong><?php echo __('Tracking Number'); ?>:</strong> 
                                    <span class="tracking-number"><?php echo htmlspecialchars($order['tracking_number']); ?></span>
                                </p>
                                <button class="btn btn-sm btn-outline-primary" onclick="copyTrackingNumber()">
                                    <i class="fas fa-copy"></i> <?php echo __('Copy Tracking Number'); ?>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Order Items -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo __('Order Items'); ?></h5>
                        
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th><?php echo __('Product'); ?></th>
                                        <th><?php echo __('Price'); ?></th>
                                        <th><?php echo __('Quantity'); ?></th>
                                        <th><?php echo __('Total'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order['items'] as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php 
                                                    $productImage = $item['product_image'] ?? '';
                                                    $hasImage = !empty($productImage) && file_exists('assets/uploads/products/' . $productImage);
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
                                                        <h6 class="mb-0 small"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                                                        <small class="text-muted"><?php echo __('SKU'); ?>: <?php echo htmlspecialchars($item['product_sku'] ?? ''); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo $currency->format($item['product_price']); ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td><?php echo $currency->format($item['total_price']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-6 offset-md-6">
                                <div class="d-flex justify-content-between mb-2">
                                    <span><?php echo __('Subtotal'); ?>:</span>
                                    <span><?php echo $currency->format($order['subtotal']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span><?php echo __('Tax'); ?>:</span>
                                    <span><?php echo $currency->format($order['tax_amount']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span><?php echo __('Shipping'); ?>:</span>
                                    <span><?php echo $currency->format($order['shipping_cost'] ?? ($order['shipping_amount'] ?? 0)); ?></span>
                                </div>
                                <?php if ($order['discount_amount'] > 0): ?>
                                    <div class="d-flex justify-content-between mb-2 text-success">
                                        <span><?php echo __('Discount'); ?>:</span>
                                        <span>-<?php echo $currency->format($order['discount_amount']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <strong><?php echo __('Total'); ?>:</strong>
                                    <strong><?php echo $currency->format($order['total_amount']); ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Customer & Billing Information -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo __('Customer & Billing Information'); ?></h5>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-muted mb-2"><?php echo __('Customer Information'); ?></h6>
                                <?php if ($order['user_id']): ?>
                                    <p class="mb-1">
                                        <strong><?php echo __('Account Name'); ?>:</strong> 
                                        <?php echo htmlspecialchars($order['user_first_name'] . ' ' . $order['user_last_name']); ?>
                                    </p>
                                    <?php if (!empty($order['user_email'])): ?>
                                    <p class="mb-1">
                                        <strong><?php echo __('Account Email'); ?>:</strong> 
                                        <?php echo htmlspecialchars($order['user_email']); ?>
                                    </p>
                                    <?php endif; ?>
                                    <p class="mb-1">
                                        <strong><?php echo __('Account Type'); ?>:</strong> 
                                        <span class="badge bg-success"><?php echo __('Registered User'); ?></span>
                                    </p>
                                <?php else: ?>
                                    <p class="mb-1">
                                        <strong><?php echo __('Account Type'); ?>:</strong> 
                                        <span class="badge bg-secondary"><?php echo __('Guest Checkout'); ?></span>
                                    </p>
                                <?php endif; ?>
                            </div>
                             
                            <div class="col-md-6">
                                <h6 class="text-muted mb-2"><?php echo __('Billing Information'); ?></h6>
                                <?php 
                                // Get billing details, fallback to JSON or shipping if empty
                                $bFirst = trim($order['billing_first_name'] ?? '');
                                
                                // Fallback to billing_address JSON if individual fields are missing
                                if (empty($bFirst) && !empty($order['billing_address'])) {
                                    $bJson = json_decode($order['billing_address'], true);
                                    if ($bJson && !empty($bJson['first_name'])) {
                                        $order['billing_first_name'] = $bJson['first_name'];
                                        $order['billing_last_name'] = $bJson['last_name'] ?? '';
                                        $order['billing_email'] = $bJson['email'] ?? $order['guest_email'] ?? $order['user_email'] ?? '';
                                        $order['billing_phone'] = $bJson['phone'] ?? $order['shipping_phone'] ?? '';
                                        $order['billing_company'] = $bJson['company'] ?? '';
                                        $order['billing_address_line1'] = $bJson['address_line1'] ?? '';
                                        $order['billing_address_line2'] = $bJson['address_line2'] ?? '';
                                        $order['billing_city'] = $bJson['city'] ?? '';
                                        $order['billing_state'] = $bJson['state'] ?? '';
                                        $order['billing_postal_code'] = $bJson['postal_code'] ?? '';
                                        $order['billing_country'] = $bJson['country'] ?? '';
                                    }
                                }
                                
                                // Final fallback to shipping info if still empty
                                if (empty(trim($order['billing_first_name'] ?? ''))) {
                                    $order['billing_first_name'] = $order['shipping_first_name'] ?? '';
                                    $order['billing_last_name'] = $order['shipping_last_name'] ?? '';
                                    $order['billing_email'] = $order['billing_email'] ?? $order['guest_email'] ?? $order['user_email'] ?? '';
                                    $order['billing_phone'] = $order['billing_phone'] ?? $order['shipping_phone'] ?? '';
                                    $order['billing_company'] = $order['billing_company'] ?? $order['shipping_company'] ?? '';
                                    $order['billing_address_line1'] = $order['billing_address_line1'] ?? $order['shipping_address_line1'] ?? '';
                                    $order['billing_address_line2'] = $order['billing_address_line2'] ?? $order['shipping_address_line2'] ?? '';
                                    $order['billing_city'] = $order['billing_city'] ?? $order['shipping_city'] ?? '';
                                    $order['billing_state'] = $order['billing_state'] ?? $order['shipping_state'] ?? '';
                                    $order['billing_postal_code'] = $order['billing_postal_code'] ?? $order['shipping_postal_code'] ?? '';
                                    $order['billing_country'] = $order['billing_country'] ?? $order['shipping_country'] ?? '';
                                }
                                
                                $bFirst = $order['billing_first_name'] ?? '';
                                $bLast = $order['billing_last_name'] ?? '';
                                ?>
                                <?php if (!empty(trim($bFirst)) || !empty(trim($bLast))): ?>
                                    <div class="address">
                                        <p class="mb-1">
                                            <strong><?php echo htmlspecialchars($order['billing_first_name'] . ' ' . $order['billing_last_name']); ?></strong>
                                        </p>
                                        <?php if (!empty($order['billing_email'])): ?>
                                        <p class="mb-1">
                                            <strong><?php echo __('Email'); ?>:</strong> <?php echo htmlspecialchars($order['billing_email']); ?>
                                        </p>
                                        <?php endif; ?>
                                        <?php if (!empty($order['billing_phone'])): ?>
                                        <p class="mb-1">
                                            <strong><?php echo __('Phone'); ?>:</strong> <?php echo htmlspecialchars($order['billing_phone']); ?>
                                        </p>
                                        <?php endif; ?>
                                        <?php if (!empty($order['billing_company'])): ?>
                                        <p class="mb-1"><?php echo htmlspecialchars($order['billing_company']); ?></p>
                                        <?php endif; ?>
                                        <p class="mb-1"><?php echo htmlspecialchars($order['billing_address_line1']); ?></p>
                                        <?php if (!empty($order['billing_address_line2'])): ?>
                                        <p class="mb-1"><?php echo htmlspecialchars($order['billing_address_line2']); ?></p>
                                        <?php endif; ?>
                                        <p class="mb-1">
                                            <?php echo htmlspecialchars($order['billing_city']); ?>
                                            <?php echo !empty($order['billing_state']) ? ', ' . htmlspecialchars($order['billing_state']) : ''; ?>
                                            <?php echo !empty($order['billing_postal_code']) ? ' ' . htmlspecialchars($order['billing_postal_code']) : ''; ?>
                                        </p>
                                        <p class="mb-0"><?php echo htmlspecialchars($order['billing_country']); ?></p>
                                    </div>
                                <?php else: ?>
                                    <p class="mb-1">
                                        <strong><?php echo __('Customer'); ?>:</strong> 
                                        <?php echo htmlspecialchars($order['guest_email'] ?? __('Guest')); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Shipping Address -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo __('Shipping Address'); ?></h5>
                        
                        <?php 
                        // Try to get shipping address from individual fields first, then JSON as fallback
                        $shippingData = null;
                        
                        // First try individual fields
                        if (!empty($order['shipping_first_name'])) {
                            $shippingData = [
                                'first_name' => $order['shipping_first_name'],
                                'last_name' => $order['shipping_last_name'],
                                'company' => $order['shipping_company'],
                                'address_line1' => $order['shipping_address_line1'],
                                'address_line2' => $order['shipping_address_line2'],
                                'city' => $order['shipping_city'],
                                'state' => $order['shipping_state'],
                                'postal_code' => $order['shipping_postal_code'],
                                'country' => $order['shipping_country'],
                                'phone' => $order['shipping_phone']
                            ];
                        }
                        // Fallback to JSON if individual fields are empty
                        elseif (!empty($order['shipping_address'])) {
                            $shippingData = json_decode($order['shipping_address'], true);
                        }
                        
                        if ($shippingData):
                        ?>
                            <div class="address">
                                <p class="mb-1">
                                    <strong><?php echo htmlspecialchars($shippingData['first_name'] . ' ' . $shippingData['last_name']); ?></strong>
                                </p>
                                <?php if (!empty($shippingData['company'])): ?>
                                <p class="mb-1"><?php echo htmlspecialchars($shippingData['company']); ?></p>
                                <?php endif; ?>
                                <p class="mb-1"><?php echo htmlspecialchars($shippingData['address_line1']); ?></p>
                                <?php if (!empty($shippingData['address_line2'])): ?>
                                <p class="mb-1"><?php echo htmlspecialchars($shippingData['address_line2']); ?></p>
                                <?php endif; ?>
                                <p class="mb-1">
                                    <?php echo htmlspecialchars($shippingData['city'] . ', ' . $shippingData['state'] . ' ' . $shippingData['postal_code']); ?>
                                </p>
                                <p class="mb-1"><?php echo htmlspecialchars($shippingData['country']); ?></p>
                                <?php if (!empty($shippingData['phone'])): ?>
                                <p class="mb-1"><?php echo __('Phone'); ?>: <?php echo htmlspecialchars($shippingData['phone']); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted"><?php echo __('No shipping address available'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="text-center">
                    <a href="shop.php" class="btn btn-primary">
                        <i class="fas fa-shopping-bag"></i> <?php echo __('Continue Shopping'); ?>
                    </a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="account/orders.php" class="btn btn-outline-primary ms-2">
                            <i class="fas fa-list"></i> <?php echo __('My Orders'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Helper functions for status display
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

function getStatusIcon($status) {
    $icons = [
        'pending' => 'fa-clock',
        'confirmed' => 'fa-check',
        'processing' => 'fa-cog fa-spin',
        'shipped' => 'fa-truck',
        'delivered' => 'fa-check-circle',
        'cancelled' => 'fa-times-circle'
    ];
    return $icons[$status] ?? 'fa-circle';
}

function getStatusTitle($status) {
    $titles = [
        'pending' => __('Order Pending'),
        'confirmed' => __('Order Confirmed'),
        'processing' => __('Processing'),
        'shipped' => __('Shipped'),
        'delivered' => __('Delivered'),
        'cancelled' => __('Cancelled')
    ];
    return $titles[$status] ?? $status;
}

function getStatusDescription($status) {
    $descriptions = [
        'pending' => __('Your order has been received and is pending confirmation.'),
        'confirmed' => __('Your order has been confirmed and is being prepared.'),
        'processing' => __('Your order is being processed and prepared for shipping.'),
        'shipped' => __('Your order has been shipped and is on its way to you.'),
        'delivered' => __('Your order has been successfully delivered.'),
        'cancelled' => __('Your order has been cancelled.')
    ];
    return $descriptions[$status] ?? '';
}
?>

<style>
.timeline {
    position: relative;
    padding-left: 40px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background-color: #dee2e6;
}

.timeline-item {
    position: relative;
    margin-bottom: 30px;
}

.timeline-marker {
    position: absolute;
    left: -30px;
    top: 0;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background-color: #fff;
    border: 2px solid #dee2e6;
    justify-content: center;
    font-size: 12px;
    color: #6c757d;
}

.timeline-item.completed .timeline-marker {
    background-color: #28a745;
    border-color: #28a745;
    color: #fff;
}

.timeline-content h6 {
    margin-bottom: 5px;
    color: #495057;
}

.timeline-item.completed .timeline-content h6 {
    color: #28a745;
}

.address {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
}

.tracking-number {
    font-family: monospace;
    font-weight: bold;
}
</style>

<script>
function copyTrackingNumber() {
    const trackingNumber = document.querySelector('.tracking-number').textContent;
    navigator.clipboard.writeText(trackingNumber).then(function() {
        alert('<?php echo __('Tracking number copied to clipboard!'); ?>');
    });
}
</script>

<?php include 'includes/footer.php'; ?>
