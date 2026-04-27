<?php
require_once 'config.php';
header('Content-Type: text/plain');

echo "IMAGE DEBUGGING TOOL\n";
echo "====================\n";
echo "BASE_URL: " . BASE_URL . "\n";
echo "UPLOADS_URL: " . UPLOADS_URL . "\n";
echo "UPLOADS_PATH: " . UPLOADS_PATH . "\n";

$products = $db->fetchAll("SELECT id, slug, main_image FROM products LIMIT 5");

foreach ($products as $p) {
    echo "\nProduct ID: " . $p['id'] . "\n";
    echo "Slug: " . $p['slug'] . "\n";
    echo "Main Image (DB): " . ($p['main_image'] ?: "NULL") . "\n";
    
    $fullUrl = UPLOADS_URL . 'products/' . $p['main_image'];
    $fullPath = UPLOADS_PATH . 'products/' . $p['main_image'];
    
    echo "Full URL: " . $fullUrl . "\n";
    echo "Full Path: " . $fullPath . "\n";
    
    if ($p['main_image']) {
        if (file_exists($fullPath)) {
            echo "FILE EXISTS ON SERVER: YES\n";
            $size = filesize($fullPath);
            echo "File Size: " . $size . " bytes\n";
        } else {
            echo "FILE EXISTS ON SERVER: NO\n";
            // Try Case-Insensitive Check or parent dir check
            $parentDir = UPLOADS_PATH . 'products/';
            if (is_dir($parentDir)) {
                echo "Directory 'products/' exists: YES\n";
                // List files in directory
                $files = scandir($parentDir);
                echo "Files in directory (first 5): " . implode(", ", array_slice($files, 0, 7)) . "\n";
            } else {
                echo "Directory 'products/' exists: NO\n";
                // Check uploads dir
                if (is_dir(UPLOADS_PATH)) {
                    echo "Directory 'uploads/' exists: YES\n";
                    $subdirs = scandir(UPLOADS_PATH);
                    echo "Subdirs in uploads/: " . implode(", ", $subdirs) . "\n";
                }
            }
        }
    }
}
?>
