<?php
/**
 * Database Connection Test - Hostinger Diagnostic
 * Run: https://cateyesbeauty.com/db-connection-test.php
 * DELETE this file after fixing! (security)
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

$hostsToTry = ['127.0.0.1', 'localhost', 'mysql'];
$dbName = 'u138580725_cateyes';
$dbUser = 'u138580725_cateyes';
$dbPass = 'Cateyesbeauty.com@1122';
$charset = 'utf8mb4';

echo "<h3>Hostinger DB Connection Test</h3>";
echo "<p><strong>Domain:</strong> " . htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'unknown') . "</p>";
$localCfg = __DIR__ . '/local-config.php';
if (file_exists($localCfg)) {
    echo "<p style='background:#fff3cd;padding:8px;'><strong>⚠ local-config.php exists on server.</strong> ";
    echo "Old config used it on live too → <code>localhost:3307</code> → Connection refused. ";
    echo "Upload latest <code>config.php</code> (fix applied) OR delete/rename <code>local-config.php</code> on Hostinger.</p>";
}
$sock = ini_get('pdo_mysql.default_socket') ?: ini_get('mysqli.default_socket');
if ($sock) {
    echo "<p><strong>PHP MySQL socket:</strong> " . htmlspecialchars($sock) . "</p>";
}
echo "<p><strong>DB:</strong> $dbName | <strong>User:</strong> $dbUser</p><hr>";

$workingHost = null;
if ($sock) {
    try {
        $dsn = "mysql:unix_socket=$sock;dbname=$dbName;charset=$charset";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $stmt = $pdo->query("SELECT COUNT(*) as c FROM information_schema.tables WHERE table_schema = '$dbName'");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0;
        echo "<p style='color:green;'>✓ <strong>unix_socket (php.ini)</strong> — OK (tables: $count)</p>";
        $workingHost = 'unix_socket';
    } catch (PDOException $e) {
        echo "<p style='color:red;'>✗ <strong>unix_socket</strong> — " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
foreach ($hostsToTry as $h) {
    try {
        $dsn = "mysql:host=$h;dbname=$dbName;charset=$charset";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $stmt = $pdo->query("SELECT COUNT(*) as c FROM information_schema.tables WHERE table_schema = '$dbName'");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0;
        echo "<p style='color:green;'>✓ <strong>$h</strong> — OK (tables: $count)</p>";
        if ($workingHost === null) $workingHost = $h;
    } catch (PDOException $e) {
        echo "<p style='color:red;'>✗ <strong>$h</strong> — " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

echo "<hr>";
if ($workingHost) {
    echo "<p style='color:green;font-weight:bold;'>→ Use HOSTINGER_DB_HOST = '$workingHost' in hostinger-config.php</p>";
} else {
    echo "<h4>All hosts failed. Check:</h4>";
    echo "<ul>";
    echo "<li><strong>Hostinger hPanel → Databases</strong>: Is user <code>$dbUser</code> added to database <code>$dbName</code>?</li>";
    echo "<li><strong>Password</strong>: No extra spaces. Special chars (@, etc.) correct?</li>";
    echo "<li><strong>Domain</strong>: Is cateyesbeauty.com pointing to THIS Hostinger account?</li>";
    echo "</ul>";
}
echo "<p><small>⚠️ DELETE this file after testing!</small></p>";
