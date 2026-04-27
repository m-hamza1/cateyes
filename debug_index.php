<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require_once 'config.php';

try {
    echo "Checking Index...\n";
    $existingIndex = $db->fetchOne("SHOW INDEX FROM product_warehouse_stock WHERE Key_name = 'unique_stock_index'");
    if ($existingIndex) {
        echo "Index 'unique_stock_index' FOUND.\n";
    } else {
         echo "Index 'unique_stock_index' NOT FOUND.\n";
         echo "Attempting to create it again...\n";
         $db->query("ALTER TABLE product_warehouse_stock ADD UNIQUE INDEX unique_stock_index (product_id, warehouse_id, variant_id)");
         echo "Index created.\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
