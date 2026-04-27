<?php
require_once '../../includes/auth.php';
$pageTitle = __('delete_supplier_return', 'Delete Supplier Return');

// Get supplier return details
$returnId = $_GET['id'] ?? 0;
$return = $db->fetchOne("
    SELECT sr.*, s.name as supplier_name 
    FROM supplier_returns sr
    LEFT JOIN suppliers s ON sr.supplier_id = s.id
    WHERE sr.id = :id
", ['id' => $returnId]);

if (!$return) {
    setFlashMessage(__('supplier_return_not_found', 'Supplier return not found'), 'error');
    header('Location: index.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        // Delete supplier return items first
        $db->delete('supplier_return_items', 'supplier_return_id = :id', ['id' => $returnId]);
        
        // Delete the supplier return
        $deleted = $db->delete('supplier_returns', 'id = :id', ['id' => $returnId]);
        
        if ($deleted) {
            $db->commit();
            setFlashMessage(__('supplier_return_deleted', 'Supplier return deleted successfully!'), 'success');
            header('Location: index.php');
            exit;
        } else {
            $db->rollback();
            setFlashMessage(__('delete_failed', 'Failed to delete supplier return'), 'error');
            header('Location: index.php');
            exit;
        }
    } catch (Exception $e) {
        $db->rollback();
        setFlashMessage(__('error_deleting_supplier_return', 'Error deleting supplier return: ') . $e->getMessage(), 'error');
        header('Location: index.php');
        exit;
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('delete_supplier_return', 'Delete Supplier Return'); ?></h1>
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
                    <p><?php echo __('delete_supplier_return_warning', 'Are you sure you want to delete this supplier return? This action cannot be undone and will also delete all associated items.'); ?></p>
                </div>
                
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0"><?php echo __('supplier_return_details', 'Supplier Return Details'); ?></h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <td><strong><?php echo __('return_number', 'Return Number'); ?>:</strong></td>
                                <td><?php echo htmlspecialchars($return['return_number'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo __('supplier', 'Supplier'); ?>:</strong></td>
                                <td><?php echo htmlspecialchars($return['supplier_name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo __('return_date', 'Return Date'); ?>:</strong></td>
                                <td><?php echo date('Y-m-d', strtotime($return['return_date'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo __('total_amount', 'Total Amount'); ?>:</strong></td>
                                <td><?php echo $currency->format($return['total_amount']); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo __('status', 'Status'); ?>:</strong></td>
                                <td>
                                    <span class="badge bg-<?php echo getReturnStatusBadgeClass($return['status']); ?>">
                                        <?php echo __(ucfirst($return['status']), ucfirst($return['status'])); ?>
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
