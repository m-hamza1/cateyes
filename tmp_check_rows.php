<?php
require 'config.php';
$rows = $db->fetchAll("SELECT * FROM product_warehouse_stock WHERE warehouse_id = 1");
foreach ($rows as $r) {
    echo "ID: {$r['id']} | Prod: {$r['product_id']} | Warehouse: {$r['warehouse_id']} | Variant: " . ($r['variant_id'] ?: 'NULL') . " | Qty: {$r['quantity']}\n";
}
