<?php
require_once 'config.php';
$product = $db->fetchOne("SELECT product_id FROM product_translations WHERE name LIKE '%Armonia%' LIMIT 1");
if ($product) {
    echo "Product ID: " . $product['product_id'] . "\n";
} else {
    echo "Product not found\n";
}
