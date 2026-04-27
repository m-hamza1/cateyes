<?php
$dbHost = 'localhost:3307';
$dbUser = 'root';
$dbPass = '';
$dbName = 'u744799766_cat_eye';
try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Columns in attribute_values:\n";
    $stmt = $pdo->query("DESCRIBE attribute_values");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    
    echo "\nColumns in product_variants:\n";
    $stmt = $pdo->query("DESCRIBE product_variants");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
