<?php
require 'config.php';
$search = 'High Light';
$searchTerm = "%" . $search . "%";
$langId = $lang->getCurrentId() ?: 1;

$res = $db->fetchAll("
    SELECT pt.product_id, pt.name, pt.language_id, l.code
    FROM product_translations pt
    JOIN languages l ON pt.language_id = l.id
    WHERE pt.name LIKE :search
", ['search' => $searchTerm]);

echo "Searching for '$search' in translations (Current Lang ID: $langId):\n";
print_r($res);

$prod = $db->fetchAll("SELECT id, sku FROM products WHERE sku LIKE :search", ['search' => $searchTerm]);
echo "\nSearching for '$search' in products SKU:\n";
print_r($prod);
