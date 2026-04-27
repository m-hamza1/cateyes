<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require_once 'config.php';
$stocks = $db->fetchAll("SELECT * FROM product_warehouse_stock WHERE product_id = 1");
print_r($stocks);
