<?php
require_once '../../includes/auth.php';
$pageTitle = __('edit_production_order', 'Edit Production Order');

// Get production order details
$orderId = $_GET['id'] ?? 0;
$order = $db->fetchOne("
    SELECT po.*, pt.name as product_name, p.sku, w.name as warehouse_name
    FROM production_orders po
    LEFT JOIN products p ON po.product_id = p.id
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang_id
    LEFT JOIN warehouses w ON po.warehouse_id = w.id
    WHERE po.id = :id
", ['id' => $orderId, 'lang_id' => $lang->getCurrentId()]);

if (!$order) {
    setFlashMessage(__('production_order_not_found', 'Production order not found'), 'error');
    header('Location: index.php');
    exit;
}

// Get warehouses
$warehouses = $db->fetchAll("SELECT * FROM warehouses WHERE is_active = 1 ORDER BY name");

// Get default currency symbol
$defaultCurrency = $db->fetchOne("SELECT symbol FROM currencies WHERE is_default = 1 AND is_active = 1");
$currency_symbol = $defaultCurrency['symbol'] ?? '$';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = $_POST['product_id'] ?? 0;
    $warehouseId = $_POST['warehouse_id'] ?? 0;
    $quantity = (int)($_POST['quantity'] ?? 0);
    $productionDate = $_POST['production_date'] ?? date('Y-m-d');
    $expectedCompletion = $_POST['expected_completion'] ?? null;
    $notes = trim($_POST['notes'] ?? '');
    
    $errors = [];
    if (empty($productId)) $errors[] = __('product_required', 'Product is required');
    if (empty($warehouseId)) $errors[] = __('warehouse_required', 'Warehouse is required');
    if (empty($quantity) || $quantity <= 0) $errors[] = __('quantity_required', 'Valid quantity is required');
    
    // Check if order can be edited (only pending orders can be edited)
    if ($order['status'] !== 'pending') {
        $errors[] = __('order_cannot_be_edited', 'Only pending orders can be edited');
    }
    
    if (empty($errors)) {
        try {
            $updated = $db->update('production_orders', [
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'quantity' => $quantity,
                'production_date' => $productionDate,
                'expected_completion' => $expectedCompletion ?: null,
                'notes' => $notes ?: null,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = :id', ['id' => $orderId]);
            
            if ($updated) {
                setFlashMessage(__('production_order_updated', 'Production order updated successfully!'), 'success');
                header('Location: view.php?id=' . $orderId);
                exit;
            } else {
                $errors[] = __('update_failed', 'Failed to update production order');
            }
        } catch (Exception $e) {
            $errors[] = __('error_updating_order', 'Error updating production order: ') . $e->getMessage();
        }
    }
}

// Get products for dropdown
$products = $db->fetchAll("
    SELECT p.id, pt.name as product_name, p.sku 
    FROM products p 
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang_id 
    WHERE p.is_active = 1 
    ORDER BY pt.name
", ['lang_id' => $lang->getCurrentId()]);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('edit_production_order', 'Edit Production Order'); ?></h1>
    <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> <?php echo __('back_to_list', 'Back to List'); ?>
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?php echo __('production_order_details', 'Production Order Details'); ?></h5>
            </div>
            <div class="card-body">
                <?php if ($order['status'] !== 'pending'): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong><?php echo __('warning', 'Warning'); ?>:</strong>
                        <?php echo __('order_status_warning', 'This order has a status of "') . __(ucfirst($order['status']), ucfirst($order['status'])) . __('" and cannot be edited. Only pending orders can be modified.'); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" <?php echo $order['status'] !== 'pending' ? 'onsubmit="return false;"' : ''; ?>>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="production_number" class="form-label"><?php echo __('production_number', 'Production Number'); ?></label>
                                <input type="text" class="form-control" id="production_number" value="<?php echo htmlspecialchars($order['production_number'] ?? 'N/A'); ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label"><?php echo __('status', 'Status'); ?></label>
                                <input type="text" class="form-control" id="status" value="<?php echo __(ucfirst($order['status']), ucfirst($order['status'])); ?>" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="product_id" class="form-label"><?php echo __('product', 'Product'); ?> *</label>
                                <select class="form-select" id="product_id" name="product_id" <?php echo $order['status'] !== 'pending' ? 'disabled' : ''; ?> required>
                                    <option value=""><?php echo __('select_product', 'Select Product'); ?></option>
                                    <?php foreach ($products as $product): ?>
                                    <option value="<?php echo $product['id']; ?>" 
                                            <?php echo (isset($_POST['product_id']) ? $_POST['product_id'] : $order['product_id']) == $product['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($product['product_name'] ?? 'N/A'); ?> (<?php echo htmlspecialchars($product['sku'] ?? 'N/A'); ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="warehouse_id" class="form-label"><?php echo __('warehouse', 'Warehouse'); ?> *</label>
                                <select class="form-select" id="warehouse_id" name="warehouse_id" <?php echo $order['status'] !== 'pending' ? 'disabled' : ''; ?> required>
                                    <option value=""><?php echo __('select_warehouse', 'Select Warehouse'); ?></option>
                                    <?php foreach ($warehouses as $warehouse): ?>
                                    <option value="<?php echo $warehouse['id']; ?>" 
                                            <?php echo (isset($_POST['warehouse_id']) ? $_POST['warehouse_id'] : $order['warehouse_id']) == $warehouse['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($warehouse['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="quantity" class="form-label"><?php echo __('quantity', 'Quantity'); ?> *</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" 
                                       value="<?php echo htmlspecialchars($_POST['quantity'] ?? $order['quantity']); ?>" 
                                       min="1" <?php echo $order['status'] !== 'pending' ? 'disabled' : ''; ?> required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="production_date" class="form-label"><?php echo __('production_date', 'Production Date'); ?> *</label>
                                <input type="date" class="form-control" id="production_date" name="production_date" 
                                       value="<?php echo htmlspecialchars($_POST['production_date'] ?? date('Y-m-d', strtotime($order['production_date']))); ?>" 
                                       <?php echo $order['status'] !== 'pending' ? 'disabled' : ''; ?> required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="expected_completion" class="form-label"><?php echo __('expected_completion', 'Expected Completion'); ?></label>
                                <input type="date" class="form-control" id="expected_completion" name="expected_completion" 
                                       value="<?php echo htmlspecialchars($_POST['expected_completion'] ?? $order['expected_completion']); ?>" 
                                       <?php echo $order['status'] !== 'pending' ? 'disabled' : ''; ?>>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="produced_quantity" class="form-label"><?php echo __('produced_quantity', 'Produced Quantity'); ?></label>
                                <input type="number" class="form-control" id="produced_quantity" value="<?php echo number_format($order['produced_quantity']); ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="created_at" class="form-label"><?php echo __('created_at', 'Created At'); ?></label>
                                <input type="text" class="form-control" id="created_at" value="<?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?>" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label"><?php echo __('notes', 'Notes'); ?></label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" 
                                  <?php echo $order['status'] !== 'pending' ? 'disabled' : ''; ?>><?php echo htmlspecialchars($_POST['notes'] ?? $order['notes']); ?></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary" <?php echo $order['status'] !== 'pending' ? 'disabled' : ''; ?>>
                            <i class="fas fa-save"></i> <?php echo __('update', 'Update'); ?>
                        </button>
                        <a href="view.php?id=<?php echo $orderId; ?>" class="btn btn-secondary">
                            <i class="fas fa-times"></i> <?php echo __('cancel', 'Cancel'); ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?php echo __('order_actions', 'Order Actions'); ?></h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="view.php?id=<?php echo $orderId; ?>" class="btn btn-outline-primary">
                        <i class="fas fa-eye"></i> <?php echo __('view_order', 'View Order'); ?>
                    </a>
                    
                    <?php if ($order['status'] === 'pending'): ?>
                        <a href="start-production.php?id=<?php echo $orderId; ?>" class="btn btn-success">
                            <i class="fas fa-play"></i> <?php echo __('start_production', 'Start Production'); ?>
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($order['status'] === 'in_progress'): ?>
                        <a href="receive.php?id=<?php echo $orderId; ?>" class="btn btn-info">
                            <i class="fas fa-check-circle"></i> <?php echo __('receive_production', 'Receive Production'); ?>
                        </a>
                    <?php endif; ?>
                    
                    <a href="delete.php?id=<?php echo $orderId; ?>" class="btn btn-outline-danger">
                        <i class="fas fa-trash"></i> <?php echo __('delete_order', 'Delete Order'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>