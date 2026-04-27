<?php
require_once '../../includes/auth.php';
require_once '../inventory_helper.php';
$pageTitle = __('edit_stock_adjustment', 'Edit Stock Adjustment');

// Get default currency symbol
$defaultCurrency = $db->fetchOne("SELECT symbol FROM currencies WHERE is_default = 1 AND is_active = 1");
$currency_symbol = $defaultCurrency['symbol'] ?? '$';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$adjustment = $db->fetchOne("SELECT * FROM stock_adjustments WHERE id = :id", ['id' => $id]);

if (!$adjustment) {
    setFlashMessage(__('adjustment_not_found', 'Stock adjustment not found'), 'danger');
    header('Location: index.php');
    exit;
}

// Get adjustment items (read-only - cannot modify after creation)
$items = $db->fetchAll("
    SELECT sai.*,
           p.sku,
           pt.name as product_name,
           p.price
    FROM stock_adjustment_items sai
    LEFT JOIN products p ON sai.product_id = p.id
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
    WHERE sai.stock_adjustment_id = :adjustment_id
    ORDER BY sai.id
", ['adjustment_id' => $id]);

// Handle form submission - only date and reason are editable
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adjustmentDate = $_POST['adjustment_date'] ?? $adjustment['adjustment_date'];
    $reason = trim($_POST['reason'] ?? '');
    
    $errors = [];
    if (empty($adjustmentDate)) {
        $errors[] = __('adjustment_date_required', 'Adjustment date is required');
    }
    
    if (empty($errors)) {
        try {
            $db->update('stock_adjustments', [
                'adjustment_date' => $adjustmentDate,
                'reason' => $reason ?: null
            ], 'id = :id', ['id' => $id]);
            
            setFlashMessage(__('stock_adjustment_updated', 'Stock adjustment updated successfully!'), 'success');
            header('Location: view.php?id=' . $id);
            exit;
        } catch (Exception $e) {
            $errors[] = __('error_updating_adjustment', 'Error updating adjustment: ') . $e->getMessage();
        }
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';

// Get warehouse name
$warehouse = $db->fetchOne("SELECT name FROM warehouses WHERE id = :id", ['id' => $adjustment['warehouse_id']]);
$warehouseName = $warehouse['name'] ?? __('unknown', 'Unknown');
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('edit_stock_adjustment', 'Edit Stock Adjustment'); ?></h1>
    <div>
        <a href="view.php?id=<?php echo $id; ?>" class="btn btn-outline-secondary">
            <i class="fas fa-eye"></i> <?php echo __('view', 'View'); ?>
        </a>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> <?php echo __('back_to_list', 'Back to List'); ?>
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?php echo __('adjustment_details', 'Adjustment Details'); ?></h5>
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

                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    <?php echo __('adjustment_edit_note', 'Only date and reason can be edited. Products and quantities cannot be changed after creation.'); ?>
                </div>

                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label"><?php echo __('adjustment_number', 'Adjustment Number'); ?></label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($adjustment['adjustment_number']); ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label"><?php echo __('warehouse', 'Warehouse'); ?></label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($warehouseName); ?>" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="adjustment_date" class="form-label"><?php echo __('adjustment_date', 'Adjustment Date'); ?> <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="adjustment_date" name="adjustment_date" 
                                       value="<?php echo htmlspecialchars($adjustment['adjustment_date']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label"><?php echo __('adjustment_type', 'Adjustment Type'); ?></label>
                                <?php
                                $typeLabels = ['add' => __('add_stock', 'Add Stock'), 'subtract' => __('subtract_stock', 'Subtract Stock'), 'set' => __('set_stock', 'Set Stock')];
                                $typeLabel = $typeLabels[$adjustment['adjustment_type']] ?? ucfirst($adjustment['adjustment_type']);
                                ?>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($typeLabel); ?>" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="reason" class="form-label"><?php echo __('reason', 'Reason'); ?></label>
                        <textarea class="form-control" id="reason" name="reason" rows="3"><?php echo htmlspecialchars($adjustment['reason'] ?? ''); ?></textarea>
                    </div>
                    
                    <hr>
                    
                    <h5><?php echo __('adjustment_items', 'Adjustment Items'); ?> <small class="text-muted">(<?php echo __('read_only', 'read-only'); ?>)</small></h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th><?php echo __('product', 'Product'); ?></th>
                                    <th><?php echo __('sku', 'SKU'); ?></th>
                                    <th class="text-end"><?php echo __('old_quantity', 'Old Qty'); ?></th>
                                    <th class="text-end"><?php echo __('adjustment', 'Adjustment'); ?></th>
                                    <th class="text-end"><?php echo __('new_quantity', 'New Qty'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                    <td><code><?php echo htmlspecialchars($item['sku']); ?></code></td>
                                    <td class="text-end"><?php echo number_format($item['old_quantity']); ?></td>
                                    <td class="text-end"><?php echo $item['adjustment_quantity'] >= 0 ? '+' : ''; ?><?php echo number_format($item['adjustment_quantity']); ?></td>
                                    <td class="text-end"><strong><?php echo number_format($item['new_quantity']); ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-end">
                        <a href="view.php?id=<?php echo $id; ?>" class="btn btn-secondary me-2"><?php echo __('cancel', 'Cancel'); ?></a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo __('save_changes', 'Save Changes'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
