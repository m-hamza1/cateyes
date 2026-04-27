<?php
/**
 * Delete Order
 */

require_once '../includes/auth.php';
requirePermission('orders');

// Get order ID
$orderId = $_GET['id'] ?? 0;
if (!$orderId) {
    $_SESSION['error'] = __('order_id_not_provided', 'Order ID not provided');
    header('Location: index.php');
    exit;
}

// Initialize classes
$order = new Order($db);

// Get order details for confirmation
$orderDetails = $order->getById($orderId);
if (!$orderDetails) {
    $_SESSION['error'] = __('order_not_found', 'Order not found');
    header('Location: index.php');
    exit;
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $result = $order->delete($orderId);
    
    if ($result) {
        $_SESSION['success'] = __('order_deleted_successfully', 'Order #{order} has been deleted successfully', ['order' => $orderDetails['order_number']]);
        header('Location: index.php');
        exit;
    } else {
        $_SESSION['error'] = __('failed_to_delete_order', 'Failed to delete order');
    }
}

$pageTitle = __('delete_order', 'Delete Order');
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-danger text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo __('delete_order_confirmation', 'Delete Order Confirmation'); ?>
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong><?php echo __('warning', 'Warning'); ?>:</strong> <?php echo __('delete_warning', 'This action cannot be undone. All order data, including items, status history, and payment information will be permanently deleted.'); ?>
                            </div>

                            <!-- Order Information -->
                            <div class="mb-4">
                                <h6><?php echo __('order_information', 'Order Information'); ?></h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong><?php echo __('order_number', 'Order Number'); ?>:</strong></td>
                                        <td>#<?php echo htmlspecialchars($orderDetails['order_number']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php echo __('customer', 'Customer'); ?>:</strong></td>
                                        <td>
                                            <?php echo htmlspecialchars($orderDetails['billing_first_name'] . ' ' . $orderDetails['billing_last_name']); ?>
                                            <?php if ($orderDetails['billing_email']): ?>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($orderDetails['billing_email']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php echo __('total_amount', 'Total Amount'); ?>:</strong></td>
                                        <td><?php echo $currency->format($orderDetails['total_amount']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php echo __('status', 'Status'); ?>:</strong></td>
                                        <td>
                                            <span class="badge bg-<?php echo getStatusColor($orderDetails['order_status']); ?>">
                                                <?php echo ucfirst($orderDetails['order_status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php echo __('created', 'Created'); ?>:</strong></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($orderDetails['created_at'])); ?></td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Order Items Summary -->
                            <div class="mb-4">
                                <h6><?php echo __('order_items', 'Order Items'); ?></h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th><?php echo __('product', 'Product'); ?></th>
                                                <th><?php echo __('quantity', 'Quantity'); ?></th>
                                                <th><?php echo __('total', 'Total'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $orderItems = $orderDetails['items'] ?? [];
                                            foreach ($orderItems as $item): 
                                            ?>
                                                <tr>
                                                    <td>
                                                        <?php echo htmlspecialchars($item['product_name']); ?>
                                                        <?php if (!empty($item['product_options'])): ?>
                                                            <br>
                                                            <small class="text-muted"><?php echo htmlspecialchars($item['product_options']); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo $item['quantity']; ?></td>
                                                    <td><?php echo $currency->format($item['total_price']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Confirmation Form -->
                            <form method="POST">
                                <input type="hidden" name="confirm_delete" value="1">
                                <div class="d-flex justify-content-between">
                                    <a href="view.php?id=<?php echo $orderId; ?>" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-1"></i> <?php echo __('cancel', 'Cancel'); ?>
                                    </a>
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-trash me-1"></i> <?php echo __('yes_delete_order', 'Yes, Delete Order #{order}', ['order' => $orderDetails['order_number']]); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<?php
// Helper function
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
?>
