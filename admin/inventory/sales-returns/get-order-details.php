<?php
require_once '../../includes/auth.php';

header('Content-Type: application/json');

$orderNumber = trim($_GET['order_number'] ?? '');

if (empty($orderNumber)) {
    echo json_encode(['success' => false, 'message' => 'Order number is required']);
    exit;
}

try {
    // Get order details with customer and items
    // Allow returns from delivered, completed, or fulfilled orders
    $order = $db->fetchOne("
        SELECT o.*, 
               CONCAT(c.first_name, ' ', c.last_name) as customer_name,
               c.email as customer_email
        FROM orders o
        LEFT JOIN customers c ON o.customer_id = c.id
        WHERE o.order_number = :order_number 
        AND o.order_status IN ('delivered', 'completed', 'fulfilled', 'shipped')
    ", ['order_number' => $orderNumber]);
    
    if (!$order) {
        // Try to find the order regardless of status to give better error message
        $anyOrder = $db->fetchOne("
            SELECT o.order_status, o.created_at
            FROM orders o
            WHERE o.order_number = :order_number
        ", ['order_number' => $orderNumber]);
        
        if ($anyOrder) {
            echo json_encode([
                'success' => false, 
                'message' => "Order found but status is '{$anyOrder['order_status']}'. Only delivered, completed, fulfilled, or shipped orders can be returned."
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Order not found. Please check the order number.']);
        }
        exit;
    }
    
    // Get order items
    $items = $db->fetchAll("
        SELECT oi.*,
               p.sku as product_sku
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = :order_id
        ORDER BY oi.id
    ", ['order_id' => $order['id']]);
    
    echo json_encode([
        'success' => true,
        'order' => [
            'id' => $order['id'],
            'order_number' => $order['order_number'],
            'customer_name' => $order['customer_name'],
            'customer_email' => $order['customer_email'],
            'order_status' => $order['order_status'],
            'total_amount' => $order['total_amount']
        ],
        'items' => array_map(function($item) {
            return [
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'product_sku' => $item['product_sku'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price']
            ];
        }, $items)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading order details: ' . $e->getMessage()
    ]);
}
?>
