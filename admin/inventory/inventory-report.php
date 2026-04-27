<?php
require_once '../includes/auth.php';
requirePermission('inventory');
$pageTitle = __('inventory_report', 'Inventory Report');

// Get default currency symbol from database
$defaultCurrency = $db->fetchOne("SELECT symbol, code FROM currencies WHERE is_default = 1 LIMIT 1");
$currency_symbol = $defaultCurrency['symbol'] ?? '$';
$currency_code = $defaultCurrency['code'] ?? 'USD';

// Get warehouses for filtering
$warehouses = $db->fetchAll("SELECT * FROM warehouses WHERE is_active = 1 ORDER BY name");

// Get filter parameters
$warehouseId = $_GET['warehouse_id'] ?? 'all';
$productSearch = trim($_GET['product_search'] ?? '');
$stockStatus = $_GET['stock_status'] ?? 'all';
$page = max(1, $_GET['page'] ?? 1);
$limit = 50;
$offset = ($page - 1) * $limit;

// Build WHERE clause
$whereConditions = [];
$stockStatusCondition = '';
$params = [];

if ($warehouseId !== 'all') {
    $whereConditions[] = "w.id = :warehouse_id";
    $params['warehouse_id'] = $warehouseId;
}

if (!empty($productSearch)) {
    $whereConditions[] = "(p.sku LIKE :product_search OR pt.name LIKE :product_search OR pv.sku LIKE :product_search)";
    $params['product_search'] = "%$productSearch%";
}

// Use product_warehouse_stock as single source of truth (matches inventory index & export)
if ($stockStatus !== 'all') {
    switch ($stockStatus) {
        case 'in_stock':
            $stockStatusCondition = "COALESCE(pws.quantity, 0) > 0";
            break;
        case 'low_stock':
            $stockStatusCondition = "COALESCE(pws.quantity, 0) > 0 AND COALESCE(pws.quantity, 0) <= COALESCE(p.low_stock_threshold, 5)";
            break;
        case 'out_of_stock':
            $stockStatusCondition = "COALESCE(pws.quantity, 0) = 0";
            break;
    }
}

// Use product_warehouse_stock as single source of truth (matches inventory index, Products page, export)
$baseFrom = "product_warehouse_stock pws
    LEFT JOIN products p ON pws.product_id = p.id
    LEFT JOIN product_variants pv ON pws.variant_id = pv.id AND pv.is_active = 1
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang_id
    LEFT JOIN warehouses w ON pws.warehouse_id = w.id
    WHERE p.is_active = 1 AND COALESCE(p.track_inventory, 1) = 1 AND w.is_active = 1";
$baseWhere = ($warehouseId !== 'all' ? 'AND pws.warehouse_id = :warehouse_id' : '') .
    (!empty($productSearch) ? " AND (p.sku LIKE :product_search OR pt.name LIKE :product_search OR pv.sku LIKE :product_search)" : "") .
    ($stockStatus !== 'all' ? ' AND ' . $stockStatusCondition : '');

$countSql = "SELECT COUNT(*) as total FROM $baseFrom $baseWhere";
$totalResult = $db->fetchOne($countSql, array_merge($params, ['lang_id' => $lang->getCurrentId()]));
$totalProducts = $totalResult ? (int)$totalResult['total'] : 0;
$totalPages = ceil($totalProducts / $limit);

$sql = "
    SELECT 
        p.id,
        p.sku,
        pt.name as product_name,
        pws.variant_id,
        pv.sku as variant_sku,
        (
            SELECT GROUP_CONCAT(CONCAT(a.name, ': ', av.value) SEPARATOR ', ')
            FROM product_variant_attributes pva
            JOIN attribute_values av ON pva.attribute_value_id = av.id
            JOIN attributes a ON av.attribute_id = a.id
            WHERE pva.variant_id = pws.variant_id
        ) as variant_name,
        p.price,
        p.cost_price,
        p.low_stock_threshold,
        p.track_inventory,
        p.is_active,
        w.id as warehouse_id,
        w.name as warehouse_name,
        COALESCE(pws.quantity, 0) as current_quantity,
        COALESCE(pws.reserved_quantity, 0) as reserved_quantity,
        (COALESCE(pws.quantity, 0) - COALESCE(pws.reserved_quantity, 0)) as available_quantity,
        (COALESCE(pws.quantity, 0) * COALESCE(p.cost_price, 0)) as stock_value,
        COALESCE(p.cost_price, 0) as avg_unit_cost,
        CASE 
            WHEN COALESCE(pws.quantity, 0) = 0 THEN 'out_of_stock'
            WHEN COALESCE(pws.quantity, 0) <= COALESCE(p.low_stock_threshold, 5) AND COALESCE(p.low_stock_threshold, 5) > 0 THEN 'low_stock'
            ELSE 'in_stock'
        END as stock_status,
        (SELECT CONCAT(it.transaction_type, ' on ', DATE_FORMAT(it.created_at, '%Y-%m-%d'))
            FROM inventory_transactions it 
                        WHERE it.product_id = p.id AND it.warehouse_id = pws.warehouse_id
                            AND (it.variant_id = pws.variant_id OR (it.variant_id IS NULL AND pws.variant_id IS NULL))
            ORDER BY it.created_at DESC LIMIT 1) as last_movement,
        (SELECT COUNT(*) FROM inventory_transactions it 
                        WHERE it.product_id = p.id AND it.warehouse_id = pws.warehouse_id
                            AND (it.variant_id = pws.variant_id OR (it.variant_id IS NULL AND pws.variant_id IS NULL))
                ) as total_movements
    FROM $baseFrom $baseWhere
    ORDER BY p.sku, w.name
    LIMIT :limit OFFSET :offset
";

$params['lang_id'] = $lang->getCurrentId();
$params['limit'] = $limit;
$params['offset'] = $offset;

$inventory = $db->fetchAll($sql, $params);

// Summary statistics from product_warehouse_stock
$summarySql = "
    SELECT 
        SUM(COALESCE(pws.quantity, 0)) as total_quantity,
        SUM(COALESCE(pws.quantity, 0) * COALESCE(p.cost_price, 0)) as total_stock_value,
        COUNT(DISTINCT pws.product_id) as total_products,
        COUNT(DISTINCT pws.warehouse_id) as total_warehouses
    FROM product_warehouse_stock pws
    LEFT JOIN products p ON pws.product_id = p.id
    LEFT JOIN warehouses w ON pws.warehouse_id = w.id
    WHERE p.is_active = 1 AND COALESCE(p.track_inventory, 1) = 1 AND w.is_active = 1
    " . ($warehouseId !== 'all' ? 'AND pws.warehouse_id = :warehouse_id' : '');

$summaryParams = [];
if ($warehouseId !== 'all') {
    $summaryParams['warehouse_id'] = $warehouseId;
}

$summary = $db->fetchOne($summarySql, $summaryParams);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-boxes"></i> <?php echo __('inventory_report', 'Inventory Report'); ?>
                </h1>
                <div>
                    <button onclick="exportToExcel()" class="btn btn-success">
                        <i class="fas fa-file-excel"></i> <?php echo __('export_excel', 'Export to Excel'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title"><?php echo number_format($summary['total_products'] ?? 0); ?></h5>
                <p class="card-text"><?php echo __('total_products', 'Total Products'); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5 class="card-title"><?php echo number_format($summary['total_warehouses'] ?? 0); ?></h5>
                <p class="card-text"><?php echo __('total_warehouses', 'Total Warehouses'); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title"><?php echo number_format($summary['total_quantity'] ?? 0); ?></h5>
                <p class="card-text"><?php echo __('total_quantity', 'Total Quantity'); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="warehouse_id" class="form-label"><?php echo __('warehouse', 'Warehouse'); ?></label>
                <select class="form-select" id="warehouse_id" name="warehouse_id">
                    <option value="all"><?php echo __('all_warehouses', 'All Warehouses'); ?></option>
                    <?php foreach ($warehouses as $warehouse): ?>
                    <option value="<?php echo $warehouse['id']; ?>" <?php echo $warehouseId == $warehouse['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($warehouse['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="stock_status" class="form-label"><?php echo __('stock_status', 'Stock Status'); ?></label>
                <select class="form-select" id="stock_status" name="stock_status">
                    <option value="all"><?php echo __('all_status', 'All Status'); ?></option>
                    <option value="in_stock" <?php echo $stockStatus === 'in_stock' ? 'selected' : ''; ?>><?php echo __('in_stock', 'In Stock'); ?></option>
                    <option value="low_stock" <?php echo $stockStatus === 'low_stock' ? 'selected' : ''; ?>><?php echo __('low_stock', 'Low Stock'); ?></option>
                    <option value="out_of_stock" <?php echo $stockStatus === 'out_of_stock' ? 'selected' : ''; ?>><?php echo __('out_of_stock', 'Out of Stock'); ?></option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="product_search" class="form-label"><?php echo __('search_product', 'Search Product'); ?></label>
                <input type="text" class="form-control" id="product_search" name="product_search" 
                       value="<?php echo htmlspecialchars($productSearch); ?>" placeholder="<?php echo __('search_by_sku_or_name', 'Search by SKU or Name'); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search"></i> <?php echo __('search', 'Search'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Inventory Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th><?php echo __('product', 'Product'); ?></th>
                        <th><?php echo __('sku', 'SKU'); ?></th>
                        <th><?php echo __('warehouse', 'Warehouse'); ?></th>
                        <th><?php echo __('current_quantity', 'Current'); ?></th>
                        <th><?php echo __('stock_status', 'Status'); ?></th>
                        <th><?php echo __('last_movement', 'Last Movement'); ?></th>
                        <th><?php echo __('total_movements', 'Movements'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($inventory)): ?>
                        <?php foreach ($inventory as $item): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($item['product_name'] ?? 'N/A'); ?></strong>
                                <?php if (!empty($item['variant_id'])): ?>
                                    <div class="text-muted small">
                                        <?php echo __('variant', 'Variant'); ?>: <?php echo htmlspecialchars($item['variant_name'] ?: ($item['variant_sku'] ?? '')); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!$item['is_active']): ?>
                                    <span class="badge bg-danger"><?php echo __('inactive', 'Inactive'); ?></span>
                                <?php endif; ?>
                                <?php if (!$item['track_inventory']): ?>
                                    <span class="badge bg-warning"><?php echo __('not_tracked', 'Not Tracked'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($item['variant_id']) && !empty($item['variant_sku'])): ?>
                                    <div><?php echo htmlspecialchars($item['variant_sku']); ?></div>
                                    <div class="text-muted small"><?php echo __('product_sku', 'Product SKU'); ?>: <?php echo htmlspecialchars($item['sku'] ?? 'N/A'); ?></div>
                                <?php else: ?>
                                    <?php echo htmlspecialchars($item['sku'] ?? 'N/A'); ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($item['warehouse_name'] ?? 'N/A'); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $item['current_quantity'] > 0 ? 'success' : 'danger'; ?>">
                                    <?php echo number_format($item['current_quantity']); ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $statusClass = $item['stock_status'] === 'in_stock' ? 'success' : 
                                             ($item['stock_status'] === 'low_stock' ? 'warning' : 'danger');
                                $statusText = $item['stock_status'] === 'in_stock' ? __('in_stock', 'In Stock') :
                                             ($item['stock_status'] === 'low_stock' ? __('low_stock', 'Low Stock') : __('out_of_stock', 'Out of Stock'));
                                ?>
                                <span class="badge bg-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                            </td>
                            <td><small><?php echo htmlspecialchars($item['last_movement'] ?? 'N/A'); ?></small></td>
                            <td><?php echo number_format($item['total_movements']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">
                                <?php echo __('no_inventory_found', 'No inventory records found'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <nav class="mt-3">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&warehouse_id=<?php echo $warehouseId; ?>&stock_status=<?php echo $stockStatus; ?>&product_search=<?php echo urlencode($productSearch); ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<script>
function exportToExcel() {
    const url = new URL(window.location);
    url.searchParams.set('export', 'excel');
    window.open(url.toString(), '_blank');
}

// Auto-refresh every 5 minutes
setInterval(() => {
    if (confirm('<?php echo __('refresh_inventory', 'Refresh inventory data?'); ?>')) {
        window.location.reload();
    }
}, 300000);
</script>

<?php include '../includes/footer.php'; ?>