<?php
// Force local connection for CLI debug
define('DB_HOST', 'localhost');
define('DB_NAME', 'ecommerce_db_new');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

echo "<h3>Database Charset Debug (FORCED LOCAL)</h3>";

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
    ]);
    
    echo "<h4>Connection Variables:</h4>";
    $vars = $pdo->query("SHOW VARIABLES LIKE 'char%'")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($vars as $v) {
        echo $v['Variable_name'] . ": " . $v['Value'] . "\n";
    }
    
    echo "<h4>Table Collation (product_translations):</h4>";
    $tables = $pdo->query("SHOW TABLE STATUS LIKE 'product_translations'")->fetch(PDO::FETCH_ASSOC);
    echo "Collation: " . $tables['Collation'] . "\n";
    
    echo "<h4>Sample Data (Arabic Language ID: 2):</h4>";
    $data = $pdo->query("SELECT * FROM product_translations WHERE language_id = 2 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($data) {
        echo "Raw Name: " . $data['name'] . "\n";
        echo "Hex: " . bin2hex($data['name']) . "\n";
    } else {
        echo "No Arabic translation found.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
