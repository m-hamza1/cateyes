<?php
$dbHost = 'localhost:3307';
$dbUser = 'root';
$dbPass = '';
$dbName = 'u744799766_cat_eye';
try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Columns in attribute_values: ";
    $res = $pdo->query("DESCRIBE attribute_values")->fetchAll(PDO::FETCH_COLUMN);
    echo implode(', ', $res) . "\n";
    
    echo "Columns in product_translations: ";
    $res = $pdo->query("DESCRIBE product_translations")->fetchAll(PDO::FETCH_COLUMN);
    echo implode(', ', $res) . "\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
