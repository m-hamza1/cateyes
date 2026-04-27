<?php
require_once '../../includes/auth.php';
$pageTitle = __('delete_stock_transfer', 'Delete Stock Transfer');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    try {
        $transfer = $db->fetchOne("SELECT status FROM stock_transfers WHERE id = :id", ['id' => $id]);
        
        if ($transfer && $transfer['status'] === 'completed') {
            setFlashMessage(__('cannot_delete_completed_transfer', 'Cannot delete completed transfer'), 'danger');
            header('Location: index.php');
            exit;
        }
        
        $db->beginTransaction();
        
        // Delete transfer items
        $db->delete('stock_transfer_items', 'stock_transfer_id = :id', ['id' => $id]);
        
        // Delete transfer
        $db->delete('stock_transfers', 'id = :id', ['id' => $id]);
        
        $db->commit();
        setFlashMessage(__('transfer_deleted', 'Stock transfer deleted successfully!'), 'success');
        header('Location: index.php');
        exit;
    } catch (Exception $e) {
        $db->rollback();
        setFlashMessage(__('error_deleting_transfer', 'Error deleting stock transfer: ') . $e->getMessage(), 'danger');
        header('Location: index.php');
        exit;
    }
}

$transfer = $db->fetchOne("
    SELECT st.*, w1.name as from_warehouse_name, w2.name as to_warehouse_name
    FROM stock_transfers st
    LEFT JOIN warehouses w1 ON st.from_warehouse_id = w1.id
    LEFT JOIN warehouses w2 ON st.to_warehouse_id = w2.id
    WHERE st.id = :id
", ['id' => $id]);

if (!$transfer) {
    setFlashMessage(__('transfer_not_found', 'Stock transfer not found'), 'danger');
    header('Location: index.php');
    exit;
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('delete_stock_transfer', 'Delete Stock Transfer'); ?></h1>
    <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> <?php echo __('back_to_list', 'Back to List'); ?>
    </a>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h5 class="card-title mb-0"><?php echo __('confirm_delete', 'Confirm Delete'); ?></h5>
            </div>
            <div class="card-body">
                <p><?php echo __('are_you_sure_delete_transfer', 'Are you sure you want to delete this stock transfer?'); ?></p>
                <p><strong><?php echo __('transfer_number', 'Transfer Number'); ?>:</strong> <?php echo htmlspecialchars($transfer['transfer_number']); ?></p>
                <p><strong><?php echo __('from_warehouse', 'From Warehouse'); ?>:</strong> <?php echo htmlspecialchars($transfer['from_warehouse_name']); ?></p>
                <p><strong><?php echo __('to_warehouse', 'To Warehouse'); ?>:</strong> <?php echo htmlspecialchars($transfer['to_warehouse_name']); ?></p>
                <p><strong><?php echo __('status', 'Status'); ?>:</strong> <?php echo ucfirst(str_replace('_', ' ', $transfer['status'])); ?></p>
                
                <?php if ($transfer['status'] === 'completed'): ?>
                    <div class="alert alert-danger">
                        <?php echo __('cannot_delete_completed_transfer', 'Cannot delete completed transfer'); ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <?php echo __('warning_delete_transfer', 'Warning: This action cannot be undone.'); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <input type="hidden" name="confirm" value="1">
                    <?php if ($transfer['status'] !== 'completed'): ?>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> <?php echo __('delete', 'Delete'); ?>
                        </button>
                    <?php endif; ?>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> <?php echo __('cancel', 'Cancel'); ?>
                    </a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
