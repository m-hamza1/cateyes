<?php
$dbHost = 'localhost:3307';
$dbUser = 'root';
$dbPass = '';
$dbName = 'u744799766_cat_eye';
try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables in $dbName (" . count($tables) . "):\n";
    print_r($tables);
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
