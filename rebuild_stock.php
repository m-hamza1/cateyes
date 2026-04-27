<?php
require_once 'config.php';
require_once 'admin/inventory/inventory_helper.php';
rebuildAllStockFromTransactions($db);
echo "Stock rebuilt successfully for products and variants!";
?>
