<?php
// Force local config for CLI
$_SERVER['HTTP_HOST'] = 'localhost';
require 'config.php';
$items = $db->fetchAll("SELECT name, main_image FROM products LIMIT 10");
foreach($items as $item) {
    echo "Product: {$item['name']}, Image: {$item['main_image']}\n";
}
