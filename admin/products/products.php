<?php
require_once '../config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_product':
            $productId = $_POST['product_id'] ?? 0;
            $data = [
                'name' => $_POST['name'] ?? '',
                'description' => $_POST['description'] ?? '',
                'price' => $_POST['price'] ?? 0,
                'weight' => $_POST['weight'] ?? 1.0,
                'sku' => $_POST['sku'] ?? '',
                'stock_status' => $_POST['stock_status'] ?? 'in_stock',
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];
            
            $result = $db->update('products', $data, 'id = :id', ['id' => $productId]);
            if ($result) {
                // Update translation
                $db->update('product_translations', [
                    'name' => $data['name'],
                    'description' => $data['description']
                ], 'product_id = :product_id AND language_id = :lang_id', [
                    'product_id' => $productId,
                    'lang_id' => 1 // English
                ]);
                
                $_SESSION['success'] = 'Product updated successfully';
            } else {
                $_SESSION['error'] = 'Failed to update product';
            }
            header('Location: products.php');
            exit;
            
        case 'toggle_status':
            $productId = $_POST['product_id'] ?? 0;
            $currentStatus = $db->fetchOne("SELECT is_active FROM products WHERE id = :id", ['id' => $productId]);
            
            if ($currentStatus) {
                $newStatus = $currentStatus['is_active'] ? 0 : 1;
                $result = $db->update('products', ['is_active' => $newStatus], 'id = :id', ['id' => $productId]);
                
                if ($result) {
                    $_SESSION['success'] = 'Product status updated successfully';
                } else {
                    $_SESSION['error'] = 'Failed to update product status';
                }
            }
            header('Location: products.php');
            exit;
            
        case 'delete':
            $productId = $_POST['product_id'] ?? 0;
            
            // Check if product has orders
            $orderCount = $db->fetchOne(
                "SELECT COUNT(*) as count FROM order_items WHERE product_id = :product_id",
                ['product_id' => $productId]
            )['count'] ?? 0;
            
            if ($orderCount > 0) {
                $_SESSION['error'] = 'Cannot delete product with existing orders';
            } else {
                // Delete product and related data
                $db->delete('product_translations', 'product_id = :product_id', ['product_id' => $productId]);
                $result = $db->delete('products', 'id = :id', ['id' => $productId]);
                
                if ($result) {
                    $_SESSION['success'] = 'Product deleted successfully';
                } else {
                    $_SESSION['error'] = 'Failed to delete product';
                }
            }
            header('Location: products.php');
            exit;
    }
}

// Get products with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$brand = $_GET['brand'] ?? '';
$status = $_GET['status'] ?? '';
$stock_status = $_GET['stock_status'] ?? '';

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

if ($stock_status !== '') {
    if ($stock_status === 'in_stock') {
        $whereConditions[] = "p.stock_quantity > 0";
    } elseif ($stock_status === 'out_of_stock') {
        $whereConditions[] = "p.stock_quantity = 0";
    } elseif ($stock_status === 'low_stock') {
        $whereConditions[] = "p.stock_quantity <= p.low_stock_threshold AND p.stock_quantity > 0";
    }
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get total products count
$totalProducts = $db->fetchOne("
    SELECT COUNT(*) as total 
    FROM products p
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
    LEFT JOIN product_categories pc ON p.id = pc.product_id
    $whereClause
", $params)['total'] ?? 0;

$totalPages = ceil($totalProducts / $per_page);

// Get products
$products = $db->fetchAll("
    SELECT p.*, 
           pt.name, 
           pt.description, 
           GROUP_CONCAT(DISTINCT ct.name) as category_names,
           b.name as brand_name,
           (SELECT COUNT(*) FROM product_reviews WHERE product_id = p.id AND is_approved = 1) as review_count,
           (SELECT COUNT(*) FROM order_items WHERE product_id = p.id) as sales_count,
           CASE WHEN p.stock_quantity = 0 THEN 'Out of Stock'
                WHEN p.stock_quantity <= p.low_stock_threshold THEN 'Low Stock'
                ELSE 'In Stock' END as stock_status_label
    FROM products p
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
    LEFT JOIN product_categories pc ON p.id = pc.product_id
    LEFT JOIN categories c ON pc.category_id = c.id
    LEFT JOIN category_translations ct ON c.id = ct.category_id AND ct.language_id = 1
    LEFT JOIN brands b ON p.brand_id = b.id
    $whereClause
    GROUP BY p.id
    ORDER BY p.created_at DESC
    LIMIT $offset, $per_page
", $params);

// Get categories for filter
$categories = $db->fetchAll("
    SELECT c.id, ct.name 
    FROM categories c 
    LEFT JOIN category_translations ct ON c.id = ct.category_id AND ct.language_id = 1
    WHERE c.is_active = 1 
    ORDER BY ct.name
");

// Get brands for filter
$brands = $db->fetchAll("SELECT * FROM brands WHERE is_active = 1 ORDER BY name");

// Get product details for edit
$editProduct = null;
$editId = $_GET['edit'] ?? 0;
if ($editId > 0) {
    $editProduct = $db->fetchOne("
        SELECT p.*, pt.name, pt.description
        FROM products p
        LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = 1
        WHERE p.id = :id
    ", ['id' => $editId]);
}

$pageTitle = 'Products Management';
include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-box me-2"></i>
                    Products Management
                </h1>
                <div class="d-flex gap-2">
                    <a href="product-import.php" class="btn btn-outline-secondary">
                        <i class="fas fa-upload me-1"></i> Import
                    </a>
                    <a href="product-export.php" class="btn btn-outline-secondary">
                        <i class="fas fa-download me-1"></i> Export
                    </a>
                    <a href="product-add.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Add Product
                    </a>
                </div>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Filters -->
            <form method="get" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search products..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-2">
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
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="1" <?php echo $status === '1' ? 'selected' : ''; ?>>Active</option>
                            <option value="0" <?php echo $status === '0' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="stock_status" class="form-select">
                            <option value="">All Stock</option>
                            <option value="in_stock" <?php echo $stock_status === 'in_stock' ? 'selected' : ''; ?>>In Stock</option>
                            <option value="low_stock" <?php echo $stock_status === 'low_stock' ? 'selected' : ''; ?>>Low Stock</option>
                            <option value="out_of_stock" <?php echo $stock_status === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-1"></i> Filter
                        </button>
                    </div>
                    <div class="col-md-2">
                        <div class="d-flex gap-2">
                            <span class="badge bg-primary"><?php echo $totalProducts; ?> Total</span>
                            <?php if ($search || $category || $status !== ''): ?>
                                <a href="products.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-times me-1"></i> Clear
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>
            
            <!-- Products Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Price</th>
                                    <th>Weight</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td>
                                            <?php 
                                            $productImage = $product['main_image'] ?? '';
                                            $hasImage = !empty($productImage) && file_exists('../../assets/uploads/products/' . $productImage);
                                            ?>
                                            <?php if ($hasImage): ?>
                                                <img src="<?php echo UPLOADS_URL; ?>products/<?php echo htmlspecialchars($productImage); ?>" 
                                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                     class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;"
                                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <?php endif; ?>
                                            <div class="img-thumbnail d-flex align-items-center justify-content-center bg-light text-muted" 
                                                 style="width: 50px; height: 50px; font-size: 8px; <?php echo $hasImage ? 'display: none;' : ''; ?>">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                                <?php if ($product['category_names']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($product['category_names']); ?></small>
                                                <?php endif; ?>
                                                <?php if ($product['brand_name']): ?>
                                                    <br><small class="text-info"><?php echo htmlspecialchars($product['brand_name']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['sku'] ?? ''); ?></td>
                                        <td class="fw-bold"><?php echo $currency->format($product['price']); ?></td>
                                        <td><?php echo htmlspecialchars($product['weight'] ?? '1.0'); ?> kg</td>
                                        <td>
                                            <span class="badge bg-<?php echo 
                                                $product['stock_quantity'] == 0 ? 'danger' : 
                                                ($product['stock_quantity'] <= $product['low_stock_threshold'] ? 'warning' : 'success'); ?>">
                                                <?php echo $product['stock_quantity']; ?>
                                            </span>
                                            <br><small class="text-muted"><?php echo $product['stock_status_label']; ?></small>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <?php if ($product['review_count'] > 0): ?>
                                                    <span class="badge bg-info" title="Reviews"><?php echo $product['review_count']; ?></span>
                                                <?php endif; ?>
                                                <?php if ($product['sales_count'] > 0): ?>
                                                    <span class="badge bg-primary" title="Sales"><?php echo $product['sales_count']; ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="product-edit.php?id=<?php echo $product['id']; ?>" 
                                                   class="btn btn-outline-primary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button class="btn btn-outline-<?php echo $product['is_active'] ? 'warning' : 'success'; ?>" 
                                                        onclick="toggleStatus(<?php echo $product['id']; ?>)"
                                                        title="<?php echo $product['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                    <i class="fas fa-<?php echo $product['is_active'] ? 'eye-slash' : 'eye'; ?>"></i>
                                                </button>
                                                <button class="btn btn-outline-info" 
                                                        onclick="viewStats(<?php echo $product['id']; ?>)"
                                                        title="View Stats">
                                                    <i class="fas fa-chart-bar"></i>
                                                </button>
                                                <button class="btn btn-outline-danger" 
                                                        onclick="deleteProduct(<?php echo $product['id']; ?>)"
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
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Products pagination">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $category ? '&category=' . $category : ''; ?><?php echo $status !== '' ? '&status=' . $status : ''; ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <?php if ($i == $page): ?>
                                        <li class="page-item active">
                                            <span class="page-link"><?php echo $i; ?></span>
                                        </li>
                                    <?php elseif ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $category ? '&category=' . $category : ''; ?><?php echo $status !== '' ? '&status=' . $status : ''; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $category ? '&category=' . $category : ''; ?><?php echo $status !== '' ? '&status=' . $status : ''; ?>">
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
    </div>
</div>

<script>
// Toggle product status
function toggleStatus(productId) {
    if (confirm('Are you sure you want to toggle this product status?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="toggle_status">
            <input type="hidden" name="product_id" value="${productId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Delete product
function deleteProduct(productId) {
    if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="product_id" value="${productId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// View product stats
function viewStats(productId) {
    // This would open a modal with product statistics
    // For now, just show a simple alert
    alert('Product statistics feature coming soon!');
}

// Bulk actions
document.addEventListener('DOMContentLoaded', function() {
    // Add select all functionality
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.product-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
