<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require_once 'config.php';

try {
    echo "Dropping all duplicate records again...\n";
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
    echo "Done.\n";
} catch (Exception $e) {
    echo "ERR: " . $e->getMessage() . "\n";
}
