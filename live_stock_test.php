<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require_once 'config.php';

// 1. Get a product and a warehouse
$product = $db->fetchOne("SELECT p.id, pt.name, p.stock_quantity FROM products p JOIN product_translations pt ON p.id = pt.product_id WHERE p.is_active = 1 LIMIT 1");
$warehouse = $db->fetchOne("SELECT id, name FROM warehouses WHERE is_active = 1 LIMIT 1");

if (!$product || !$warehouse) {
    die("No product or warehouse found for testing.");
}

echo "TEST START:\n";
echo "Product: " . $product['name'] . " (ID: " . $product['id'] . ")\n";
echo "Warehouse: " . $warehouse['name'] . " (ID: " . $warehouse['id'] . ")\n";
echo "Initial Total Stock in Products Table: " . $product['stock_quantity'] . "\n";

// Get initial warehouse stock
$whStock = $db->fetchOne("SELECT quantity FROM product_warehouse_stock WHERE product_id = :p_id AND warehouse_id = :w_id", ['p_id' => $product['id'], 'w_id' => $warehouse['id']]);
$initialWhQty = $whStock['quantity'] ?? 0;
echo "Initial Stock in this Warehouse: " . $initialWhQty . "\n\n";

// 2. Perform Stock Adjustment (Add 5 units)
require_once 'admin/inventory/inventory_helper.php';
$adjustmentQty = 5;
echo "ADJUSTMENT: Adding $adjustmentQty units via logInventoryTransaction...\n";

logInventoryTransaction($db, [
    'product_id' => $product['id'],
    'warehouse_id' => $warehouse['id'],
    'transaction_type' => 'adjustment',
    'quantity' => $adjustmentQty,
    'notes' => "LIVE TEST: Adding $adjustmentQty units",
    'created_by' => 3 // Admin ID 3
]);

echo "Adjustment Logged.\n\n";

// 3. Verify Results
$updatedProduct = $db->fetchOne("SELECT stock_quantity FROM products WHERE id = :id", ['id' => $product['id']]);
$updatedWhStock = $db->fetchOne("SELECT quantity FROM product_warehouse_stock WHERE product_id = :p_id AND warehouse_id = :w_id", ['p_id' => $product['id'], 'w_id' => $warehouse['id']]);
$latestMovement = $db->fetchOne("SELECT * FROM inventory_transactions WHERE product_id = :p_id ORDER BY id DESC LIMIT 1");

echo "VERIFICATION:\n";
echo "New Stock in this Warehouse: " . $updatedWhStock['quantity'] . " (Expected: " . ($initialWhQty + $adjustmentQty) . ")\n";
echo "New Total Stock in Products Table: " . $updatedProduct['stock_quantity'] . " (Expected: " . ($product['stock_quantity'] + $adjustmentQty) . ")\n";
echo "Latest Movement Logged: " . $latestMovement['notes'] . " (Qty: " . $latestMovement['quantity'] . ")\n";

if ($updatedWhStock['quantity'] == ($initialWhQty + $adjustmentQty) && $updatedProduct['stock_quantity'] == ($product['stock_quantity'] + $adjustmentQty)) {
    echo "\nRESULT: TEST SUCCESSFUL! Stock synced perfectly across all tables.\n";
} else {
    echo "\nRESULT: TEST FAILED! Mismatch detected.\n";
}
