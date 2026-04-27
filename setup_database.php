<?php
/**
 * Local database setup for Bug-Fix (XAMPP / phpMyAdmin)
 * - Creates database: ecommerce_db_new
 * - Generates SQL file without DEFINER for local import
 * Run once: http://localhost/public_html (4)/setup_database.php
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'u744799766_cat_eye';
$sqlDump  = __DIR__ . '/DataBase/u138580725_cateyesbeauty (1).sql';
$sqlLocal = __DIR__ . '/sql/import_for_local.sql';

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Database Setup</title>";
echo "<style>body{font-family:sans-serif;max-width:600px;margin:40px auto;padding:20px;} .ok{color:green;} .err{color:red;} pre{background:#f0f0f0;padding:10px;} a{color:#06c;}</style></head><body>";
echo "<h1>Bug-Fix – Local DB Setup</h1>";

// 1) Create database
try {
    $pdo = new PDO("mysql:host=$dbHost", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p class='ok'>✓ Database <strong>$dbName</strong> created (or already exists).</p>";
} catch (PDOException $e) {
    echo "<p class='err'>✗ Could not create database: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Make sure XAMPP MySQL is running and user <code>root</code> has no password (default).</p></body></html>";
    exit;
}

// 2) Generate local SQL (remove DEFINER so root can import)
if (!is_readable($sqlDump)) {
    echo "<p class='err'>✗ Dump file not found: <code>sql/u600298837_ssset (1).sql</code></p></body></html>";
    exit;
}

$sql = file_get_contents($sqlDump);
$sql = preg_replace('/\s*DEFINER\s*=\s*`[^`]+`@`[^`]+`\s*/i', ' ', $sql);
$header = "-- Local import for XAMPP (ecommerce_db_new)\n";
$header .= "USE `$dbName`;\n\n";
file_put_contents($sqlLocal, $header . $sql);
echo "<p class='ok'>✓ Local SQL file created: <code>sql/import_for_local.sql</code> (DEFINER removed for local MySQL).</p>";

// 3) Auto-import: run mysql CLI to create all tables (procedures, views, data)
$mysqlExe = 'C:\\xampp-1\\mysql\\bin\\mysql.exe';
if (!file_exists($mysqlExe)) {
    $mysqlExe = 'C:\\xampp\\mysql\\bin\\mysql.exe';
}
$imported = false;
if (file_exists($mysqlExe)) {
    $sqlPath = $sqlLocal;
    $spec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ];
    $cmd = '"' . $mysqlExe . '" -u root ' . ($dbPass !== '' ? '-p' . $dbPass : '') . ' ' . $dbName;
    $proc = @proc_open($cmd, $spec, $pipes, null, null, ['bypass_shell' => true]);
    if (is_resource($proc)) {
        $fh = fopen($sqlPath, 'rb');
        if ($fh) {
            stream_copy_to_stream($fh, $pipes[0]);
            fclose($fh);
        }
        fclose($pipes[0]);
        $out = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($proc);
        if (trim($err) === '' || stripos($err, 'ERROR') === false) {
            $imported = true;
        }
    }
}

if ($imported) {
    echo "<p class='ok'>✓ <strong>Saari tables, procedures, views aur data import ho gaye.</strong> Database ready.</p>";
} else {
    echo "<p><strong>Tables import (manual):</strong> phpMyAdmin kholen → database <strong>$dbName</strong> select karein → Import → file <code>sql/import_for_local.sql</code> choose karein → Go.</p>";
}

echo "<p class='ok'><strong>Next:</strong> Open the site → <a href='http://localhost/public_html%20(4)/'>http://localhost/public_html (4)/</a></p>";
echo "<p>Admin: <a href='http://localhost/public_html%20(4)/admin/'>http://localhost/public_html (4)/admin/</a> (login from <code>users</code> table if needed).</p>";
echo "</body></html>";
