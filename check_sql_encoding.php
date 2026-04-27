<?php
$f = 'ecommerce_db_hostinger.sql';
$content = file_get_contents($f, false, null, 0, 10000);
echo "Detection: " . mb_detect_encoding($content, 'UTF-8, ISO-8859-1, WINDOWS-1256') . "\n";
echo "First 100 chars: " . substr($content, 0, 100) . "\n";

// Find the Arabic translation line
$lines = explode("\n", $content);
foreach ($lines as $line) {
    if (strpos($line, "(6, 1, 2,") !== false) {
        echo "Found Arabic Line: " . $line . "\n";
        echo "Hex of that line fragment: " . bin2hex(substr($line, 0, 100)) . "\n";
    }
}
