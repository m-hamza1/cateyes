<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'ecommerce_db_new';

try {
    $pdo = new PDO("mysql:host=localhost:3307", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("SHOW DATABASES LIKE '$dbname'");
    if ($stmt->fetch()) {
        echo "Database $dbname exists.\n";
        $pdo->query("USE $dbname");
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "Tables: " . implode(', ', $tables) . "\n";
    } else {
        echo "Database $dbname does NOT exist.\n";
    }
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
