<?php
$file = 'checkout.php';
if (file_exists($file)) {
    echo "File size: " . filesize($file) . " bytes\n";
    $content = file_get_contents($file, false, null, 0, 100);
    echo "First 100 bytes (hex): " . bin2hex($content) . "\n";
    echo "First 100 bytes (text): [" . $content . "]\n";
} else {
    echo "File not found\n";
}
