<?php
/**
 * Customers Management - Modern Interface
 */

require_once '../includes/auth.php';
requirePermission('customers');
require_once INCLUDES_PATH . 'Customer.php';

$pageTitle = 'Customers Management';

// Initialize Customer class
$customer = new Customer($db);

// Get pagination and filter parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$group = isset($_GET['group']) ? (int)$_GET['group'] : '';

// Get customers data
$customersData = $customer->getAllCustomers($page, $perPage, $search, $status, $group);
$customers = $customersData['customers'];
$totalPages = $customersData['totalPages'];
$total = $customersData['total'];

// Get customer groups for filter dropdown
$customerGroups = $customer->getCustomerGroups();

// Get customer statistics
$stats = $customer->getCustomerStats();

// Handle export request
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="customers_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // CSV header
    fputcsv($output, ['ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Company', 'Group', 'Status', 'Orders', 'Total Spent', 'Member Since']);
    
    // Get all customers for export
    $exportData = $customer->exportToCSV($search, $status, $group);
    
    foreach ($exportData as $row) {
        fputcsv($output, [
            $row['id'],
            $row['first_name'],
            $row['last_name'],
            $row['email'],
            $row['phone'] ?? '',
            $row['company'] ?? '',
            $row['customer_group'] ?? '',
            $row['is_active'] ? 'Active' : 'Inactive',
            $row['total_orders'],
            $currency->format($row['total_spent']),
            date('Y-m-d', strtotime($row['created_at']))
        ]);
    }
    
    fclose($output);
    exit;
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title"><?php echo number_format($stats['total']); ?></h4>
                        <p class="card-text"><?php echo $lang->get('total_customers'); ?></p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title"><?php echo number_format($stats['active']); ?></h4>
                        <p class="card-text"><?php echo $lang->get('active_customers'); ?></p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-user-check fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title"><?php echo number_format($stats['new_this_month']); ?></h4>
                        <p class="card-text">New This Month</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-user-plus fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title"><?php echo number_format($stats['with_orders']); ?></h4>
                        <p class="card-text">With Orders</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-shopping-cart fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo $lang->get('customers_management'); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> <?php echo $lang->get('add_new_customer'); ?>
            </a>
            <a href="?export=csv&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&group=<?php echo urlencode($group); ?>" 
               class="btn btn-success">
                <i class="fas fa-download"></i> Export CSV
            </a>
        </div>
    </div>
</div>

<!-- Search and Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label"><?php echo $lang->get('search_customers'); ?></label>
                <div class="input-group">
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search by name, email, phone...">
                    <button type="submit" class="btn btn-outline-secondary">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Status</option>
                    <option value="1" <?php echo $status === '1' ? 'selected' : ''; ?>>Active</option>
                    <option value="0" <?php echo $status === '0' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="group" class="form-label">Customer Group</label>
                <select class="form-select" id="group" name="group">
                    <option value="">All Groups</option>
                    <?php foreach ($customerGroups as $cg): ?>
                        <option value="<?php echo $cg['id']; ?>" 
                                <?php echo $group == $cg['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cg['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="per_page" class="form-label">Per Page</label>
                <select class="form-select" id="per_page" name="per_page">
                    <option value="10" <?php echo $perPage === 10 ? 'selected' : ''; ?>>10</option>
                    <option value="20" <?php echo $perPage === 20 ? 'selected' : ''; ?>>20</option>
                    <option value="50" <?php echo $perPage === 50 ? 'selected' : ''; ?>>50</option>
                    <option value="100" <?php echo $perPage === 100 ? 'selected' : ''; ?>>100</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <a href="index.php" class="btn btn-secondary w-100">
                    <i class="fas fa-times"></i> Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Customers Table -->
<div class="card">
    <div class="card-body">
        <?php if (empty($customers)): ?>
            <div class="text-center py-5">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h5><?php echo $lang->get('no_customers_found'); ?></h5>
                <p class="text-muted">Try adjusting your search criteria or add a new customer.</p>
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> <?php echo $lang->get('add_new_customer'); ?>
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover datatable">
                    <thead class="table-light">
                        <tr>
                            <th>
                                <input type="checkbox" class="form-check-input" id="selectAll">
                            </th>
                            <th>ID</th>
                            <th><?php echo $lang->get('customer_name'); ?></th>
                            <th><?php echo $lang->get('customer_email'); ?></th>
                            <th><?php echo $lang->get('customer_phone'); ?></th>
                            <th>Company</th>
                            <th>Group</th>
                            <th>Orders</th>
                            <th>Total Spent</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $cust): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input customer-checkbox" value="<?php echo $cust['id']; ?>">
                                </td>
                                <td>
                                    <span class="badge bg-secondary">#<?php echo $cust['id']; ?></span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                            <?php echo strtoupper(substr($cust['first_name'], 0, 1) . substr($cust['last_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="fw-medium"><?php echo htmlspecialchars($cust['first_name'] . ' ' . $cust['last_name']); ?></div>
                                            <small class="text-muted">Member since <?php echo date('M j, Y', strtotime($cust['created_at'])); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <a href="mailto:<?php echo htmlspecialchars($cust['email']); ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($cust['email']); ?>
                                    </a>
                                    <?php if ($cust['email_verified']): ?>
                                        <i class="fas fa-check-circle text-success ms-1" title="Email Verified"></i>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($cust['phone'])): ?>
                                        <a href="tel:<?php echo htmlspecialchars($cust['phone']); ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($cust['phone']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo !empty($cust['company']) ? htmlspecialchars($cust['company']) : '-'; ?>
                                </td>
                                <td>
                                    <?php if (!empty($cust['customer_group_name'])): ?>
                                        <span class="badge bg-info">
                                            <?php echo htmlspecialchars($cust['customer_group_name']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-primary"><?php echo $cust['total_orders']; ?></span>
                                </td>
                                <td>
                                    <strong><?php echo $currency->format($cust['total_spent']); ?></strong>
                                </td>
                                <td>
                                    <?php if ($cust['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="view.php?id=<?php echo $cust['id']; ?>" class="btn btn-outline-primary" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit.php?id=<?php echo $cust['id']; ?>" class="btn btn-outline-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger btn-delete-customer" 
                                                data-id="<?php echo $cust['id']; ?>" 
                                                data-name="<?php echo htmlspecialchars($cust['first_name'] . ' ' . $cust['last_name']); ?>"
                                                title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted">
                    Showing <?php echo ($page - 1) * $perPage + 1; ?> to 
                    <?php echo min($page * $perPage, $total); ?> of 
                    <?php echo $total; ?> customers
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&group=<?php echo urlencode($group); ?>&per_page=<?php echo $perPage; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&group=<?php echo urlencode($group); ?>&per_page=<?php echo $perPage; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&group=<?php echo urlencode($group); ?>&per_page=<?php echo $perPage; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete customer <strong id="deleteCustomerName"></strong>?</p>
                <p class="text-warning">
                    <small>
                        <?php if (false): ?>
                            This customer has orders and will be deactivated instead of deleted.
                        <?php else: ?>
                            This action cannot be undone.
                        <?php endif; ?>
                    </small>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" action="delete.php" style="display: inline;">
                    <input type="hidden" name="id" id="deleteCustomerId">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Delete confirmation
document.querySelectorAll('.btn-delete-customer').forEach(button => {
    button.addEventListener('click', function() {
        const id = this.dataset.id;
        const name = this.dataset.name;
        document.getElementById('deleteCustomerId').value = id;
        document.getElementById('deleteCustomerName').textContent = name;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    });
});

// Select all checkbox
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.customer-checkbox').forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});
</script>

<?php include '../includes/footer.php'; ?>