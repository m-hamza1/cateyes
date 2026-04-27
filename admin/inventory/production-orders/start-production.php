<?php
require_once '../../includes/auth.php';
$pageTitle = __('start_production', 'Start Production');

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

// Check if order can be started (must be pending)
if ($order['status'] !== 'pending') {
    setFlashMessage(__('order_cannot_be_started', 'This order cannot be started. Status must be pending.'), 'error');
    header('Location: view.php?id=' . $orderId);
    exit;
}

// Auto-start logic
if (isset($_GET['auto_start']) && $_GET['auto_start'] == 1) {
    try {
        $updated = $db->update('production_orders', [
            'status' => 'in_progress'
        ], 'id = :id', ['id' => $orderId]);
        
        if (!$updated) {
            // If it was already in_progress, it might return 0, but that's fine. 
            // We just ensure it's in_progress.
        }
        
        setFlashMessage(__('production_started', 'Production started successfully!'), 'success');
        header('Location: view.php?id=' . $orderId);
        exit;
        
    } catch (Exception $e) {
        setFlashMessage(__('error_starting_production', 'Error starting production: ') . $e->getMessage(), 'error');
        header('Location: view.php?id=' . $orderId);
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $updated = $db->update('production_orders', [
            'status' => 'in_progress'
        ], 'id = :id', ['id' => $orderId]);
        
        if (!$updated) {
            throw new Exception(__('failed_to_start_production', 'Failed to start production'));
        }
        
        setFlashMessage(__('production_started', 'Production started successfully!'), 'success');
        header('Location: view.php?id=' . $orderId);
        exit;
        
    } catch (Exception $e) {
        setFlashMessage(__('error_starting_production', 'Error starting production: ') . $e->getMessage(), 'error');
        header('Location: start-production.php?id=' . $orderId);
        exit;
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('start_production', 'Start Production'); ?></h1>
    <a href="view.php?id=<?php echo $orderId; ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> <?php echo __('back_to_order', 'Back to Order'); ?>
    </a>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?php echo __('production_order_details', 'Production Order Details'); ?></h5>
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
                        </td>
                    </tr>
                    <tr>
                        <td><strong><?php echo __('sku', 'SKU'); ?>:</strong></td>
                        <td><?php echo htmlspecialchars($order['sku'] ?? 'N/A'); ?></td>
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
                        <td><strong><?php echo __('produced_quantity', 'Produced Quantity'); ?>:</strong></td>
                        <td><?php echo number_format($order['produced_quantity']); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo __('production_date', 'Production Date'); ?>:</strong></td>
                        <td><?php echo date('Y-m-d', strtotime($order['production_date'])); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo __('expected_completion', 'Expected Completion'); ?>:</strong></td>
                        <td><?php echo $order['expected_completion'] ? date('Y-m-d', strtotime($order['expected_completion'])) : 'N/A'; ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo __('status', 'Status'); ?>:</strong></td>
                        <td>
                            <span class="badge bg-warning">
                                <?php echo __('pending', 'Pending'); ?>
                            </span>
                        </td>
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
                <h5 class="card-title mb-0"><?php echo __('start_production', 'Start Production'); ?></h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <p class="mb-0"><?php echo __('start_production_info', 'Starting production will change the order status to "In Progress". You can track production progress and record produced quantities from the order view page.'); ?></p>
                </div>
                
                <form method="POST" onsubmit="return confirmSubmission();">
                    <div class="mb-3">
                        <label for="start_notes" class="form-label"><?php echo __('start_notes', 'Start Notes'); ?></label>
                        <textarea class="form-control" id="start_notes" name="start_notes" rows="4" 
                                  placeholder="<?php echo __('add_any_start_notes', 'Add any notes about starting production...'); ?>"></textarea>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-play"></i> <?php echo __('start_production', 'Start Production'); ?>
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
    return confirm('<?php echo __('start_production_warning', 'Are you sure you want to start production for this order? This will change the order status to "In Progress".'); ?>');
}
</script>

<?php include '../../includes/footer.php'; ?>
