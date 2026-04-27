<?php
require_once 'config/config.php';
require_once 'includes/Database.php';
$db = new Database();

$sql = "
            INSERT INTO product_warehouse_stock (
                product_id,
                warehouse_id,
                variant_id,
                quantity,
                reserved_quantity
            )
            SELECT 
                product_id,
                warehouse_id,
                variant_id,
                new_quantity,
                0
            FROM (
                SELECT 
                    product_id,
                    warehouse_id,
                    variant_id,
                    new_quantity,
                    ROW_NUMBER() OVER (
                        PARTITION BY product_id, warehouse_id, COALESCE(variant_id, 0)
                        ORDER BY created_at DESC, id DESC
                    ) as rn
                FROM inventory_transactions
            ) latest
            WHERE rn = 1
            ON DUPLICATE KEY UPDATE 
                quantity = VALUES(quantity),
                updated_at = NOW()
";

$res = $db->query($sql);
if ($res === false) {
    echo "FAILED";
} else {
    echo "SUCCESS";
}
?>
