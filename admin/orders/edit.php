<?php
/**
 * Edit Order
 */

require_once '../includes/auth.php';
requirePermission('orders');
$pageTitle = __('edit_order', 'Edit Order');

// Get order ID
$orderId = $_GET['id'] ?? 0;
if (!$orderId) {
    $_SESSION['error'] = __('order_id_not_provided', 'Order ID not provided');
    header('Location: index.php');
    exit;
}

// Initialize classes
$order = new Order($db);
$invoiceService = new Invoice($db);

// Get order details
$orderDetails = $order->getById($orderId);
if (!$orderDetails) {
    $_SESSION['error'] = __('order_not_found', 'Order not found');
    header('Location: index.php');
    exit;
}

// Get order items
$orderItems = $orderDetails['items'] ?? [];

// Load warehouses for selection
$warehouses = $db->fetchAll("SELECT id, name, is_default FROM warehouses ORDER BY is_default DESC, name ASC");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'order_status' => $_POST['order_status'] ?? $orderDetails['order_status'],
        'payment_status' => $_POST['payment_status'] ?? $orderDetails['payment_status'],
        'warehouse_id' => $_POST['warehouse_id'] ?? $orderDetails['warehouse_id'],
        'tracking_number' => $_POST['tracking_number'] ?? $orderDetails['tracking_number'] ?? '',
        'notes' => $_POST['notes'] ?? ''
    ];
    
    $oldStatus = $orderDetails['order_status'];
    
    // Update basic order info
    $result = $order->update($orderId, $data);
    
    if ($result) {
        // Send status update email if status changed
        if ($data['order_status'] !== $oldStatus) {
            try {
                require_once INCLUDES_PATH . 'Email.php';
                $mailer = new Email($db);
                $mailer->orderStatusUpdate($orderId, $data['order_status'], $data['tracking_number'] ?? '');
            } catch (Exception $e) {
                error_log("Email send failed on order status update: " . $e->getMessage());
            }
        }
        
        $_SESSION['success'] = __('order_updated_successfully', 'Order updated successfully');
        header("Location: view.php?id=$orderId");
        exit;
    } else {
        $_SESSION['error'] = $order->getLastError() ?: __('failed_to_update_order', 'Failed to update order');
    }
}

$invoiceInfo = $invoiceService->getInfoForOrder($orderId);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-edit me-2"></i>
                    <?php echo __('edit_order', 'Edit Order'); ?>: #<?php echo htmlspecialchars($orderDetails['order_number']); ?>
                </h1>
                <div class="d-flex gap-2">
                    <a href="invoice.php?id=<?php echo $orderId; ?>" class="btn btn-outline-primary" target="_blank">
                        <i class="fas fa-file-pdf me-1"></i> <?php echo __('view_invoice', 'View Invoice'); ?>
                    </a>
                    <a href="view.php?id=<?php echo $orderId; ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-eye me-1"></i> <?php echo __('view_order', 'View Order'); ?>
                    </a>
                </div>
            </div>

            <div class="alert <?php echo !empty($invoiceInfo['exists']) ? 'alert-success' : 'alert-warning'; ?>">
                <?php if (!empty($invoiceInfo['exists'])): ?>
                    <?php echo __('invoice_ready_for_shipping', 'Invoice PDF is ready for shipping.'); ?>
                    <?php if (!empty($invoiceInfo['invoice_number'])): ?>
                        <strong><?php echo htmlspecialchars($invoiceInfo['invoice_number']); ?></strong>
                    <?php endif; ?>
                <?php else: ?>
                    <?php echo __('invoice_auto_generated_when_prepared', 'Invoice PDF will be auto-generated when the order is moved to prepared/processing. Shipping stays blocked until the invoice exists.'); ?>
                <?php endif; ?>
            </div>

            <form method="POST">
                <div class="row">
                    <!-- Order Information -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-info-circle me-2"></i><?php echo __('order_information', 'Order Information'); ?>
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label"><?php echo __('order_number', 'Order Number'); ?></label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($orderDetails['order_number']); ?>" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><?php echo __('order_date', 'Order Date'); ?></label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo date('Y-m-d H:i:s', strtotime($orderDetails['created_at'])); ?>" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><?php echo __('order_status', 'Order Status'); ?></label>
                                    <select name="order_status" class="form-select" required>
                                        <option value="pending" <?php echo $orderDetails['order_status'] === 'pending' ? 'selected' : ''; ?>><?php echo __('pending', 'Pending'); ?></option>
                                        <option value="confirmed" <?php echo $orderDetails['order_status'] === 'confirmed' ? 'selected' : ''; ?>><?php echo __('confirmed', 'Confirmed'); ?></option>
                                        <option value="processing" <?php echo $orderDetails['order_status'] === 'processing' ? 'selected' : ''; ?>><?php echo __('processing', 'Processing'); ?></option>
                                        <option value="shipped" <?php echo $orderDetails['order_status'] === 'shipped' ? 'selected' : ''; ?>><?php echo __('shipped', 'Shipped'); ?></option>
                                        <option value="delivered" <?php echo $orderDetails['order_status'] === 'delivered' ? 'selected' : ''; ?>><?php echo __('delivered', 'Delivered'); ?></option>
                                        <option value="cancelled" <?php echo $orderDetails['order_status'] === 'cancelled' ? 'selected' : ''; ?>><?php echo __('cancelled', 'Cancelled'); ?></option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><?php echo __('payment_status', 'Payment Status'); ?></label>
                                    <select name="payment_status" class="form-select" required>
                                        <option value="pending" <?php echo $orderDetails['payment_status'] === 'pending' ? 'selected' : ''; ?>><?php echo __('pending', 'Pending'); ?></option>
                                        <option value="paid" <?php echo $orderDetails['payment_status'] === 'paid' ? 'selected' : ''; ?>><?php echo __('paid', 'Paid'); ?></option>
                                        <option value="failed" <?php echo $orderDetails['payment_status'] === 'failed' ? 'selected' : ''; ?>><?php echo __('failed', 'Failed'); ?></option>
                                        <option value="refunded" <?php echo $orderDetails['payment_status'] === 'refunded' ? 'selected' : ''; ?>><?php echo __('refunded', 'Refunded'); ?></option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-warehouse me-1"></i><?php echo __('warehouse', 'Warehouse'); ?>
                                    </label>
                                    <select name="warehouse_id" class="form-select" required>
                                        <option value=""><?php echo __('select_warehouse', 'Select Warehouse'); ?></option>
                                        <?php foreach ($warehouses as $warehouse): ?>
                                            <option value="<?php echo $warehouse['id']; ?>" 
                                                <?php echo ($orderDetails['warehouse_id'] ?? 0) == $warehouse['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($warehouse['name']); ?>
                                                <?php echo $warehouse['is_default'] ? ' (Default)' : ''; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="form-text text-muted">
                                        <?php echo __('warehouse_fulfillment_note', 'This warehouse will be used for inventory tracking when the order is delivered'); ?>
                                    </small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><?php echo __('tracking_number', 'Tracking Number'); ?></label>
                                    <input type="text" name="tracking_number" class="form-control" 
                                           value="<?php echo htmlspecialchars($orderDetails['tracking_number'] ?? ''); ?>"
                                           placeholder="<?php echo __('enter_tracking_number', 'Enter tracking number...'); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><?php echo __('admin_notes', 'Admin Notes'); ?></label>
                                    <textarea name="notes" class="form-control" rows="4" 
                                              placeholder="<?php echo __('add_internal_notes', 'Add internal notes about this order...'); ?>"><?php echo htmlspecialchars($orderDetails['notes'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Information -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-user me-2"></i><?php echo __('customer_information', 'Customer Information'); ?>
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label"><?php echo __('customer_name', 'Customer Name'); ?></label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo htmlspecialchars($orderDetails['billing_first_name'] . ' ' . $orderDetails['billing_last_name']); ?>" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><?php echo __('email', 'Email'); ?></label>
                                    <input type="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($orderDetails['billing_email']); ?>" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo htmlspecialchars($orderDetails['billing_phone']); ?>" readonly>
                                </div>
                                
                                <?php if ($orderDetails['user_id']): ?>
                                    <div class="mb-3">
                                        <label class="form-label"><?php echo __('customer_id', 'Customer ID'); ?></label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" 
                                                   value="<?php echo $orderDetails['user_id']; ?>" readonly>
                                            <a href="../customers/edit.php?id=<?php echo $orderDetails['user_id']; ?>" 
                                               class="btn btn-outline-secondary" target="_blank">
                                                <i class="fas fa-user"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Information -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-dollar-sign me-2"></i><?php echo __('financial_information', 'Financial Information'); ?>
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label"><?php echo __('subtotal', 'Subtotal'); ?></label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo $currency->format($orderDetails['subtotal']); ?>" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><?php echo __('tax_amount', 'Tax Amount'); ?></label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo $currency->format($orderDetails['tax_amount']); ?>" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><?php echo __('shipping_cost', 'Shipping Cost'); ?></label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo $currency->format($orderDetails['shipping_cost']); ?>" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><?php echo __('discount_amount', 'Discount Amount'); ?></label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo $currency->format($orderDetails['discount_amount']); ?>" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><?php echo __('total_amount', 'Total Amount'); ?></label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo $currency->format($orderDetails['total_amount']); ?>" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Address -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-truck me-2"></i><?php echo __('shipping_address', 'Shipping Address'); ?>
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php
                                $shippingData = json_decode($orderDetails['shipping_address'], true);
                                if ($shippingData):
                                ?>
                                    <div class="mb-3">
                                        <label class="form-label"><?php echo __('recipient_name', 'Recipient Name'); ?></label>
                                        <input type="text" class="form-control" 
                                               value="<?php echo htmlspecialchars($shippingData['first_name'] . ' ' . $shippingData['last_name']); ?>" readonly>
                                    </div>
                                    
                                    <?php if (!empty($shippingData['company'])): ?>
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo __('company', 'Company'); ?></label>
                                            <input type="text" class="form-control" 
                                                   value="<?php echo htmlspecialchars($shippingData['company']); ?>" readonly>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="mb-3">
                                        <label class="form-label"><?php echo __('address_line1', 'Address Line 1'); ?></label>
                                        <input type="text" class="form-control" 
                                               value="<?php echo htmlspecialchars($shippingData['address_line1']); ?>" readonly>
                                    </div>
                                    
                                    <?php if (!empty($shippingData['address_line2'])): ?>
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo __('address_line2', 'Address Line 2'); ?></label>
                                            <input type="text" class="form-control" 
                                                   value="<?php echo htmlspecialchars($shippingData['address_line2']); ?>" readonly>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label"><?php echo __('city', 'City'); ?></label>
                                            <input type="text" class="form-control" 
                                                   value="<?php echo htmlspecialchars($shippingData['city']); ?>" readonly>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label"><?php echo __('state', 'State'); ?></label>
                                            <input type="text" class="form-control" 
                                                   value="<?php echo htmlspecialchars($shippingData['state']); ?>" readonly>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label"><?php echo __('postal_code', 'Postal Code'); ?></label>
                                            <input type="text" class="form-control" 
                                                   value="<?php echo htmlspecialchars($shippingData['postal_code']); ?>" readonly>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label"><?php echo __('country', 'Country'); ?></label>
                                        <input type="text" class="form-control" 
                                               value="<?php echo htmlspecialchars($shippingData['country']); ?>" readonly>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Phone</label>
                                        <input type="text" class="form-control" 
                                               value="<?php echo htmlspecialchars($shippingData['phone']); ?>" readonly>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted"><?php echo __('no_shipping_address', 'No shipping address available'); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="col-12 mb-4">
                    <div class="card">
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
                                                        <img src="<?php echo UPLOADS_URL; ?>products/<?php echo htmlspecialchars($item['product_image'] ?? 'placeholder.jpg'); ?>" 
                                                             alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                                             class="img-thumbnail me-2" style="width: 40px; height: 40px; object-fit: cover;">
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
                </div>

                <!-- Actions -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> <?php echo __('update_order', 'Update Order'); ?>
                                    </button>
                                    <a href="view.php?id=<?php echo $orderId; ?>" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i> <?php echo __('cancel', 'Cancel'); ?>
                                    </a>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-danger" onclick="deleteOrder()">
                                        <i class="fas fa-trash me-1"></i> <?php echo __('delete_order', 'Delete Order'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function deleteOrder() {
    if (confirm('<?php echo __('confirm_delete_order', 'Are you sure you want to delete this order? This action cannot be undone.'); ?>')) {
        window.location.href = 'delete.php?id=<?php echo $orderId; ?>';
    }
}
</script>

<?php include '../includes/footer.php'; ?>
