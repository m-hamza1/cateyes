<?php
/**
 * Orders Management - Main Dashboard
 */

require_once '../includes/auth.php';
requirePermission('orders');
$pageTitle = __('orders_management', 'Orders Management');

// Initialize classes
$orderModel = new Order($db);

// Handle Manual Bulk Payment Sync
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync_all_gateways'])) {
    require_once INCLUDES_PATH . 'Payment.php';
    $paymentObj = new Payment($db);
    
    // Get all pending orders that use a supported gateway
    // Currently only 'networks' (N-Genius) supports manual sync
    $pendingOrders = $db->fetchAll("
        SELECT id, order_number, payment_method 
        FROM orders 
        WHERE payment_status = 'pending' 
        AND payment_method = 'networks'
    ");
    
    if (empty($pendingOrders)) {
        $_SESSION['info'] = __('no_pending_orders_to_sync', 'No pending orders found that support synchronization.');
    } else {
        $syncedCount = 0;
        $updatedCount = 0;
        $errors = [];
        
        foreach ($pendingOrders as $pOrder) {
            $syncedCount++;
            $result = $paymentObj->verifyNetworksPayment($pOrder['id']);
            
            if ($result['success']) {
                $updatedCount++;
            } elseif (isset($result['message'])) {
                // We don't want to show all errors if there are many, but we can log them
                error_log("Bulk Sync Error for Order #{$pOrder['order_number']}: " . $result['message']);
            }
        }
        
        if ($updatedCount > 0) {
            $_SESSION['success'] = sprintf(__('gateways_synced_successfully', 'Sync complete: %d orders checked, %d updated to Paid.'), $syncedCount, $updatedCount);
        } else {
            $_SESSION['info'] = sprintf(__('gateways_synced_no_updates', 'Sync complete: %d orders checked, but no status updates were found.'), $syncedCount);
        }
    }
    
    header('Location: index.php' . (!empty($_GET) ? '?' . http_build_query($_GET) : ''));
    exit;
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $orderIds = array_filter(array_map('intval', (array)($_POST['order_ids'] ?? [])));
    $action = trim($_POST['bulk_action'] ?? '');
    
    if (!empty($orderIds) && $action) {
        $successCount = 0;
        $errorMessages = [];
        foreach ($orderIds as $orderId) {
            if ($orderId <= 0) continue;
            switch ($action) {
                case 'mark_pending':
                    if ($orderModel->updateStatus($orderId, 'pending')) $successCount++;
                    elseif ($orderModel->getLastError()) $errorMessages[] = '#' . $orderId . ': ' . $orderModel->getLastError();
                    break;
                case 'mark_confirmed':
                    if ($orderModel->updateStatus($orderId, 'confirmed')) $successCount++;
                    elseif ($orderModel->getLastError()) $errorMessages[] = '#' . $orderId . ': ' . $orderModel->getLastError();
                    break;
                case 'mark_processing':
                    if ($orderModel->updateStatus($orderId, 'processing')) $successCount++;
                    elseif ($orderModel->getLastError()) $errorMessages[] = '#' . $orderId . ': ' . $orderModel->getLastError();
                    break;
                case 'mark_shipped':
                    if ($orderModel->updateStatus($orderId, 'shipped')) $successCount++;
                    elseif ($orderModel->getLastError()) $errorMessages[] = '#' . $orderId . ': ' . $orderModel->getLastError();
                    break;
                case 'mark_delivered':
                    if ($orderModel->updateStatus($orderId, 'delivered')) $successCount++;
                    break;
                case 'mark_cancelled':
                    if ($orderModel->updateStatus($orderId, 'cancelled')) $successCount++;
                    break;
                case 'delete':
                    if ($orderModel->delete($orderId)) $successCount++;
                    break;
            }
        }
        
        $actionLabel = $action === 'delete' ? 'deleted' : str_replace('mark_', '', $action);
        $_SESSION['success'] = sprintf(__('orders_updated_successfully', '%d order(s) %s successfully.'), $successCount, $actionLabel);
        if (!empty($errorMessages)) {
            $_SESSION['error'] = implode(' ', array_slice($errorMessages, 0, 3));
        }
        header('Location: index.php' . (!empty($_GET) ? '?' . http_build_query($_GET) : ''));
        exit;
    } else {
        $_SESSION['error'] = __('please_select_orders', 'Please select at least one order and choose an action.');
        header('Location: index.php' . (!empty($_GET) ? '?' . http_build_query($_GET) : ''));
        exit;
    }
}

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

// Filters
$status = $_GET['status'] ?? '';
$paymentStatus = $_GET['payment_status'] ?? '';
$search = $_GET['search'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Build query conditions
$whereConditions = [];
$params = [];

if ($status) {
    $whereConditions[] = "o.order_status = :status";
    $params['status'] = $status;
}

if ($paymentStatus) {
    $whereConditions[] = "o.payment_status = :payment_status";
    $params['payment_status'] = $paymentStatus;
}

if ($search) {
    $whereConditions[] = "(o.order_number LIKE :search OR o.guest_email LIKE :search OR CONCAT(u.first_name, ' ', u.last_name) LIKE :search)";
    $params['search'] = '%' . $search . '%';
}

if ($dateFrom) {
    $whereConditions[] = "DATE(o.created_at) >= :date_from";
    $params['date_from'] = $dateFrom;
}

if ($dateTo) {
    $whereConditions[] = "DATE(o.created_at) <= :date_to";
    $params['date_to'] = $dateTo;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get total count
$totalOrders = $db->fetchOne("
    SELECT COUNT(*) as total 
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    $whereClause
", $params)['total'] ?? 0;

$totalPages = ceil($totalOrders / $perPage);

// Get orders
$orders = $db->fetchAll("
    SELECT o.*, 
           u.first_name, u.last_name, u.email as user_email,
           COUNT(oi.id) as item_count
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    $whereClause
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT $offset, $perPage
", $params);

// Get default currency symbol
$defaultCurrency = $db->fetchOne("SELECT symbol, code FROM currencies WHERE is_default = 1 LIMIT 1");
$currency_symbol = $defaultCurrency['symbol'] ?? '$';

// Get statistics
$stats = [
    'total' => $db->fetchOne("SELECT COUNT(*) as count FROM orders")['count'] ?? 0,
    'pending' => $db->fetchOne("SELECT COUNT(*) as count FROM orders WHERE order_status = 'pending'")['count'] ?? 0,
    'processing' => $db->fetchOne("SELECT COUNT(*) as count FROM orders WHERE order_status = 'processing'")['count'] ?? 0,
    'shipped' => $db->fetchOne("SELECT COUNT(*) as count FROM orders WHERE order_status = 'shipped'")['count'] ?? 0,
    'delivered' => $db->fetchOne("SELECT COUNT(*) as count FROM orders WHERE order_status = 'delivered'")['count'] ?? 0,
    'cancelled' => $db->fetchOne("SELECT COUNT(*) as count FROM orders WHERE order_status = 'cancelled'")['count'] ?? 0,
    'total_revenue' => $db->fetchOne("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE payment_status = 'paid' AND order_status != 'cancelled'")['total'] ?? 0
];

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-2 col-sm-6 mb-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?php echo number_format($stats['total']); ?></h4>
                                    <p class="mb-0"><?php echo __('total_orders', 'Total Orders'); ?></p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-shopping-bag fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6 mb-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?php echo number_format($stats['pending']); ?></h4>
                                    <p class="mb-0"><?php echo __('pending', 'Pending'); ?></p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6 mb-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?php echo number_format($stats['processing']); ?></h4>
                                    <p class="mb-0"><?php echo __('processing', 'Processing'); ?></p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-cog fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6 mb-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?php echo number_format($stats['delivered']); ?></h4>
                                    <p class="mb-0"><?php echo __('delivered', 'Delivered'); ?></p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-check-circle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6 mb-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?php echo number_format($stats['cancelled']); ?></h4>
                                    <p class="mb-0"><?php echo __('cancelled', 'Cancelled'); ?></p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-times-circle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6 mb-3">
                    <div class="card bg-secondary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0"><?php echo $currency->format($stats['total_revenue']); ?></h4>
                                    <p class="mb-0"><?php echo __('total_revenue', 'Total Revenue'); ?></p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-dollar-sign fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Header with Actions -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-shopping-bag me-2"></i>
                    <?php echo __('orders_management', 'Orders Management'); ?>
                </h1>
                <div class="d-flex gap-2">
                    <form method="POST" class="d-inline">
                        <button type="submit" name="sync_all_gateways" value="1" class="btn btn-outline-primary" title="<?php echo __('sync_gateways_info', 'Check real-time status for all pending online payments'); ?>">
                            <i class="fas fa-sync-alt me-1"></i> <?php echo __('sync_with_gateways', 'Sync with Gateways'); ?>
                        </button>
                    </form>
                    <a href="export.php" class="btn btn-outline-secondary">
                        <i class="fas fa-download me-1"></i> <?php echo __('export', 'Export'); ?>
                    </a>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bulkActionsModal">
                        <i class="fas fa-tasks me-1"></i> <?php echo __('bulk_actions', 'Bulk Actions'); ?>
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label"><?php echo __('search', 'Search'); ?></label>
                            <input type="text" name="search" class="form-control" 
                                   placeholder="<?php echo __('order_customer_email_placeholder', 'Order #, Customer, Email'); ?>" 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><?php echo __('status', 'Status'); ?></label>
                            <select name="status" class="form-select">
                                <option value=""><?php echo __('all_statuses', 'All Statuses'); ?></option>
                                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>><?php echo __('pending', 'Pending'); ?></option>
                                <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>><?php echo __('confirmed', 'Confirmed'); ?></option>
                                <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>><?php echo __('processing', 'Processing'); ?></option>
                                <option value="shipped" <?php echo $status === 'shipped' ? 'selected' : ''; ?>><?php echo __('shipped', 'Shipped'); ?></option>
                                <option value="delivered" <?php echo $status === 'delivered' ? 'selected' : ''; ?>><?php echo __('delivered', 'Delivered'); ?></option>
                                <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>><?php echo __('cancelled', 'Cancelled'); ?></option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><?php echo __('payment_status', 'Payment Status'); ?></label>
                            <select name="payment_status" class="form-select">
                                <option value=""><?php echo __('all_payment', 'All Payment'); ?></option>
                                <option value="pending" <?php echo $paymentStatus === 'pending' ? 'selected' : ''; ?>><?php echo __('pending', 'Pending'); ?></option>
                                <option value="paid" <?php echo $paymentStatus === 'paid' ? 'selected' : ''; ?>><?php echo __('paid', 'Paid'); ?></option>
                                <option value="failed" <?php echo $paymentStatus === 'failed' ? 'selected' : ''; ?>><?php echo __('failed', 'Failed'); ?></option>
                                <option value="refunded" <?php echo $paymentStatus === 'refunded' ? 'selected' : ''; ?>><?php echo __('refunded', 'Refunded'); ?></option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><?php echo __('date_from', 'Date From'); ?></label>
                            <input type="date" name="date_from" class="form-control" 
                                   value="<?php echo htmlspecialchars($dateFrom); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><?php echo __('date_to', 'Date To'); ?></label>
                            <input type="date" name="date_to" class="form-control" 
                                   value="<?php echo htmlspecialchars($dateTo); ?>">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter"></i>
                            </button>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <a href="index.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="card">
                <div class="card-body">
                    <form method="POST" id="bulkActionsForm" action="index.php<?php echo !empty($_GET) ? '?' . htmlspecialchars(http_build_query($_GET)) : ''; ?>">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th width="40">
                                            <input type="checkbox" id="selectAll" class="form-check-input">
                                        </th>
                                        <th><?php echo __('order_number', 'Order #'); ?></th>
                                        <th><?php echo __('customer', 'Customer'); ?></th>
                                        <th><?php echo __('date', 'Date'); ?></th>
                                        <th><?php echo __('items', 'Items'); ?></th>
                                        <th><?php echo __('total', 'Total'); ?></th>
                                        <th><?php echo __('status', 'Status'); ?></th>
                                        <th><?php echo __('payment', 'Payment'); ?></th>
                                        <th><?php echo __('actions', 'Actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($orders)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center py-4">
                                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                                <h5><?php echo __('no_orders_found', 'No orders found'); ?></h5>
                                                <p class="text-muted"><?php echo __('try_adjusting_filters', 'Try adjusting your filters or search criteria.'); ?></p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($orders as $order): ?>
                                            <tr>
                                                <td>
                                                    <input type="checkbox" name="order_ids[]" 
                                                           class="form-check-input order-checkbox" 
                                                           value="<?php echo $order['id']; ?>">
                                                </td>
                                                <td>
                                                    <strong>#<?php echo htmlspecialchars($order['order_number']); ?></strong>
                                                </td>
                                                <td>
                                                    <?php if ($order['user_id']): ?>
                                                        <a href="../customers/edit.php?id=<?php echo $order['user_id']; ?>" 
                                                           class="text-decoration-none">
                                                            <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?>
                                                        </a>
                                                        <br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($order['user_email']); ?></small>
                                                    <?php else: ?>
                                                        <?php echo htmlspecialchars($order['guest_email'] ?? __('guest', 'Guest')); ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php echo date('M d, Y', strtotime($order['created_at'] . ' UTC')); ?>
                                                    <br>
                                                    <small class="text-muted"><?php echo date('H:i', strtotime($order['created_at'] . ' UTC')); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary"><?php echo $order['item_count']; ?></span>
                                                </td>
                                                <td>
                                                    <strong><?php echo $currency->format($order['total_amount']); ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo getStatusColor($order['order_status']); ?>">
                                                        <?php echo ucfirst($order['order_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo getPaymentStatusColor($order['payment_status']); ?>">
                                                        <?php echo ucfirst($order['payment_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="view.php?id=<?php echo $order['id']; ?>" 
                                                           class="btn btn-outline-primary" title="View Order">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="edit.php?id=<?php echo $order['id']; ?>" 
                                                           class="btn btn-outline-secondary" title="<?php echo __('edit_order', 'Edit Order'); ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="invoice.php?id=<?php echo $order['id']; ?>" 
                                                           class="btn btn-outline-dark" title="<?php echo __('view_invoice', 'View Invoice'); ?>" target="_blank">
                                                            <i class="fas fa-file-invoice"></i>
                                                        </a>
                                                        <?php if ($order['order_status'] === 'delivered'): ?>
                                                        <a href="../inventory/sales-returns/add.php?order_id=<?php echo $order['id']; ?>" 
                                                           class="btn btn-outline-success" title="Create Sales Return">
                                                            <i class="fas fa-undo"></i>
                                                        </a>
                                                        <?php endif; ?>
                                                        <a href="delete.php?id=<?php echo $order['id']; ?>" 
                                                           class="btn btn-outline-danger" 
                                                           title="<?php echo __('delete_order', 'Delete Order'); ?>"
                                                           onclick="return confirm('Are you sure you want to delete order #<?php echo htmlspecialchars($order['order_number']); ?>? This action cannot be undone.')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Bulk Actions (hidden) -->
                        <input type="hidden" name="bulk_action" id="bulkAction">
                    </form>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Orders pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $status ? '&status=' . $status : ''; ?><?php echo $paymentStatus ? '&payment_status=' . $paymentStatus : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php 
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        for ($i = $startPage; $i <= $endPage; $i++): 
                        ?>
                            <?php if ($i == $page): ?>
                                <li class="page-item active">
                                    <span class="page-link"><?php echo $i; ?></span>
                                </li>
                            <?php else: ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $status ? '&status=' . $status : ''; ?><?php echo $paymentStatus ? '&payment_status=' . $paymentStatus : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $status ? '&status=' . $status : ''; ?><?php echo $paymentStatus ? '&payment_status=' . $paymentStatus : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Bulk Actions Modal -->
<div class="modal fade" id="bulkActionsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo __('bulk_actions', 'Bulk Actions'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><?php echo __('select_bulk_action_message', 'Select an action to apply to all selected orders:'); ?></p>
                <div class="list-group">
                    <button type="button" class="list-group-item list-group-item-action" onclick="applyBulkAction('mark_pending')">
                        <i class="fas fa-clock text-warning me-2"></i> <?php echo __('mark_as_pending', 'Mark as Pending'); ?>
                    </button>
                    <button type="button" class="list-group-item list-group-item-action" onclick="applyBulkAction('mark_confirmed')">
                        <i class="fas fa-check text-info me-2"></i> <?php echo __('mark_as_confirmed', 'Mark as Confirmed'); ?>
                    </button>
                    <button type="button" class="list-group-item list-group-item-action" onclick="applyBulkAction('mark_processing')">
                        <i class="fas fa-cog text-primary me-2"></i> <?php echo __('mark_as_processing', 'Mark as Processing'); ?>
                    </button>
                    <button type="button" class="list-group-item list-group-item-action" onclick="applyBulkAction('mark_shipped')">
                        <i class="fas fa-truck text-success me-2"></i> <?php echo __('mark_as_shipped', 'Mark as Shipped'); ?>
                    </button>
                    <button type="button" class="list-group-item list-group-item-action" onclick="applyBulkAction('mark_delivered')">
                        <i class="fas fa-check-circle text-success me-2"></i> <?php echo __('mark_as_delivered', 'Mark as Delivered'); ?>
                    </button>
                    <button type="button" class="list-group-item list-group-item-action text-danger" onclick="applyBulkAction('mark_cancelled')">
                        <i class="fas fa-times-circle me-2"></i> <?php echo __('mark_as_cancelled', 'Mark as Cancelled'); ?>
                    </button>
                    <button type="button" class="list-group-item list-group-item-action text-danger" onclick="applyBulkAction('delete')">
                        <i class="fas fa-trash me-2"></i> <?php echo __('delete_orders', 'Delete Orders'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
// Select all functionality
var selectAllEl = document.getElementById('selectAll');
if (selectAllEl) {
    selectAllEl.addEventListener('change', function() {
        document.querySelectorAll('.order-checkbox').forEach(function(cb) { cb.checked = this.checked; }.bind(this));
    });
}

document.querySelectorAll('.order-checkbox').forEach(function(checkbox) {
    checkbox.addEventListener('change', function() {
        var all = document.querySelectorAll('.order-checkbox');
        var checked = document.querySelectorAll('.order-checkbox:checked');
        if (selectAllEl) selectAllEl.checked = all.length > 0 && all.length === checked.length;
    });
});

// Bulk action functionality
function applyBulkAction(action) {
    const checkedBoxes = document.querySelectorAll('.order-checkbox:checked');
    const count = checkedBoxes.length;
    if (count === 0) {
        alert('<?php echo addslashes(__('please_select_orders', 'Please select at least one order to perform bulk actions.')); ?>');
        return;
    }
    
    let msg;
    if (action === 'delete') {
        msg = '<?php echo addslashes(__('confirm_delete_orders', 'Are you sure you want to delete %d order(s)? This action cannot be undone.')); ?>'.replace('%d', count);
    } else {
        msg = '<?php echo addslashes(__('confirm_bulk_action', 'Are you sure you want to apply this action to %d order(s)?')); ?>'.replace('%d', count);
    }
    if (!confirm(msg)) return;
    
    var modal = document.getElementById('bulkActionsModal');
    if (modal && typeof bootstrap !== 'undefined') {
        var bsModal = bootstrap.Modal.getInstance(modal);
        if (bsModal) bsModal.hide();
    }
    document.getElementById('bulkAction').value = action;
    document.getElementById('bulkActionsForm').submit();
}
</script>

<?php
// Helper functions
function getStatusColor($status) {
    $colors = [
        'pending' => 'warning',
        'confirmed' => 'info',
        'processing' => 'primary',
        'shipped' => 'success',
        'delivered' => 'success',
        'cancelled' => 'danger'
    ];
    return $colors[$status] ?? 'secondary';
}

function getPaymentStatusColor($status) {
    $colors = [
        'pending' => 'warning',
        'paid' => 'success',
        'failed' => 'danger',
        'refunded' => 'info'
    ];
    return $colors[$status] ?? 'secondary';
}
?>
