<?php
require_once 'config.php';
echo "Products Table:\n";
print_r($db->fetchAll("DESCRIBE products"));
echo "\nProduct Translations Table:\n";
print_r($db->fetchAll("DESCRIBE product_translations"));
