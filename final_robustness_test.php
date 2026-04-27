<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require_once 'config.php';
require_once 'admin/inventory/inventory_helper.php';

// 1. Get a product and a warehouse
$product = $db->fetchOne("SELECT p.id, pt.name, p.stock_quantity FROM products p JOIN product_translations pt ON p.id = pt.product_id WHERE p.is_active = 1 LIMIT 1");
$warehouse = $db->fetchOne("SELECT id, name FROM warehouses WHERE is_active = 1 LIMIT 1");

if (!$product || !$warehouse) {
    die("No product or warehouse found for testing.");
}

echo "FINAL TEST START:\n";
echo "Product: " . $product['name'] . " (ID: " . $product['id'] . ")\n";
echo "Warehouse: " . $warehouse['name'] . " (ID: " . $warehouse['id'] . ")\n";
$initialTotalQty = (int)$product['stock_quantity'];
echo "Initial Total Stock in Products Table: " . $initialTotalQty . "\n";

// Get initial warehouse stock
$whStock = $db->fetchOne("SELECT quantity FROM product_warehouse_stock WHERE product_id = :p_id AND warehouse_id = :w_id", ['p_id' => $product['id'], 'w_id' => $warehouse['id']]);
$initialWhQty = (int)($whStock['quantity'] ?? 0);
echo "Initial Stock in this Warehouse: " . $initialWhQty . "\n\n";

// 2. Perform Stock Adjustment (Add 10 units)
$adjustmentQty = 10;
echo "ADJUSTMENT: Adding $adjustmentQty units...\n";

logInventoryTransaction($db, [
    'product_id' => $product['id'],
    'warehouse_id' => $warehouse['id'],
    'transaction_type' => 'adjustment',
    'quantity' => $adjustmentQty,
    'notes' => "FINAL ROBUSTNESS TEST: Adding $adjustmentQty units",
    'created_by' => 3
]);

echo "Adjustment Logged. Waiting for trigger processing...\n\n";
sleep(1); // Slight pause to ensure trigger finishes

// 3. Verify Results
$updatedProduct = $db->fetchOne("SELECT stock_quantity FROM products WHERE id = :id", ['id' => $product['id']]);
$updatedWhStock = $db->fetchOne("SELECT quantity FROM product_warehouse_stock WHERE product_id = :p_id AND warehouse_id = :w_id", ['p_id' => $product['id'], 'w_id' => $warehouse['id']]);
$allWhRecordsCount = $db->count('product_warehouse_stock', 'product_id = :p_id AND warehouse_id = :w_id', ['p_id' => $product['id'], 'w_id' => $warehouse['id']]);

$newWhQty = (int)$updatedWhStock['quantity'];
$newTotalQty = (int)$updatedProduct['stock_quantity'];

echo "VERIFICATION:\n";
echo "New Stock in this Warehouse: " . $newWhQty . " (Expected: " . ($initialWhQty + $adjustmentQty) . ")\n";
echo "New Total Stock in Products Table: " . $newTotalQty . " (Expected: " . ($initialTotalQty + $adjustmentQty) . ")\n";
echo "Number of records in DB for this product/warehouse: " . $allWhRecordsCount . " (Expected: 1)\n";

if ($newWhQty === ($initialWhQty + $adjustmentQty) && 
    $newTotalQty === ($initialTotalQty + $adjustmentQty) && 
    $allWhRecordsCount === 1) {
    echo "\nRESULT: FINAL TEST SUCCESSFUL! System is now robust and single-source.\n";
} else {
    echo "\nRESULT: FINAL TEST FAILED!\n";
}
