<?php
require_once '../../includes/auth.php';

// Get supplier return ID
$returnId = $_POST['id'] ?? 0;
$notes = $_POST['notes'] ?? '';

if ($returnId <= 0) {
    setFlashMessage(__('invalid_return_id', 'Invalid supplier return ID'), 'danger');
    header('Location: index.php');
    exit;
}

if (empty($notes)) {
    setFlashMessage(__('reject_reason_required', 'Rejection reason is required'), 'danger');
    header('Location: view.php?id=' . $returnId);
    exit;
}

// Get supplier return details
$return = $db->fetchOne("SELECT * FROM supplier_returns WHERE id = :id", ['id' => $returnId]);

if (!$return) {
    setFlashMessage(__('supplier_return_not_found', 'Supplier return not found'), 'danger');
    header('Location: index.php');
    exit;
}

if ($return['status'] !== 'pending') {
    setFlashMessage(__('cannot_reject_return', 'This supplier return cannot be rejected'), 'danger');
    header('Location: view.php?id=' . $returnId);
    exit;
}

try {
    $db->beginTransaction();
    
    // Update supplier return status to rejected
    $db->update('supplier_returns', [
        'status' => 'rejected',
        'notes' => $notes,
        'updated_at' => date('Y-m-d H:i:s')
    ], 'id = :id', ['id' => $returnId]);
    
    // Create inventory transaction record for rejection
    $db->insert('inventory_transactions', [
        'product_id' => null,
        'warehouse_id' => $return['warehouse_id'],
        'transaction_type' => 'supplier_return_rejected',
        'quantity' => 0,
        'reference_type' => 'supplier_return',
        'reference_id' => $returnId,
        'notes' => 'Supplier return rejected: ' . ($return['return_number'] ?? ('SUPR-' . $returnId)) . ' - ' . $notes,
        'created_by' => $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null,
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    $db->commit();
    
    setFlashMessage(__('supplier_return_rejected', 'Supplier return rejected successfully'), 'success');
    header('Location: view.php?id=' . $returnId);
    exit;
    
} catch (Exception $e) {
    $db->rollback();
    setFlashMessage(__('error_rejecting_return', 'Error rejecting supplier return: ') . $e->getMessage(), 'danger');
    header('Location: view.php?id=' . $returnId);
    exit;
}
?>
