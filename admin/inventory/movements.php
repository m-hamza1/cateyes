<?php
require_once '../includes/auth.php';
requirePermission('inventory');
$pageTitle = __('stock_movements', 'Stock Movements');

// Get filter parameters
$page = max(1, $_GET['page'] ?? 1);
$limit = 50;
$offset = ($page - 1) * $limit;

$filters = [
    'movement_type' => $_GET['movement_type'] ?? '',
    'product_id' => $_GET['product_id'] ?? '',
    'user_id' => $_GET['user_id'] ?? '',
    'reference_type' => $_GET['reference_type'] ?? '',
    'reference_id' => $_GET['reference_id'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? ''
];

// Build WHERE conditions
$whereConditions = [];
$params = ['lang_id' => $lang->getCurrentId()];

if (!empty($filters['movement_type'])) {
    $whereConditions[] = "it.transaction_type = :movement_type";
    $params['movement_type'] = $filters['movement_type'];
}

if (!empty($filters['product_id'])) {
    $whereConditions[] = "it.product_id = :product_id";
    $params['product_id'] = $filters['product_id'];
}

if (!empty($filters['user_id'])) {
    $whereConditions[] = "it.created_by = :user_id";
    $params['user_id'] = $filters['user_id'];
}

if (!empty($filters['reference_type'])) {
    $whereConditions[] = "it.reference_type = :reference_type";
    $params['reference_type'] = $filters['reference_type'];
}

if (!empty($filters['reference_id'])) {
    $whereConditions[] = "it.reference_id = :reference_id";
    $params['reference_id'] = $filters['reference_id'];
}

if (!empty($filters['date_from'])) {
    $whereConditions[] = "DATE(it.created_at) >= :date_from";
    $params['date_from'] = $filters['date_from'];
}

if (!empty($filters['date_to'])) {
    $whereConditions[] = "DATE(it.created_at) <= :date_to";
    $params['date_to'] = $filters['date_to'];
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// SINGLE SOURCE: inventory_transactions - all stock movements (purchase, sale, adjustment, transfer, production, returns) flow through here
$queryParams = array_merge($params, ['lang_id' => $lang->getCurrentId()]);

// Get total count for pagination
$countResult = $db->fetchOne("
    SELECT COUNT(*) as total
    FROM inventory_transactions it
    LEFT JOIN products p ON it.product_id = p.id
    $whereClause
", $queryParams);
$totalMovements = (int)($countResult['total'] ?? 0);
$totalPages = ceil($totalMovements / $limit);

// Fetch movements with proper pagination
$inventoryTransactions = $db->fetchAll("
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
    $whereClause
    ORDER BY it.created_at DESC
    LIMIT :limit OFFSET :offset
", array_merge($queryParams, ['limit' => $limit, 'offset' => $offset]));

// Build movements list (inventory_transactions is the single source - no duplicates)
$allMovements = [];
foreach ($inventoryTransactions as $transaction) {
    $allMovements[] = [
        'id' => $transaction['id'],
        'type' => $transaction['transaction_type'] ?? 'adjustment',
        'reference_type' => $transaction['reference_type'],
        'reference_id' => $transaction['reference_id'],
        'product_sku' => $transaction['sku'],
        'product_name' => $transaction['product_name'],
        'quantity' => (int)($transaction['quantity'] ?? 0),
        'user_name' => trim(($transaction['first_name'] ?? '') . ' ' . ($transaction['last_name'] ?? '')) ?: 'System',
        'notes' => $transaction['notes'] ?? '',
        'created_at' => $transaction['created_at']
    ];
}
$paginatedMovements = $allMovements;

// Get filter options - match inventory_transactions.transaction_type enum
$movementTypes = [
    'purchase' => __('purchase', 'Purchase'),
    'sale' => __('sale', 'Sale'),
    'adjustment' => __('adjustment', 'Adjustment'),
    'transfer_in' => __('transfer_in', 'Transfer In'),
    'transfer_out' => __('transfer_out', 'Transfer Out'),
    'production' => __('production', 'Production'),
    'sales_return' => __('sales_return', 'Sales Return'),
    'supplier_return' => __('supplier_return', 'Supplier Return'),
    'return' => __('return', 'Return')
];
$products = $db->fetchAll("SELECT p.id, pt.name FROM products p LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang_id WHERE p.is_active = 1 ORDER BY pt.name", ['lang_id' => $lang->getCurrentId()]);
$users = $db->fetchAll("SELECT id, first_name, last_name FROM users ORDER BY first_name, last_name");
$referenceTypes = ['order' => __('order', 'Order'), 'purchase_order' => __('purchase_order', 'Purchase Order'), 'stock_adjustment' => __('stock_adjustment', 'Stock Adjustment'), 'stock_transfer' => __('stock_transfer', 'Stock Transfer'), 'sales_return' => __('sales_return', 'Sales Return'), 'supplier_return' => __('supplier_return', 'Supplier Return'), 'production_order' => __('production_order', 'Production Order')];

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-exchange-alt me-2"></i><?php echo __('stock_movements', 'Stock Movements'); ?>
                </h1>
                <div>
                    <span class="badge bg-primary"><?php echo $totalMovements; ?> <?php echo __('total_movements', 'Total Movements'); ?></span>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-filter me-2"></i><?php echo __('filters', 'Filters'); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label"><?php echo __('movement_type', 'Movement Type'); ?></label>
                            <select name="movement_type" class="form-select">
                                <option value=""><?php echo __('all_types', 'All Types'); ?></option>
                                <?php foreach ($movementTypes as $typeKey => $typeLabel): ?>
    <option value="<?php echo $typeKey; ?>" <?php echo $filters['movement_type'] === $typeKey ? 'selected' : ''; ?>>
        <?php echo $typeLabel; ?>
    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label"><?php echo __('product', 'Product'); ?></label>
                            <select name="product_id" class="form-select">
                                <option value=""><?php echo __('all_products', 'All Products'); ?></option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?php echo $product['id']; ?>" <?php echo $filters['product_id'] == $product['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label"><?php echo __('user', 'User'); ?></label>
                            <select name="user_id" class="form-select">
                                <option value=""><?php echo __('all_users', 'All Users'); ?></option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" <?php echo $filters['user_id'] == $user['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label"><?php echo __('reference_type', 'Reference Type'); ?></label>
                            <select name="reference_type" class="form-select">
                                <option value=""><?php echo __('all_references', 'All References'); ?></option>
                                <?php foreach ($referenceTypes as $typeKey => $typeLabel): ?>
                                    <option value="<?php echo $typeKey; ?>" <?php echo $filters['reference_type'] === $typeKey ? 'selected' : ''; ?>>
                                        <?php echo $typeLabel; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label"><?php echo __('date_from', 'Date From'); ?></label>
                            <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($filters['date_from']); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label"><?php echo __('date_to', 'Date To'); ?></label>
                            <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($filters['date_to']); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label"><?php echo __('reference_id', 'Reference ID'); ?></label>
                            <input type="text" name="reference_id" class="form-control" value="<?php echo htmlspecialchars($filters['reference_id']); ?>" placeholder="<?php echo __('enter_reference_id', 'Enter reference ID'); ?>">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search me-1"></i><?php echo __('search', 'Search'); ?>
                            </button>
                            <a href="movements.php" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i><?php echo __('clear', 'Clear'); ?>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Movements Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i><?php echo __('movements_list', 'Movements List'); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th><?php echo __('date', 'Date'); ?></th>
                                    <th><?php echo __('type', 'Type'); ?></th>
                                    <th><?php echo __('product', 'Product'); ?></th>
                                    <th><?php echo __('quantity', 'Quantity'); ?></th>
                                    <th><?php echo __('reference', 'Reference'); ?></th>
                                    <th><?php echo __('user', 'User'); ?></th>
                                    <th><?php echo __('notes', 'Notes'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($paginatedMovements)): ?>
                                    <?php foreach ($paginatedMovements as $movement): ?>
                                    <tr>
                                        <td><?php echo date('Y-m-d H:i', strtotime($movement['created_at'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo getMovementBadgeClass($movement['type']); ?>">
                                                <?php echo $movementTypes[$movement['type']] ?? ucfirst($movement['type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($movement['product_name'] ?? 'N/A'); ?>
                                            <?php if ($movement['product_sku']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($movement['product_sku']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($movement['quantity'] !== null && $movement['quantity'] > 0): ?>
                                                <span class="text-success fw-bold">+<?php echo number_format($movement['quantity']); ?></span>
                                            <?php elseif ($movement['quantity'] !== null && $movement['quantity'] < 0): ?>
                                                <span class="text-danger fw-bold"><?php echo number_format($movement['quantity']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($movement['reference_type'] && $movement['reference_id']): ?>
                                                <?php echo $referenceTypes[$movement['reference_type']] ?? ucfirst($movement['reference_type']); ?> #<?php echo $movement['reference_id']; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($movement['user_name']): ?>
                                                <?php echo htmlspecialchars($movement['user_name']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">System</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($movement['notes']): ?>
                                                <small class="text-muted"><?php echo htmlspecialchars(substr($movement['notes'], 0, 50)); ?><?php echo strlen($movement['notes']) > 50 ? '...' : ''; ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox fa-3x mb-3"></i>
                                            <p class="mb-0"><?php echo __('no_movements_found', 'No movements found'); ?></p>
                                            <small><?php echo __('try_adjusting_filters', 'Try adjusting your filters'); ?></small>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php 
                    $filterQuery = http_build_query(array_filter($filters, function($v) { return $v !== ''; }));
                    $paginationBase = 'movements.php?' . ($filterQuery ? $filterQuery . '&' : '');
                    if ($totalPages > 1): ?>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted">
                            <?php echo __('showing', 'Showing'); ?> <?php echo $totalMovements > 0 ? (($offset + 1) . '-' . min($offset + $limit, $totalMovements)) : '0'; ?> <?php echo __('of', 'of'); ?> <?php echo $totalMovements; ?> <?php echo __('movements', 'movements'); ?>
                        </div>
                        <nav>
                            <ul class="pagination mb-0">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?php echo $paginationBase; ?>page=<?php echo $page - 1; ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                                for ($i = $startPage; $i <= $endPage; $i++):
                                ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?php echo $paginationBase; ?>page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?php echo $paginationBase; ?>page=<?php echo $page + 1; ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
function getMovementBadgeClass($type) {
    switch ($type) {
        case 'purchase':
            return 'success';
        case 'sale':
            return 'danger';
        case 'adjustment':
            return 'primary';
        case 'transfer_in':
            return 'info';
        case 'transfer_out':
            return 'warning';
        case 'production':
            return 'secondary';
        case 'sales_return':
            return 'warning';
        case 'supplier_return':
        case 'return':
            return 'info';
        default:
            return 'light';
    }
}

include '../includes/footer.php';
?>
