<?php
require_once 'config.php';


$sql = "
DROP TRIGGER IF EXISTS after_inventory_transaction_insert;

CREATE TRIGGER after_inventory_transaction_insert
AFTER INSERT ON inventory_transactions
FOR EACH ROW
BEGIN
    DECLARE v_product_id INT;
    DECLARE v_warehouse_id INT;
    DECLARE v_variant_id INT;
    DECLARE v_new_quantity INT;
    DECLARE v_stock_id INT;

    SET v_product_id = NEW.product_id;
    SET v_warehouse_id = NEW.warehouse_id;
    SET v_variant_id = NEW.variant_id;
    SET v_new_quantity = NEW.new_quantity;

    -- Update or Insert into product_warehouse_stock
    SELECT id INTO v_stock_id
    FROM product_warehouse_stock
    WHERE product_id = v_product_id
      AND warehouse_id = v_warehouse_id
      AND (variant_id = v_variant_id OR (variant_id IS NULL AND v_variant_id IS NULL));

    IF v_stock_id IS NOT NULL THEN
        UPDATE product_warehouse_stock
        SET quantity = v_new_quantity,
            available_quantity = v_new_quantity,
            updated_at = NOW()
        WHERE id = v_stock_id;
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

    -- Update parent product total stock
    UPDATE products
    SET stock_quantity = (
        SELECT COALESCE(SUM(quantity), 0)
        FROM product_warehouse_stock
        WHERE product_id = v_product_id
    ),
    updated_at = NOW()
    WHERE id = v_product_id;

    -- NEW: Update variant total stock if applicable
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
    $db->getConnection()->exec($sql);
    echo "Trigger updated successfully!";
} catch (Exception $e) {
    echo "Error updating trigger: " . $e->getMessage();
}
?>
