<?php
require_once '../../includes/auth.php';
requirePermission('inventory');
$pageTitle = __('purchase_orders', 'Purchase Orders');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'create':
            handleCreatePurchaseOrder();
            break;
        case 'update':
            handleUpdatePurchaseOrder();
            break;
        case 'delete':
            handleDeletePurchaseOrder();
            break;
        case 'receive':
            handleReceivePurchaseOrder();
            break;
    }
}

// Get purchase orders with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$supplier = $_GET['supplier'] ?? '';

// Backward compatibility: older UI used "pending" where we now use "draft"
if ($status === 'pending') {
    $status = 'draft';
}

// Build WHERE clause
$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(po.order_number LIKE :search OR s.name LIKE :search)";
    $params['search'] = "%$search%";
}

if (!empty($status)) {
    $where[] = "po.status = :status";
    $params['status'] = $status;
}

if (!empty($supplier)) {
    $where[] = "po.supplier_id = :supplier";
    $params['supplier'] = $supplier;
}

$whereClause = implode(' AND ', $where);

$purchaseOrders = $db->fetchAll("
    SELECT po.*, s.name as supplier_name, w.name as warehouse_name,
           COUNT(poi.id) as item_count,
           SUM(poi.total_price) as total_amount
    FROM purchase_orders po
    LEFT JOIN suppliers s ON po.supplier_id = s.id
    LEFT JOIN warehouses w ON po.warehouse_id = w.id
    LEFT JOIN purchase_order_items poi ON po.id = poi.purchase_order_id
    WHERE $whereClause
    GROUP BY po.id
    ORDER BY po.created_at DESC
    LIMIT $perPage OFFSET $offset
", $params);

$totalItems = $db->fetchOne("
    SELECT COUNT(DISTINCT po.id) as total
    FROM purchase_orders po
    LEFT JOIN suppliers s ON po.supplier_id = s.id
    WHERE $whereClause
", $params)['total'] ?? 0;

$totalPages = ceil($totalItems / $perPage);

// Get suppliers and warehouses for filters
$suppliers = $db->fetchAll("SELECT * FROM suppliers WHERE is_active = 1 ORDER BY name");
$warehouses = $db->fetchAll("SELECT * FROM warehouses WHERE is_active = 1 ORDER BY name");

// Get default currency
$defaultCurrency = $db->fetchOne("SELECT * FROM currencies WHERE is_default = 1");

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('purchase_orders', 'Purchase Orders'); ?></h1>
    <a href="add.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> <?php echo __('create_purchase_order', 'Create Purchase Order'); ?>
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
                       placeholder="<?php echo __('search_by_order_or_supplier', 'Search by order number or supplier'); ?>">
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label"><?php echo __('status', 'Status'); ?></label>
                <select class="form-select" id="status" name="status">
                    <option value=""><?php echo __('all', 'All'); ?></option>
                    <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>
                        <?php echo __('draft', 'Draft'); ?>
                    </option>
                    <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>
                        <?php echo __('confirmed', 'Confirmed'); ?>
                    </option>
                    <option value="received" <?php echo $status === 'received' ? 'selected' : ''; ?>>
                        <?php echo __('received', 'Received'); ?>
                    </option>
                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>
                        <?php echo __('cancelled', 'Cancelled'); ?>
                    </option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="supplier" class="form-label"><?php echo __('supplier', 'Supplier'); ?></label>
                <select class="form-select" id="supplier" name="supplier">
                    <option value=""><?php echo __('all', 'All'); ?></option>
                    <?php foreach ($suppliers as $sup): ?>
                    <option value="<?php echo $sup['id']; ?>" <?php echo $supplier == $sup['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($sup['name']); ?>
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

<!-- Purchase Orders Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <?php echo __('purchase_orders_list', 'Purchase Orders List'); ?>
            <span class="badge bg-secondary ms-2"><?php echo $totalItems; ?> <?php echo __('items', 'items'); ?></span>
        </h5>
    </div>
    <div class="card-body">
        <?php if (!empty($purchaseOrders)): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th><?php echo __('order_number', 'Order Number'); ?></th>
                            <th><?php echo __('supplier', 'Supplier'); ?></th>
                            <th><?php echo __('warehouse', 'Warehouse'); ?></th>
                            <th><?php echo __('order_date', 'Order Date'); ?></th>
                            <th><?php echo __('expected_date', 'Expected Date'); ?></th>
                            <th><?php echo __('total_amount', 'Total Amount'); ?></th>
                            <th><?php echo __('status', 'Status'); ?></th>
                            <th><?php echo __('items', 'Items'); ?></th>
                            <th><?php echo __('actions', 'Actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($purchaseOrders as $order): ?>
                        <tr>
                            <td>
                                <a href="view.php?id=<?php echo $order['id']; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($order['po_number'] ?? $order['order_number'] ?? 'N/A'); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($order['supplier_name']); ?></td>
                            <td><?php echo htmlspecialchars($order['warehouse_name']); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($order['order_date'])); ?></td>
                            <td>
                                <?php echo !empty($order['expected_delivery']) ? date('Y-m-d', strtotime($order['expected_delivery'])) : '-'; ?>
                            </td>
                            <td><?php echo $currency->format($order['total_amount']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo getStatusBadgeClass($order['status']); ?>">
                                    <?php echo __($order['status'], ucfirst($order['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo $order['item_count']; ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="view.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary" title="<?php echo __('view', 'View'); ?>">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-secondary" title="<?php echo __('edit', 'Edit'); ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($order['status'] === 'confirmed'): ?>
                                    <button type="button" class="btn btn-sm btn-success" onclick="receiveOrder(<?php echo $order['id']; ?>)" title="<?php echo __('receive_order', 'Receive Order'); ?>">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php if (in_array($order['status'], ['draft', 'pending'])): ?>
                                        <a href="delete.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-danger btn-delete" 
                                           title="<?php echo __('delete', 'Delete'); ?>"
                                           onclick="return confirm('<?php echo __('delete_purchase_order_warning', 'Are you sure you want to delete this purchase order?'); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-outline-danger" disabled 
                                                title="<?php echo __('cannot_delete_processed_order', 'Cannot delete received, cancelled, or confirmed orders'); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
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
                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                <h5><?php echo __('no_purchase_orders_found', 'No purchase orders found'); ?></h5>
                <p class="text-muted"><?php echo __('try_different_search', 'Try adjusting your search criteria'); ?></p>
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> <?php echo __('create_first_purchase_order', 'Create Your First Purchase Order'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function receiveOrder(orderId) {
    if (confirm('<?php echo __('confirm_receive_order', 'Are you sure you want to receive this order?'); ?>')) {
        window.location.href = 'receive.php?id=' + orderId;
    }
}
</script>

<?php
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'draft':
            return 'secondary';
        case 'pending':
            // legacy value
            return 'secondary';
        case 'confirmed':
            return 'info';
        case 'received':
            return 'success';
        case 'cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
}

function handleCreatePurchaseOrder() {
    // Implementation for creating purchase order
    // This would handle form validation and database insertion
}

function handleUpdatePurchaseOrder() {
    // Implementation for updating purchase order
}

function handleDeletePurchaseOrder() {
    // Implementation for deleting purchase order
}

function handleReceivePurchaseOrder() {
    // Implementation for receiving purchase order
}
?>

<?php include '../../includes/footer.php'; ?>