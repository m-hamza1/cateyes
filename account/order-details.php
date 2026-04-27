<?php
require_once '../config.php';

// Initialize language system
$lang = new Language($db);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_message'] = __('login_required', 'Please login to view your order details');
    $redirect = BASE_URL . 'account/order-details.php?id=' . ($_GET['id'] ?? '');
    header('Location: ' . BASE_URL . 'account/login.php?redirect=' . urlencode($redirect));
    exit;
}

// Get order ID from URL
$orderId = $_GET['id'] ?? '';

if (empty($orderId) || !is_numeric($orderId)) {
    $_SESSION['error'] = __('Invalid order ID');
    header('Location: orders.php');
    exit;
}

// Get order details
$orderClass = new Order($db);
$order = $orderClass->getById($orderId);

// Verify order belongs to current user
if (!$order || $order['user_id'] != $_SESSION['user_id']) {
    $_SESSION['error'] = __('Order not found or access denied');
    header('Location: orders.php');
    exit;
}

// Get translated product names for order items
if (!empty($order['items'])) {
    foreach ($order['items'] as &$item) {
        // Try to get translated product name
        $translation = $db->fetchOne(
            "SELECT pt.name as translated_name 
             FROM product_translations pt 
             WHERE pt.product_id = :product_id AND pt.language_id = :lang_id",
            [
                'product_id' => $item['product_id'],
                'lang_id' => $lang->getCurrentId()
            ]
        );
        
        // Use translated name if available, otherwise keep original
        if ($translation && !empty($translation['translated_name'])) {
            $item['product_name'] = $translation['translated_name'];
        }
    }
    unset($item); // Break the reference to avoid corruption in later loops
}

$pageTitle = __('Order Details');
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-12">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../index.php"><?php echo __('Home'); ?></a></li>
                    <li class="breadcrumb-item"><a href="index.php"><?php echo __('My Account'); ?></a></li>
                    <li class="breadcrumb-item"><a href="orders.php"><?php echo __('My Orders'); ?></a></li>
                    <li class="breadcrumb-item active"><?php echo __('Order Details'); ?></li>
                </ol>
            </nav>

            <!-- Order Header -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="card-title"><?php echo __('Order'); ?> #<?php echo htmlspecialchars($order['order_number']); ?></h5>
                            <p class="text-muted mb-0"><?php echo __('Placed on'); ?> <?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?></p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <span class="badge bg-<?php echo getStatusColor($order['order_status']); ?> fs-6">
                                <?php echo __(ucfirst($order['order_status'])); ?>
                            </span>
                            <br>
                            <small class="text-muted"><?php echo __('Payment'); ?>: <?php echo __(ucfirst($order['payment_status'])); ?></small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <!-- Order Items -->
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
                                        <?php foreach ($order['items'] as $item): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="<?php echo UPLOADS_URL; ?>products/<?php echo htmlspecialchars($item['product_image'] ?? 'placeholder.jpg'); ?>" 
                                                             alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                                             class="img-thumbnail me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                                        <div>
                                                            <h6 class="mb-1"><?php echo htmlspecialchars($item['product_name']); ?></h6>
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
                        </div>
                    </div>

                    <!-- Order Status Timeline -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo __('Order Status'); ?></h5>
                            
                            <div class="order-timeline">
                                <?php
                                $statusHistory = $orderClass->getStatusHistory($order['id']);
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
                                                        echo $statusDate ? date('M j, Y g:i a', strtotime($statusDate)) : '';
                                                        ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tracking Information -->
                    <?php if ($order['tracking_number']): ?>
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo __('Tracking Information'); ?></h5>
                                <div class="alert alert-info">
                                    <p class="mb-2">
                                        <strong><?php echo __('Tracking Number'); ?>:</strong> 
                                        <span class="tracking-number"><?php echo htmlspecialchars($order['tracking_number']); ?></span>
                                    </p>
                                    <button class="btn btn-sm btn-outline-primary" onclick="copyTrackingNumber()">
                                        <i class="fas fa-copy"></i> <?php echo __('Copy Tracking Number'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-lg-4">
                    <!-- Order Summary -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo __('Order Summary'); ?></h5>
                            
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

                    <!-- Shipping Address -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo __('Shipping Address'); ?></h5>
                            
                            <?php
                            // Display shipping address from JSON field
                            if (!empty($order['shipping_address'])):
                                $shippingAddress = json_decode($order['shipping_address'], true);
                                if ($shippingAddress):
                            ?>
                                <div class="address">
                                    <p class="mb-1">
                                        <strong><?php echo htmlspecialchars($shippingAddress['first_name'] . ' ' . $shippingAddress['last_name']); ?></strong>
                                    </p>
                                    <?php if (!empty($shippingAddress['company'])): ?>
                                        <p class="mb-1"><?php echo htmlspecialchars($shippingAddress['company']); ?></p>
                                    <?php endif; ?>
                                    <p class="mb-1"><?php echo htmlspecialchars($shippingAddress['address_line1']); ?></p>
                                    <?php if (!empty($shippingAddress['address_line2'])): ?>
                                        <p class="mb-1"><?php echo htmlspecialchars($shippingAddress['address_line2']); ?></p>
                                    <?php endif; ?>
                                    <p class="mb-1">
                                        <?php echo htmlspecialchars($shippingAddress['city'] . ', ' . $shippingAddress['state'] . ' ' . $shippingAddress['postal_code']); ?>
                                    </p>
                                    <p class="mb-1"><?php echo htmlspecialchars($shippingAddress['country']); ?></p>
                                    <p class="mb-1"><?php echo __('Phone'); ?>: <?php echo htmlspecialchars($shippingAddress['phone']); ?></p>
                                </div>
                            <?php 
                                endif;
                            endif;
                            ?>
                        </div>
                    </div>
                </div>
            </div>
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
    display: flex;
    align-items: center;
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

function reorderItems() {
    if (confirm('<?php echo __('Add all items from this order to your cart?'); ?>')) {
        // Implementation for reordering items
        alert('<?php echo __('Items added to cart!'); ?>');
    }
}
</script>

<?php include '../includes/footer.php'; ?>
