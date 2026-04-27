<?php
// Mock GET parameters
$_GET['search'] = 'high';
$_GET['warehouse_id'] = '1';

// Directly include and capture output
ob_start();
require_once 'admin/inventory/stock-transfers/search-products.php';
$output = ob_get_clean();

echo "=== AJAX OUTPUT ===\n";
echo $output;
echo "\n===================\n";

// Manual SQL Check again
require_once 'config.php';
$pdo = $db->getConnection();
$search = 'high';
$warehouseId = 1;
$langId = 1; 
$searchTerm = "%" . $search . "%";

$sql = "
    SELECT 
        p.id, 
        pt_current.name as current_name,
        pt_any.name as any_name
    FROM product_warehouse_stock pws
    INNER JOIN products p ON p.id = pws.product_id AND p.is_active = 1 AND COALESCE(p.track_inventory, 1) = 1
    LEFT JOIN product_translations pt_current ON p.id = pt_current.product_id AND pt_current.language_id = ?
    LEFT JOIN product_translations pt_any ON p.id = pt_any.product_id AND pt_any.language_id = (
        SELECT MIN(pt2.language_id) FROM product_translations pt2 WHERE pt2.product_id = p.id
    )
    LEFT JOIN product_variants pv ON pv.id = pws.variant_id AND pv.is_active = 1
    WHERE pws.warehouse_id = ?
    AND (COALESCE(pws.quantity, 0) - COALESCE(pws.reserved_quantity, 0)) > 0
    AND (
        ? = '' OR
        EXISTS (
            SELECT 1 FROM product_translations pt_search 
            WHERE pt_search.product_id = p.id 
            AND (pt_search.name LIKE ? OR COALESCE(pt_search.description, '') LIKE ?)
        )
        OR p.sku LIKE ?
    )
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$langId, $warehouseId, $search, $searchTerm, $searchTerm, $searchTerm]);
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Manual SQL count: " . count($res) . "\n";
print_r($res);
?>
