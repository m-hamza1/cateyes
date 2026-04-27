<?php
require_once '../../includes/auth.php';
requirePermission('inventory');
$pageTitle = __('suppliers', 'Suppliers');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'create':
            handleCreateSupplier();
            break;
        case 'update':
            handleUpdateSupplier();
            break;
        case 'delete':
            handleDeleteSupplier();
            break;
    }
}

// Get suppliers with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';

// Build WHERE clause
$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(name LIKE :search OR company LIKE :search OR email LIKE :search)";
    $params['search'] = "%$search%";
}

if ($status !== '') {
    $where[] = "is_active = :status";
    $params['status'] = $status == 'active' ? 1 : 0;
}

$whereClause = implode(' AND ', $where);

$suppliers = $db->fetchAll("
    SELECT * FROM suppliers 
    WHERE $whereClause
    ORDER BY name ASC
    LIMIT $perPage OFFSET $offset
", $params);

$totalItems = $db->fetchOne("
    SELECT COUNT(*) as total FROM suppliers 
    WHERE $whereClause
", $params)['total'] ?? 0;

$totalPages = ceil($totalItems / $perPage);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('suppliers', 'Suppliers'); ?></h1>
    <a href="add.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> <?php echo __('add_supplier', 'Add Supplier'); ?>
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
                       placeholder="<?php echo __('search_by_name_or_company', 'Search by name or company'); ?>">
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

<!-- Suppliers Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <?php echo __('suppliers_list', 'Suppliers List'); ?>
            <span class="badge bg-secondary ms-2"><?php echo $totalItems; ?> <?php echo __('items', 'items'); ?></span>
        </h5>
    </div>
    <div class="card-body">
        <?php if (!empty($suppliers)): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th><?php echo __('name', 'Name'); ?></th>
                            <th><?php echo __('company', 'Company'); ?></th>
                            <th><?php echo __('email', 'Email'); ?></th>
                            <th><?php echo __('phone', 'Phone'); ?></th>
                            <th><?php echo __('city', 'City'); ?></th>
                            <th><?php echo __('status', 'Status'); ?></th>
                            <th><?php echo __('actions', 'Actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($suppliers as $supplier): ?>
                        <tr>
                            <td>
                                <a href="view.php?id=<?php echo $supplier['id']; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($supplier['name'] ?? ''); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($supplier['company'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($supplier['email'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($supplier['phone'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($supplier['city'] ?? ''); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $supplier['is_active'] ? 'success' : 'danger'; ?>">
                                    <?php echo $supplier['is_active'] ? __('active', 'Active') : __('inactive', 'Inactive'); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="view.php?id=<?php echo $supplier['id']; ?>" class="btn btn-sm btn-outline-primary" title="<?php echo __('view', 'View'); ?>">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?php echo $supplier['id']; ?>" class="btn btn-sm btn-outline-secondary" title="<?php echo __('edit', 'Edit'); ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete.php?id=<?php echo $supplier['id']; ?>" class="btn btn-sm btn-outline-danger btn-delete" title="<?php echo __('delete', 'Delete'); ?>">
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
                <i class="fas fa-truck fa-3x text-muted mb-3"></i>
                <h5><?php echo __('no_suppliers_found', 'No suppliers found'); ?></h5>
                <p class="text-muted"><?php echo __('try_different_search', 'Try adjusting your search criteria'); ?></p>
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> <?php echo __('add_first_supplier', 'Add Your First Supplier'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
function handleCreateSupplier() {
    // Implementation for creating supplier
}

function handleUpdateSupplier() {
    // Implementation for updating supplier
}

function handleDeleteSupplier() {
    // Implementation for deleting supplier
}
?>

<?php include '../../includes/footer.php'; ?>
