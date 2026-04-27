<?php
require_once '../includes/auth.php';
requirePermission('shipping');
$pageTitle = __('shipping_methods', 'Shipping Methods');

// Handle search and filtering
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$calculationType = $_GET['calculation_type'] ?? '';

// Build WHERE clause
$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(sm.name LIKE :search OR sm.code LIKE :search)";
    $params['search'] = "%$search%";
}

if (!empty($status)) {
    $where[] = "sm.is_active = :status";
    $params['status'] = $status === 'active' ? 1 : 0;
}

if (!empty($calculationType)) {
    $where[] = "sm.calculation_type = :calculation_type";
    $params['calculation_type'] = $calculationType;
}

$whereClause = implode(' AND ', $where);

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get shipping methods with rates count
$items = $db->fetchAll("
    SELECT sm.*, 
           COUNT(sr.id) as rates_count,
           GROUP_CONCAT(DISTINCT sz.name) as zones
    FROM shipping_methods sm
    LEFT JOIN shipping_rates sr ON sm.id = sr.shipping_method_id
    LEFT JOIN shipping_zones sz ON sr.shipping_zone_id = sz.id
    WHERE $whereClause
    GROUP BY sm.id
    ORDER BY sm.sort_order ASC, sm.name ASC
    LIMIT $perPage OFFSET $offset
", $params);

$totalItems = $db->fetchOne("
    SELECT COUNT(DISTINCT sm.id) as total
    FROM shipping_methods sm
    WHERE $whereClause
", $params)['total'] ?? 0;

$totalPages = ceil($totalItems / $perPage);

// Get statistics
$stats = [
    'total' => $db->count('shipping_methods'),
    'active' => $db->count('shipping_methods', 'is_active = 1'),
    'inactive' => $db->count('shipping_methods', 'is_active = 0'),
    'flat_rate' => $db->count('shipping_methods', 'calculation_type = "flat_rate"'),
    'weight_based' => $db->count('shipping_methods', 'calculation_type = "weight_based"'),
    'location_based' => $db->count('shipping_methods', 'calculation_type = "location_based"'),
    'free' => $db->count('shipping_methods', 'calculation_type = "free"')
];

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('shipping_methods', 'Shipping Methods'); ?></h1>
    <a href="add.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> <?php echo __('add_shipping_method', 'Add Shipping Method'); ?>
    </a>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title"><?php echo $stats['total']; ?></h4>
                        <p class="card-text"><?php echo __('total_methods', 'Total Methods'); ?></p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-shipping-fast fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title"><?php echo $stats['active']; ?></h4>
                        <p class="card-text"><?php echo __('active_methods', 'Active Methods'); ?></p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title"><?php echo $stats['flat_rate']; ?></h4>
                        <p class="card-text"><?php echo __('flat_rate_methods', 'Flat Rate'); ?></p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-dollar-sign fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title"><?php echo $stats['weight_based'] + $stats['location_based']; ?></h4>
                        <p class="card-text"><?php echo __('dynamic_methods', 'Dynamic Methods'); ?></p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-chart-line fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search and Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label"><?php echo __('search', 'Search'); ?></label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="<?php echo __('search_shipping_methods', 'Search by name or code...'); ?>">
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label"><?php echo __('status', 'Status'); ?></label>
                <select class="form-select" id="status" name="status">
                    <option value=""><?php echo __('all', 'All'); ?></option>
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>
                        <?php echo __('active', 'Active'); ?>
                    </option>
                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>
                        <?php echo __('inactive', 'Inactive'); ?>
                    </option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="calculation_type" class="form-label"><?php echo __('calculation_type', 'Calculation Type'); ?></label>
                <select class="form-select" id="calculation_type" name="calculation_type">
                    <option value=""><?php echo __('all', 'All'); ?></option>
                    <option value="flat_rate" <?php echo $calculationType === 'flat_rate' ? 'selected' : ''; ?>>
                        <?php echo __('flat_rate', 'Flat Rate'); ?>
                    </option>
                    <option value="weight_based" <?php echo $calculationType === 'weight_based' ? 'selected' : ''; ?>>
                        <?php echo __('weight_based', 'Weight Based'); ?>
                    </option>
                    <option value="location_based" <?php echo $calculationType === 'location_based' ? 'selected' : ''; ?>>
                        <?php echo __('location_based', 'Location Based'); ?>
                    </option>
                    <option value="free" <?php echo $calculationType === 'free' ? 'selected' : ''; ?>>
                        <?php echo __('free_shipping', 'Free Shipping'); ?>
                    </option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-outline-primary w-100">
                    <i class="fas fa-search"></i> <?php echo __('search', 'Search'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Shipping Methods Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <?php echo __('shipping_methods_list', 'Shipping Methods List'); ?>
            <span class="badge bg-secondary ms-2"><?php echo $totalItems; ?> <?php echo __('items', 'items'); ?></span>
        </h5>
    </div>
    <div class="card-body">
        <?php if (!empty($items)): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th><?php echo __('name', 'Name'); ?></th>
                            <th><?php echo __('code', 'Code'); ?></th>
                            <th><?php echo __('calculation_type', 'Type'); ?></th>
                            <th><?php echo __('cost', 'Cost'); ?></th>
                            <th><?php echo __('zones', 'Zones'); ?></th>
                            <th><?php echo __('rates', 'Rates'); ?></th>
                            <th><?php echo __('status', 'Status'); ?></th>
                            <th><?php echo __('sort_order', 'Order'); ?></th>
                            <th><?php echo __('actions', 'Actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                <?php if (!empty($item['description'])): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($item['description'], 0, 100)); ?>...</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code><?php echo htmlspecialchars($item['code']); ?></code>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo getCalculationTypeBadgeClass($item['calculation_type']); ?>">
                                    <?php echo __($item['calculation_type'], $item['calculation_type']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($item['calculation_type'] === 'flat_rate'): ?>
                                    <?php echo $currency->format($item['flat_rate_cost']); ?>
                                <?php elseif ($item['calculation_type'] === 'free'): ?>
                                    <?php if (!empty($item['free_shipping_threshold'])): ?>
                                        <?php echo __('free_above', 'Free above'); ?> <?php echo $currency->format($item['free_shipping_threshold']); ?>
                                    <?php else: ?>
                                        <?php echo __('always_free', 'Always Free'); ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php echo __('dynamic', 'Dynamic'); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($item['zones'])): ?>
                                    <span class="badge bg-info"><?php echo count(explode(',', $item['zones'])); ?> <?php echo __('zones', 'Zones'); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($item['rates_count'] > 0): ?>
                                    <span class="badge bg-success"><?php echo $item['rates_count']; ?> <?php echo __('rates', 'Rates'); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($item['is_active']): ?>
                                    <span class="badge bg-success"><?php echo __('active', 'Active'); ?></span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?php echo __('inactive', 'Inactive'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $item['sort_order']; ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="edit.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-primary" title="<?php echo __('edit', 'Edit'); ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-danger btn-delete" title="<?php echo __('delete', 'Delete'); ?>">
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
                <i class="fas fa-shipping-fast fa-3x text-muted mb-3"></i>
                <h5><?php echo __('no_shipping_methods_found', 'No shipping methods found'); ?></h5>
                <p class="text-muted"><?php echo __('try_different_search', 'Try adjusting your search criteria'); ?></p>
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> <?php echo __('add_first_shipping_method', 'Add Your First Shipping Method'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Helper function for badge colors
function getCalculationTypeBadgeClass($type) {
    switch ($type) {
        case 'flat_rate':
            return 'primary';
        case 'weight_based':
            return 'warning';
        case 'location_based':
            return 'info';
        case 'free':
            return 'success';
        default:
            return 'secondary';
    }
}
?>

<?php include '../includes/footer.php'; ?>