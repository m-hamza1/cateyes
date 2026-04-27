<?php
require_once '../includes/auth.php';
requirePermission('inventory');

// Check if export parameter is set
if (!isset($_GET['export']) || $_GET['export'] !== 'excel') {
    header('HTTP/1.0 403 Forbidden');
    exit('Access denied');
}

// Get same filters as main report
$warehouseId = $_GET['warehouse_id'] ?? 'all';
$productSearch = trim($_GET['product_search'] ?? '');
$stockStatus = $_GET['stock_status'] ?? 'all';

// Build WHERE clause (same as main report)
$whereConditions = [];
$params = [];

if ($warehouseId !== 'all') {
    $whereConditions[] = "pws.warehouse_id = :warehouse_id";
    $params['warehouse_id'] = $warehouseId;
}

if (!empty($productSearch)) {
    $whereConditions[] = "(p.sku LIKE :product_search OR pt.name LIKE :product_search)";
    $params['product_search'] = "%$productSearch%";
}

if ($stockStatus !== 'all') {
    switch ($stockStatus) {
        case 'in_stock':
            $whereConditions[] = "pws.quantity > 0";
            break;
        case 'low_stock':
            $whereConditions[] = "pws.quantity > 0 AND pws.quantity <= p.low_stock_threshold";
            break;
        case 'out_of_stock':
            $whereConditions[] = "pws.quantity = 0";
            break;
    }
}

$whereConditions[] = "p.is_active = 1";
$whereConditions[] = "COALESCE(p.track_inventory, 1) = 1";
$whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

// Get data for export (same query as main report but without pagination)
$sql = "
    SELECT 
        p.sku,
        pt.name as product_name,
        w.name as warehouse_name,
        COALESCE(pws.quantity, 0) as current_quantity,
        COALESCE(pws.reserved_quantity, 0) as reserved_quantity,
        (COALESCE(pws.quantity, 0) - COALESCE(pws.reserved_quantity, 0)) as available_quantity,
        p.price as selling_price,
        COALESCE(p.cost_price, 0) as unit_cost,
        (COALESCE(pws.quantity, 0) * COALESCE(p.cost_price, 0)) as stock_value,
        CASE 
            WHEN COALESCE(pws.quantity, 0) = 0 THEN 'Out of Stock'
            WHEN COALESCE(pws.quantity, 0) <= p.low_stock_threshold THEN 'Low Stock'
            ELSE 'In Stock'
        END as stock_status,
        p.low_stock_threshold,
        p.track_inventory,
        p.is_active,
        (
            SELECT CONCAT(it.transaction_type, ' on ', DATE_FORMAT(it.created_at, '%Y-%m-%d'))
            FROM inventory_transactions it
            WHERE it.product_id = p.id 
            AND it.warehouse_id = pws.warehouse_id
            ORDER BY it.created_at DESC
            LIMIT 1
        ) as last_movement,
        (
            SELECT COUNT(*)
            FROM inventory_transactions it
            WHERE it.product_id = p.id 
            AND it.warehouse_id = pws.warehouse_id
        ) as total_movements,
        p.updated_at as last_updated

    FROM product_warehouse_stock pws
    LEFT JOIN products p ON pws.product_id = p.id
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang_id
    LEFT JOIN warehouses w ON pws.warehouse_id = w.id
    $whereClause
    ORDER BY 
        CASE 
            WHEN COALESCE(pws.quantity, 0) = 0 THEN 1
            WHEN COALESCE(pws.quantity, 0) <= p.low_stock_threshold THEN 2
            ELSE 3
        END ASC,
        pt.name ASC
";

$inventory = $db->fetchAll($sql, array_merge($params, [
    'lang_id' => $lang->getCurrentId()
]));

// Get currency symbol
$defaultCurrency = $db->fetchOne("SELECT symbol, code FROM currencies WHERE is_default = 1 LIMIT 1");
$currency_symbol = $defaultCurrency['symbol'] ?? '$';

// Set headers for Excel export
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="inventory_report_' . date('Y-m-d_H-i-s') . '.xls"');
header('Cache-Control: max-age=0');
header('Expires: 0');
header('Pragma: public');

// Output Excel compatible HTML
echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>' . __('inventory_report', 'Inventory Report') . '</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .number { text-align: right; }
        .status-in-stock { color: green; font-weight: bold; }
        .status-low-stock { color: orange; font-weight: bold; }
        .status-out-of-stock { color: red; font-weight: bold; }
    </style>
</head>
<body>';

echo '<table>
    <thead>
        <tr>
            <th>' . __('product_name', 'Product Name') . '</th>
            <th>' . __('sku', 'SKU') . '</th>
            <th>' . __('warehouse', 'Warehouse') . '</th>
            <th>' . __('current_quantity', 'Current Quantity') . '</th>
            <th>' . __('reserved_quantity', 'Reserved Quantity') . '</th>
            <th>' . __('available_quantity', 'Available Quantity') . '</th>
            <th>' . __('selling_price', 'Selling Price') . '</th>
            <th>' . __('unit_cost', 'Unit Cost') . '</th>
            <th>' . __('stock_value', 'Stock Value') . '</th>
            <th>' . __('stock_status', 'Stock Status') . '</th>
            <th>' . __('low_stock_threshold', 'Low Stock Threshold') . '</th>
            <th>' . __('last_movement', 'Last Movement') . '</th>
            <th>' . __('total_movements', 'Total Movements') . '</th>
            <th>' . __('last_updated', 'Last Updated') . '</th>
        </tr>
    </thead>
    <tbody>';

foreach ($inventory as $item) {
    $statusClass = $item['stock_status'] === 'In Stock' ? 'status-in-stock' : 
                   ($item['stock_status'] === 'Low Stock' ? 'status-low-stock' : 'status-out-of-stock');
    
    echo '<tr>';
    echo '<td>' . htmlspecialchars($item['product_name'] ?? 'N/A') . '</td>';
    echo '<td>' . htmlspecialchars($item['sku'] ?? 'N/A') . '</td>';
    echo '<td>' . htmlspecialchars($item['warehouse_name'] ?? 'N/A') . '</td>';
    echo '<td class="number">' . number_format($item['current_quantity']) . '</td>';
    echo '<td class="number">' . number_format($item['reserved_quantity']) . '</td>';
    echo '<td class="number">' . number_format($item['available_quantity']) . '</td>';
    echo '<td class="number">' . $currency_symbol . number_format($item['selling_price'], 2) . '</td>';
    echo '<td class="number">' . $currency_symbol . number_format($item['unit_cost'], 2) . '</td>';
    echo '<td class="number">' . $currency_symbol . number_format($item['stock_value'], 2) . '</td>';
    echo '<td class="' . $statusClass . '">' . __($item['stock_status'], $item['stock_status']) . '</td>';
    echo '<td class="number">' . number_format($item['low_stock_threshold']) . '</td>';
    echo '<td>' . htmlspecialchars($item['last_movement'] ?? 'N/A') . '</td>';
    echo '<td class="number">' . number_format($item['total_movements']) . '</td>';
    echo '<td>' . htmlspecialchars($item['last_updated'] ?? 'N/A') . '</td>';
    echo '</tr>';
}

echo '</tbody>
</table>
</body>
</html>';

exit;
?>
