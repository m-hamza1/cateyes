<?php
require_once '../../includes/auth.php';
$pageTitle = __('delete_supplier', 'Delete Supplier');

// Get supplier details
$supplierId = $_GET['id'] ?? 0;
$supplier = $db->fetchOne("SELECT * FROM suppliers WHERE id = :id", ['id' => $supplierId]);

if (!$supplier) {
    setFlashMessage(__('supplier_not_found', 'Supplier not found'), 'error');
    header('Location: index.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Check if supplier has any purchase orders
        $orderCount = $db->fetchOne("SELECT COUNT(*) as count FROM purchase_orders WHERE supplier_id = :id", ['id' => $supplierId])['count'];
        
        if ($orderCount > 0) {
            setFlashMessage(__('supplier_has_orders', 'Cannot delete supplier with existing purchase orders'), 'error');
            header('Location: index.php');
            exit;
        }
        
        $deleted = $db->delete('suppliers', 'id = :id', ['id' => $supplierId]);
        
        if ($deleted) {
            setFlashMessage(__('supplier_deleted', 'Supplier deleted successfully!'), 'success');
            header('Location: index.php');
            exit;
        } else {
            setFlashMessage(__('delete_failed', 'Failed to delete supplier'), 'error');
            header('Location: index.php');
            exit;
        }
    } catch (Exception $e) {
        setFlashMessage(__('error_deleting_supplier', 'Error deleting supplier: ') . $e->getMessage(), 'error');
        header('Location: index.php');
        exit;
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('delete_supplier', 'Delete Supplier'); ?></h1>
    <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> <?php echo __('back_to_list', 'Back to List'); ?>
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?php echo __('confirm_delete', 'Confirm Delete'); ?></h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <h5><i class="fas fa-exclamation-triangle"></i> <?php echo __('warning', 'Warning'); ?></h5>
                    <p><?php echo __('delete_supplier_warning', 'Are you sure you want to delete this supplier? This action cannot be undone.'); ?></p>
                </div>
                
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0"><?php echo __('supplier_details', 'Supplier Details'); ?></h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <td><strong><?php echo __('name', 'Name'); ?>:</strong></td>
                                <td><?php echo htmlspecialchars($supplier['name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo __('email', 'Email'); ?>:</strong></td>
                                <td><?php echo htmlspecialchars($supplier['email']); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo __('phone', 'Phone'); ?>:</strong></td>
                                <td><?php echo htmlspecialchars($supplier['phone'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo __('contact_person', 'Contact Person'); ?>:</strong></td>
                                <td><?php echo htmlspecialchars($supplier['contact_person'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo __('address', 'Address'); ?>:</strong></td>
                                <td><?php echo nl2br(htmlspecialchars($supplier['address'] ?? 'N/A')); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo __('status', 'Status'); ?>:</strong></td>
                                <td>
                                    <span class="badge bg-<?php echo $supplier['is_active'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $supplier['is_active'] ? __('active', 'Active') : __('inactive', 'Inactive'); ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <form method="POST">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> <?php echo __('delete', 'Delete'); ?>
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> <?php echo __('cancel', 'Cancel'); ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
