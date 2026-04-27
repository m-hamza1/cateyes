<?php
require_once '../includes/auth.php';
requirePermission('shipping');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id) {
    // Check if shipping method exists
    $shipping = $db->fetchOne("SELECT * FROM shipping_methods WHERE id = :id", ['id' => $id]);
    
    if (!$shipping) {
        setFlashMessage(__('shipping_method_not_found', 'Shipping method not found'), 'danger');
        header('Location: index.php');
        exit;
    }
    
    // Check if shipping method is being used in orders
    $orderCount = $db->fetchOne("
        SELECT COUNT(*) as count 
        FROM orders o
        JOIN order_shipping os ON o.id = os.order_id
        WHERE os.shipping_method_id = :id
    ", ['id' => $id])['count'] ?? 0;
    
    if ($orderCount > 0) {
        // Instead of deleting, deactivate the method
        $db->update('shipping_methods', [
            'is_active' => 0
        ], 'id = :id', ['id' => $id]);
        
        setFlashMessage(__('shipping_method_deactivated', 'Shipping method has been deactivated because it is used in existing orders.'), 'warning');
    } else {
        // Safe to delete - also delete associated rates
        $db->delete('shipping_rates', 'shipping_method_id = :id', ['id' => $id]);
        $db->delete('shipping_methods', 'id = :id', ['id' => $id]);
        
        setFlashMessage(__('shipping_method_deleted', 'Shipping method deleted successfully!'), 'success');
    }
} else {
    setFlashMessage(__('invalid_shipping_method_id', 'Invalid shipping method ID'), 'danger');
}

header('Location: index.php');
exit;