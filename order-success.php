<?php
require_once 'config.php';

// Check if order was completed
if (!isset($_SESSION['order_success'])) {
    header('Location: cart.php');
    exit;
}

$orderData = $_SESSION['order_success'];
$orderId = $orderData['order_id'];
$orderNumber = $orderData['order_number'];
$paymentMethod = $orderData['payment_method'];

// Clear session data
unset($_SESSION['order_success']);

// Clear cart after successful order
require_once 'config.php';
$cart = new Cart($db);
$cart->clear();

// Get order details
$orderClass = new Order($db);
$order = $orderClass->getById($orderId);

$pageTitle = 'Order Successful';
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="text-center mb-5">
                <div class="success-icon mb-4">
                    <i class="fas fa-check-circle fa-5x text-success"></i>
                </div>
                <h1 class="h2 mb-3"><?php echo __('Order Successful!'); ?></h1>
                <p class="lead text-muted"><?php echo __('Thank you for your order. We\'ll send you an email with order details shortly.'); ?></p>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title"><?php echo __('Order Details'); ?></h5>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong><?php echo __('Order Number'); ?>:</strong>
                            <span class="order-number"><?php echo htmlspecialchars($orderNumber); ?></span>
                        </div>
                        <div class="col-md-6">
                            <strong><?php echo __('Payment Method'); ?>:</strong>
                            <span><?php echo ucfirst(str_replace('_', ' ', $paymentMethod)); ?></span>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong><?php echo __('Order Status'); ?>:</strong>
                            <span class="badge bg-<?php echo ($order['order_status'] ?? '') === 'confirmed' ? 'success' : 'warning'; ?>"><?php echo __(ucfirst($order['order_status'] ?? 'pending')); ?></span>
                        </div>
                        <div class="col-md-6">
                            <strong><?php echo __('Payment Status'); ?>:</strong>
                            <span class="badge bg-<?php echo ($order['payment_status'] ?? '') === 'paid' ? 'success' : 'info'; ?>"><?php echo __(ucfirst($order['payment_status'] ?? 'pending')); ?></span>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong><?php echo __('Order Date'); ?>:</strong>
                            <span><?php echo date('F j, Y, g:i a', strtotime($order['created_at'] . ' UTC')); ?></span>
                        </div>
                        <div class="col-md-6">
                            <strong><?php echo __('Total Amount'); ?>:</strong>
                            <span class="text-primary fw-bold"><?php echo $currency->format($order['total_amount']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title"><?php echo __('Order Items'); ?></h5>
                    
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><?php echo __('Product'); ?></th>
                                    <th><?php echo __('Price'); ?></th>
                                    <th><?php echo __('Quantity'); ?></th>
                                    <th><?php echo __('Total'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order['items'] as $item): 
                                    $prodName = $item['product_name'];
                                    if (preg_match('/^Product\s+\d+$/i', trim($prodName ?? ''))) {
                                        $row = $db->fetchOne("SELECT name FROM product_translations WHERE product_id = :pid AND name IS NOT NULL AND TRIM(name) != '' ORDER BY (language_id = 1) DESC LIMIT 1", ['pid' => $item['product_id'] ?? 0]);
                                        if ($row && !empty(trim($row['name'] ?? ''))) $prodName = trim($row['name']);
                                    }
                                ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php 
                                                $productImage = $item['product_image'] ?? '';
                                                $hasImage = !empty($productImage) && file_exists(BASE_PATH . 'assets/uploads/products/' . $productImage);
                                                ?>
                                                <?php if ($hasImage): ?>
                                                    <img src="<?php echo rtrim(UPLOADS_URL, '/') . '/products/' . htmlspecialchars($productImage); ?>" 
                                                         alt="<?php echo htmlspecialchars($prodName); ?>" 
                                                         class="img-thumbnail me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                                <?php endif; ?>
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($prodName); ?></h6>
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
                                <span><?php echo $currency->format($order['shipping_cost']); ?></span>
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
            
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <h5 class="card-title"><?php echo __('Shipping Address'); ?></h5>
                            <?php
                            // Display shipping address from JSON field
                            $shippingData = null;
                            if (!empty($order['shipping_address'])) {
                                $shippingData = json_decode($order['shipping_address'], true);
                            }
                            
                            // Fallback to individual fields
                            if (!$shippingData && !empty($order['shipping_first_name'])) {
                                $shippingData = [
                                    'first_name' => $order['shipping_first_name'],
                                    'last_name' => $order['shipping_last_name'],
                                    'company' => $order['shipping_company'] ?? '',
                                    'address_line1' => $order['shipping_address_line1'],
                                    'address_line2' => $order['shipping_address_line2'] ?? '',
                                    'city' => $order['shipping_city'],
                                    'state' => $order['shipping_state'],
                                    'postal_code' => $order['shipping_postal_code'],
                                    'country' => $order['shipping_country'],
                                    'phone' => $order['shipping_phone']
                                ];
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
                                    <p class="mb-0"><?php echo __('Phone'); ?>: <?php echo htmlspecialchars($shippingData['phone']); ?></p>
                                </div>
                            <?php else: ?>
                                <p class="text-muted small"><?php echo __('No shipping address provided.'); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6">
                            <h5 class="card-title"><?php echo __('Billing Information'); ?></h5>
                            <?php 
                            // Get billing details, fallback to JSON or shipping if empty
                            $billingData = null;
                            if (!empty($order['billing_address'])) {
                                $billingData = json_decode($order['billing_address'], true);
                            }
                            
                            // Fallback to individual billing fields
                            if ((empty($billingData) || empty($billingData['first_name'])) && !empty($order['billing_first_name'])) {
                                $billingData = [
                                    'first_name' => $order['billing_first_name'],
                                    'last_name' => $order['billing_last_name'],
                                    'company' => $order['billing_company'] ?? '',
                                    'email' => $order['billing_email'] ?? $order['guest_email'] ?? $order['user_email'] ?? '',
                                    'phone' => $order['billing_phone'] ?? $order['shipping_phone'] ?? '',
                                    'address_line1' => $order['billing_address_line1'],
                                    'address_line2' => $order['billing_address_line2'] ?? '',
                                    'city' => $order['billing_city'],
                                    'state' => $order['billing_state'],
                                    'postal_code' => $order['billing_postal_code'],
                                    'country' => $order['billing_country']
                                ];
                            }
                            
                            // Final fallback to shipping info if still empty
                            if (empty($billingData) || empty(trim($billingData['first_name'] ?? ''))) {
                                $billingData = $shippingData;
                                if ($billingData) {
                                    $billingData['email'] = $order['billing_email'] ?? $order['guest_email'] ?? $order['user_email'] ?? '';
                                }
                            }
                            
                            if ($billingData && (!empty($billingData['first_name']) || !empty($billingData['last_name']))):
                            ?>
                                <div class="address">
                                    <p class="mb-1">
                                        <strong><?php echo htmlspecialchars(($billingData['first_name'] ?? '') . ' ' . ($billingData['last_name'] ?? '')); ?></strong>
                                    </p>
                                    <?php if (!empty($billingData['email'])): ?>
                                        <p class="mb-1"><strong><?php echo __('Email'); ?>:</strong> <?php echo htmlspecialchars($billingData['email']); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($billingData['company'])): ?>
                                        <p class="mb-1"><?php echo htmlspecialchars($billingData['company']); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($billingData['address_line1'])): ?>
                                        <p class="mb-1"><?php echo htmlspecialchars($billingData['address_line1']); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($billingData['address_line2'])): ?>
                                        <p class="mb-1"><?php echo htmlspecialchars($billingData['address_line2']); ?></p>
                                    <?php endif; ?>
                                    <p class="mb-1">
                                        <?php echo htmlspecialchars(($billingData['city'] ?? '') . ', ' . ($billingData['state'] ?? '') . ' ' . ($billingData['postal_code'] ?? '')); ?>
                                    </p>
                                    <p class="mb-1"><?php echo htmlspecialchars($billingData['country'] ?? ''); ?></p>
                                    <?php if (!empty($billingData['phone'])): ?>
                                        <p class="mb-0"><?php echo __('Phone'); ?>: <?php echo htmlspecialchars($billingData['phone']); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <p class="mb-1">
                                    <strong><?php echo __('Customer'); ?>:</strong> 
                                    <?php echo htmlspecialchars($order['guest_email'] ?? $order['user_email'] ?? __('Guest')); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($paymentMethod === 'bank_transfer'): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo __('Bank Transfer Instructions'); ?></h5>
                        <div class="alert alert-info">
                            <p class="mb-2"><?php echo __('Please transfer the total amount to our bank account:'); ?></p>
                            <p class="mb-1"><strong><?php echo __('Bank Name'); ?>:</strong> Example Bank</p>
                            <p class="mb-1"><strong><?php echo __('Account Name'); ?>:</strong> Your Company Name</p>
                            <p class="mb-1"><strong><?php echo __('Account Number'); ?>:</strong> 1234567890</p>
                            <p class="mb-1"><strong><?php echo __('Routing Number'); ?>:</strong> 987654321</p>
                            <p class="mb-0"><strong><?php echo __('Reference'); ?>:</strong> <?php echo $orderNumber; ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title"><?php echo __('What\'s Next?'); ?></h5>
                    
                    <div class="row">
                        <div class="col-md-4 text-center mb-3">
                            <div class="next-step-icon mb-2">
                                <i class="fas fa-envelope fa-2x text-primary"></i>
                            </div>
                            <h6><?php echo __('Order Confirmation'); ?></h6>
                            <p class="small text-muted"><?php echo __('You\'ll receive an email confirmation shortly'); ?></p>
                        </div>
                        <div class="col-md-4 text-center mb-3">
                            <div class="next-step-icon mb-2">
                                <i class="fas fa-box fa-2x text-primary"></i>
                            </div>
                            <h6><?php echo __('Order Processing'); ?></h6>
                            <p class="small text-muted"><?php echo __('We\'ll process your order within 1-2 business days'); ?></p>
                        </div>
                        <div class="col-md-4 text-center mb-3">
                            <div class="next-step-icon mb-2">
                                <i class="fas fa-truck fa-2x text-primary"></i>
                            </div>
                            <h6><?php echo __('Shipping'); ?></h6>
                            <p class="small text-muted"><?php echo __('You\'ll receive tracking information once shipped'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center">
                <a href="order-tracking.php?number=<?php echo $orderNumber; ?>" class="btn btn-primary me-2">
                    <i class="fas fa-search"></i> <?php echo __('Track Order'); ?>
                </a>
                <a href="shop.php" class="btn btn-outline-primary">
                    <i class="fas fa-shopping-bag"></i> <?php echo __('Continue Shopping'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.success-icon {
    animation: successPulse 2s ease-in-out infinite;
}

@keyframes successPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.order-number {
    font-family: monospace;
    font-size: 1.1em;
    color: #007bff;
}

.next-step-icon {
    transition: transform 0.3s ease;
}

.next-step-icon:hover {
    transform: translateY(-5px);
}

.address {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
}
</style>

<?php include 'includes/footer.php'; ?>
