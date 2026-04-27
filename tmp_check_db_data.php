<?php
// Mocking the DB
$dbHost = 'localhost:3307';
$dbUser = 'root';
$dbPass = '';
$dbName = 'u744799766_cat_eye';
try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check products
    $count = $pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn();
    echo "Active Products: $count\n";
    
    // Check if any tracked inventory products
    $trackedCount = $pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1 AND COALESCE(track_inventory, 1) = 1")->fetchColumn();
    echo "Tracked Inventory Products: $trackedCount\n";
    
    // Get some names
    $names = $pdo->query("
        SELECT pt.name 
        FROM products p 
        LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_COLUMN);
    echo "Example names: " . implode(', ', $names) . "\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
