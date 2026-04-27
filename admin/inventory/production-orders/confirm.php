<?php
require_once '../../includes/auth.php';
$pageTitle = __('confirm_production_order', 'Confirm Production Order');

// Get production order details
$orderId = $_GET['id'] ?? 0;
$order = $db->fetchOne("
    SELECT po.*, w.name as warehouse_name, pt.name as product_name, p.sku
    FROM production_orders po
    LEFT JOIN warehouses w ON po.warehouse_id = w.id
    LEFT JOIN products p ON po.product_id = p.id
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang_id
    WHERE po.id = :id AND po.status = 'pending'
", ['id' => $orderId, 'lang_id' => $lang->getCurrentId()]);

if (!$order) {
    setFlashMessage(__('production_order_not_found_or_confirmed', 'Production order not found or already confirmed'), 'error');
    header('Location: view.php?id=' . $orderId);
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $updated = $db->update('production_orders', [
            'status' => 'confirmed',
            'confirmed_at' => date('Y-m-d H:i:s'),
            'confirmed_by' => $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null
        ], 'id = :id', ['id' => $orderId]);
        
        if (!$updated) {
            throw new Exception(__('failed_to_confirm_order', 'Failed to confirm production order'));
        }
        
        setFlashMessage(__('production_order_confirmed', 'Production order confirmed successfully!'), 'success');
        header('Location: view.php?id=' . $orderId);
        exit;
        
    } catch (Exception $e) {
        setFlashMessage(__('error_confirming_order', 'Error confirming production order: ') . $e->getMessage(), 'error');
        header('Location: confirm.php?id=' . $orderId);
        exit;
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('confirm_production_order', 'Confirm Production Order'); ?></h1>
    <a href="view.php?id=<?php echo $orderId; ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> <?php echo __('back_to_order', 'Back to Order'); ?>
    </a>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?php echo __('order_details', 'Order Details'); ?></h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td><strong><?php echo __('production_number', 'Production Number'); ?>:</strong></td>
                        <td><?php echo htmlspecialchars($order['production_number'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo __('product', 'Product'); ?>:</strong></td>
                        <td>
                            <a href="../../products/edit.php?id=<?php echo $order['product_id']; ?>" target="_blank">
                                <?php echo htmlspecialchars($order['product_name'] ?? 'N/A'); ?>
                            </a>
                            <?php if (!empty($order['sku'])): ?>
                                <br><small><?php echo __('sku', 'SKU'); ?>: <?php echo htmlspecialchars($order['sku']); ?></small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong><?php echo __('warehouse', 'Warehouse'); ?>:</strong></td>
                        <td><?php echo htmlspecialchars($order['warehouse_name']); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo __('quantity', 'Quantity'); ?>:</strong></td>
                        <td><?php echo number_format($order['quantity']); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo __('production_date', 'Production Date'); ?>:</strong></td>
                        <td><?php echo date('Y-m-d', strtotime($order['production_date'])); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo __('expected_completion', 'Expected Completion'); ?>:</strong></td>
                        <td><?php echo $order['expected_completion'] ? date('Y-m-d', strtotime($order['expected_completion'])) : 'N/A'; ?></td>
                    </tr>
                </table>
                
                <?php if (!empty($order['notes'])): ?>
                <div class="mt-3">
                    <h6><?php echo __('notes', 'Notes'); ?>:</h6>
                    <p><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?php echo __('confirmation', 'Confirmation'); ?></h5>
            </div>
            <div class="card-body">
                <form method="POST" onsubmit="return confirmSubmission();">
                    <div class="mb-3">
                        <label for="confirmation_notes" class="form-label"><?php echo __('confirmation_notes', 'Confirmation Notes'); ?></label>
                        <textarea class="form-control" id="confirmation_notes" name="confirmation_notes" rows="4" 
                                  placeholder="<?php echo __('add_any_confirmation_notes', 'Add any confirmation notes here...'); ?>"></textarea>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check"></i> <?php echo __('confirm_order', 'Confirm Order'); ?>
                        </button>
                        <a href="view.php?id=<?php echo $orderId; ?>" class="btn btn-secondary">
                            <i class="fas fa-times"></i> <?php echo __('cancel', 'Cancel'); ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmSubmission() {
    return confirm('<?php echo __('confirm_production_order_warning', 'Are you sure you want to confirm this production order? This action cannot be undone.'); ?>');
}
</script>

<?php include '../../includes/footer.php'; ?>
