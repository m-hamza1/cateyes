<?php
$dbHost = 'localhost:3307';
$dbUser = 'root';
$dbPass = '';
try {
    $pdo = new PDO("mysql:host=$dbHost", $dbUser, $dbPass);
    echo "✓ Success: Connected to MySQL on $dbHost\n";
    $stmt = $pdo->query("SHOW DATABASES");
    $dbs = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Databases found: " . implode(', ', $dbs) . "\n";
} catch (PDOException $e) {
    echo "✗ Failed to connect to MySQL on $dbHost: " . $e->getMessage() . "\n";
}
