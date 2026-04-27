<?php
require_once '../includes/auth.php';
requirePermission('inventory');
$pageTitle = __('stock_management', 'Stock Management');

// Get default currency symbol from database
$defaultCurrency = $db->fetchOne("SELECT symbol, code FROM currencies WHERE is_default = 1 LIMIT 1");
$currency_symbol = $defaultCurrency['symbol'] ?? '$';
$currency_code = $defaultCurrency['code'] ?? 'USD';

// Get stock statistics
$totalProducts = $db->count('products', 'is_active = 1');
$totalWarehouses = $db->count('warehouses', 'is_active = 1');

// Out of Stock & Low Stock
// Use aggregated warehouse stock (single source of truth). Fallback to products table if needed.
try {
    $stockSummary = $db->fetchOne("
        SELECT
            SUM(CASE WHEN t.total_qty <= 0 THEN 1 ELSE 0 END) AS out_of_stock,
            SUM(CASE WHEN t.total_qty > 0 AND t.total_qty <= t.low_stock_threshold THEN 1 ELSE 0 END) AS low_stock
        FROM (
            SELECT
                p.id,
                COALESCE(p.low_stock_threshold, 5) AS low_stock_threshold,
                CASE
                    WHEN COUNT(pws.product_id) = 0 THEN COALESCE(p.stock_quantity, 0)
                    ELSE COALESCE(SUM(COALESCE(pws.quantity, 0) - COALESCE(pws.reserved_quantity, 0)), 0)
                END AS total_qty
            FROM products p
            LEFT JOIN product_warehouse_stock pws ON p.id = pws.product_id
            WHERE p.is_active = 1 AND COALESCE(p.track_inventory, 1) = 1
            GROUP BY p.id
        ) t
    ");

    $outOfStockItems = (int)($stockSummary['out_of_stock'] ?? 0);
    $lowStockItems = (int)($stockSummary['low_stock'] ?? 0);
} catch (Exception $e) {
    // Fallback: products table (same logic as Products page)
    $outOfStockRow = $db->fetchOne("
        SELECT COUNT(*) as cnt FROM products p
        WHERE p.is_active = 1 AND COALESCE(p.track_inventory, 1) = 1
        AND COALESCE(p.stock_quantity, 0) = 0
    ");
    $outOfStockItems = (int)($outOfStockRow['cnt'] ?? 0);

    $lowStockRow = $db->fetchOne("
        SELECT COUNT(*) as cnt FROM products p
        WHERE p.is_active = 1 AND COALESCE(p.track_inventory, 1) = 1
        AND COALESCE(p.stock_quantity, 0) > 0
        AND COALESCE(p.stock_quantity, 0) <= COALESCE(p.low_stock_threshold, 5)
    ");
    $lowStockItems = (int)($lowStockRow['cnt'] ?? 0);
}

// Get recent inventory movements from inventory_transactions
$recentMovements = $db->fetchAll("
    SELECT it.*, 
           p.sku, 
           pt.name as product_name,
           w.name as warehouse_name,
           u.first_name, u.last_name
    FROM inventory_transactions it
    LEFT JOIN products p ON it.product_id = p.id
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang_id
    LEFT JOIN warehouses w ON it.warehouse_id = w.id
    LEFT JOIN users u ON it.created_by = u.id
    ORDER BY it.created_at DESC
    LIMIT 4
", ['lang_id' => $lang->getCurrentId()]);

// Get warehouse stock levels using product_warehouse_stock
$warehouses = $db->fetchAll("SELECT id, name FROM warehouses WHERE is_active = 1 ORDER BY name");
$warehouseStocks = [];

foreach ($warehouses as $warehouse) {
    // Aggregate stock per PRODUCT (sum across variants) - out of stock = product with 0 total in this warehouse
    $stockData = $db->fetchOne("
        SELECT 
            COUNT(*) as product_count,
            SUM(agg.available_qty) as total_quantity,
            SUM(CASE 
                WHEN agg.available_qty > 0 AND agg.available_qty <= agg.low_stock_threshold THEN 1 
                ELSE 0 
            END) as low_stock_count,
            SUM(CASE WHEN agg.available_qty = 0 THEN 1 ELSE 0 END) as out_of_stock_count
        FROM (
            SELECT 
                pws.product_id,
                COALESCE(p.low_stock_threshold, 5) as low_stock_threshold,
                SUM(COALESCE(pws.quantity, 0) - COALESCE(pws.reserved_quantity, 0)) as available_qty
            FROM product_warehouse_stock pws
            INNER JOIN products p ON pws.product_id = p.id AND p.is_active = 1 AND COALESCE(p.track_inventory, 1) = 1
            WHERE pws.warehouse_id = :warehouse_id
            GROUP BY pws.product_id, p.low_stock_threshold
        ) agg
    ", ['warehouse_id' => $warehouse['id']]);
    
    $warehouseStocks[] = [
        'id' => $warehouse['id'],
        'name' => $warehouse['name'],
        'product_count' => $stockData['product_count'] ?? 0,
        'total_quantity' => $stockData['total_quantity'] ?? 0,
        'low_stock_count' => $stockData['low_stock_count'] ?? 0,
        'out_of_stock_count' => $stockData['out_of_stock_count'] ?? 0
    ];
}

// Calculate totals for warehouse stock levels section
$totalWarehouseProducts = 0;
$totalWarehouseQuantity = 0;
$totalWarehouseLowStock = 0;
$totalWarehouseOutOfStock = 0;

foreach ($warehouseStocks as $stock) {
    $totalWarehouseProducts += $stock['product_count'];
    $totalWarehouseQuantity += $stock['total_quantity'];
    $totalWarehouseLowStock += $stock['low_stock_count'];
    $totalWarehouseOutOfStock += $stock['out_of_stock_count'];
}

// Get top products by actual sales from order items (last 30 days)
$topProducts = $db->fetchAll("
    SELECT p.id, pt.name as name, p.sku, p.stock_quantity,
           SUM(oi.quantity) as total_sold,
           COUNT(DISTINCT oi.order_id) as order_count
    FROM products p
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang_id
    LEFT JOIN order_items oi ON p.id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.id AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    WHERE p.is_active = 1
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 10
", ['lang_id' => $lang->getCurrentId()]);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<style>
    .stats-card-premium {
        transition: all 0.3s cubic-bezier(.25,.8,.25,1);
        border: none;
        overflow: hidden;
        position: relative;
    }
    .stats-card-premium:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }
    .stats-card-premium::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, transparent 100%);
        pointer-events: none;
    }
    .btn-group .btn {
        transition: all 0.2s;
        border-radius: 8px !important;
        margin-right: 5px;
        font-weight: 600;
    }
    .btn-group .btn:hover {
        transform: scale(1.05);
    }
    .table-premium tbody tr {
        transition: background-color 0.2s;
    }
    .table-premium tbody tr:hover {
        background-color: rgba(66, 153, 225, 0.05) !important;
    }
</style>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('stock_management', 'Stock Management'); ?></h1>
    <div class="btn-group">
        <a href="purchase-orders/" class="btn btn-outline-primary">
            <i class="fas fa-shopping-cart"></i> <?php echo __('purchase_orders', 'Purchase Orders'); ?>
        </a>
        <a href="sales-returns/" class="btn btn-outline-success">
            <i class="fas fa-undo"></i> <?php echo __('sales_returns', 'Sales Returns'); ?>
        </a>
        <a href="supplier-returns/" class="btn btn-outline-info">
            <i class="fas fa-exchange-alt"></i> <?php echo __('supplier_returns', 'Supplier Returns'); ?>
        </a>
        <a href="production-orders/" class="btn btn-outline-dark">
            <i class="fas fa-industry"></i> <?php echo __('production_orders', 'Production Orders'); ?>
        </a>
        <a href="stock-adjustments/" class="btn btn-outline-warning">
            <i class="fas fa-edit"></i> <?php echo __('stock_adjustments', 'Stock Adjustments'); ?>
        </a>
        <a href="stock-transfers/" class="btn btn-outline-secondary">
            <i class="fas fa-exchange-alt"></i> <?php echo __('stock_transfers', 'Stock Transfers'); ?>
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card bg-primary text-white stats-card-premium">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title"><?php echo $totalProducts; ?></h4>
                        <p class="card-text"><?php echo __('total_products', 'Total Products'); ?></p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-box fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-success text-white stats-card-premium">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title"><?php echo $totalWarehouses; ?></h4>
                        <p class="card-text"><?php echo __('total_warehouses', 'Total Warehouses'); ?></p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-warehouse fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-warning text-white stats-card-premium">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title"><?php echo $lowStockItems; ?></h4>
                        <p class="card-text"><?php echo __('low_stock_items', 'Low Stock Items'); ?></p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-exclamation-triangle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-danger text-white stats-card-premium">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title"><?php echo $outOfStockItems; ?></h4>
                        <p class="card-text"><?php echo __('out_of_stock_items', 'Out of Stock Items'); ?></p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-times-circle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Warehouse Stock Levels -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-warehouse"></i> <?php echo __('warehouse_stock_levels', 'Warehouse Stock Levels'); ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th><?php echo __('warehouse', 'Warehouse'); ?></th>
                                <th><?php echo __('products', 'Products'); ?></th>
                                <th><?php echo __('total_quantity', 'Total Quantity'); ?></th>
                                <th><?php echo __('low_stock', 'Low Stock'); ?></th>
                                <th><?php echo __('out_of_stock', 'Out of Stock'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($warehouseStocks as $stock): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($stock['name']); ?></td>
                                <td><?php echo $stock['product_count']; ?></td>
                                <td><?php echo number_format($stock['total_quantity']); ?></td>
                                <td>
                                    <?php if ($stock['low_stock_count'] > 0): ?>
                                        <span class="badge bg-warning"><?php echo $stock['low_stock_count']; ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($stock['out_of_stock_count'] > 0): ?>
                                        <span class="badge bg-danger"><?php echo $stock['out_of_stock_count']; ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <!-- Total Row - Low/Out of Stock match Products page (products table) -->
                            <tr class="table-primary fw-bold">
                                <td><?php echo __('total', 'Total'); ?></td>
                                <td><?php echo $totalWarehouseProducts; ?></td>
                                <td><?php echo number_format($totalWarehouseQuantity); ?></td>
                                <td>
                                    <?php if ($lowStockItems > 0): ?>
                                        <span class="badge bg-warning"><?php echo $lowStockItems; ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($outOfStockItems > 0): ?>
                                        <span class="badge bg-danger"><?php echo $outOfStockItems; ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Stock Movements -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-exchange-alt"></i> <?php echo __('recent_movements', 'Recent Movements'); ?>
                    </h5>
                    <a href="movements.php" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-list me-1"></i><?php echo __('view_all', 'View All'); ?>
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th><?php echo __('date', 'Date'); ?></th>
                                <th><?php echo __('type', 'Type'); ?></th>
                                <th><?php echo __('product', 'Product'); ?></th>
                                <th><?php echo __('user', 'User'); ?></th>
                                <th><?php echo __('quantity', 'Quantity'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recentMovements)): ?>
                                <?php foreach ($recentMovements as $movement): ?>
                                <tr>
                                    <td><?php echo date('Y-m-d H:i', strtotime($movement['created_at'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo getMovementBadgeClass($movement['transaction_type']); ?>">
                                            <?php echo ucfirst($movement['transaction_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($movement['product_name'] ?? 'N/A'); ?>
                                        <?php if ($movement['sku']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($movement['sku']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($movement['first_name'] . ' ' . $movement['last_name'] ?? 'System'); ?>
                                        <?php if ($movement['warehouse_name']): ?>
                                            <br><small class="text-muted">Warehouse: <?php echo htmlspecialchars($movement['warehouse_name']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($movement['quantity'] > 0): ?>
                                            <span class="text-success">+<?php echo number_format($movement['quantity']); ?></span>
                                        <?php else: ?>
                                            <span class="text-danger"><?php echo number_format($movement['quantity']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted"><?php echo __('no_recent_movements', 'No recent movements'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Top Selling Products -->
<div class="row">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-line"></i> <?php echo __('top_selling_products', 'Top Selling Products (Last 30 Days)'); ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><?php echo __('product', 'Product'); ?></th>
                                <th><?php echo __('sku', 'SKU'); ?></th>
                                <th><?php echo __('total_sold', 'Total Sold'); ?></th>
                                <th><?php echo __('order_count', 'Order Count'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($topProducts)): ?>
                                <?php foreach ($topProducts as $product): ?>
                                <tr>
                                    <td>
                                        <a href="../../products/edit.php?id=<?php echo $product['id']; ?>" target="_blank">
                                            <?php echo htmlspecialchars($product['name'] ?? 'N/A'); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['sku'] ?? 'N/A'); ?></td>
                                    <td><?php echo number_format($product['total_sold'] ?? 0); ?></td>
                                    <td><?php echo $product['order_count'] ?? 0; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted"><?php echo __('no_products_found', 'No products found'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
function getMovementBadgeClass($type) {
    switch ($type) {
        case 'sale':
            return 'danger';
        case 'purchase':
            return 'success';
        case 'adjustment':
            return 'warning';
        case 'transfer_in':
            return 'info';
        case 'transfer_out':
            return 'secondary';
        case 'production':
            return 'primary';
        case 'return':
            return 'dark';
        default:
            return 'secondary';
    }
}

// Helper function to get product name
if (!function_exists('getProductName')) {
    function getProductName($productId, $db, $lang) {
        $product = $db->fetchOne("
            SELECT pt.name 
            FROM product_translations pt 
            WHERE pt.product_id = :product_id AND pt.language_id = :lang_id
        ", ['product_id' => $productId, 'lang_id' => $lang->getCurrentId()]);
        return $product['name'] ?? 'N/A';
    }
}
?>

<?php include '../includes/footer.php'; ?>
