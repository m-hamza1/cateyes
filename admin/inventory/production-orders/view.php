<?php
require_once '../../includes/auth.php';
$pageTitle = __('view_production_order', 'View Production Order');

// Get production order details
$orderId = $_GET['id'] ?? 0;
$order = $db->fetchOne("
    SELECT po.*, w.name as warehouse_name, pt.name as product_name, p.sku
    FROM production_orders po
    LEFT JOIN warehouses w ON po.warehouse_id = w.id
    LEFT JOIN products p ON po.product_id = p.id
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang_id
    WHERE po.id = :id
", ['id' => $orderId, 'lang_id' => $lang->getCurrentId()]);

if (!$order) {
    setFlashMessage(__('production_order_not_found', 'Production order not found'), 'error');
    header('Location: index.php');
    exit;
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('view_production_order', 'View Production Order'); ?></h1>
    <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> <?php echo __('back_to_list', 'Back to List'); ?>
    </a>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <?php echo __('production_order_details', 'Production Order Details'); ?>
                    <span class="badge bg-<?php echo getProductionStatusBadgeClass($order['status']); ?> ms-2">
                        <?php echo __(ucfirst($order['status']), ucfirst($order['status'])); ?>
                    </span>
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
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
                                <td><strong><?php echo __('production_date', 'Production Date'); ?>:</strong></td>
                                <td><?php echo date('Y-m-d', strtotime($order['production_date'])); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr>
                                <td><strong><?php echo __('quantity', 'Quantity'); ?>:</strong></td>
                                <td><?php echo number_format($order['quantity']); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo __('produced_quantity', 'Produced Quantity'); ?>:</strong></td>
                                <td><?php echo number_format($order['produced_quantity']); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo __('expected_completion', 'Expected Completion'); ?>:</strong></td>
                                <td><?php echo $order['expected_completion'] ? date('Y-m-d', strtotime($order['expected_completion'])) : 'N/A'; ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo __('status', 'Status'); ?>:</strong></td>
                                <td>
                                    <span class="badge bg-<?php echo getProductionStatusBadgeClass($order['status']); ?>">
                                        <?php echo __(ucfirst($order['status']), ucfirst($order['status'])); ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <?php if (!empty($order['notes'])): ?>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h6><?php echo __('notes', 'Notes'); ?>:</h6>
                        <p><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
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
                <h5 class="card-title mb-0"><?php echo __('actions', 'Actions'); ?></h5>
            </div>
            <div class="card-body">
                <div class="btn-group">
                    <a href="print.php?id=<?php echo $order['id']; ?>" class="btn btn-secondary" target="_blank">
                        <i class="fas fa-print"></i> <?php echo __('print', 'Print'); ?>
                    </a>
                    
                    <a href="edit.php?id=<?php echo $order['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> <?php echo __('edit', 'Edit'); ?>
                    </a>

                    <?php if ($order['status'] === 'pending'): ?>
                        <a href="javascript:void(0)" onclick="startProduction(<?php echo $order['id']; ?>)" class="btn btn-warning">
                            <i class="fas fa-play"></i> <?php echo __('start_production', 'Start Production'); ?>
                        </a>
                    <?php endif; ?>

                    <?php if (in_array($order['status'], ['pending', 'in_progress', 'confirmed'])): ?>
                        <a href="javascript:void(0)" onclick="completeProduction(<?php echo $order['id']; ?>)" class="btn btn-success">
                            <i class="fas fa-check-double"></i> <?php echo __('complete_production', 'Complete Production'); ?>
                        </a>
                        <a href="receive.php?id=<?php echo $order['id']; ?>" class="btn btn-info text-white">
                            <i class="fas fa-plus"></i> <?php echo __('receive_partial', 'Receive Partial'); ?>
                        </a>
                    <?php endif; ?>
                    
                    <a href="delete.php?id=<?php echo $order['id']; ?>" class="btn btn-danger" onclick="return confirm('<?php echo addslashes(__('confirm_delete', 'Are you sure you want to delete this?')); ?>');">
                        <i class="fas fa-trash"></i> <?php echo __('delete', 'Delete'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function startProduction(orderId) {
    if (confirm("<?php echo addslashes(__('start_production_warning', 'Are you sure you want to start production for this order?')); ?>")) {
        window.location.href = 'start-production.php?id=' + orderId + '&auto_start=1';
    }
}

function completeProduction(orderId) {
    if (confirm("<?php echo addslashes(__('complete_production_warning', 'Are you sure you want to complete this production order? This will receive all remaining quantity and update stock instantly.')); ?>")) {
        window.location.href = 'receive.php?id=' + orderId + '&auto_complete=1';
    }
}
</script>

<?php
function getProductionStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'warning';
        case 'in_progress':
            return 'primary';
        case 'completed':
            return 'success';
        case 'cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
}
?>

<?php include '../../includes/footer.php'; ?>
