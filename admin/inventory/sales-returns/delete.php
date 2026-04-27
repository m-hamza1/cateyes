<?php
require_once '../../includes/auth.php';
$pageTitle = __('delete_sales_return', 'Delete Sales Return');

// Get sales return details
$returnId = $_GET['id'] ?? 0;
$return = $db->fetchOne("
    SELECT sr.*, c.first_name, c.last_name 
    FROM sales_returns sr
    LEFT JOIN customers c ON sr.customer_id = c.id
    WHERE sr.id = :id
", ['id' => $returnId]);

if (!$return) {
    setFlashMessage(__('sales_return_not_found', 'Sales return not found'), 'error');
    header('Location: index.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        // Delete sales return items first
        $db->delete('sales_return_items', 'sales_return_id = :id', ['id' => $returnId]);
        
        // Delete the sales return
        $deleted = $db->delete('sales_returns', 'id = :id', ['id' => $returnId]);
        
        if ($deleted) {
            $db->commit();
            setFlashMessage(__('sales_return_deleted', 'Sales return deleted successfully!'), 'success');
            header('Location: index.php');
            exit;
        } else {
            $db->rollback();
            setFlashMessage(__('delete_failed', 'Failed to delete sales return'), 'error');
            header('Location: index.php');
            exit;
        }
    } catch (Exception $e) {
        $db->rollback();
        setFlashMessage(__('error_deleting_sales_return', 'Error deleting sales return: ') . $e->getMessage(), 'error');
        header('Location: index.php');
        exit;
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('delete_sales_return', 'Delete Sales Return'); ?></h1>
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
                    <p><?php echo __('delete_sales_return_warning', 'Are you sure you want to delete this sales return? This action cannot be undone and will also delete all associated items.'); ?></p>
                </div>
                
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0"><?php echo __('sales_return_details', 'Sales Return Details'); ?></h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <td><strong><?php echo __('return_number', 'Return Number'); ?>:</strong></td>
                                <td><?php echo htmlspecialchars($return['return_number'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo __('customer', 'Customer'); ?>:</strong></td>
                                <td><?php echo htmlspecialchars($return['first_name'] . ' ' . $return['last_name']); ?></td>
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
