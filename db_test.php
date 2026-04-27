<?php
/**
 * Database connection test - DELETE THIS FILE after debugging!
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Use the unified config for all DB settings
require_once __DIR__ . '/config.php';

echo "<h2>Database Connection Test</h2>";

try {
    $pdo = $db->getConnection();
    echo "<p style='color:green'><strong>✓ Connection successful!</strong></p>";
    echo "<p>Database: " . DB_NAME . "</p>";
    echo "<p>Host: " . DB_HOST . "</p>";
    
    // Test a simple query
    $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "'");
    $count = $stmt->fetchColumn();
    echo "<p>Tables found: " . $count . "</p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red'><strong>✗ Connection failed</strong></p>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr><p><small>Delete db_test.php after debugging for security!</small></p>";
