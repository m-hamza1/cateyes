<?php
require_once '../../includes/auth.php';
$pageTitle = __('edit_sales_return', 'Edit Sales Return');

// Get sales return details
$returnId = $_GET['id'] ?? 0;
$return = $db->fetchOne("
    SELECT sr.*, c.first_name, c.last_name, w.name as warehouse_name
    FROM sales_returns sr
    LEFT JOIN customers c ON sr.customer_id = c.id
    LEFT JOIN warehouses w ON sr.warehouse_id = w.id
    WHERE sr.id = :id
", ['id' => $returnId]);

if (!$return) {
    setFlashMessage(__('sales_return_not_found', 'Sales return not found'), 'error');
    header('Location: index.php');
    exit;
}

// Get customers and warehouses
$customers = $db->fetchAll("SELECT * FROM customers WHERE is_active = 1 ORDER BY first_name, last_name");
$warehouses = $db->fetchAll("SELECT * FROM warehouses WHERE is_active = 1 ORDER BY name");

// Get return items
$returnItems = $db->fetchAll("
    SELECT sri.*, p.sku, pt.name as product_name
    FROM sales_return_items sri
    LEFT JOIN products p ON sri.product_id = p.id
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang_id
    WHERE sri.sales_return_id = :return_id
    ORDER BY sri.id
", ['return_id' => $returnId, 'lang_id' => $lang->getCurrentId()]);

// Default currency is handled automatically by the Currency class based on ADMIN_AREA constant

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId = $_POST['customer_id'] ?? 0;
    $warehouseId = $_POST['warehouse_id'] ?? 0;
    $returnDate = $_POST['return_date'] ?? date('Y-m-d');
    $reason = trim($_POST['reason'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    $errors = [];
    if (empty($customerId)) $errors[] = __('customer_required', 'Customer is required');
    if (empty($warehouseId)) $errors[] = __('warehouse_required', 'Warehouse is required');
    if (empty($reason)) $errors[] = __('reason_required', 'Reason is required');
    
    if (empty($errors)) {
        try {
            $updated = $db->update('sales_returns', [
                'customer_id' => $customerId,
                'warehouse_id' => $warehouseId,
                'return_date' => $returnDate,
                'reason' => $reason,
                'notes' => $notes ?: null,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = :id', ['id' => $returnId]);
            
            if ($updated) {
                setFlashMessage(__('sales_return_updated', 'Sales return updated successfully!'), 'success');
                header('Location: index.php');
                exit;
            } else {
                $errors[] = __('update_failed', 'Failed to update sales return');
            }
        } catch (Exception $e) {
            $errors[] = __('error_updating_return', 'Error updating sales return: ') . $e->getMessage();
        }
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('edit_sales_return', 'Edit Sales Return'); ?></h1>
    <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> <?php echo __('back_to_list', 'Back to List'); ?>
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?php echo __('sales_return_details', 'Sales Return Details'); ?></h5>
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

                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="customer_id" class="form-label"><?php echo __('customer', 'Customer'); ?> *</label>
                                <select class="form-select" id="customer_id" name="customer_id" required>
                                    <option value=""><?php echo __('select_customer', 'Select Customer'); ?></option>
                                    <?php foreach ($customers as $customer): ?>
                                    <option value="<?php echo $customer['id']; ?>" <?php echo (isset($_POST['customer_id']) ? $_POST['customer_id'] : $return['customer_id']) == $customer['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="warehouse_id" class="form-label"><?php echo __('warehouse', 'Warehouse'); ?> *</label>
                                <select class="form-select" id="warehouse_id" name="warehouse_id" required>
                                    <option value=""><?php echo __('select_warehouse', 'Select Warehouse'); ?></option>
                                    <?php foreach ($warehouses as $warehouse): ?>
                                    <option value="<?php echo $warehouse['id']; ?>" <?php echo (isset($_POST['warehouse_id']) ? $_POST['warehouse_id'] : $return['warehouse_id']) == $warehouse['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($warehouse['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="return_date" class="form-label"><?php echo __('return_date', 'Return Date'); ?> *</label>
                                <input type="date" class="form-control" id="return_date" name="return_date" 
                                       value="<?php echo htmlspecialchars($_POST['return_date'] ?? date('Y-m-d', strtotime($return['return_date']))); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="reason" class="form-label"><?php echo __('reason', 'Reason'); ?> *</label>
                                <select class="form-select" id="reason" name="reason" required>
                                    <option value=""><?php echo __('select_reason', 'Select Reason'); ?></option>
                                    <option value="defective" <?php echo (isset($_POST['reason']) ? $_POST['reason'] : $return['reason']) == 'defective' ? 'selected' : ''; ?>>
                                        <?php echo __('defective_items', 'Defective Items'); ?>
                                    </option>
                                    <option value="wrong_item" <?php echo (isset($_POST['reason']) ? $_POST['reason'] : $return['reason']) == 'wrong_item' ? 'selected' : ''; ?>>
                                        <?php echo __('wrong_items', 'Wrong Items'); ?>
                                    </option>
                                    <option value="damaged" <?php echo (isset($_POST['reason']) ? $_POST['reason'] : $return['reason']) == 'damaged' ? 'selected' : ''; ?>>
                                        <?php echo __('damaged_items', 'Damaged Items'); ?>
                                    </option>
                                    <option value="expired" <?php echo (isset($_POST['reason']) ? $_POST['reason'] : $return['reason']) == 'expired' ? 'selected' : ''; ?>>
                                        <?php echo __('expired_items', 'Expired Items'); ?>
                                    </option>
                                    <option value="other" <?php echo (isset($_POST['reason']) ? $_POST['reason'] : $return['reason']) == 'other' ? 'selected' : ''; ?>>
                                        <?php echo __('other', 'Other'); ?>
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label"><?php echo __('notes', 'Notes'); ?></label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($_POST['notes'] ?? $return['notes']); ?></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> <?php echo __('update', 'Update'); ?>
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

<div class="row mt-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?php echo __('return_items', 'Return Items'); ?></h5>
            </div>
            <div class="card-body">
                <?php if (!empty($returnItems)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th><?php echo __('product', 'Product'); ?></th>
                                    <th><?php echo __('sku', 'SKU'); ?></th>
                                    <th><?php echo __('quantity', 'Quantity'); ?></th>
                                    <th><?php echo __('unit_price', 'Unit Price'); ?></th>
                                    <th><?php echo __('total_price', 'Total Price'); ?></th>
                                    <th><?php echo __('actions', 'Actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($returnItems as $item): ?>
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
                                    <td>
                                        <a href="delete-item.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
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
                                    <td><?php echo $currency->format($return['subtotal']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php echo __('tax_amount', 'Tax Amount'); ?>:</strong></td>
                                    <td><?php echo $currency->format($return['tax_amount']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php echo __('shipping_cost', 'Shipping Cost'); ?>:</strong></td>
                                    <td><?php echo $currency->format($return['shipping_cost']); ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <td><strong><?php echo __('total_amount', 'Total Amount'); ?>:</strong></td>
                                    <td><h5><?php echo $currency->format($return['total_amount']); ?></h5></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-3">
                        <i class="fas fa-box fa-2x text-muted mb-2"></i>
                        <p class="text-muted"><?php echo __('no_items_found', 'No items found'); ?></p>
                        <a href="add-item.php?id=<?php echo $returnId; ?>" class="btn btn-primary">
                            <i class="fas fa-plus"></i> <?php echo __('add_item', 'Add Item'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
