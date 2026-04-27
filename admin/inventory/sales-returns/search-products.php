<?php
// Get products for search (AJAX endpoint)
$search = trim($_GET['search'] ?? '');
$supplierId = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 0;

$warehouseId = isset($_GET['warehouse_id']) ? (int)$_GET['warehouse_id'] : 0;

try {
    // Use unified config which creates $db automatically
    require_once __DIR__ . '/../../../config.php';
    
    // Use the shared Database connection
    $pdo = $db->getConnection();
    
    $where = ["p.is_active = 1"];
    $params = [];
    
    if (!empty($search)) {
        $searchTerm = "%$search%";
        $where[] = "(pt.name LIKE :search1 OR p.sku LIKE :search2 OR pt.description LIKE :search3)";
        $params['search1'] = $searchTerm;
        $params['search2'] = $searchTerm;
        $params['search3'] = $searchTerm;
    }
    
    $params['warehouse_id'] = $warehouseId;
    $whereClause = implode(' AND ', $where);
    
    // Modified query to include variants and stock information
    $stmt = $pdo->prepare("
        SELECT 
            p.id, 
            pv.id as variant_id,
            COALESCE(pv.sku, p.sku) as sku,
            COALESCE(pv.price, p.price) as price,
            CONCAT(pt.name, IF(pv.id IS NOT NULL, CONCAT(' (', 
                (SELECT GROUP_CONCAT(av.value SEPARATOR ', ') 
                 FROM product_variant_attributes pva 
                 JOIN attribute_values av ON pva.attribute_value_id = av.id 
                 WHERE pva.variant_id = pv.id), 
            ')'), '')) as name,
            pt.short_description as description,
            b.name as brand_name,
            COALESCE(pws.quantity, 0) as stock_quantity
        FROM products p
        LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
        LEFT JOIN brands b ON p.brand_id = b.id
        LEFT JOIN product_variants pv ON p.id = pv.product_id AND pv.is_active = 1
        LEFT JOIN product_warehouse_stock pws ON p.id = pws.product_id 
            AND pws.warehouse_id = :warehouse_id 
            AND (pws.variant_id = pv.id OR (pws.variant_id IS NULL AND pv.id IS NULL))
        WHERE $whereClause 
        AND ((p.product_type != 'variable' AND pv.id IS NULL) OR (p.product_type = 'variable' AND pv.id IS NOT NULL))
        ORDER BY pt.name ASC, pv.id ASC
        LIMIT 100
    ");
    
    // Bind parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'products' => $products
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
