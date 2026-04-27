<?php
require_once '../../includes/auth.php';
$pageTitle = __('reject_sales_return', 'Reject Sales Return');

// Get sales return details
$returnId = $_GET['id'] ?? 0;
$return = $db->fetchOne("
    SELECT sr.*, c.first_name, c.last_name, w.name as warehouse_name
    FROM sales_returns sr
    LEFT JOIN customers c ON sr.customer_id = c.id
    LEFT JOIN warehouses w ON sr.warehouse_id = w.id
    WHERE sr.id = :id AND sr.status = 'pending'
", ['id' => $returnId]);

if (!$return) {
    setFlashMessage(__('sales_return_not_found_or_not_pending', 'Sales return not found or not pending'), 'error');
    header('Location: view.php?id=' . $returnId);
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $rejectionReason = trim($_POST['rejection_reason'] ?? '');
        
        if (empty($rejectionReason)) {
            $errors[] = __('rejection_reason_required', 'Rejection reason is required');
        }
        
        if (empty($errors)) {
            $updated = $db->update('sales_returns', [
                'status' => 'rejected',
                'rejected_at' => date('Y-m-d H:i:s'),
                'rejected_by' => $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null,
                'rejection_reason' => $rejectionReason
            ], 'id = :id', ['id' => $returnId]);
            
            if (!$updated) {
                throw new Exception(__('failed_to_reject_return', 'Failed to reject sales return'));
            }
            
            setFlashMessage(__('sales_return_rejected', 'Sales return rejected successfully!'), 'success');
            header('Location: view.php?id=' . $returnId);
            exit;
        }
    } catch (Exception $e) {
        setFlashMessage(__('error_rejecting_return', 'Error rejecting sales return: ') . $e->getMessage(), 'error');
        header('Location: reject.php?id=' . $returnId);
        exit;
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('reject_sales_return', 'Reject Sales Return'); ?></h1>
    <a href="view.php?id=<?php echo $returnId; ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> <?php echo __('back_to_return', 'Back to Return'); ?>
    </a>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?php echo __('return_details', 'Return Details'); ?></h5>
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
                        <td><strong><?php echo __('warehouse', 'Warehouse'); ?>:</strong></td>
                        <td><?php echo htmlspecialchars($return['warehouse_name']); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo __('return_date', 'Return Date'); ?>:</strong></td>
                        <td><?php echo date('Y-m-d', strtotime($return['return_date'])); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo __('reason', 'Reason'); ?>:</strong></td>
                        <td><?php echo __(ucfirst($return['reason']), ucfirst($return['reason'])); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo __('total_amount', 'Total Amount'); ?>:</strong></td>
                        <td><?php echo $currency->format($return['total_amount']); ?></td>
                    </tr>
                </table>
                
                <?php if (!empty($return['notes'])): ?>
                <div class="mt-3">
                    <h6><?php echo __('notes', 'Notes'); ?>:</h6>
                    <p><?php echo nl2br(htmlspecialchars($return['notes'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?php echo __('rejection', 'Rejection'); ?></h5>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" onsubmit="return confirmSubmission();">
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label"><?php echo __('rejection_reason', 'Rejection Reason'); ?> *</label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="4" 
                                  placeholder="<?php echo __('provide_rejection_reason', 'Provide a reason for rejection...'); ?>" required></textarea>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-times"></i> <?php echo __('reject_return', 'Reject Return'); ?>
                        </button>
                        <a href="view.php?id=<?php echo $returnId; ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> <?php echo __('cancel', 'Cancel'); ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmSubmission() {
    const reason = document.getElementById('rejection_reason').value;
    if (!reason.trim()) {
        alert('<?php echo __('rejection_reason_required', 'Rejection reason is required'); ?>');
        return false;
    }
    
    return confirm('<?php echo __('reject_sales_return_warning', 'Are you sure you want to reject this sales return? This action cannot be undone.'); ?>');
}
</script>

<?php include '../../includes/footer.php'; ?>
