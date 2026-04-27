<?php
require_once '../../includes/auth.php';
$pageTitle = __('view_sales_return', 'View Sales Return');

// Get sales return details
$returnId = $_GET['id'] ?? 0;
$return = $db->fetchOne("
    SELECT sr.*, c.first_name, c.last_name, c.email as customer_email, w.name as warehouse_name
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

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('view_sales_return', 'View Sales Return'); ?></h1>
    <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> <?php echo __('back_to_list', 'Back to List'); ?>
    </a>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <?php echo __('sales_return_details', 'Sales Return Details'); ?>
                    <span class="badge bg-<?php echo getReturnStatusBadgeClass($return['status']); ?> ms-2">
                        <?php echo __(ucfirst($return['status']), ucfirst($return['status'])); ?>
                    </span>
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr>
                                <td><strong><?php echo __('return_number', 'Return Number'); ?>:</strong></td>
                                <td><?php echo htmlspecialchars($return['return_number'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo __('customer', 'Customer'); ?>:</strong></td>
                                <td>
                                    <a href="mailto:<?php echo htmlspecialchars($return['customer_email']); ?>">
                                        <?php echo htmlspecialchars($return['first_name'] . ' ' . $return['last_name']); ?>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td><strong><?php echo __('warehouse', 'Warehouse'); ?>:</strong></td>
                                <td><?php echo htmlspecialchars($return['warehouse_name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo __('return_date', 'Return Date'); ?>:</strong></td>
                                <td><?php echo date('Y-m-d', strtotime($return['return_date'])); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr>
                                <td><strong><?php echo __('reason', 'Reason'); ?>:</strong></td>
                                <td><?php echo __(ucfirst($return['reason']), ucfirst($return['reason'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo __('status', 'Status'); ?>:</strong></td>
                                <td>
                                    <span class="badge bg-<?php echo getReturnStatusBadgeClass($return['status']); ?>">
                                        <?php echo __(ucfirst($return['status']), ucfirst($return['status'])); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong><?php echo __('created_at', 'Created At'); ?>:</strong></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($return['created_at'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo __('updated_at', 'Updated At'); ?>:</strong></td>
                                <td><?php echo $return['updated_at'] ? date('Y-m-d H:i', strtotime($return['updated_at'])) : 'N/A'; ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <?php if (!empty($return['notes'])): ?>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h6><?php echo __('notes', 'Notes'); ?>:</h6>
                        <p><?php echo nl2br(htmlspecialchars($return['notes'])); ?></p>
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
                    <a href="print.php?id=<?php echo $return['id']; ?>" class="btn btn-secondary" target="_blank">
                        <i class="fas fa-print"></i> <?php echo __('print', 'Print'); ?>
                    </a>
                    <a href="edit.php?id=<?php echo $return['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> <?php echo __('edit', 'Edit'); ?>
                    </a>
                    <?php if ($return['status'] === 'pending'): ?>
                        <a href="javascript:void(0)" onclick="approveReturn(<?php echo $return['id']; ?>)" class="btn btn-success">
                            <i class="fas fa-check"></i> <?php echo __('approve', 'Approve'); ?>
                        </a>
                        <a href="javascript:void(0)" onclick="rejectReturn(<?php echo $return['id']; ?>)" class="btn btn-danger">
                            <i class="fas fa-times"></i> <?php echo __('reject', 'Reject'); ?>
                        </a>
                    <?php endif; ?>
                    <a href="delete.php?id=<?php echo $return['id']; ?>" class="btn btn-danger">
                        <i class="fas fa-trash"></i> <?php echo __('delete', 'Delete'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function approveReturn(returnId) {
    if (confirm('<?php echo __('approve_return_warning', 'Are you sure you want to approve this return?'); ?>')) {
        window.location.href = 'approve.php?id=' + returnId;
    }
}

function rejectReturn(returnId) {
    if (confirm('<?php echo __('reject_return_warning', 'Are you sure you want to reject this return?'); ?>')) {
        window.location.href = 'reject.php?id=' + returnId;
    }
}
</script>

<?php
function getReturnStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'warning';
        case 'approved':
            return 'info';
        case 'rejected':
            return 'danger';
        case 'completed':
            return 'success';
        default:
            return 'secondary';
    }
}
?>

<?php include '../../includes/footer.php'; ?>
