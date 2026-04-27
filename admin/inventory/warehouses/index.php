<?php
require_once '../../includes/auth.php';
requirePermission('inventory');
$pageTitle = __('warehouses', 'Warehouses');

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';

// Build WHERE clause
$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(w.name LIKE :search OR w.code LIKE :search OR w.city LIKE :search)";
    $params['search'] = "%$search%";
}

if ($status !== '') {
    $where[] = "w.is_active = :status";
    $params['status'] = $status == 'active' ? 1 : 0;
}

$whereClause = implode(' AND ', $where);

$warehouses = $db->fetchAll("
    SELECT w.*, 
           COUNT(DISTINCT pws.product_id) as product_count,
           SUM(pws.quantity) as total_stock
    FROM warehouses w
    LEFT JOIN product_warehouse_stock pws ON w.id = pws.warehouse_id
    WHERE $whereClause
    GROUP BY w.id
    ORDER BY w.name ASC
    LIMIT $perPage OFFSET $offset
", $params);

$totalItems = $db->fetchOne("
    SELECT COUNT(*) as total FROM warehouses w WHERE $whereClause
", $params)['total'] ?? 0;

$totalPages = ceil($totalItems / $perPage);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('warehouses', 'Warehouses'); ?></h1>
    <a href="add.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> <?php echo __('add_warehouse', 'Add Warehouse'); ?>
    </a>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label"><?php echo __('search', 'Search'); ?></label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="<?php echo __('search_by_name_or_code', 'Search by name or code'); ?>">
            </div>
            <div class="col-md-2">
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
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-outline-primary w-100">
                    <i class="fas fa-search"></i> <?php echo __('search', 'Search'); ?>
                </button>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <a href="index.php" class="btn btn-outline-secondary w-100">
                    <i class="fas fa-times"></i> <?php echo __('clear', 'Clear'); ?>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Warehouses Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <?php echo __('warehouses_list', 'Warehouses List'); ?>
            <span class="badge bg-secondary ms-2"><?php echo $totalItems; ?> <?php echo __('items', 'items'); ?></span>
        </h5>
    </div>
    <div class="card-body">
        <?php if (!empty($warehouses)): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th><?php echo __('code', 'Code'); ?></th>
                            <th><?php echo __('name', 'Name'); ?></th>
                            <th><?php echo __('city', 'City'); ?></th>
                            <th><?php echo __('manager', 'Manager'); ?></th>
                            <th><?php echo __('products', 'Products'); ?></th>
                            <th><?php echo __('total_stock', 'Total Stock'); ?></th>
                            <th><?php echo __('status', 'Status'); ?></th>
                            <th><?php echo __('actions', 'Actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($warehouses as $warehouse): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($warehouse['code']); ?></strong></td>
                            <td><?php echo htmlspecialchars($warehouse['name']); ?></td>
                            <td><?php echo htmlspecialchars($warehouse['city'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($warehouse['manager_name'] ?? '-'); ?></td>
                            <td><?php echo $warehouse['product_count'] ?? 0; ?></td>
                            <td><?php echo number_format($warehouse['total_stock'] ?? 0); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $warehouse['is_active'] ? 'success' : 'danger'; ?>">
                                    <?php echo $warehouse['is_active'] ? __('active', 'Active') : __('inactive', 'Inactive'); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="edit.php?id=<?php echo $warehouse['id']; ?>" class="btn btn-sm btn-outline-secondary" title="<?php echo __('edit', 'Edit'); ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete.php?id=<?php echo $warehouse['id']; ?>" class="btn btn-sm btn-outline-danger btn-delete" title="<?php echo __('delete', 'Delete'); ?>">
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
                <i class="fas fa-warehouse fa-3x text-muted mb-3"></i>
                <h5><?php echo __('no_warehouses_found', 'No warehouses found'); ?></h5>
                <p class="text-muted"><?php echo __('try_different_search', 'Try adjusting your search criteria'); ?></p>
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> <?php echo __('add_first_warehouse', 'Add Your First Warehouse'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
