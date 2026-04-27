<?php
require_once '../../includes/auth.php';
require_once '../inventory_helper.php';

// Get purchase order ID
$orderId = $_POST['id'] ?? 0;

if ($orderId <= 0) {
    setFlashMessage(__('invalid_order_id', 'Invalid purchase order ID'), 'danger');
    header('Location: index.php');
    exit;
}

// Get purchase order details
$purchaseOrder = $db->fetchOne("SELECT * FROM purchase_orders WHERE id = :id", ['id' => $orderId]);

if (!$purchaseOrder) {
    setFlashMessage(__('purchase_order_not_found', 'Purchase order not found'), 'danger');
    header('Location: index.php');
    exit;
}

// Check if order can be cancelled
if (!in_array($purchaseOrder['status'], ['draft', 'pending', 'confirmed'])) {
    setFlashMessage(__('cannot_cancel_order', 'This purchase order cannot be cancelled'), 'danger');
    header('Location: view.php?id=' . $orderId);
    exit;
}

try {
    $db->beginTransaction();
    
    // Update purchase order status to cancelled
    $db->update('purchase_orders', [
        'status' => 'cancelled',
        'updated_at' => date('Y-m-d H:i:s')
    ], 'id = :id', ['id' => $orderId]);
    
    // Create inventory transaction record for cancellation
    $db->insert('inventory_transactions', [
        'product_id' => null,
        'warehouse_id' => $purchaseOrder['warehouse_id'],
        'transaction_type' => 'purchase_order_cancelled',
        'quantity' => 0,
        'reference_type' => 'purchase_order',
        'reference_id' => $orderId,
        'previous_quantity' => 0,
        'new_quantity' => 0,
        'unit_cost' => 0,
        'total_cost' => 0,
        'notes' => 'Purchase order cancelled: ' . ($purchaseOrder['po_number'] ?? $purchaseOrder['order_number'] ?? 'PO-' . $orderId),
        'created_by' => $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null,
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    $db->commit();
    
    setFlashMessage(__('purchase_order_cancelled', 'Purchase order cancelled successfully'), 'success');
    header('Location: view.php?id=' . $orderId);
    exit;
    
} catch (Exception $e) {
    $db->rollback();
    setFlashMessage(__('error_cancelling_order', 'Error cancelling purchase order: ') . $e->getMessage(), 'danger');
    header('Location: view.php?id=' . $orderId);
    exit;
}
?>
