<?php
// Files to delete from purchase-orders folder
$poFilesToDelete = [
    'confirm.php',
    'process-confirmation.php', 
    'test-db.php',
    'test-search.php'
];

// Files to delete from supplier-returns folder
$srFilesToDelete = [
    'approve.php',
    'reject.php',
    'search-products.php',
    'test-search.php',
    'test-search-ui.html',
    'update-database.php',
    'get-purchase-order-items.php'
];

$poDir = __DIR__ . '/purchase-orders/';
$srDir = __DIR__ . '/supplier-returns/';

echo "Deleting unnecessary files from purchase-orders folder:\n";
foreach ($poFilesToDelete as $file) {
    $filePath = $poDir . $file;
    if (file_exists($filePath)) {
        if (unlink($filePath)) {
            echo "✓ Deleted: $file\n";
        } else {
            echo "✗ Failed to delete: $file\n";
        }
    } else {
        echo "- File not found: $file\n";
    }
}

echo "\nDeleting unnecessary files from supplier-returns folder:\n";
foreach ($srFilesToDelete as $file) {
    $filePath = $srDir . $file;
    if (file_exists($filePath)) {
        if (unlink($filePath)) {
            echo "✓ Deleted: $file\n";
        } else {
            echo "✗ Failed to delete: $file\n";
        }
    } else {
        echo "- File not found: $file\n";
    }
}

echo "\nCleanup completed!\n";
?>
