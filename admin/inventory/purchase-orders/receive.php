<?php
require_once '../../includes/auth.php';
require_once '../inventory_helper.php';
$pageTitle = __('receive_purchase_order', 'Receive Purchase Order');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$order = $db->fetchAll("
    SELECT po.*, s.name as supplier_name, w.name as warehouse_name
    FROM purchase_orders po
    LEFT JOIN suppliers s ON po.supplier_id = s.id
    LEFT JOIN warehouses w ON po.warehouse_id = w.id
    WHERE po.id = :id
", ['id' => $id]);

if (empty($order)) {
    setFlashMessage(__('order_not_found', 'Purchase order not found'), 'danger');
    header('Location: index.php');
    exit;
}

$order = $order[0];

if ($order['status'] === 'received') {
    setFlashMessage(__('order_already_received', 'Purchase order already received'), 'warning');
    header('Location: index.php');
    exit;
}

// Get order items with current stock
$orderItems = $db->fetchAll("
    SELECT poi.*, p.sku, pt.name as product_name, p.stock_quantity as current_stock
    FROM purchase_order_items poi
    LEFT JOIN products p ON poi.product_id = p.id
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang_id
    WHERE poi.purchase_order_id = :order_id
", ['order_id' => $id, 'lang_id' => $lang->getCurrentId()]);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receivedItems = $_POST['received_items'] ?? [];
    
    try {
        receivePurchaseOrder($db, $id, $receivedItems, $_SESSION['admin_id'] ?? $_SESSION['user_id']);
        setFlashMessage(__('order_received_successfully', 'Purchase order received successfully!'), 'success');
        header('Location: index.php');
        exit;
    } catch (Exception $e) {
        $errors[] = __('error_receiving_order', 'Error receiving purchase order: ') . $e->getMessage();
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('receive_purchase_order', 'Receive Purchase Order'); ?></h1>
    <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> <?php echo __('back_to_list', 'Back to List'); ?>
    </a>
</div>

<div class="row">
    <div class="col-lg-10">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?php echo __('order_information', 'Order Information'); ?></h5>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <p><strong><?php echo __('order_number', 'Order Number'); ?>:</strong> <?php echo htmlspecialchars($order['po_number']); ?></p>
                        <p><strong><?php echo __('supplier', 'Supplier'); ?>:</strong> <?php echo htmlspecialchars($order['supplier_name']); ?></p>
                        <p><strong><?php echo __('warehouse', 'Warehouse'); ?>:</strong> <?php echo htmlspecialchars($order['warehouse_name']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong><?php echo __('order_date', 'Order Date'); ?>:</strong> <?php echo date('Y-m-d', strtotime($order['order_date'])); ?></p>
                        <p><strong><?php echo __('expected_date', 'Expected Date'); ?>:</strong> <?php echo $order['expected_delivery'] ? date('Y-m-d', strtotime($order['expected_delivery'])) : '-'; ?></p>
                        <p><strong><?php echo __('status', 'Status'); ?>:</strong> <span class="badge bg-warning"><?php echo ucfirst($order['status']); ?></span></p>
                    </div>
                </div>

                <form method="POST">
                    <h6 class="mb-3"><?php echo __('receive_items', 'Receive Items'); ?></h6>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th><?php echo __('product', 'Product'); ?></th>
                                    <th><?php echo __('sku', 'SKU'); ?></th>
                                    <th><?php echo __('ordered', 'Ordered'); ?></th>
                                    <th><?php echo __('current_stock', 'Current Stock'); ?></th>
                                    <th><?php echo __('receive_quantity', 'Receive Quantity'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orderItems as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['product_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($item['sku'] ?? 'N/A'); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td><?php echo $item['current_stock']; ?></td>
                                    <td>
                                        <input type="number" class="form-control" 
                                               name="received_items[<?php echo $item['id']; ?>]" 
                                               value="<?php echo max(0, $item['quantity'] - $item['received_quantity']); ?>" 
                                               min="0" max="<?php echo $item['quantity'] - $item['received_quantity']; ?>" 
                                               required>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check"></i> <?php echo __('receive_order', 'Receive Order'); ?>
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> <?php echo __('cancel', 'Cancel'); ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
