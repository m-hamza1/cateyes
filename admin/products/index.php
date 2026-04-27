<?php
/**
 * Products Management - Main Dashboard
 */

require_once '../includes/auth.php';
requirePermission('products');
$pageTitle = __('products_management', 'Products Management');

// Initialize classes
$product = new Product($db);

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $productIds = $_POST['product_ids'] ?? [];
    $action = $_POST['bulk_action'] ?? '';
    
    if (!empty($productIds) && $action) {
        $successCount = 0;
        foreach ($productIds as $productId) {
            switch ($action) {
                case 'activate':
                    if ($product->updateStatus($productId, 1)) $successCount++;
                    break;
                case 'deactivate':
                    if ($product->updateStatus($productId, 0)) $successCount++;
                    break;
                case 'delete':
                    if ($product->delete($productId)) $successCount++;
                    break;
                case 'feature':
                    if ($product->updateFeatured($productId, 1)) $successCount++;
                    break;
                case 'unfeature':
                    if ($product->updateFeatured($productId, 0)) $successCount++;
                    break;
            }
        }
        
        $_SESSION['success'] = __('products_updated_successfully', '{count} products successfully {action}', ['count' => $successCount, 'action' => $action]);
        header('Location: index.php?' . http_build_query($_GET));
        exit;
    }
}

// Get filters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$brand = $_GET['brand'] ?? '';
$status = $_GET['status'] ?? '';
$stockStatus = $_GET['stock_status'] ?? '';

// Build query conditions
$whereConditions = [];
$params = [];

if ($search) {
    $whereConditions[] = "(p.sku LIKE :search OR pt.name LIKE :search)";
    $params['search'] = '%' . $search . '%';
}

if ($category) {
    $whereConditions[] = "pc.category_id = :category";
    $params['category'] = $category;
}

if ($brand) {
    $whereConditions[] = "p.brand_id = :brand";
    $params['brand'] = $brand;
}

if ($status !== '') {
    $whereConditions[] = "p.is_active = :status";
    $params['status'] = $status;
}

if ($stockStatus !== '') {
    $whereConditions[] = "CASE WHEN COALESCE(p.stock_quantity, 0) = 0 THEN 'out_of_stock'
                         WHEN COALESCE(p.stock_quantity, 0) <= p.low_stock_threshold THEN 'low_stock'
                         ELSE 'in_stock' END = :stock_status";
    $params['stock_status'] = $stockStatus;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

// Get total count
$totalProducts = $db->fetchOne("
    SELECT COUNT(*) as total 
    FROM products p
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
    LEFT JOIN product_categories pc ON p.id = pc.product_id
    LEFT JOIN categories c ON pc.category_id = c.id
    LEFT JOIN brands b ON p.brand_id = b.id
    $whereClause
", $params)['total'] ?? 0;

$totalPages = ceil($totalProducts / $perPage);

// Get products
$products = $db->fetchAll("
    SELECT p.*, 
           pt.name as name, pt.description as description,
           GROUP_CONCAT(DISTINCT ct.name) as category_names,
           b.name as brand_name,
           (SELECT COUNT(*) FROM product_reviews WHERE product_id = p.id AND is_approved = 1) as review_count,
           (SELECT COUNT(*) FROM order_items WHERE product_id = p.id) as sales_count,
           COALESCE(p.stock_quantity, 0) as stock_quantity,
           CASE WHEN COALESCE(p.stock_quantity, 0) = 0 THEN 'Out of Stock'
                WHEN COALESCE(p.stock_quantity, 0) <= p.low_stock_threshold THEN 'Low Stock'
                ELSE 'In Stock' END as stock_status
    FROM products p
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
    LEFT JOIN product_categories pc ON p.id = pc.product_id
    LEFT JOIN categories c ON pc.category_id = c.id
    LEFT JOIN category_translations ct ON c.id = ct.category_id AND ct.language_id = 1
    LEFT JOIN brands b ON p.brand_id = b.id
    $whereClause
    GROUP BY p.id
    ORDER BY p.created_at DESC
    LIMIT $offset, $perPage
", $params);

// Get categories for filter
$categories = $db->fetchAll("
    SELECT c.id, ct.name 
    FROM categories c 
    LEFT JOIN category_translations ct ON c.id = ct.category_id AND ct.language_id = 1
    WHERE c.is_active = 1 
    ORDER BY c.sort_order, ct.name
");
$brands = $db->fetchAll("SELECT * FROM brands WHERE is_active = 1 ORDER BY name");

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-box me-2"></i>
                    Products Management
                </h1>
                <div class="d-flex gap-2">
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Add New Product
                    </a>
                    <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#bulkActionsModal">
                        <i class="fas fa-tasks me-1"></i> Bulk Actions
                    </button>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo number_format($totalProducts); ?></h5>
                            <p class="mb-0">Total Products</p>
                            <i class="fas fa-box fa-2x"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo number_format($db->fetchOne("SELECT COUNT(*) as count FROM products WHERE is_active = 1")['count'] ?? 0); ?></h5>
                            <p class="mb-0">Active Products</p>
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo number_format($db->fetchOne("SELECT COUNT(*) as count FROM products WHERE is_active = 0")['count'] ?? 0); ?></h5>
                            <p class="mb-0">Inactive Products</p>
                            <i class="fas fa-pause-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo number_format($db->fetchOne("SELECT COUNT(*) as count FROM products WHERE COALESCE(stock_quantity, 0) <= low_stock_threshold")['count'] ?? 0); ?></h5>
                            <p class="mb-0">Low Stock</p>
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-filter me-2"></i> Filters
                    </h6>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Search by SKU, Name..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" 
                                            <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Brand</label>
                            <select name="brand" class="form-select">
                                <option value="">All Brands</option>
                                <?php foreach ($brands as $brand): ?>
                                    <option value="<?php echo $brand['id']; ?>" 
                                            <?php echo $brand == $brand['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($brand['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="1" <?php echo $status === '1' ? 'selected' : ''; ?>>Active</option>
                                <option value="0" <?php echo $status === '0' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Stock Status</label>
                            <select name="stock_status" class="form-select">
                                <option value="">All Stock</option>
                                <option value="in_stock" <?php echo $stockStatus === 'in_stock' ? 'selected' : ''; ?>>In Stock</option>
                                <option value="low_stock" <?php echo $stockStatus === 'low_stock' ? 'selected' : ''; ?>>Low Stock</option>
                                <option value="out_of_stock" <?php echo $stockStatus === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-1"></i> Apply Filters
                            </button>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <a href="index.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-times me-1"></i> Clear Filters
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Products Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-list me-2"></i> Products (<?php echo number_format($totalProducts); ?>)
                    </h6>
                    <div class="form-check">
                        <input type="checkbox" id="selectAll" class="form-check-input">
                        <label class="form-check-label" for="selectAll">Select All</label>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="40">
                                        <input type="checkbox" class="form-check-input" id="selectAllHeader">
                                    </th>
                                    <th>Image</th>
                                    <th>SKU</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Brand</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th>Reviews</th>
                                    <th>Sales</th>
                                    <th width="120">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($products)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center py-4">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                            <h5>No products found</h5>
                                            <p class="text-muted">Try adjusting your filters or add new products.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="product_ids[]" 
                                                       class="form-check-input product-checkbox" 
                                                       value="<?php echo $product['id']; ?>">
                                            </td>
                                            <td>
                                                <?php if ($product['main_image']): ?>
                                                    <img src="<?php echo UPLOADS_URL; ?>products/<?php echo htmlspecialchars($product['main_image']); ?>" 
                                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                         class="img-thumbnail" 
                                                         style="width: 50px; height: 50px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-secondary text-white text-center p-2" style="width: 50px; height: 50px;">
                                                        <i class="fas fa-image fa-2x"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($product['sku']); ?></strong>
                                            </td>
                                            <td>
                                                <a href="edit.php?id=<?php echo $product['id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($product['name']); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($product['category_names'] ?? '-'); ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($product['brand_name'] ?? '-'); ?>
                                            </td>
                                            <td>
                                                <?php echo $currency->format($product['price']); ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo 
                                                    $product['stock_quantity'] == 0 ? 'danger' : 
                                                    ($product['stock_quantity'] <= $product['low_stock_threshold'] ? 'warning' : 'success'); ?>">
                                                    <?php echo $product['stock_quantity']; ?>
                                                </span>
                                                <br><small class="text-muted"><?php echo $product['stock_status']; ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $product['is_active'] ? 'success' : 'secondary'; ?>">
                                                    <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $product['review_count']; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo $product['sales_count']; ?></span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="edit.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="edit.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-info" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete" 
                                                            title="Delete"
                                                            data-id="<?php echo $product['id']; ?>"
                                                            data-name="<?php echo htmlspecialchars($product['name']); ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Products pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $category ? '&category=' . $category : ''; ?><?php echo $brand ? '&brand=' . $brand : ''; ?><?php echo $status ? '&status=' . $status : ''; ?><?php echo $stockStatus ? '&stock_status=' . $stockStatus : ''; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php 
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        for ($i = $startPage; $i <= $endPage; $i++): 
                        ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $category ? '&category=' . $category : ''; ?><?php echo $brand ? '&brand=' . $brand : ''; ?><?php echo $status ? '&status=' . $status : ''; ?><?php echo $stockStatus ? '&stock_status=' . $stockStatus : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $category ? '&category=' . $category : ''; ?><?php echo $brand ? '&brand=' . $brand : ''; ?><?php echo $status ? '&status=' . $status : ''; ?><?php echo $stockStatus ? '&stock_status=' . $stockStatus : ''; ?>">
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
                <h5 class="modal-title">Bulk Actions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Select an action to apply to all selected products:</p>
                <div class="list-group">
                    <button type="button" class="list-group-item list-group-item-action" onclick="applyBulkAction('activate')">
                        <i class="fas fa-check text-success me-2"></i> Activate Selected
                    </button>
                    <button type="button" class="list-group-item list-group-item-action" onclick="applyBulkAction('deactivate')">
                        <i class="fas fa-pause text-warning me-2"></i> Deactivate Selected
                    </button>
                    <button type="button" class="list-group-item list-group-item-action" onclick="applyBulkAction('feature')">
                        <i class="fas fa-star text-primary me-2"></i> Feature Selected
                    </button>
                    <button type="button" class="list-group-item list-group-item-action" onclick="applyBulkAction('unfeature')">
                        <i class="fas fa-star-o text-secondary me-2"></i> Unfeature Selected
                    </button>
                    <button type="button" class="list-group-item text-danger" onclick="applyBulkAction('delete')">
                        <i class="fas fa-trash me-2"></i> Delete Selected
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Select all functionality
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.product-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});

// Update select all when individual checkboxes change
document.querySelectorAll('.product-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const allCheckboxes = document.querySelectorAll('.product-checkbox');
        const checkedBoxes = document.querySelectorAll('.product-checkbox:checked');
        document.getElementById('selectAll').checked = allCheckboxes.length === checkedBoxes.length;
        document.getElementById('selectAllHeader').checked = allCheckboxes.length === checkedBoxes.length;
    });
});

// Bulk action functionality
function applyBulkAction(action) {
    const checkedBoxes = document.querySelectorAll('.product-checkbox:checked');
    if (checkedBoxes.length === 0) {
        alert('Please select at least one product to perform bulk actions.');
        return;
    }
    
    let confirmMessage = '';
    switch (action) {
        case 'activate':
            confirmMessage = 'Are you sure you want to activate ' + checkedBoxes.length + ' products?';
            break;
        case 'deactivate':
            confirmMessage = 'Are you sure you want to deactivate ' + checkedBoxes.length + ' products?';
            break;
        case 'feature':
            confirmMessage = 'Are you sure you want to feature ' + checkedBoxes.length + ' products?';
            break;
        case 'unfeature':
            confirmMessage = 'Are you sure you want to unfeature ' + checkedBoxes.length + ' products?';
            break;
        case 'delete':
            confirmMessage = 'Are you sure you want to delete ' + checkedBoxes.length + ' products? This action cannot be undone.';
            break;
    }
    
    if (confirm(confirmMessage)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'index.php';
        
        // Add hidden inputs
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'bulk_action';
        actionInput.value = action;
        form.appendChild(actionInput);
        
        checkedBoxes.forEach(checkbox => {
            const productIdInput = document.createElement('input');
            productIdInput.type = 'hidden';
            productIdInput.name = 'product_ids[]';
            productIdInput.value = checkbox.value;
            form.appendChild(productIdInput);
        });
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Delete functionality
document.querySelectorAll('.btn-delete').forEach(button => {
    button.addEventListener('click', function() {
        const productId = this.dataset.id;
        const productName = this.dataset.name;
        
        if (confirm('Are you sure you want to delete "' + productName + '"? This action cannot be undone.')) {
            window.location.href = 'delete.php?id=' + productId;
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
