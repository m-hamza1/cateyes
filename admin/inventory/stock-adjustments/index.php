<?php
require_once '../../includes/auth.php';
requirePermission('inventory');
$pageTitle = __('stock_adjustments', 'Stock Adjustments');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'create':
            handleCreateStockAdjustment();
            break;
        case 'update':
            handleUpdateStockAdjustment();
            break;
        case 'delete':
            handleDeleteStockAdjustment();
            break;
    }
}

// Get stock adjustments with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$search = trim($_GET['search'] ?? '');
$adjustmentType = $_GET['adjustment_type'] ?? '';
$warehouse = $_GET['warehouse'] ?? '';

// Build WHERE clause
$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(sa.adjustment_number LIKE :search OR sa.reason LIKE :search)";
    $params['search'] = "%$search%";
}

if (!empty($adjustmentType)) {
    $where[] = "sa.adjustment_type = :adjustment_type";
    $params['adjustment_type'] = $adjustmentType;
}

if (!empty($warehouse)) {
    $where[] = "sa.warehouse_id = :warehouse";
    $params['warehouse'] = $warehouse;
}

$whereClause = implode(' AND ', $where);

$stockAdjustments = $db->fetchAll("
    SELECT sa.*, w.name as warehouse_name,
           COUNT(sai.id) as item_count,
           u.first_name as created_by_name
    FROM stock_adjustments sa
    LEFT JOIN warehouses w ON sa.warehouse_id = w.id
    LEFT JOIN users u ON sa.created_by = u.id
    LEFT JOIN stock_adjustment_items sai ON sa.id = sai.stock_adjustment_id
    WHERE $whereClause
    GROUP BY sa.id
    ORDER BY sa.created_at DESC
    LIMIT $perPage OFFSET $offset
", $params);

$totalItems = $db->fetchOne("
    SELECT COUNT(DISTINCT sa.id) as total
    FROM stock_adjustments sa
    WHERE $whereClause
", $params)['total'] ?? 0;

$totalPages = ceil($totalItems / $perPage);

// Get warehouses for filters
$warehouses = $db->fetchAll("SELECT * FROM warehouses WHERE is_active = 1 ORDER BY name");

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('stock_adjustments', 'Stock Adjustments'); ?></h1>
    <a href="add.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> <?php echo __('create_adjustment', 'Create Adjustment'); ?>
    </a>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="search" class="form-label"><?php echo __('search', 'Search'); ?></label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="<?php echo __('search_by_number_or_reason', 'Search by number or reason'); ?>">
            </div>
            <div class="col-md-2">
                <label for="adjustment_type" class="form-label"><?php echo __('adjustment_type', 'Adjustment Type'); ?></label>
                <select class="form-select" id="adjustment_type" name="adjustment_type">
                    <option value=""><?php echo __('all', 'All'); ?></option>
                    <option value="add" <?php echo $adjustmentType === 'add' ? 'selected' : ''; ?>>
                        <?php echo __('add_stock', 'Add Stock'); ?>
                    </option>
                    <option value="subtract" <?php echo $adjustmentType === 'subtract' ? 'selected' : ''; ?>>
                        <?php echo __('subtract_stock', 'Subtract Stock'); ?>
                    </option>
                    <option value="set" <?php echo $adjustmentType === 'set' ? 'selected' : ''; ?>>
                        <?php echo __('set_stock', 'Set Stock'); ?>
                    </option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="warehouse" class="form-label"><?php echo __('warehouse', 'Warehouse'); ?></label>
                <select class="form-select" id="warehouse" name="warehouse">
                    <option value=""><?php echo __('all', 'All'); ?></option>
                    <?php foreach ($warehouses as $wh): ?>
                    <option value="<?php echo $wh['id']; ?>" <?php echo $warehouse == $wh['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($wh['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-outline-primary w-100">
                    <i class="fas fa-search"></i> <?php echo __('search', 'Search'); ?>
                </button>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <a href="index.php" class="btn btn-outline-secondary w-100">
                    <i class="fas fa-times"></i> <?php echo __('clear', 'Clear'); ?>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Stock Adjustments Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <?php echo __('stock_adjustments_list', 'Stock Adjustments List'); ?>
            <span class="badge bg-secondary ms-2"><?php echo $totalItems; ?> <?php echo __('items', 'items'); ?></span>
        </h5>
    </div>
    <div class="card-body">
        <?php if (!empty($stockAdjustments)): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th><?php echo __('adjustment_number', 'Adjustment Number'); ?></th>
                            <th><?php echo __('warehouse', 'Warehouse'); ?></th>
                            <th><?php echo __('adjustment_date', 'Adjustment Date'); ?></th>
                            <th><?php echo __('adjustment_type', 'Type'); ?></th>
                            <th><?php echo __('reason', 'Reason'); ?></th>
                            <th><?php echo __('created_by', 'Created By'); ?></th>
                            <th><?php echo __('items', 'Items'); ?></th>
                            <th><?php echo __('actions', 'Actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stockAdjustments as $adjustment): ?>
                        <tr>
                            <td>
                                <a href="view.php?id=<?php echo $adjustment['id']; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($adjustment['adjustment_number'] ?? ''); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($adjustment['warehouse_name'] ?? ''); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($adjustment['adjustment_date'] ?? 'now')); ?></td>
                            <td>
                                <span class="badge bg-<?php echo getAdjustmentTypeBadgeClass($adjustment['adjustment_type'] ?? ''); ?>">
                                    <?php echo __($adjustment['adjustment_type'] ?? 'unknown', getAdjustmentTypeLabel($adjustment['adjustment_type'] ?? '')); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo htmlspecialchars(substr($adjustment['reason'] ?? '', 0, 50)); ?>
                                <?php if (strlen($adjustment['reason'] ?? '') > 50): ?>
                                    <span class="text-muted">...</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($adjustment['created_by_name'] ?? ''); ?></td>
                            <td><?php echo $adjustment['item_count'] ?? 0; ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="view.php?id=<?php echo $adjustment['id']; ?>" class="btn btn-sm btn-outline-primary" title="<?php echo __('view', 'View'); ?>">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?php echo $adjustment['id']; ?>" class="btn btn-sm btn-outline-secondary" title="<?php echo __('edit', 'Edit'); ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete.php?id=<?php echo $adjustment['id']; ?>" class="btn btn-sm btn-outline-danger btn-delete" title="<?php echo __('delete', 'Delete'); ?>">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="<?php echo __('pagination', 'Pagination'); ?>">
                    <ul class="pagination justify-content-center">
                        <?php
                        $currentUrl = $_SERVER['REQUEST_URI'];
                        $parsedUrl = parse_url($currentUrl);
                        $query = [];
                        if (isset($parsedUrl['query'])) {
                            parse_str($parsedUrl['query'], $query);
                        }
                        unset($query['page']);
                        $baseUrl = $parsedUrl['path'] . '?' . http_build_query($query);
                        ?>
                        
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo $baseUrl; ?>&page=<?php echo $page - 1; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php
                        $start = max(1, $page - 2);
                        $end = min($totalPages, $page + 2);
                        for ($i = $start; $i <= $end; $i++):
                        ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo $baseUrl; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo $baseUrl; ?>&page=<?php echo $page + 1; ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-edit fa-3x text-muted mb-3"></i>
                <h5><?php echo __('no_stock_adjustments_found', 'No stock adjustments found'); ?></h5>
                <p class="text-muted"><?php echo __('try_different_search', 'Try adjusting your search criteria'); ?></p>
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> <?php echo __('create_first_adjustment', 'Create Your First Stock Adjustment'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
function getAdjustmentTypeBadgeClass($type) {
    switch ($type) {
        case 'add':
            return 'success';
        case 'subtract':
            return 'danger';
        case 'set':
            return 'warning';
        default:
            return 'secondary';
    }
}

function getAdjustmentTypeLabel($type) {
    switch ($type) {
        case 'add':
            return 'Add Stock';
        case 'subtract':
            return 'Subtract Stock';
        case 'set':
            return 'Set Stock';
        default:
            return $type;
    }
}

function handleCreateStockAdjustment() {
    // Implementation for creating stock adjustment
}

function handleUpdateStockAdjustment() {
    // Implementation for updating stock adjustment
}

function handleDeleteStockAdjustment() {
    // Implementation for deleting stock adjustment
}
?>

<?php include '../../includes/footer.php'; ?>