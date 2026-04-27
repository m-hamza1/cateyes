<?php
/**
 * Perfect Database Connection Test - Hostinger Diagnostic
 * Loads settings from hostinger-config.php and config.php
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>Hostinger Project - Perfect Connection Test</h3>";

// 1. Check Config Loading
require_once 'config.php';
echo "<p>✓ <code>config.php</code> loaded successfully.</p>";

// 2. Display used constants
echo "<h4>Active Database Settings:</h4>";
echo "<ul>";
echo "<li><strong>DB_HOST:</strong> " . DB_HOST . "</li>";
echo "<li><strong>DB_NAME:</strong> " . DB_NAME . "</li>";
echo "<li><strong>DB_USER:</strong> " . DB_USER . "</li>";
echo "<li><strong>DB_PASS:</strong> " . (DB_PASS ? "******** (Set)" : "EMPTY") . "</li>";
echo "<li><strong>BASE_URL:</strong> " . BASE_URL . "</li>";
echo "</ul>";

// 3. Test Connection via PDO (used by the app)
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "<p style='color:green; font-weight:bold;'>✓ [PDO] Connection Successful!</p>";
    
    // 4. Test Table access
    echo "<h4>Table Check:</h4>";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($tables)) {
        echo "<p>✓ Found " . count($tables) . " tables in database.</p>";
        echo "<ul>";
        foreach (array_slice($tables, 0, 5) as $table) echo "<li>$table</li>";
        if (count($tables) > 5) echo "<li>... and others.</li>";
        echo "</ul>";
    } else {
        echo "<p style='color:orange;'>! Database is empty. Please import your SQL file.</p>";
    }

    // 5. Test Settings Table (Core App logic)
    try {
        $check = $pdo->query("SELECT COUNT(*) FROM site_settings")->fetchColumn();
        echo "<p style='color:green;'>✓ <code>site_settings</code> table is readable. (Rows: $check)</p>";
    } catch (Exception $e) {
         echo "<p style='color:red;'>✗ <code>site_settings</code> check failed: " . $e->getMessage() . "</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color:red; font-weight:bold;'>✗ Connection Failed: " . $e->getMessage() . "</p>";
    echo "<h5>Common Fixes:</h5><ul>";
    echo "<li>Double check if <code>DB_USER</code> is added to <code>DB_NAME</code> in Hostinger panel.</li>";
    echo "<li>Ensure <code>DB_PASS</code> is correct (no extra spaces).</li>";
    echo "<li>Try changing <code>HOSTINGER_DB_HOST</code> to <code>'127.0.0.1'</code> in <code>hostinger-config.php</code>.</li>";
    echo "</ul>";
}

echo "<hr><p><small>⚠️ Security Reminder: Delete this file after your site is live.</p>";
