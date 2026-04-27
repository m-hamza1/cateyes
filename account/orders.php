<?php
require_once '../config.php';
$pageTitle = __('orders', 'Orders');

// Redirect if not logged in
if (!$user) {
    $_SESSION['flash_message'] = __('login_required', 'Please login to access your orders');
    $_SESSION['flash_type'] = 'warning';
    header('Location: ' . BASE_URL . 'account/login.php?redirect=' . urlencode(BASE_URL . 'account/orders.php'));
    exit;
}

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get total orders count
$total_orders = $db->fetchOne("
    SELECT COUNT(*) as total 
    FROM orders 
    WHERE user_id = :user_id
", ['user_id' => $user['id']])['total'] ?? 0;

$total_pages = ceil($total_orders / $per_page);

// Get orders with items
$orders = $db->fetchAll("
    SELECT o.*, COUNT(oi.id) as item_count
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = :user_id
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT $offset, $per_page
", ['user_id' => $user['id']]);

// Ensure orders is an array
if (!is_array($orders)) {
    $orders = [];
}

// Get order details for view
$order_id = $_GET['view'] ?? 0;
$order_details = null;
$order_items = [];

if ($order_id > 0) {
    $order_details = $db->fetchOne("
        SELECT * FROM orders 
        WHERE id = :id AND user_id = :user_id
    ", [
        'id' => $order_id,
        'user_id' => $user['id']
    ]);
    
    if ($order_details) {
        $order_items = $db->fetchAll("
            SELECT oi.*, p.main_image as product_image, pt.name as product_name
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang
            WHERE oi.order_id = :order_id
        ", [
            'order_id' => $order_id,
            'lang' => $lang->getCurrentId()
        ]);
        
        // Ensure order_items is an array
        if (!is_array($order_items)) {
            $order_items = [];
        }
    }
}

include '../includes/header.php';
?>
<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="bg-light py-3">
    <div class="container">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>"><?php echo __('home', 'Home'); ?></a></li>
            <li class="breadcrumb-item active"><?php echo __('orders', 'Orders'); ?></li>
        </ol>
    </div>
</nav>

<!-- Main content -->
<section class="content py-5">
    <div class="container">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="card shadow-sm mb-4">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-user-circle fa-5x text-primary"></i>
                        </div>
                        <h5 class="card-title"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                        <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title mb-3"><?php echo __('quick_links', 'Quick Links'); ?></h6>
                        <div class="list-group list-group-flush">
                            <a href="<?php echo BASE_URL; ?>account/dashboard.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-tachometer-alt me-2"></i> <?php echo __('dashboard', 'Dashboard'); ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>account/profile.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-user me-2"></i> <?php echo __('profile', 'Profile'); ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>account/mybookings.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-calendar-check me-2"></i> <?php echo __('my_bookings', 'My Bookings'); ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>account/orders.php" class="list-group-item list-group-item-action active">
                                <i class="fas fa-shopping-bag me-2"></i> <?php echo __('orders', 'Orders'); ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>account/wishlist.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-heart me-2"></i> <?php echo __('wishlist', 'Wishlist'); ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>account/addresses.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-map-marker-alt me-2"></i> <?php echo __('addresses', 'Addresses'); ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>account/logout.php" class="list-group-item list-group-item-action text-danger">
                                <i class="fas fa-sign-out-alt me-2"></i> <?php echo __('logout', 'Logout'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9">
                <?php if ($order_details): ?>
                    <!-- Order Details View -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">
                                <i class="fas fa-file-invoice me-2"></i>
                                <?php echo __('order_details', 'Order Details'); ?> #<?php echo $order_details['order_number']; ?>
                            </h4>
                            <a href="<?php echo BASE_URL; ?>account/orders.php" class="btn btn-warning btn-sm">
                                <i class="fas fa-arrow-left me-1"></i>
                                <?php echo __('back_to_orders', 'Back to Orders'); ?>
                            </a>
                        </div>
                        <div class="card-body">
                            <!-- Order Info -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6><?php echo __('order_information', 'Order Information'); ?></h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong><?php echo __('order_number', 'Order Number'); ?>:</strong></td>
                                            <td>#<?php echo $order_details['order_number']; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong><?php echo __('order_date', 'Order Date'); ?>:</strong></td>
                                            <td><?php echo date('M d, Y H:i', strtotime($order_details['created_at'] . ' UTC')); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong><?php echo __('order_status', 'Order Status'); ?>:</strong></td>
                                            <td>
                                                <span class="badge bg-<?php echo getStatusColor($order_details['order_status'] ?? 'pending'); ?>">
                                                    <?php echo __(ucfirst($order_details['order_status'] ?? 'pending'), ucfirst($order_details['order_status'] ?? 'pending')); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong><?php echo __('payment_status', 'Payment Status'); ?>:</strong></td>
                                            <td>
                                                <span class="badge bg-<?php echo getPaymentStatusColor($order_details['payment_status']); ?>">
                                                    <?php echo __(ucfirst($order_details['payment_status']), ucfirst($order_details['payment_status'])); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6><?php echo __('shipping_address', 'Shipping Address'); ?></h6>
                                    <?php
                                    $shippingData = json_decode($order_details['shipping_address'], true);
                                    if ($shippingData):
                                    ?>
                                        <address>
                                            <strong><?php echo htmlspecialchars($shippingData['first_name'] . ' ' . $shippingData['last_name']); ?></strong><br>
                                            <?php echo htmlspecialchars($shippingData['address_line1']); ?><br>
                                            <?php if (!empty($shippingData['address_line2'])): ?>
                                                <?php echo htmlspecialchars($shippingData['address_line2']); ?><br>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($shippingData['city'] . ', ' . $shippingData['state'] . ' ' . $shippingData['postal_code']); ?><br>
                                            <?php echo htmlspecialchars($shippingData['country']); ?><br>
                                            <i class="fas fa-phone me-1"></i> <?php echo htmlspecialchars($shippingData['phone']); ?>
                                        </address>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Order Items -->
                            <h6><?php echo __('order_items', 'Order Items'); ?></h6>
                            <div class="table-responsive mb-4">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th><?php echo __('product', 'Product'); ?></th>
                                            <th><?php echo __('price', 'Price'); ?></th>
                                            <th><?php echo __('quantity', 'Quantity'); ?></th>
                                            <th><?php echo __('total', 'Total'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($order_items as $item): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if ($item['product_image']): ?>
                                                            <img src="<?php echo BASE_URL; ?>assets/uploads/products/<?php echo htmlspecialchars($item['product_image']); ?>" 
                                                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                                                 class="me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                                        <?php else: ?>
                                                            <div class="me-3 bg-light d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                                <i class="fas fa-image text-muted"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo $currency->format($item['product_price']); ?></td>
                                                <td><?php echo $item['quantity']; ?></td>
                                                <td><strong><?php echo $currency->format($item['total_price']); ?></strong></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong><?php echo __('subtotal', 'Subtotal'); ?>:</strong></td>
                                            <td><strong><?php echo $currency->format($order_details['subtotal']); ?></strong></td>
                                        </tr>
                                        <?php if (($order_details['shipping_cost'] ?? ($order_details['shipping_amount'] ?? 0)) > 0): ?>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong><?php echo __('shipping', 'Shipping'); ?>:</strong></td>
                                            <td><strong><?php echo $currency->format($order_details['shipping_cost'] ?? ($order_details['shipping_amount'] ?? 0)); ?></strong></td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php if ($order_details['tax_amount'] > 0): ?>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong><?php echo __('tax', 'Tax'); ?>:</strong></td>
                                            <td><strong><?php echo $currency->format($order_details['tax_amount']); ?></strong></td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php if ($order_details['discount_amount'] > 0): ?>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong><?php echo __('discount', 'Discount'); ?>:</strong></td>
                                            <td><strong class="text-danger">-<?php echo $currency->format($order_details['discount_amount']); ?></strong></td>
                                        </tr>
                                        <?php endif; ?>
                                        <tr class="table-primary">
                                            <td colspan="3" class="text-end"><strong><?php echo __('total', 'Total'); ?>:</strong></td>
                                            <td><strong><?php echo $currency->format($order_details['total_amount']); ?></strong></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            
                            <!-- Order Actions -->
                            <div class="d-flex justify-content-between">
                                <div>
                                    <?php if (($order_details['order_status'] ?? 'pending') === 'pending'): ?>
                                        <button class="btn btn-warning" onclick="cancelOrder(<?php echo $order_details['id']; ?>)">
                                            <i class="fas fa-times me-1"></i>
                                            <?php echo __('cancel_order', 'Cancel Order'); ?>
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn btn-outline-primary ms-2" onclick="reorder(<?php echo $order_details['id']; ?>)">
                                        <i class="fas fa-redo me-1"></i>
                                        <?php echo __('reorder', 'Reorder'); ?>
                                    </button>
                                </div>
                                <button class="btn btn-outline-secondary" onclick="window.print()">
                                    <i class="fas fa-print me-1"></i>
                                    <?php echo __('print_order', 'Print Order'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Orders List -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0">
                                <i class="fas fa-shopping-bag me-2"></i>
                                <?php echo __('my_orders', 'My Orders'); ?>
                                <span class="badge bg-light text-primary ms-2"><?php echo $total_orders; ?></span>
                            </h4>
                        </div>
                        <div class="card-body">
                            <?php if (empty($orders)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-shopping-bag fa-4x text-muted mb-3"></i>
                                    <h5><?php echo __('no_orders', 'No orders yet'); ?></h5>
                                    <p class="text-muted"><?php echo __('start_shopping_message', 'Start shopping to see your orders here'); ?></p>
                                    <a href="<?php echo BASE_URL; ?>products.php" class="btn btn-primary">
                                        <i class="fas fa-shopping-bag me-2"></i>
                                        <?php echo __('start_shopping', 'Start Shopping'); ?>
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th><?php echo __('order_number', 'Order Number'); ?></th>
                                                <th><?php echo __('date', 'Date'); ?></th>
                                                <th><?php echo __('items', 'Items'); ?></th>
                                                <th><?php echo __('total', 'Total'); ?></th>
                                                <th><?php echo __('status', 'Status'); ?></th>
                                                <th><?php echo __('actions', 'Actions'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($orders as $order): ?>
                                                <tr>
                                                    <td>
                                                        <strong>#<?php echo $order['order_number']; ?></strong>
                                                    </td>
                                                    <td><?php echo date('M d, Y', strtotime($order['created_at'] . ' UTC')); ?></td>
                                                    <td><?php echo $order['item_count']; ?></td>
                                                    <td><strong><?php echo $currency->format($order['total_amount']); ?></strong></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo getStatusColor($order['order_status'] ?? 'pending'); ?>">
                                                            <?php echo __(ucfirst($order['order_status'] ?? 'pending'), ucfirst($order['order_status'] ?? 'pending')); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="<?php echo BASE_URL; ?>account/order-details.php?id=<?php echo $order['id']; ?>" 
                                                               class="btn btn-outline-primary">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <?php if (($order['order_status'] ?? 'pending') === 'delivered'): ?>
                                                                <button class="btn btn-outline-success" onclick="reviewOrder(<?php echo $order['id']; ?>)">
                                                                    <i class="fas fa-star"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Pagination -->
                                <?php if ($total_pages > 1): ?>
                                    <nav aria-label="Orders pagination">
                                        <ul class="pagination justify-content-center">
                                            <?php if ($page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                                                        <i class="fas fa-chevron-left"></i>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                <?php if ($i == $page): ?>
                                                    <li class="page-item active">
                                                        <span class="page-link"><?php echo $i; ?></span>
                                                    </li>
                                                <?php elseif ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                                    <li class="page-item">
                                                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                                    </li>
                                                <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                                    <li class="page-item disabled">
                                                        <span class="page-link">...</span>
                                                    </li>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                            
                                            <?php if ($page < $total_pages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>">
                                                        <i class="fas fa-chevron-right"></i>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php
// Helper functions for status colors
function getStatusColor($status) {
    switch($status) {
        case 'pending': return 'warning';
        case 'processing': return 'info';
        case 'shipped': return 'primary';
        case 'delivered': return 'success';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}

function getPaymentStatusColor($status) {
    switch($status) {
        case 'pending': return 'warning';
        case 'paid': return 'success';
        case 'failed': return 'danger';
        case 'refunded': return 'info';
        default: return 'secondary';
    }
}
?>

<script>
function cancelOrder(orderId) {
    if (confirm('<?php echo __('confirm_cancel_order', 'Are you sure you want to cancel this order?'); ?>')) {
        // In a real implementation, you would make an AJAX call to cancel the order
        alert('<?php echo __('feature_coming_soon', 'This feature is coming soon!'); ?>');
    }
}

function reorder(orderId) {
    // In a real implementation, you would add all items from the order to cart
    alert('<?php echo __('feature_coming_soon', 'This feature is coming soon!'); ?>');
}

function reviewOrder(orderId) {
    // In a real implementation, you would redirect to review page
    alert('<?php echo __('feature_coming_soon', 'Review feature is coming soon!'); ?>');
}
</script>

<?php include '../includes/footer.php'; ?>
