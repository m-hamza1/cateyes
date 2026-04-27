<?php
require_once 'config.php';
global $db;

echo "<h3>Database Charset Debug</h3>";
echo "DB_CHARSET: " . DB_CHARSET . "<br>";

try {
    $pdo = $db->getConnection();
    
    // Check connection variables
    echo "<h4>Connection Variables:</h4>";
    $vars = $pdo->query("SHOW VARIABLES LIKE 'char%'")->fetchAll();
    foreach ($vars as $v) {
        echo $v['Variable_name'] . ": " . $v['Value'] . "<br>";
    }
    
    echo "<h4>Table Collation (product_translations):</h4>";
    $tables = $pdo->query("SHOW TABLE STATUS LIKE 'product_translations'")->fetch();
    echo "Collation: " . $tables['Collation'] . "<br>";
    
    echo "<h4>Sample Data (Arabic):</h4>";
    $data = $pdo->query("SELECT * FROM product_translations WHERE language_id = 2 LIMIT 1")->fetch();
    if ($data) {
        echo "Raw Name: " . $data['name'] . "<br>";
        echo "Encoded? " . mb_detect_encoding($data['name']) . "<br>";
    } else {
        echo "No Arabic translation found.";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
