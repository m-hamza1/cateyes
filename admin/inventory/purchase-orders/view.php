<?php
require_once '../../includes/auth.php';
$pageTitle = __('view_purchase_order', 'View Purchase Order');

// Helper function for status badge classes
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'draft':
            return 'secondary';
        case 'confirmed':
            return 'primary';
        case 'received':
            return 'success';
        case 'cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
}

// Get purchase order details
$orderId = $_GET['id'] ?? 0;
$purchaseOrder = $db->fetchOne("
    SELECT po.*, s.name as supplier_name, w.name as warehouse_name
    FROM purchase_orders po
    LEFT JOIN suppliers s ON po.supplier_id = s.id
    LEFT JOIN warehouses w ON po.warehouse_id = w.id
    WHERE po.id = :id
", ['id' => $orderId]);

if (!$purchaseOrder) {
    setFlashMessage(__('purchase_order_not_found', 'Purchase order not found'), 'error');
    header('Location: index.php');
    exit;
}

// Get purchase order items
$orderItems = $db->fetchAll("
    SELECT poi.*, p.sku, pt.name as product_name
    FROM purchase_order_items poi
    LEFT JOIN products p ON poi.product_id = p.id
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang_id
    WHERE poi.purchase_order_id = :order_id
    ORDER BY poi.id
", ['order_id' => $orderId, 'lang_id' => $lang->getCurrentId()]);

// Get default currency symbol
$defaultCurrency = $db->fetchOne("SELECT symbol, code FROM currencies WHERE is_default = 1 LIMIT 1");
$currency_symbol = $defaultCurrency['symbol'] ?? '$';
$currency_code = $defaultCurrency['code'] ?? 'USD';

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('view_purchase_order', 'View Purchase Order'); ?></h1>
    <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> <?php echo __('back_to_list', 'Back to List'); ?>
    </a>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <?php echo __('purchase_order_details', 'Purchase Order Details'); ?>
                    <span class="badge bg-<?php echo getStatusBadgeClass($purchaseOrder['status']); ?> ms-2">
                        <?php echo __(ucfirst($purchaseOrder['status']), ucfirst($purchaseOrder['status'])); ?>
                    </span>
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr>
                                <td><strong><?php echo __('order_number', 'Order Number'); ?>:</strong></td>
                                <td><?php echo htmlspecialchars($purchaseOrder['po_number'] ?? $purchaseOrder['order_number'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo __('supplier', 'Supplier'); ?>:</strong></td>
                                <td><?php echo htmlspecialchars($purchaseOrder['supplier_name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo __('warehouse', 'Warehouse'); ?>:</strong></td>
                                <td><?php echo htmlspecialchars($purchaseOrder['warehouse_name']); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr>
                                <td><strong><?php echo __('order_date', 'Order Date'); ?>:</strong></td>
                                <td><?php echo date('Y-m-d', strtotime($purchaseOrder['order_date'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo __('expected_delivery', 'Expected Delivery'); ?>:</strong></td>
                                <td><?php echo $purchaseOrder['expected_delivery'] ? date('Y-m-d', strtotime($purchaseOrder['expected_delivery'])) : 'N/A'; ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo __('status', 'Status'); ?>:</strong></td>
                                <td>
                                    <span class="badge bg-<?php echo getStatusBadgeClass($purchaseOrder['status']); ?>">
                                        <?php echo __(ucfirst($purchaseOrder['status']), ucfirst($purchaseOrder['status'])); ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <?php if (!empty($purchaseOrder['notes'])): ?>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h6><?php echo __('notes', 'Notes'); ?>:</h6>
                        <p><?php echo nl2br(htmlspecialchars($purchaseOrder['notes'])); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?php echo __('order_items', 'Order Items'); ?></h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th><?php echo __('product', 'Product'); ?></th>
                                <th><?php echo __('sku', 'SKU'); ?></th>
                                <th><?php echo __('quantity', 'Quantity'); ?></th>
                                <th><?php echo __('unit_price', 'Unit Price'); ?></th>
                                <th><?php echo __('total_price', 'Total Price'); ?></th>
                                <th><?php echo __('received_quantity', 'Received'); ?></th>
                                <th><?php echo __('remaining_quantity', 'Remaining'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orderItems as $item): ?>
                            <tr>
                                <td>
                                    <a href="../../products/edit.php?id=<?php echo $item['product_id']; ?>" target="_blank">
                                        <?php echo htmlspecialchars($item['product_name'] ?? 'N/A'); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($item['sku'] ?? 'N/A'); ?></td>
                                <td><?php echo number_format($item['quantity']); ?></td>
                                <td><?php echo $currency->format($item['unit_price']); ?></td>
                                <td><?php echo $currency->format($item['total_price']); ?></td>
                                <td><?php echo number_format($item['received_quantity']); ?></td>
                                <td><?php echo number_format($item['quantity'] - $item['received_quantity']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr>
                                <td><strong><?php echo __('subtotal', 'Subtotal'); ?>:</strong></td>
                                <td><?php echo $currency->format($purchaseOrder['subtotal']); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo __('tax_amount', 'Tax Amount'); ?>:</strong></td>
                                <td><?php echo $currency->format($purchaseOrder['tax_amount']); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo __('shipping_cost', 'Shipping Cost'); ?>:</strong></td>
                                <td><?php echo $currency->format($purchaseOrder['shipping_cost']); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr>
                                <td><strong><?php echo __('total_amount', 'Total Amount'); ?>:</strong></td>
                                <td><h5><?php echo $currency->format($purchaseOrder['total_amount']); ?></h5></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?php echo __('actions', 'Actions'); ?></h5>
            </div>
            <div class="card-body">
                <div class="btn-group">
                    <a href="edit.php?id=<?php echo $purchaseOrder['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> <?php echo __('edit', 'Edit'); ?>
                    </a>
                    <?php if ($purchaseOrder['status'] === 'draft'): ?>
                        <a href="javascript:void(0)" onclick="confirmOrder(<?php echo $purchaseOrder['id']; ?>)" class="btn btn-success">
                            <i class="fas fa-check"></i> <?php echo __('confirm', 'Confirm'); ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($purchaseOrder['status'] === 'confirmed'): ?>
                        <a href="javascript:void(0)" onclick="receiveOrder(<?php echo $purchaseOrder['id']; ?>)" class="btn btn-info">
                            <i class="fas fa-truck"></i> <?php echo __('receive', 'Receive'); ?>
                        </a>
                    <?php endif; ?>
                    <?php if (in_array($purchaseOrder['status'], ['draft', 'pending', 'confirmed'])): ?>
                        <a href="javascript:void(0)" onclick="cancelOrder(<?php echo $purchaseOrder['id']; ?>)" class="btn btn-danger">
                            <i class="fas fa-times"></i> <?php echo __('cancel', 'Cancel'); ?>
                        </a>
                    <?php endif; ?>
                    <a href="print.php?id=<?php echo $purchaseOrder['id']; ?>" class="btn btn-secondary" target="_blank">
                        <i class="fas fa-print"></i> <?php echo __('print', 'Print'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmOrder(orderId) {
    if (confirm('<?php echo __('confirm_order_warning', 'Are you sure you want to confirm this purchase order?'); ?>')) {
        // Create a form to submit the confirmation request directly
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'confirm-direct.php';
        form.style.display = 'none';
        
        const orderIdInput = document.createElement('input');
        orderIdInput.type = 'hidden';
        orderIdInput.name = 'id';
        orderIdInput.value = orderId;
        
        form.appendChild(orderIdInput);
        document.body.appendChild(form);
        form.submit();
    }
}

function receiveOrder(orderId) {
    if (confirm('<?php echo __('receive_order_warning', 'Are you sure you want to receive this purchase order?'); ?>')) {
        window.location.href = 'receive.php?id=' + orderId;
    }
}

function cancelOrder(orderId) {
    if (confirm('<?php echo __('cancel_order_warning', 'Are you sure you want to cancel this purchase order? This action cannot be undone.'); ?>')) {
        // Create a form to submit the cancellation request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'cancel.php';
        form.style.display = 'none';
        
        const orderIdInput = document.createElement('input');
        orderIdInput.type = 'hidden';
        orderIdInput.name = 'id';
        orderIdInput.value = orderId;
        
        form.appendChild(orderIdInput);
        document.body.appendChild(form);
        form.submit();
    }
}

</script>

<?php include '../../includes/footer.php'; ?>
