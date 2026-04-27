<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require_once 'config.php';
$stock = $db->fetchOne("SELECT * FROM product_warehouse_stock WHERE product_id = 1 AND warehouse_id = 1");
print_r($stock);
$product = $db->fetchOne("SELECT stock_quantity FROM products WHERE id = 1");
print_r($product);
