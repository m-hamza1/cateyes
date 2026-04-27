<?php
require_once '../../includes/auth.php';
requirePermission('inventory');
require_once '../inventory_helper.php';
$pageTitle = __('production_orders', 'Production Orders');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'complete' && isset($_POST['order_id'])) {
        try {
            $orderId = (int)$_POST['order_id'];
            $order = $db->fetchOne("SELECT * FROM production_orders WHERE id = :id", ['id' => $orderId]);
            
            if (!$order) {
                throw new Exception('Production order not found');
            }
            
            if ($order['status'] === 'completed') {
                throw new Exception('Production order already completed');
            }
            
            completeProductionOrder($db, $orderId, $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null);
            setFlashMessage(__('production_order_completed', 'Production order completed successfully!'), 'success');
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $db->rollback();
            setFlashMessage(__('error_completing_order', 'Error completing production order: ') . $e->getMessage(), 'danger');
        }
    }
}

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
    $where[] = "(po.production_number LIKE :search OR pt.name LIKE :search)";
    $params['search'] = "%$search%";
}

if (!empty($status)) {
    $where[] = "po.status = :status";
    $params['status'] = $status;
}

$whereClause = implode(' AND ', $where);

$orders = $db->fetchAll("
    SELECT po.*, 
           pt.name as product_name,
           p.sku,
           w.name as warehouse_name,
           u.first_name, u.last_name
    FROM production_orders po
    LEFT JOIN products p ON po.product_id = p.id
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang_id
    LEFT JOIN warehouses w ON po.warehouse_id = w.id
    LEFT JOIN users u ON po.created_by = u.id
    WHERE $whereClause
    ORDER BY po.created_at DESC
    LIMIT $perPage OFFSET $offset
", array_merge($params, ['lang_id' => $lang->getCurrentId()]));

$totalItems = $db->fetchOne("
    SELECT COUNT(*) as total
    FROM production_orders po
    LEFT JOIN products p ON po.product_id = p.id
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang_id
    WHERE $whereClause
", array_merge($params, ['lang_id' => $lang->getCurrentId()]))['total'] ?? 0;

$totalPages = ceil($totalItems / $perPage);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('production_orders', 'Production Orders'); ?></h1>
    <a href="add.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> <?php echo __('create_production_order', 'Create Production Order'); ?>
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
                       placeholder="<?php echo __('search_by_number_or_product', 'Search by number or product'); ?>">
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label"><?php echo __('status', 'Status'); ?></label>
                <select class="form-select" id="status" name="status">
                    <option value=""><?php echo __('all', 'All'); ?></option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>><?php echo __('pending', 'Pending'); ?></option>
                    <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>><?php echo __('in_progress', 'In Progress'); ?></option>
                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>><?php echo __('completed', 'Completed'); ?></option>
                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>><?php echo __('cancelled', 'Cancelled'); ?></option>
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

<!-- Production Orders Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <?php echo __('production_orders_list', 'Production Orders List'); ?>
            <span class="badge bg-secondary ms-2"><?php echo $totalItems; ?> <?php echo __('items', 'items'); ?></span>
        </h5>
    </div>
    <div class="card-body">
        <?php if (!empty($orders)): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th><?php echo __('production_number', 'Production Number'); ?></th>
                            <th><?php echo __('product', 'Product'); ?></th>
                            <th><?php echo __('warehouse', 'Warehouse'); ?></th>
                            <th><?php echo __('quantity', 'Quantity'); ?></th>
                            <th><?php echo __('produced', 'Produced'); ?></th>
                            <th><?php echo __('production_date', 'Production Date'); ?></th>
                            <th><?php echo __('status', 'Status'); ?></th>
                            <th><?php echo __('actions', 'Actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <a href="view.php?id=<?php echo $order['id']; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($order['production_number']); ?>
                                </a>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($order['product_name'] ?? 'N/A'); ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($order['sku'] ?? ''); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($order['warehouse_name'] ?? 'N/A'); ?></td>
                            <td><?php echo number_format($order['quantity']); ?></td>
                            <td><?php echo number_format($order['produced_quantity']); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($order['production_date'])); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $order['status'] === 'completed' ? 'success' : 
                                        ($order['status'] === 'cancelled' ? 'danger' : 
                                        ($order['status'] === 'in_progress' ? 'info' : 'warning')); 
                                ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="view.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary" title="<?php echo __('view', 'View'); ?>">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($order['status'] === 'pending'): ?>
                                        <a href="start-production.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-success" title="<?php echo __('start_production', 'Start Production'); ?>">
                                            <i class="fas fa-play"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($order['status'] === 'pending' || $order['status'] === 'in_progress'): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('<?php echo __('confirm_approve_production', 'Are you sure you want to approve this production order?'); ?>');">
                                        <input type="hidden" name="action" value="complete">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-success" title="<?php echo __('approve_order', 'Approve Order'); ?>">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <a href="delete.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-danger btn-delete" title="<?php echo __('delete', 'Delete'); ?>">
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
                <i class="fas fa-industry fa-3x text-muted mb-3"></i>
                <h5><?php echo __('no_production_orders_found', 'No production orders found'); ?></h5>
                <p class="text-muted"><?php echo __('try_different_search', 'Try adjusting your search criteria'); ?></p>
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> <?php echo __('create_first_production_order', 'Create Your First Production Order'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
