<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require_once 'config.php';

try {
    echo "Dropping all unique indexes to start clean...\n";
    $db->query("ALTER TABLE product_warehouse_stock DROP INDEX IF EXISTS unique_stock_index");
    $db->query("ALTER TABLE product_warehouse_stock DROP INDEX IF EXISTS unique_product_variant_warehouse");
    $db->query("ALTER TABLE product_warehouse_stock DROP INDEX IF EXISTS product_warehouse");
    
    echo "Deleting duplicates...\n";
    $db->query("
        DELETE FROM product_warehouse_stock 
        WHERE id NOT IN (
            SELECT max_id FROM (
                SELECT MAX(id) as max_id 
                FROM product_warehouse_stock 
                GROUP BY product_id, warehouse_id, COALESCE(variant_id, 0)
            ) as t
        )
    ");

    echo "Adding single UNIQUE INDEX...\n";
    // Using a combined unique key for product, warehouse and variant
    $db->query("ALTER TABLE product_warehouse_stock ADD UNIQUE INDEX idx_unique_stock (product_id, warehouse_id, variant_id)");
    
    echo "Done.\n";
} catch (Exception $e) {
    echo "ERR: " . $e->getMessage() . "\n";
}
