<?php
require_once '../../includes/auth.php';
requirePermission('inventory');
$pageTitle = __('supplier_returns', 'Supplier Returns');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'create':
            handleCreateSupplierReturn();
            break;
        case 'update':
            handleUpdateSupplierReturn();
            break;
        case 'delete':
            handleDeleteSupplierReturn();
            break;
        case 'approve':
            handleApproveSupplierReturn();
            break;
        case 'reject':
            handleRejectSupplierReturn();
            break;
    }
}

// Get supplier returns with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$supplier = $_GET['supplier'] ?? '';

// Build WHERE clause
$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(sr.return_number LIKE :search OR s.name LIKE :search)";
    $params['search'] = "%$search%";
}

if (!empty($status)) {
    $where[] = "sr.status = :status";
    $params['status'] = $status;
}

if (!empty($supplier)) {
    $where[] = "sr.supplier_id = :supplier";
    $params['supplier'] = $supplier;
}

$whereClause = implode(' AND ', $where);

// Get total items
$totalItems = $db->fetchOne("
    SELECT COUNT(DISTINCT sr.id) as total
    FROM supplier_returns sr
    LEFT JOIN suppliers s ON sr.supplier_id = s.id
    WHERE $whereClause
", $params)['total'] ?? 0;

$totalPages = ceil($totalItems / $perPage);

// Get suppliers and warehouses for filters
$suppliers = $db->fetchAll("SELECT * FROM suppliers WHERE is_active = 1 ORDER BY name");
$warehouses = $db->fetchAll("SELECT * FROM warehouses WHERE is_active = 1 ORDER BY name");

// Get supplier returns
$supplierReturns = $db->fetchAll("
    SELECT sr.*, s.name as supplier_name, w.name as warehouse_name
    FROM supplier_returns sr
    LEFT JOIN suppliers s ON sr.supplier_id = s.id
    LEFT JOIN warehouses w ON sr.warehouse_id = w.id
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
    <h1 class="h2"><?php echo __('supplier_returns', 'Supplier Returns'); ?></h1>
    <a href="add.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> <?php echo __('create_supplier_return', 'Create Supplier Return'); ?>
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
                       placeholder="<?php echo __('search_by_return_or_supplier', 'Search by return number or supplier'); ?>">
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

<!-- Supplier Returns Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0"><?php echo __('supplier_returns_list', 'Supplier Returns List'); ?></h5>
    </div>
    <div class="card-body">
        <?php if (!empty($supplierReturns)): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th><?php echo __('return_number', 'Return Number'); ?></th>
                            <th><?php echo __('supplier', 'Supplier'); ?></th>
                            <th><?php echo __('warehouse', 'Warehouse'); ?></th>
                            <th><?php echo __('return_date', 'Return Date'); ?></th>
                            <th><?php echo __('total_amount', 'Total Amount'); ?></th>
                            <th><?php echo __('status', 'Status'); ?></th>
                            <th><?php echo __('actions', 'Actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($supplierReturns as $return): ?>
                        <tr>
                            <td>
                                <a href="view.php?id=<?php echo $return['id']; ?>">
                                    <?php echo htmlspecialchars($return['return_number'] ?? 'N/A'); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($return['supplier_name']); ?></td>
                            <td><?php echo htmlspecialchars($return['warehouse_name']); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($return['return_date'])); ?></td>
                            <td><?php echo $currency->format($return['total_amount']); ?></td>
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
                                    <?php if (in_array($return['status'], ['pending'])): ?>
                                        <a href="delete.php?id=<?php echo $return['id']; ?>" class="btn btn-outline-danger" 
                                           onclick="return confirm('<?php echo __('delete_return_warning', 'Are you sure you want to delete this supplier return?'); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-outline-danger" disabled title="Cannot delete approved or rejected returns">
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
                <nav aria-label="Supplier returns pagination">
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
                <i class="fas fa-undo fa-3x text-muted mb-3"></i>
                <h5><?php echo __('no_supplier_returns_found', 'No supplier returns found'); ?></h5>
                <p class="text-muted"><?php echo __('try_different_search', 'Try adjusting your search criteria'); ?></p>
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> <?php echo __('create_first_supplier_return', 'Create Your First Supplier Return'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function approveReturn(returnId) {
    const notes = prompt('<?php echo __('approve_notes_prompt', 'Please enter any notes for this approval (optional):'); ?>');
    
    if (notes === null) {
        return; // User cancelled
    }
    
    // Create a form to submit the approval request directly
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'approve-direct.php';
    form.style.display = 'none';
    
    const returnIdInput = document.createElement('input');
    returnIdInput.type = 'hidden';
    returnIdInput.name = 'id';
    returnIdInput.value = returnId;
    
    const notesInput = document.createElement('input');
    notesInput.type = 'hidden';
    notesInput.name = 'notes';
    notesInput.value = notes;
    
    form.appendChild(returnIdInput);
    form.appendChild(notesInput);
    document.body.appendChild(form);
    form.submit();
}

function rejectReturn(returnId) {
    const notes = prompt('<?php echo __('reject_reason_prompt', 'Please enter the reason for rejection:'); ?>');
    
    if (notes === null || notes.trim() === '') {
        alert('<?php echo __('reject_reason_required', 'Rejection reason is required.'); ?>');
        return;
    }
    
    // Create a form to submit the rejection request directly
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'reject-direct.php';
    form.style.display = 'none';
    
    const returnIdInput = document.createElement('input');
    returnIdInput.type = 'hidden';
    returnIdInput.name = 'id';
    returnIdInput.value = returnId;
    
    const notesInput = document.createElement('input');
    notesInput.type = 'hidden';
    notesInput.name = 'notes';
    notesInput.value = notes;
    
    form.appendChild(returnIdInput);
    form.appendChild(notesInput);
    document.body.appendChild(form);
    form.submit();
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
