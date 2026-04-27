<?php
require_once 'config.php';
$sql = file_get_contents(__DIR__ . '/sql/create_certificates_table.sql');
try {
    // Use the shared connection from config.php
    $pdo = $db->getConnection();
    $pdo->exec($sql);
    echo "Certificates table created successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
