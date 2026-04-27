<?php
require_once 'config.php';
try {
    // 1. Get all active warehouses (removing missing 'location' column)
    $warehouses = $db->fetchAll("SELECT id, name, is_active, is_default FROM warehouses ORDER BY name ASC");
    echo "--- WAREHOUSES ---\n";
    foreach ($warehouses as $w) {
        $type = $w['is_default'] ? "[DEFAULT]" : "";
        echo "ID: {$w['id']} | Name: {$w['name']} $type\n";
    }

    echo "\n--- PRODUCT STOCK BY WAREHOUSE ---\n";
    // 2. Get product stock levels per warehouse with variant details
    $langId = $lang->getCurrentId() ?: 1;
    $stockItems = $db->fetchAll("
        SELECT w.name as warehouse_name, pt.name as product_name, p.sku, 
               pws.variant_id, 
               (SELECT GROUP_CONCAT(av.value SEPARATOR ', ') FROM product_variant_attributes pva JOIN attribute_values av ON pva.attribute_value_id = av.id WHERE pva.variant_id = pws.variant_id) as variant_info,
               COALESCE(pws.quantity, 0) as quantity, 
               COALESCE(pws.reserved_quantity, 0) as reserved
        FROM product_warehouse_stock pws
        JOIN warehouses w ON pws.warehouse_id = w.id
        JOIN products p ON pws.product_id = p.id
        LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang_id
        WHERE pws.quantity > 0 OR pws.reserved_quantity > 0
        ORDER BY w.name ASC, pt.name ASC
    ", ['lang_id' => $langId]);

    if (empty($stockItems)) {
        echo "No stock found in any warehouse.\n";
    } else {
        $currentWarehouse = '';
        foreach ($stockItems as $item) {
            if ($currentWarehouse !== $item['warehouse_name']) {
                $currentWarehouse = $item['warehouse_name'];
                echo "\nWarehouse: $currentWarehouse\n";
                echo str_repeat("-", 40) . "\n";
            }
            $variant = $item['variant_info'] ? " ({$item['variant_info']})" : "";
            $name = $item['product_name'] . $variant;
            $available = $item['quantity'] - $item['reserved'];
            echo "- " . str_pad($name, 40) . " | SKU: " . str_pad($item['sku'], 15) . " | Stock: " . str_pad($item['quantity'], 5) . " | Available: $available\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
