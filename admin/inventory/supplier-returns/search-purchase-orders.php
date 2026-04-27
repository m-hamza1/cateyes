<?php
require_once '../../includes/auth.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get search term
$searchTerm = $_GET['search'] ?? '';

error_log("Search term received: " . $searchTerm);

if (empty($searchTerm)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Search term is required']);
    exit;
}

try {
    // Check for specific status filter, otherwise use default multiple statuses
    $statusFilter = isset($_GET['status']) && !empty($_GET['status']) ? "po.status = :status_filter" : "po.status IN ('draft', 'pending', 'confirmed', 'received')";

    // Search purchase orders by PO number or digits
    $sql = "
        SELECT po.id, po.po_number, po.supplier_id, po.warehouse_id, 
               s.name as supplier_name, w.name as warehouse_name, po.status, po.total_amount
        FROM purchase_orders po
        LEFT JOIN suppliers s ON po.supplier_id = s.id
        LEFT JOIN warehouses w ON po.warehouse_id = w.id
        WHERE {$statusFilter}
        AND (po.po_number LIKE :search_term OR po.id LIKE :search_term)
        ORDER BY po.created_at DESC
        LIMIT 10
    ";
    
    error_log("SQL: " . $sql);
    error_log("Search term param: %" . $searchTerm . "%");
    
    $params = ['search_term' => '%' . $searchTerm . '%'];
    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $params['status_filter'] = $_GET['status'];
    }
    
    $purchaseOrders = $db->fetchAll($sql, $params);
    
    error_log("Found " . count($purchaseOrders) . " purchase orders");
    
    // If no results, try to find any purchase orders to debug
    if (count($purchaseOrders) == 0) {
        $allPOs = $db->fetchAll("SELECT id, po_number, status FROM purchase_orders ORDER BY created_at DESC LIMIT 5");
        error_log("All purchase orders in database: " . json_encode($allPOs));
    }

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'purchase_orders' => $purchaseOrders
    ]);

} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error searching purchase orders: ' . $e->getMessage()
    ]);
}
?>
