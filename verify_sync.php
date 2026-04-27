<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require_once 'config.php';
require_once 'admin/inventory/inventory_helper.php';

// 1. Final verification script
echo "Final Verification of Stock Logic:\n";

// Get latest transaction for product 1
$latest = $db->fetchOne("SELECT * FROM inventory_transactions WHERE product_id = 1 ORDER BY id DESC LIMIT 1");
echo "Latest Transaction Qty/NewQty: " . $latest['quantity'] . " / " . $latest['new_quantity'] . "\n";

// Get warehouse stock
$wh = $db->fetchOne("SELECT * FROM product_warehouse_stock WHERE product_id = 1 AND warehouse_id = 1");
echo "Warehouse Stock Quantity: " . $wh['quantity'] . "\n";

// Get product total
$prod = $db->fetchOne("SELECT stock_quantity FROM products WHERE id = 1");
echo "Product Table Total Stock: " . $prod['stock_quantity'] . "\n";

if ($latest['new_quantity'] == $wh['quantity'] && $wh['quantity'] == $prod['stock_quantity']) {
    echo "\nCONGRATULATIONS: Stock is perfectly synced!\n";
} else {
    echo "\nMismatch still exists.\n";
}
