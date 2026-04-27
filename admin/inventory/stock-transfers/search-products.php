<?php
// Get products for search (AJAX endpoint for stock transfers)
// Searches products that have stock in the selected warehouse
$search = trim($_GET['search'] ?? '');
$warehouseId = isset($_GET['warehouse_id']) ? (int)$_GET['warehouse_id'] : 0;

// Log search requests for debugging
$logLine = date('Y-m-d H:i:s') . " - Search: '$search', Warehouse: $warehouseId\n";
@file_put_contents(__DIR__ . '/../../../logs/search_debug.log', $logLine, FILE_APPEND);

try {
    require_once __DIR__ . '/../../../config.php';
    $pdo = $db->getConnection();
    
    // Explicitly find current language from session if $lang object fails
    $langId = 1;
    if (isset($lang) && method_exists($lang, 'getCurrentId')) {
        $langId = (int)$lang->getCurrentId();
    } elseif (isset($_SESSION['language_id'])) {
        $langId = (int)$_SESSION['language_id'];
    }
    
    $searchTerm = "%" . $search . "%";
    
    // Simplified but robust query
    // 1. We remove track_inventory check temporarily to see if that's the cause
    // 2. We use a more permissive JOIN
    // 3. We search name, sku in ALL possible translations
    $sql = "
        SELECT 
            p.id, 
            pws.variant_id as variant_id,
            COALESCE(pv.sku, p.sku) as sku,
            COALESCE(pv.price, p.price, 0) as price,
            CONCAT(
                COALESCE(
                    (SELECT name FROM product_translations pt WHERE pt.product_id = p.id AND pt.language_id = ? LIMIT 1),
                    (SELECT name FROM product_translations pt WHERE pt.product_id = p.id LIMIT 1),
                    p.sku,
                    'Product #'
                ),
                IF(
                    pv.id IS NOT NULL,
                    CONCAT(
                        ' (',
                        COALESCE((
                            SELECT GROUP_CONCAT(av.value SEPARATOR ', ') 
                            FROM product_variant_attributes pva 
                            JOIN attribute_values av ON pva.attribute_value_id = av.id 
                            WHERE pva.variant_id = pv.id
                        ), pv.sku, 'Variant'),
                        ')'
                    ),
                    ''
                )
            ) as name,
            b.name as brand_name,
            (COALESCE(pws.quantity, 0) - COALESCE(pws.reserved_quantity, 0)) as stock_quantity
        FROM product_warehouse_stock pws
        JOIN products p ON p.id = pws.product_id
        LEFT JOIN brands b ON p.brand_id = b.id
        LEFT JOIN product_variants pv ON pv.id = pws.variant_id
        WHERE pws.warehouse_id = ?
        AND p.is_active = 1
        AND (COALESCE(pws.quantity, 0) - COALESCE(pws.reserved_quantity, 0)) > 0
        AND (
            ? = '' OR
            EXISTS (
                SELECT 1 FROM product_translations pt_search 
                WHERE pt_search.product_id = p.id 
                AND (pt_search.name LIKE ? OR COALESCE(pt_search.description, '') LIKE ?)
            )
            OR p.sku LIKE ?
            OR COALESCE(pv.sku, '') LIKE ?
        )
        ORDER BY name ASC
        LIMIT 100
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $langId,           // for current name
        $warehouseId,      // for stock filter
        $search,           // empty check
        $searchTerm,       // name search
        $searchTerm,       // desc search
        $searchTerm,       // sku search
        $searchTerm        // variant sku search
    ]);
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'products' => $products,
        'count' => count($products),
        'debug' => [
            'warehouse_id' => $warehouseId,
            'search' => $search,
            'lang_id' => $langId,
            'time' => date('H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'products' => []
    ]);
}
?>
