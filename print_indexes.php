<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require_once 'config.php';

$res = $db->fetchAll("SHOW INDEX FROM product_warehouse_stock");
echo "INDEXES ON product_warehouse_stock:\n";
foreach ($res as $row) {
    echo "Key: " . $row['Key_name'] . " | Unique: " . ($row['Non_unique'] == 0 ? 'YES' : 'NO') . " | Column: " . $row['Column_name'] . "\n";
}
