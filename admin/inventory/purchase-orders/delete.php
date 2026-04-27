<?php
require_once '../../includes/auth.php';
$pageTitle = __('delete_purchase_order', 'Delete Purchase Order');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    try {
        $db->beginTransaction();
        
        // Delete order items first
        $db->delete('purchase_order_items', 'purchase_order_id = :id', ['id' => $id]);
        
        // Delete purchase order
        $db->delete('purchase_orders', 'id = :id', ['id' => $id]);
        
        $db->commit();
        setFlashMessage(__('purchase_order_deleted', 'Purchase order deleted successfully!'), 'success');
        header('Location: index.php');
        exit;
    } catch (Exception $e) {
        $db->rollback();
        setFlashMessage(__('error_deleting_order', 'Error deleting purchase order: ') . $e->getMessage(), 'danger');
        header('Location: index.php');
        exit;
    }
}

$order = $db->fetchOne("SELECT po.*, s.name as supplier_name FROM purchase_orders po LEFT JOIN suppliers s ON po.supplier_id = s.id WHERE po.id = :id", ['id' => $id]);

if (!$order) {
    setFlashMessage(__('order_not_found', 'Purchase order not found'), 'danger');
    header('Location: index.php');
    exit;
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('delete_purchase_order', 'Delete Purchase Order'); ?></h1>
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
                <p><?php echo __('are_you_sure_delete_order', 'Are you sure you want to delete this purchase order?'); ?></p>
                <p><strong><?php echo __('order_number', 'Order Number'); ?>:</strong> <?php echo htmlspecialchars($order['po_number']); ?></p>
                <p><strong><?php echo __('supplier', 'Supplier'); ?>:</strong> <?php echo htmlspecialchars($order['supplier_name']); ?></p>
                <p><strong><?php echo __('status', 'Status'); ?>:</strong> <?php echo ucfirst($order['status']); ?></p>
                
                <?php if ($order['status'] === 'received'): ?>
                    <div class="alert alert-warning">
                        <?php echo __('warning_received_order', 'Warning: This order has been received. Deleting it will not affect inventory.'); ?>
                    </div>
                <?php endif; ?>
                
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
