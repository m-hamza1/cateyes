<?php
require_once '../../includes/auth.php';
requirePermission('inventory');
require_once '../inventory_helper.php';
$pageTitle = __('stock_transfers', 'Stock Transfers');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'complete' && isset($_POST['transfer_id'])) {
        try {
            processStockTransfer($db, (int)$_POST['transfer_id'], $_SESSION['admin_id'] ?? $_SESSION['user_id']);
            setFlashMessage(__('transfer_completed', 'Stock transfer completed successfully!'), 'success');
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            setFlashMessage(__('error_completing_transfer', 'Error completing transfer: ') . $e->getMessage(), 'danger');
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
    $where[] = "(st.transfer_number LIKE :search)";
    $params['search'] = "%$search%";
}

if (!empty($status)) {
    $where[] = "st.status = :status";
    $params['status'] = $status;
}

$whereClause = implode(' AND ', $where);

$transfers = $db->fetchAll("
    SELECT st.*, 
           w1.name as from_warehouse_name,
           w2.name as to_warehouse_name,
           COUNT(sti.id) as item_count,
           u.first_name, u.last_name
    FROM stock_transfers st
    LEFT JOIN warehouses w1 ON st.from_warehouse_id = w1.id
    LEFT JOIN warehouses w2 ON st.to_warehouse_id = w2.id
    LEFT JOIN stock_transfer_items sti ON st.id = sti.stock_transfer_id
    LEFT JOIN users u ON st.created_by = u.id
    WHERE $whereClause
    GROUP BY st.id
    ORDER BY st.created_at DESC
    LIMIT $perPage OFFSET $offset
", $params);

$totalItems = $db->fetchOne("
    SELECT COUNT(DISTINCT st.id) as total
    FROM stock_transfers st
    WHERE $whereClause
", $params)['total'] ?? 0;

$totalPages = ceil($totalItems / $perPage);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('stock_transfers', 'Stock Transfers'); ?></h1>
    <a href="add.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> <?php echo __('create_transfer', 'Create Transfer'); ?>
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
                       placeholder="<?php echo __('search_by_transfer_number', 'Search by transfer number'); ?>">
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label"><?php echo __('status', 'Status'); ?></label>
                <select class="form-select" id="status" name="status">
                    <option value=""><?php echo __('all', 'All'); ?></option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>><?php echo __('pending', 'Pending'); ?></option>
                    <option value="in_transit" <?php echo $status === 'in_transit' ? 'selected' : ''; ?>><?php echo __('in_transit', 'In Transit'); ?></option>
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

<!-- Stock Transfers Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <?php echo __('stock_transfers_list', 'Stock Transfers List'); ?>
            <span class="badge bg-secondary ms-2"><?php echo $totalItems; ?> <?php echo __('items', 'items'); ?></span>
        </h5>
    </div>
    <div class="card-body">
        <?php if (!empty($transfers)): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th><?php echo __('transfer_number', 'Transfer Number'); ?></th>
                            <th><?php echo __('from_warehouse', 'From Warehouse'); ?></th>
                            <th><?php echo __('to_warehouse', 'To Warehouse'); ?></th>
                            <th><?php echo __('transfer_date', 'Transfer Date'); ?></th>
                            <th><?php echo __('status', 'Status'); ?></th>
                            <th><?php echo __('items', 'Items'); ?></th>
                            <th><?php echo __('created_by', 'Created By'); ?></th>
                            <th><?php echo __('actions', 'Actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transfers as $transfer): ?>
                        <tr>
                            <td>
                                <a href="view.php?id=<?php echo $transfer['id']; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($transfer['transfer_number']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($transfer['from_warehouse_name']); ?></td>
                            <td><?php echo htmlspecialchars($transfer['to_warehouse_name']); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($transfer['transfer_date'])); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $transfer['status'] === 'completed' ? 'success' : 
                                        ($transfer['status'] === 'cancelled' ? 'danger' : 
                                        ($transfer['status'] === 'in_transit' ? 'info' : 'warning')); 
                                ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $transfer['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo $transfer['item_count']; ?></td>
                            <td><?php echo htmlspecialchars(($transfer['first_name'] ?? '') . ' ' . ($transfer['last_name'] ?? '')); ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="view.php?id=<?php echo $transfer['id']; ?>" class="btn btn-sm btn-outline-primary" title="<?php echo __('view', 'View'); ?>">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($transfer['status'] === 'pending'): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('<?php echo __('confirm_complete_transfer', 'Are you sure you want to complete this transfer?'); ?>');">
                                        <input type="hidden" name="action" value="complete">
                                        <input type="hidden" name="transfer_id" value="<?php echo $transfer['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-success" title="<?php echo __('complete_transfer', 'Complete Transfer'); ?>">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <a href="delete.php?id=<?php echo $transfer['id']; ?>" class="btn btn-sm btn-outline-danger btn-delete" title="<?php echo __('delete', 'Delete'); ?>">
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
                <i class="fas fa-exchange-alt fa-3x text-muted mb-3"></i>
                <h5><?php echo __('no_transfers_found', 'No stock transfers found'); ?></h5>
                <p class="text-muted"><?php echo __('try_different_search', 'Try adjusting your search criteria'); ?></p>
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> <?php echo __('create_first_transfer', 'Create Your First Stock Transfer'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
