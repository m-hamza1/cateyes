<?php
require_once '../../includes/auth.php';
require_once '../inventory_helper.php';
$pageTitle = __('view_stock_transfer', 'View Stock Transfer');

// Handle complete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete' && isset($_POST['transfer_id'])) {
    $tid = (int)$_POST['transfer_id'];
    try {
        processStockTransfer($db, $tid, $_SESSION['admin_id'] ?? $_SESSION['user_id']);
        setFlashMessage(__('transfer_completed', 'Stock transfer completed successfully!'), 'success');
        header('Location: view.php?id=' . $tid);
        exit;
    } catch (Exception $e) {
        setFlashMessage(__('error_completing_transfer', 'Error: ') . $e->getMessage(), 'danger');
        header('Location: view.php?id=' . $tid);
        exit;
    }
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    setFlashMessage(__('invalid_transfer_id', 'Invalid transfer ID'), 'danger');
    header('Location: index.php');
    exit;
}

// Get transfer details
$transfer = $db->fetchOne("
    SELECT st.*, 
           w1.name as from_warehouse_name,
           w2.name as to_warehouse_name,
           u.first_name, u.last_name, u.email
    FROM stock_transfers st
    LEFT JOIN warehouses w1 ON st.from_warehouse_id = w1.id
    LEFT JOIN warehouses w2 ON st.to_warehouse_id = w2.id
    LEFT JOIN users u ON st.created_by = u.id
    WHERE st.id = :id
", ['id' => $id]);

if (!$transfer) {
    setFlashMessage(__('transfer_not_found', 'Stock transfer not found'), 'danger');
    header('Location: index.php');
    exit;
}

// Get transfer items with product details
$items = $db->fetchAll("
    SELECT sti.*, p.sku, pt.name as product_name
    FROM stock_transfer_items sti
    LEFT JOIN products p ON sti.product_id = p.id
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
    WHERE sti.stock_transfer_id = :transfer_id
    ORDER BY sti.id
", ['transfer_id' => $id]);

$totalQuantity = array_sum(array_column($items, 'quantity'));

function getTransferStatusBadge($status) {
    switch ($status) {
        case 'completed': return 'success';
        case 'cancelled': return 'danger';
        case 'in_transit': return 'info';
        default: return 'warning';
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-exchange-alt"></i> <?php echo __('stock_transfer_details', 'Stock Transfer Details'); ?></h1>
    <div class="btn-toolbar">
        <a href="index.php" class="btn btn-secondary me-2">
            <i class="fas fa-arrow-left"></i> <?php echo __('back_to_list', 'Back to List'); ?>
        </a>
        <?php if ($transfer['status'] === 'pending'): ?>
        <form method="POST" style="display:inline;" onsubmit="return confirm('<?php echo __("confirm_complete_transfer", "Are you sure you want to complete this transfer? This will move stock between warehouses."); ?>');">
            <input type="hidden" name="action" value="complete">
            <input type="hidden" name="transfer_id" value="<?php echo $id; ?>">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-check"></i> <?php echo __('complete_transfer', 'Complete Transfer'); ?>
            </button>
        </form>
        <?php endif; ?>
        <a href="print.php?id=<?php echo $id; ?>" class="btn btn-outline-primary ms-2" target="_blank">
            <i class="fas fa-print"></i> <?php echo __('print', 'Print'); ?>
        </a>
        <a href="delete.php?id=<?php echo $id; ?>" class="btn btn-outline-danger ms-2" 
           onclick="return confirm('<?php echo __("confirm_delete_transfer", "Are you sure you want to delete this transfer?"); ?>')"
           <?php echo $transfer['status'] === 'completed' ? 'style="display:none"' : ''; ?>>
            <i class="fas fa-trash"></i> <?php echo __('delete', 'Delete'); ?>
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle"></i> <?php echo __('transfer_information', 'Transfer Information'); ?>
                    <span class="badge bg-light text-dark ms-2"><?php echo ucfirst(str_replace('_', ' ', $transfer['status'])); ?></span>
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="40%"><?php echo __('transfer_number', 'Transfer Number'); ?>:</th>
                                <td><strong><?php echo htmlspecialchars($transfer['transfer_number']); ?></strong></td>
                            </tr>
                            <tr>
                                <th><?php echo __('from_warehouse', 'From Warehouse'); ?>:</th>
                                <td><?php echo htmlspecialchars($transfer['from_warehouse_name']); ?></td>
                            </tr>
                            <tr>
                                <th><?php echo __('to_warehouse', 'To Warehouse'); ?>:</th>
                                <td><?php echo htmlspecialchars($transfer['to_warehouse_name']); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="40%"><?php echo __('transfer_date', 'Transfer Date'); ?>:</th>
                                <td><?php echo date('Y-m-d', strtotime($transfer['transfer_date'])); ?></td>
                            </tr>
                            <tr>
                                <th><?php echo __('created_by', 'Created By'); ?>:</th>
                                <td><?php echo htmlspecialchars(trim(($transfer['first_name'] ?? '') . ' ' . ($transfer['last_name'] ?? '')) ?: 'System'); ?></td>
                            </tr>
                            <tr>
                                <th><?php echo __('created_at', 'Created At'); ?>:</th>
                                <td><?php echo date('Y-m-d H:i', strtotime($transfer['created_at'])); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <?php if (!empty($transfer['notes'])): ?>
                <hr>
                <div>
                    <strong><?php echo __('notes', 'Notes'); ?>:</strong>
                    <p class="mt-2 text-muted mb-0"><?php echo nl2br(htmlspecialchars($transfer['notes'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="fas fa-list"></i> <?php echo __('transfer_items', 'Transfer Items'); ?></h5>
            </div>
            <div class="card-body">
                <?php if (!empty($items)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th><?php echo __('product', 'Product'); ?></th>
                                <th><?php echo __('sku', 'SKU'); ?></th>
                                <th class="text-end"><?php echo __('quantity', 'Quantity'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $i => $item): ?>
                            <tr>
                                <td><?php echo $i + 1; ?></td>
                                <td><?php echo htmlspecialchars($item['product_name'] ?? 'N/A'); ?></td>
                                <td><code><?php echo htmlspecialchars($item['sku'] ?? 'N/A'); ?></code></td>
                                <td class="text-end"><?php echo number_format($item['quantity']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td colspan="3" class="text-end"><?php echo __('total', 'Total'); ?>:</td>
                                <td class="text-end"><?php echo number_format($totalQuantity); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted mb-0"><?php echo __('no_items', 'No items in this transfer'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-bolt"></i> <?php echo __('quick_actions', 'Quick Actions'); ?></h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="add.php" class="btn btn-primary"><i class="fas fa-plus"></i> <?php echo __('create_new_transfer', 'Create New Transfer'); ?></a>
                    <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-outline-secondary"><?php echo $transfer['status'] === 'pending' ? '<i class="fas fa-edit"></i> ' . __('edit', 'Edit') : ''; ?></a>
                    <a href="../movements.php?reference_type=stock_transfer&reference_id=<?php echo $id; ?>" class="btn btn-outline-info"><i class="fas fa-history"></i> <?php echo __('view_movements', 'View Movements'); ?></a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
