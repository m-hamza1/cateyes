<?php
require_once '../../includes/auth.php';
$pageTitle = __('delete_stock_adjustment', 'Delete Stock Adjustment');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    try {
        $db->beginTransaction();
        
        // Delete adjustment items
        $db->delete('stock_adjustment_items', 'stock_adjustment_id = :id', ['id' => $id]);
        
        // Delete adjustment
        $db->delete('stock_adjustments', 'id = :id', ['id' => $id]);
        
        $db->commit();
        setFlashMessage(__('adjustment_deleted', 'Stock adjustment deleted successfully!'), 'success');
        header('Location: index.php');
        exit;
    } catch (Exception $e) {
        $db->rollback();
        setFlashMessage(__('error_deleting_adjustment', 'Error deleting stock adjustment: ') . $e->getMessage(), 'danger');
        header('Location: index.php');
        exit;
    }
}

$adjustment = $db->fetchOne("SELECT sa.*, w.name as warehouse_name FROM stock_adjustments sa LEFT JOIN warehouses w ON sa.warehouse_id = w.id WHERE sa.id = :id", ['id' => $id]);

if (!$adjustment) {
    setFlashMessage(__('adjustment_not_found', 'Stock adjustment not found'), 'danger');
    header('Location: index.php');
    exit;
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('delete_stock_adjustment', 'Delete Stock Adjustment'); ?></h1>
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
                <p><?php echo __('are_you_sure_delete_adjustment', 'Are you sure you want to delete this stock adjustment?'); ?></p>
                <p><strong><?php echo __('adjustment_number', 'Adjustment Number'); ?>:</strong> <?php echo htmlspecialchars($adjustment['adjustment_number']); ?></p>
                <p><strong><?php echo __('warehouse', 'Warehouse'); ?>:</strong> <?php echo htmlspecialchars($adjustment['warehouse_name']); ?></p>
                <p><strong><?php echo __('adjustment_type', 'Type'); ?>:</strong> <?php echo ucfirst($adjustment['adjustment_type']); ?></p>
                
                <div class="alert alert-warning">
                    <?php echo __('warning_delete_adjustment', 'Warning: Deleting this adjustment will not reverse inventory changes.'); ?>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="confirm" value="1">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> <?php echo __('delete', 'Delete'); ?>
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> <?php echo __('cancel', 'Cancel'); ?>
                    </a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
