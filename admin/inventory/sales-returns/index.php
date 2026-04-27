<?php
require_once '../../includes/auth.php';
requirePermission('inventory');
$pageTitle = __('sales_returns', 'Sales Returns');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'create':
            handleCreateSalesReturn();
            break;
        case 'update':
            handleUpdateSalesReturn();
            break;
        case 'delete':
            handleDeleteSalesReturn();
            break;
        case 'approve':
            handleApproveSalesReturn();
            break;
        case 'reject':
            handleRejectSalesReturn();
            break;
    }
}

// Get sales returns with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$customer = $_GET['customer'] ?? '';

// Build WHERE clause
$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(sr.return_number LIKE :search OR c.first_name LIKE :search OR c.last_name LIKE :search)";
    $params['search'] = "%$search%";
}

if (!empty($status)) {
    $where[] = "sr.status = :status";
    $params['status'] = $status;
}

if (!empty($customer)) {
    $where[] = "o.customer_id = :customer";
    $params['customer'] = $customer;
}

$whereClause = implode(' AND ', $where);

// Get total items
$totalItems = $db->fetchOne("
    SELECT COUNT(DISTINCT sr.id) as total
    FROM sales_returns sr
    LEFT JOIN orders o ON sr.order_id = o.id
    LEFT JOIN customers c ON o.customer_id = c.id
    WHERE $whereClause
", $params)['total'] ?? 0;

$totalPages = ceil($totalItems / $perPage);

// Get customers and warehouses for filters
$customers = $db->fetchAll("SELECT * FROM customers WHERE is_active = 1 ORDER BY first_name, last_name");
$warehouses = $db->fetchAll("SELECT * FROM warehouses WHERE is_active = 1 ORDER BY name");

// Get sales returns
$salesReturns = $db->fetchAll("
    SELECT sr.*, c.first_name, c.last_name, o.order_number
    FROM sales_returns sr
    LEFT JOIN orders o ON sr.order_id = o.id
    LEFT JOIN customers c ON o.customer_id = c.id
    WHERE $whereClause
    ORDER BY sr.created_at DESC
    LIMIT $offset, $perPage
", $params);

// Get default currency symbol
$defaultCurrency = $db->fetchOne("SELECT symbol, code FROM currencies WHERE is_default = 1 LIMIT 1");
$currency_symbol = $defaultCurrency['symbol'] ?? '$';

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('sales_returns', 'Sales Returns'); ?></h1>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="search" class="form-label"><?php echo __('search', 'Search'); ?></label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="<?php echo __('search_by_return_or_customer', 'Search by return number or customer'); ?>">
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label"><?php echo __('status', 'Status'); ?></label>
                <select class="form-select" id="status" name="status">
                    <option value=""><?php echo __('all', 'All'); ?></option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>
                        <?php echo __('pending', 'Pending'); ?>
                    </option>
                    <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>
                        <?php echo __('approved', 'Approved'); ?>
                    </option>
                    <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>
                        <?php echo __('rejected', 'Rejected'); ?>
                    </option>
                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>
                        <?php echo __('completed', 'Completed'); ?>
                    </option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="customer" class="form-label"><?php echo __('customer', 'Customer'); ?></label>
                <select class="form-select" id="customer" name="customer">
                    <option value=""><?php echo __('all', 'All'); ?></option>
                    <?php foreach ($customers as $cust): ?>
                    <option value="<?php echo $cust['id']; ?>" <?php echo $customer == $cust['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cust['first_name'] . ' ' . $cust['last_name']); ?>
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

<!-- Sales Returns Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0"><?php echo __('sales_returns_list', 'Sales Returns List'); ?></h5>
    </div>
    <div class="card-body">
        <?php if (!empty($salesReturns)): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th><?php echo __('return_number', 'Return Number'); ?></th>
                            <th><?php echo __('customer', 'Customer'); ?></th>
                            <th><?php echo __('return_date', 'Return Date'); ?></th>
                            <th><?php echo __('refund_amount', 'Refund Amount'); ?></th>
                            <th><?php echo __('status', 'Status'); ?></th>
                            <th><?php echo __('actions', 'Actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($salesReturns as $return): ?>
                        <tr>
                            <td>
                                <a href="view.php?id=<?php echo $return['id']; ?>">
                                    <?php echo htmlspecialchars($return['return_number'] ?? 'N/A'); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($return['first_name'] . ' ' . $return['last_name']); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($return['return_date'])); ?></td>
                            <td><?php echo $currency->format($return['refund_amount']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo getReturnStatusBadgeClass($return['status']); ?>">
                                    <?php echo __(ucfirst($return['status']), ucfirst($return['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="view.php?id=<?php echo $return['id']; ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?php echo $return['id']; ?>" class="btn btn-outline-secondary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($return['status'] === 'pending'): ?>
                                        <a href="javascript:void(0)" onclick="approveReturn(<?php echo $return['id']; ?>)" class="btn btn-outline-success">
                                            <i class="fas fa-check"></i>
                                        </a>
                                        <a href="javascript:void(0)" onclick="rejectReturn(<?php echo $return['id']; ?>)" class="btn btn-outline-danger">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="delete.php?id=<?php echo $return['id']; ?>" class="btn btn-outline-danger">
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
                <nav aria-label="Sales returns pagination">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo $baseUrl; ?>&page=<?php echo $page - 1; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
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
                <h5><?php echo __('no_sales_returns_found', 'No sales returns found'); ?></h5>
                <p class="text-muted"><?php echo __('try_different_search', 'Try adjusting your search criteria'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function approveReturn(returnId) {
    if (confirm('<?php echo __('approve_return_warning', 'Are you sure you want to approve this return?'); ?>')) {
        window.location.href = 'approve.php?id=' + returnId;
    }
}

function rejectReturn(returnId) {
    if (confirm('<?php echo __('reject_return_warning', 'Are you sure you want to reject this return?'); ?>')) {
        window.location.href = 'reject.php?id=' + returnId;
    }
}
</script>

<?php
function getReturnStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'warning';
        case 'approved':
            return 'info';
        case 'rejected':
            return 'danger';
        case 'completed':
            return 'success';
        default:
            return 'secondary';
    }
}
?>

<?php include '../../includes/footer.php'; ?>
