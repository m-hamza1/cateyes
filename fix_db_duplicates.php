<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require_once 'config.php';

try {
    $db->beginTransaction();
    echo "Starting Data Cleanup and Indexing...\n";

    // 1. Delete duplicate warehouse stock records (keeping the one with the highest ID)
    echo "Step 1: Cleaning up duplicate warehouse stock records...\n";
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

    // 2. Add Unique Index to prevent future duplicates
    echo "Step 2: Adding Unique Index to product_warehouse_stock...\n";
    // Check if index already exists to avoid error
    $existingIndex = $db->fetchOne("SHOW INDEX FROM product_warehouse_stock WHERE Key_name = 'unique_stock_index'");
    if (!$existingIndex) {
        $db->query("ALTER TABLE product_warehouse_stock ADD UNIQUE INDEX unique_stock_index (product_id, warehouse_id, variant_id)");
    }

    $db->commit();
    echo "Database structure fixed successfully!\n";

    // 3. Recalculate all stocks to be sure
    echo "Step 3: Recalculating all stock quantities from transactions...\n";
    require_once 'admin/inventory/inventory_helper.php';
    rebuildAllStockFromTransactions($db);
    echo "Stocks recalculated from movement history.\n";

} catch (Exception $e) {
    if (isset($db)) $db->rollback();
    echo "ERROR: " . $e->getMessage() . "\n";
}
