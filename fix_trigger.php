<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require_once 'config.php';

$triggerSQL = "
CREATE TRIGGER `after_inventory_transaction_insert` AFTER INSERT ON `inventory_transactions` FOR EACH ROW BEGIN
    DECLARE v_product_id INT;
    DECLARE v_warehouse_id INT;
    DECLARE v_variant_id INT;
    DECLARE v_new_quantity DECIMAL(15,3);
    DECLARE v_exists INT;

    SET v_product_id = NEW.product_id;
    SET v_warehouse_id = NEW.warehouse_id;
    SET v_variant_id = NEW.variant_id;
    SET v_new_quantity = NEW.new_quantity;

    -- Check if stock record exists
    SELECT COUNT(*) INTO v_exists 
    FROM product_warehouse_stock 
    WHERE product_id = v_product_id 
      AND warehouse_id = v_warehouse_id 
      AND (variant_id = v_variant_id OR (variant_id IS NULL AND v_variant_id IS NULL));

    IF v_exists > 0 THEN
        UPDATE product_warehouse_stock
        SET quantity = v_new_quantity,
            available_quantity = v_new_quantity - reserved_quantity,
            updated_at = NOW()
        WHERE product_id = v_product_id 
          AND warehouse_id = v_warehouse_id 
          AND (variant_id = v_variant_id OR (variant_id IS NULL AND v_variant_id IS NULL));
    ELSE
        INSERT INTO product_warehouse_stock (
            product_id,
            warehouse_id,
            variant_id,
            quantity,
            available_quantity,
            reserved_quantity,
            updated_at
        ) VALUES (
            v_product_id,
            v_warehouse_id,
            v_variant_id,
            v_new_quantity,
            v_new_quantity,
            0,
            NOW()
        );
    END IF;

    -- Update total product stock
    UPDATE products
    SET stock_quantity = (
        SELECT COALESCE(SUM(quantity), 0)
        FROM product_warehouse_stock
        WHERE product_id = v_product_id
    ),
    updated_at = NOW()
    WHERE id = v_product_id;

    -- Update variant total stock
    IF v_variant_id IS NOT NULL THEN
        UPDATE product_variants
        SET stock_quantity = (
            SELECT COALESCE(SUM(quantity), 0)
            FROM product_warehouse_stock
            WHERE variant_id = v_variant_id
        )
        WHERE id = v_variant_id;
    END IF;
END;
";

try {
    echo "Dropping old trigger...\n";
    $db->query("DROP TRIGGER IF EXISTS after_inventory_transaction_insert");
    
    echo "Creating fixed trigger...\n";
    $db->query($triggerSQL);
    
    echo "Trigger fixed successfully!\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
