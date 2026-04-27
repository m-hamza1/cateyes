<?php
require_once 'config.php';
$tables = ['brands', 'product_variants', 'product_variant_attributes', 'attribute_values', 'product_warehouse_stock', 'stock_transfers', 'stock_transfer_items'];
foreach ($tables as $t) {
    $exists = $db->fetchAll("SHOW TABLES LIKE '$t'");
    echo "$t: " . (empty($exists) ? "MISSING" : "OK") . "\n";
}
