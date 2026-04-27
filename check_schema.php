<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require 'config.php';
$columns = $db->fetchAll("SHOW COLUMNS FROM products");
print_r($columns);
