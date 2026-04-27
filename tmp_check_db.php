<?php
require_once 'config.php';
try {
    $tables = ['stock_transfers', 'stock_transfer_items'];
    foreach ($tables as $table) {
        echo "DESCRIBE $table:\n";
        $columns = $db->fetchAll("DESCRIBE $table");
        print_r($columns);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
