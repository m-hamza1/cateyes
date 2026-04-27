<?php
require_once '../../includes/auth.php';

$search = trim($_GET['search'] ?? '');
$warehouseId = isset($_GET['warehouse_id']) ? (int)$_GET['warehouse_id'] : 0;

header('Content-Type: application/json');

if ($search === '') {
    echo json_encode(['success' => true, 'products' => []]);
    exit;
}

try {
    $langId = isset($lang) && method_exists($lang, 'getCurrentId') ? (int)$lang->getCurrentId() : (int)($_SESSION['language_id'] ?? 1);
    $pdo = $db->getConnection();

    $searchTerm = '%' . $search . '%';

    $stmt = $pdo->prepare("
        SELECT
            p.id as id,
            pv.id as variant_id,
            COALESCE(pv.sku, p.sku, '') as sku,
            COALESCE(pv.price, p.price, 0) as price,
            CONCAT(
                COALESCE(pt.name, pt_default.name, p.sku, ''),
                IF(
                    pv.id IS NOT NULL,
                    CONCAT(
                        ' (',
                        COALESCE((
                            SELECT GROUP_CONCAT(av.value SEPARATOR ', ')
                            FROM product_variant_attributes pva
                            JOIN attribute_values av ON pva.attribute_value_id = av.id
                            WHERE pva.variant_id = pv.id
                        ), pv.sku),
                        ')'
                    ),
                    ''
                )
            ) as name,
            GREATEST(COALESCE(pws.quantity, 0) - COALESCE(pws.reserved_quantity, 0), 0) as stock_quantity
        FROM products p
        LEFT JOIN product_translations pt
            ON p.id = pt.product_id AND pt.language_id = :lang_id
        LEFT JOIN product_translations pt_default
            ON p.id = pt_default.product_id AND pt_default.language_id = 1
        LEFT JOIN product_variants pv
            ON p.id = pv.product_id AND pv.is_active = 1
        LEFT JOIN product_warehouse_stock pws
            ON p.id = pws.product_id
            AND pws.warehouse_id = :warehouse_id
            AND (pws.variant_id = pv.id OR (pws.variant_id IS NULL AND pv.id IS NULL))
        WHERE p.is_active = 1
            AND COALESCE(p.track_inventory, 1) = 1
            AND (
                COALESCE(pt.name, pt_default.name, '') LIKE :search1
                OR COALESCE(pt.description, pt_default.description, '') LIKE :search2
                OR p.sku LIKE :search3
                OR p.slug LIKE :search5
                OR COALESCE(pv.sku, '') LIKE :search4
            )
        ORDER BY COALESCE(pt.name, pt_default.name, '') ASC, pv.id ASC
        LIMIT 100
    ");

    $stmt->bindValue(':lang_id', $langId, PDO::PARAM_INT);
    $stmt->bindValue(':warehouse_id', $warehouseId, PDO::PARAM_INT);
    $stmt->bindValue(':search1', $searchTerm, PDO::PARAM_STR);
    $stmt->bindValue(':search2', $searchTerm, PDO::PARAM_STR);
    $stmt->bindValue(':search3', $searchTerm, PDO::PARAM_STR);
    $stmt->bindValue(':search4', $searchTerm, PDO::PARAM_STR);
    $stmt->bindValue(':search5', $searchTerm, PDO::PARAM_STR);

    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'products' => $products]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
