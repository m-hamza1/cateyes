<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require_once 'config.php';
$txs = $db->fetchAll("SELECT * FROM inventory_transactions WHERE product_id = 1");
print_r($txs);
