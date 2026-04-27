<?php
require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'admin/inventory/inventory_helper.php';

$db = new Database();
echo "--- INVENTORY INTEGRITY REPORT ---\n";
$report = getStockIntegrityReport($db);
if (empty($report)) {
    echo "SUCCESS: All product stock levels are perfectly synchronized with warehouse records.\n";
} else {
    echo "WARNING: Discrepancies found:\n";
    print_r($report);
}

$stats = getInventorySystemStats($db);
echo "\n--- SYSTEM STATISTICS ---\n";
echo "Total Historical Transactions: " . $stats['total_transactions'] . "\n";
echo "Active Warehouse Records: " . $stats['warehouse_records'] . "\n";
echo "Products with Active Stock: " . $stats['products_with_stock'] . "\n";
echo "Total Warehouses: " . $stats['total_warehouses'] . "\n";
echo "Trigger Status: " . ($stats['trigger_installed'] ? "ACTIVE" : "MISSING") . "\n";
echo "---------------------------------\n";
