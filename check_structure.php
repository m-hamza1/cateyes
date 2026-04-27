<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require_once 'config.php';
$cols = $db->fetchAll("SHOW COLUMNS FROM product_warehouse_stock");
print_r($cols);
$indexes = $db->fetchAll("SHOW INDEX FROM product_warehouse_stock");
print_r($indexes);
