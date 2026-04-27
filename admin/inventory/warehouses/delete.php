<?php
require_once '../../includes/auth.php';
requirePermission('inventory');
$pageTitle = __('delete_warehouse', 'Delete Warehouse');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    try {
        // Check if warehouse has stock
        $hasStock = $db->fetchOne("
            SELECT COUNT(*) as count FROM product_warehouse_stock 
            WHERE warehouse_id = :id AND quantity > 0
        ", ['id' => $id]);
        
        if ($hasStock['count'] > 0) {
            setFlashMessage(__('warehouse_has_stock', 'Cannot delete warehouse with existing stock'), 'danger');
            header('Location: index.php');
            exit;
        }
        
        // Deactivate instead of delete
        $db->update('warehouses', [
            'is_active' => 0
        ], 'id = :id', ['id' => $id]);
        
        setFlashMessage(__('warehouse_deactivated', 'Warehouse deactivated successfully!'), 'success');
        header('Location: index.php');
        exit;
    } catch (Exception $e) {
        setFlashMessage(__('error_deleting_warehouse', 'Error deleting warehouse: ') . $e->getMessage(), 'danger');
        header('Location: index.php');
        exit;
    }
}

$warehouse = $db->fetchOne("SELECT * FROM warehouses WHERE id = :id", ['id' => $id]);

if (!$warehouse) {
    setFlashMessage(__('warehouse_not_found', 'Warehouse not found'), 'danger');
    header('Location: index.php');
    exit;
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('delete_warehouse', 'Delete Warehouse'); ?></h1>
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
                <p><?php echo __('are_you_sure_delete_warehouse', 'Are you sure you want to deactivate this warehouse?'); ?></p>
                <p><strong><?php echo __('name', 'Name'); ?>:</strong> <?php echo htmlspecialchars($warehouse['name']); ?></p>
                <p><strong><?php echo __('code', 'Code'); ?>:</strong> <?php echo htmlspecialchars($warehouse['code']); ?></p>
                
                <div class="alert alert-warning">
                    <?php echo __('warehouse_will_be_deactivated', 'The warehouse will be deactivated instead of deleted to preserve data integrity.'); ?>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="confirm" value="1">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> <?php echo __('deactivate', 'Deactivate'); ?>
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
