<?php
/**
 * Fix SQL dump for Hostinger / Shared Hosting
 * Removes DEFINER=root@localhost which causes #1227 Access denied on shared hosting
 * 
 * Run: php fix_sql_for_hosting.php
 * Then import: ecommerce_db_hosting.sql
 */

$sourceFile = __DIR__ . '/ecommerce_db (11).sql';
$outputFile = __DIR__ . '/ecommerce_db_hosting.sql';

if (!file_exists($sourceFile)) {
    die("Source file not found: $sourceFile\n");
}

$sql = file_get_contents($sourceFile);

// Remove DEFINER=`root`@`localhost` from PROCEDURE and VIEW (causes #1227 on shared hosting)
$sql = preg_replace('/DEFINER=`[^`]+`@`[^`]+`\s+/', '', $sql);

file_put_contents($outputFile, $sql);
echo "Done! Created: $outputFile\n";
echo "Import this file on Hostinger instead of the original.\n";
