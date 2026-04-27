<?php
require_once '../../includes/auth.php';

$purchaseOrderId = $_GET['id'] ?? 0;

if ($purchaseOrderId <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid purchase order ID']);
    exit;
}

try {
    // Get purchase order items with all necessary fields and variants
    $items = $db->fetchAll("
        SELECT poi.product_id, poi.variant_id, poi.quantity, poi.unit_price, poi.received_quantity,
               COALESCE(pv.sku, p.sku) as sku,
               CASE 
                   WHEN pv.id IS NOT NULL THEN CONCAT(pt.name, ' - ', (
                       SELECT GROUP_CONCAT(av.value SEPARATOR ', ')
                       FROM product_variant_attributes pva
                       JOIN attribute_values av ON pva.attribute_value_id = av.id
                       WHERE pva.variant_id = pv.id
                   ))
                   ELSE pt.name 
               END as product_name
        FROM purchase_order_items poi
        LEFT JOIN products p ON poi.product_id = p.id
        LEFT JOIN product_variants pv ON poi.variant_id = pv.id
        LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang_id
        WHERE poi.purchase_order_id = :purchase_order_id
        ORDER BY poi.id
    ", [
        'purchase_order_id' => $purchaseOrderId,
        'lang_id' => $lang->getCurrentId()
    ]);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'items' => $items
    ]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching purchase order items: ' . $e->getMessage()
    ]);
}
?>
