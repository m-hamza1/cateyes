<?php
require_once '../../includes/auth.php';
$pageTitle = __('delete_production_order', 'Delete Production Order');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    try {
        $order = $db->fetchOne("SELECT status FROM production_orders WHERE id = :id", ['id' => $id]);
        
        if ($order && $order['status'] === 'completed') {
            setFlashMessage(__('cannot_delete_completed_order', 'Cannot delete completed production order'), 'danger');
            header('Location: index.php');
            exit;
        }
        
        $db->delete('production_orders', 'id = :id', ['id' => $id]);
        
        setFlashMessage(__('production_order_deleted', 'Production order deleted successfully!'), 'success');
        header('Location: index.php');
        exit;
    } catch (Exception $e) {
        setFlashMessage(__('error_deleting_order', 'Error deleting production order: ') . $e->getMessage(), 'danger');
        header('Location: index.php');
        exit;
    }
}

$order = $db->fetchOne("
    SELECT po.*, pt.name as product_name, w.name as warehouse_name
    FROM production_orders po
    LEFT JOIN products p ON po.product_id = p.id
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang_id
    LEFT JOIN warehouses w ON po.warehouse_id = w.id
    WHERE po.id = :id
", ['id' => $id, 'lang_id' => $lang->getCurrentId()]);

if (!$order) {
    setFlashMessage(__('order_not_found', 'Production order not found'), 'danger');
    header('Location: index.php');
    exit;
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('delete_production_order', 'Delete Production Order'); ?></h1>
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
                <p><?php echo __('are_you_sure_delete_production_order', 'Are you sure you want to delete this production order?'); ?></p>
                <p><strong><?php echo __('production_number', 'Production Number'); ?>:</strong> <?php echo htmlspecialchars($order['production_number']); ?></p>
                <p><strong><?php echo __('product', 'Product'); ?>:</strong> <?php echo htmlspecialchars($order['product_name'] ?? 'N/A'); ?></p>
                <p><strong><?php echo __('warehouse', 'Warehouse'); ?>:</strong> <?php echo htmlspecialchars($order['warehouse_name'] ?? 'N/A'); ?></p>
                <p><strong><?php echo __('status', 'Status'); ?>:</strong> <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?></p>
                
                <?php if ($order['status'] === 'completed'): ?>
                    <div class="alert alert-danger">
                        <?php echo __('cannot_delete_completed_order', 'Cannot delete completed production order'); ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <?php echo __('warning_delete_order', 'Warning: This action cannot be undone.'); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <input type="hidden" name="confirm" value="1">
                    <?php if ($order['status'] !== 'completed'): ?>
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
