<?php
require_once '../../includes/auth.php';
$pageTitle = __('view_stock_adjustment', 'View Stock Adjustment');

// Default currency is handled automatically by the Currency class based on ADMIN_AREA constant

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    setFlashMessage(__('invalid_adjustment_id', 'Invalid adjustment ID'), 'danger');
    header('Location: index.php');
    exit;
}

// Get adjustment details
$adjustment = $db->fetchOne("
    SELECT sa.*, 
           w.name as warehouse_name,
           u.first_name as created_by_first_name,
           u.last_name as created_by_last_name,
           u.email as created_by_email
    FROM stock_adjustments sa
    LEFT JOIN warehouses w ON sa.warehouse_id = w.id
    LEFT JOIN users u ON sa.created_by = u.id
    WHERE sa.id = :id
", ['id' => $id]);

if (!$adjustment) {
    setFlashMessage(__('adjustment_not_found', 'Stock adjustment not found'), 'danger');
    header('Location: index.php');
    exit;
}

// Get adjustment items with product details
$items = $db->fetchAll("
    SELECT sai.*,
           p.sku,
           pt.name as product_name,
        p.cost_price,
           pv.sku as variant_sku,
           pv.sku as variant_name
    FROM stock_adjustment_items sai
    LEFT JOIN products p ON sai.product_id = p.id
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
    LEFT JOIN product_variants pv ON sai.variant_id = pv.id
    WHERE sai.stock_adjustment_id = :adjustment_id
    ORDER BY sai.id
", ['adjustment_id' => $id]);

// Calculate totals
$totalOldQty = 0;
$totalAdjustmentQty = 0;
$totalNewQty = 0;
$totalValueChange = 0;

foreach ($items as $item) {
    $totalOldQty += $item['old_quantity'];
    $totalAdjustmentQty += abs($item['adjustment_quantity']);
    $totalNewQty += $item['new_quantity'];
    $totalValueChange += ($item['new_quantity'] - $item['old_quantity']) * ($item['cost_price'] ?? 0);
}

// Get related inventory transactions
$transactions = $db->fetchAll("
    SELECT it.*,
           p.sku,
           pt.name as product_name
    FROM inventory_transactions it
    LEFT JOIN products p ON it.product_id = p.id
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
    WHERE it.reference_type = 'stock_adjustment' 
    AND it.reference_id = :adjustment_id
    ORDER BY it.created_at
", ['adjustment_id' => $id]);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-clipboard-check"></i> <?php echo __('stock_adjustment_details', 'Stock Adjustment Details'); ?>
    </h1>
    <div class="btn-toolbar">
        <div class="btn-group me-2">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> <?php echo __('back_to_list', 'Back to List'); ?>
            </a>
        </div>
        <div class="btn-group">
            <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-outline-secondary">
                <i class="fas fa-edit"></i> <?php echo __('edit', 'Edit'); ?>
            </a>
            <a href="print.php?id=<?php echo $id; ?>" class="btn btn-outline-primary" target="_blank">
                <i class="fas fa-print"></i> <?php echo __('print', 'Print'); ?>
            </a>
            <a href="delete.php?id=<?php echo $id; ?>" class="btn btn-outline-danger" 
               onclick="return confirm('<?php echo __('confirm_delete_adjustment', 'Are you sure you want to delete this adjustment?'); ?>')">
                <i class="fas fa-trash"></i> <?php echo __('delete', 'Delete'); ?>
            </a>
        </div>
    </div>
</div>

<!-- Adjustment Information -->
<div class="row">
    <div class="col-lg-8">
        <!-- Basic Details Card -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle"></i> <?php echo __('adjustment_information', 'Adjustment Information'); ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="40%"><?php echo __('adjustment_number', 'Adjustment Number'); ?>:</th>
                                <td><strong><?php echo htmlspecialchars($adjustment['adjustment_number']); ?></strong></td>
                            </tr>
                            <tr>
                                <th><?php echo __('warehouse', 'Warehouse'); ?>:</th>
                                <td><?php echo htmlspecialchars($adjustment['warehouse_name']); ?></td>
                            </tr>
                            <tr>
                                <th><?php echo __('adjustment_date', 'Adjustment Date'); ?>:</th>
                                <td><?php echo date('F j, Y', strtotime($adjustment['adjustment_date'])); ?></td>
                            </tr>
                            <tr>
                                <th><?php echo __('adjustment_type', 'Adjustment Type'); ?>:</th>
                                <td>
                                    <?php
                                    $typeLabels = [
                                        'add' => ['label' => __('add_stock', 'Add Stock'), 'class' => 'success'],
                                        'subtract' => ['label' => __('subtract_stock', 'Subtract Stock'), 'class' => 'warning'],
                                        'set' => ['label' => __('set_stock', 'Set Stock'), 'class' => 'info']
                                    ];
                                    $type = $typeLabels[$adjustment['adjustment_type']] ?? ['label' => ucfirst($adjustment['adjustment_type']), 'class' => 'secondary'];
                                    ?>
                                    <span class="badge bg-<?php echo $type['class']; ?>">
                                        <?php echo $type['label']; ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="40%"><?php echo __('created_by', 'Created By'); ?>:</th>
                                <td>
                                    <?php echo htmlspecialchars($adjustment['created_by_first_name'] . ' ' . $adjustment['created_by_last_name']); ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($adjustment['created_by_email']); ?></small>
                                </td>
                            </tr>
                            <tr>
                                <th><?php echo __('created_at', 'Created At'); ?>:</th>
                                <td><?php echo date('F j, Y \a\t g:i A', strtotime($adjustment['created_at'])); ?></td>
                            </tr>
                            <tr>
                                <th><?php echo __('total_items', 'Total Items'); ?>:</th>
                                <td><span class="badge bg-info"><?php echo count($items); ?></span></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <?php if (!empty($adjustment['reason'])): ?>
                <hr>
                <div>
                    <strong><?php echo __('reason', 'Reason'); ?>:</strong>
                    <p class="mt-2 text-muted"><?php echo nl2br(htmlspecialchars($adjustment['reason'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Adjustment Items Card -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list"></i> <?php echo __('adjustment_items', 'Adjustment Items'); ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($items)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th><?php echo __('product', 'Product'); ?></th>
                                    <th><?php echo __('sku', 'SKU'); ?></th>
                                    <th class="text-end"><?php echo __('old_quantity', 'Old Qty'); ?></th>
                                    <th class="text-end"><?php echo __('adjustment', 'Adjustment'); ?></th>
                                    <th class="text-end"><?php echo __('new_quantity', 'New Qty'); ?></th>
                                    <th class="text-end"><?php echo __('cost_price', 'Cost Price'); ?></th>
                                    <th class="text-end"><?php echo __('value_change', 'Value Change'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $rowNum = 1;
                                foreach ($items as $item): 
                                    $valueChange = ($item['new_quantity'] - $item['old_quantity']) * ($item['cost_price'] ?? 0);
                                    $adjustmentChange = $item['adjustment_quantity'];
                                ?>
                                <tr>
                                    <td><?php echo $rowNum++; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                        <?php if ($item['variant_name']): ?>
                                            <br><small class="text-muted">Variant: <?php echo htmlspecialchars($item['variant_name']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <code><?php echo htmlspecialchars($item['sku']); ?></code>
                                        <?php if ($item['variant_sku']): ?>
                                            <br><small><code><?php echo htmlspecialchars($item['variant_sku']); ?></code></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end"><?php echo number_format($item['old_quantity']); ?></td>
                                    <td class="text-end">
                                        <?php if ($adjustmentChange > 0): ?>
                                            <span class="text-success"><i class="fas fa-arrow-up"></i> +<?php echo number_format($adjustmentChange); ?></span>
                                        <?php elseif ($adjustmentChange < 0): ?>
                                            <span class="text-danger"><i class="fas fa-arrow-down"></i> <?php echo number_format($adjustmentChange); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end"><strong><?php echo number_format($item['new_quantity']); ?></strong></td>
                                    <td class="text-end"><?php echo $currency->format($item['cost_price'] ?? 0); ?></td>
                                    <td class="text-end">
                                        <?php if ($valueChange > 0): ?>
                                            <span class="text-success">+<?php echo $currency->format($valueChange); ?></span>
                                        <?php elseif ($valueChange < 0): ?>
                                            <span class="text-danger"><?php echo $currency->format($valueChange); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted"><?php echo $currency->format(0); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="3" class="text-end"><?php echo __('total', 'Total'); ?>:</th>
                                    <th class="text-end"><?php echo number_format($totalOldQty); ?></th>
                                    <th class="text-end"></th>
                                    <th class="text-end"><?php echo number_format($totalNewQty); ?></th>
                                    <th class="text-end"></th>
                                    <th class="text-end">
                                        <?php if ($totalValueChange > 0): ?>
                                            <span class="text-success">+<?php echo $currency->format($totalValueChange); ?></span>
                                        <?php elseif ($totalValueChange < 0): ?>
                                            <span class="text-danger"><?php echo $currency->format($totalValueChange); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted"><?php echo $currency->format(0); ?></span>
                                        <?php endif; ?>
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> <?php echo __('no_items_found', 'No items found for this adjustment'); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Inventory Transactions Card -->
        <?php if (!empty($transactions)): ?>
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-exchange-alt"></i> <?php echo __('inventory_transactions', 'Inventory Transactions'); ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="table-light">
                            <tr>
                                <th><?php echo __('date_time', 'Date/Time'); ?></th>
                                <th><?php echo __('product', 'Product'); ?></th>
                                <th><?php echo __('type', 'Type'); ?></th>
                                <th class="text-end"><?php echo __('quantity', 'Quantity'); ?></th>
                                <th class="text-end"><?php echo __('previous', 'Previous'); ?></th>
                                <th class="text-end"><?php echo __('new', 'New'); ?></th>
                                <th><?php echo __('notes', 'Notes'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><small><?php echo date('Y-m-d H:i', strtotime($transaction['created_at'])); ?></small></td>
                                <td>
                                    <small>
                                        <?php echo htmlspecialchars($transaction['product_name']); ?>
                                        <br><code><?php echo htmlspecialchars($transaction['sku']); ?></code>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-primary"><?php echo $transaction['transaction_type']; ?></span>
                                </td>
                                <td class="text-end">
                                    <?php if ($transaction['quantity'] > 0): ?>
                                        <span class="text-success">+<?php echo number_format($transaction['quantity']); ?></span>
                                    <?php else: ?>
                                        <span class="text-danger"><?php echo number_format($transaction['quantity']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end"><?php echo number_format($transaction['previous_quantity']); ?></td>
                                <td class="text-end"><strong><?php echo number_format($transaction['new_quantity']); ?></strong></td>
                                <td><small class="text-muted"><?php echo htmlspecialchars($transaction['notes']); ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar Summary -->
    <div class="col-lg-4">
        <!-- Summary Card -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-bar"></i> <?php echo __('summary', 'Summary'); ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted"><?php echo __('total_items_adjusted', 'Total Items Adjusted'); ?></small>
                    <h3 class="mb-0"><?php echo count($items); ?></h3>
                </div>
                <hr>
                <div class="mb-3">
                    <small class="text-muted"><?php echo __('total_quantity_change', 'Total Quantity Change'); ?></small>
                    <h4 class="mb-0">
                        <?php
                        $totalChange = $totalNewQty - $totalOldQty;
                        if ($totalChange > 0): ?>
                            <span class="text-success"><i class="fas fa-arrow-up"></i> +<?php echo number_format($totalChange); ?></span>
                        <?php elseif ($totalChange < 0): ?>
                            <span class="text-danger"><i class="fas fa-arrow-down"></i> <?php echo number_format($totalChange); ?></span>
                        <?php else: ?>
                            <span class="text-muted">0</span>
                        <?php endif; ?>
                    </h4>
                </div>
                <hr>
                <div class="mb-3">
                    <small class="text-muted"><?php echo __('total_value_impact', 'Total Value Impact'); ?></small>
                    <h4 class="mb-0">
                        <?php if ($totalValueChange > 0): ?>
                            <span class="text-success">+<?php echo $currency->format($totalValueChange); ?></span>
                        <?php elseif ($totalValueChange < 0): ?>
                            <span class="text-danger"><?php echo $currency->format($totalValueChange); ?></span>
                        <?php else: ?>
                            <span class="text-muted"><?php echo $currency->format(0); ?></span>
                        <?php endif; ?>
                    </h4>
                </div>
                <hr>
                <div class="mb-3">
                    <small class="text-muted"><?php echo __('old_total_quantity', 'Old Total Quantity'); ?></small>
                    <h5 class="mb-0"><?php echo number_format($totalOldQty); ?></h5>
                </div>
                <div class="mb-3">
                    <small class="text-muted"><?php echo __('new_total_quantity', 'New Total Quantity'); ?></small>
                    <h5 class="mb-0 text-primary"><?php echo number_format($totalNewQty); ?></h5>
                </div>
            </div>
        </div>

        <!-- Quick Actions Card -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-bolt"></i> <?php echo __('quick_actions', 'Quick Actions'); ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-outline-primary">
                        <i class="fas fa-edit"></i> <?php echo __('edit_adjustment', 'Edit Adjustment'); ?>
                    </a>
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> <?php echo __('create_new_adjustment', 'Create New Adjustment'); ?>
                    </a>
                    <a href="../movements.php?reference_type=stock_adjustment&reference_id=<?php echo $id; ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-list"></i> <?php echo __('view_movements', 'View Related Movements'); ?>
                    </a>
                    <a href="../inventory-report.php?warehouse_id=<?php echo $adjustment['warehouse_id']; ?>" class="btn btn-outline-info">
                        <i class="fas fa-chart-line"></i> <?php echo __('warehouse_report', 'Warehouse Report'); ?>
                    </a>
                    <hr>
                    <a href="print.php?id=<?php echo $id; ?>" class="btn btn-outline-primary" target="_blank">
                        <i class="fas fa-print"></i> <?php echo __('print_details', 'Print Details'); ?>
                    </a>
                    <a href="delete.php?id=<?php echo $id; ?>" class="btn btn-outline-danger" 
                       onclick="return confirm('<?php echo __('confirm_delete_adjustment', 'Are you sure you want to delete this adjustment?'); ?>')">
                        <i class="fas fa-trash"></i> <?php echo __('delete_adjustment', 'Delete Adjustment'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .btn-toolbar, .btn-group, .card:last-child, nav, .sidebar {
        display: none !important;
    }
    .card {
        border: 1px solid #ddd !important;
        page-break-inside: avoid;
    }
}
</style>

<?php include '../../includes/footer.php'; ?>