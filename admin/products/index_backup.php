<?php
require_once '../includes/auth.php';
$pageTitle = __('products', 'Products');

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $product = $db->fetchOne("SELECT main_image FROM products WHERE id = :id", ['id' => $id]);
    
    if ($product) {
        // Delete images
        if ($product['main_image'] && file_exists(UPLOADS_PATH . 'products/' . $product['main_image'])) {
            unlink(UPLOADS_PATH . 'products/' . $product['main_image']);
        }
        
        // Delete product
        $db->delete('products', 'id = :id', ['id' => $id]);
        $_SESSION['success'] = __('product_deleted_successfully', 'Product deleted successfully!');
    }
    header('Location: index.php');
    exit;
}

// Filters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$brand = $_GET['brand'] ?? '';
$status = $_GET['status'] ?? '';
$stock_status = $_GET['stock_status'] ?? '';

$where = '1=1';
$params = [];

if ($search) {
    $where .= ' AND (p.sku LIKE :search OR pt.name LIKE :search)';
    $params['search'] = '%' . $search . '%';
}
if ($category) {
    $where .= ' AND pc.category_id = :category';
    $params['category'] = $category;
}
if ($brand) {
    $where .= ' AND p.brand_id = :brand';
    $params['brand'] = $brand;
}
if ($status !== '') {
    $where .= ' AND p.is_active = :status';
    $params['status'] = $status;
}
if ($stock_status) {
    $where .= ' AND p.stock_status = :stock_status';
    $params['stock_status'] = $stock_status;
}

$products = $db->fetchAll("
    SELECT p.*, 
           pt.name,
           b.name as brand_name,
           GROUP_CONCAT(DISTINCT c.id) as category_ids
    FROM products p
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang
    LEFT JOIN brands b ON p.brand_id = b.id
    LEFT JOIN product_categories pc ON p.id = pc.product_id
    LEFT JOIN categories c ON pc.category_id = c.id
    WHERE $where
    GROUP BY p.id
    ORDER BY p.created_at DESC
", array_merge($params, ['lang' => $lang->getCurrentId()]));

// Statistics
$stats = [
    'total' => $db->count('products'),
    'active' => $db->count('products', 'is_active = 1'),
    'out_of_stock' => $db->count('products', 'stock_status = "out_of_stock"'),
    'featured' => $db->count('products', 'is_featured = 1')
];

// Get categories and brands for filters
$categories = $db->fetchAll("
    SELECT c.id, ct.name 
    FROM categories c 
    LEFT JOIN category_translations ct ON c.id = ct.category_id 
    WHERE ct.language_id = :lang 
    ORDER BY ct.name
", ['lang' => $lang->getCurrentId()]);

$brands = $db->fetchAll("SELECT id, name FROM brands WHERE is_active = 1 ORDER BY name");

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('products', 'Products'); ?></h1>
    <a href="add.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> <?php echo __('add_new_product', 'Add New Product'); ?>
    </a>
</div>

<!-- Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted"><?php echo __('total_products', 'Total Products'); ?></h6>
                <h3><?php echo $stats['total']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted"><?php echo __('active', 'Active'); ?></h6>
                <h3 class="text-success"><?php echo $stats['active']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted"><?php echo __('out_of_stock', 'Out of Stock'); ?></h6>
                <h3 class="text-danger"><?php echo $stats['out_of_stock']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted"><?php echo __('featured', 'Featured'); ?></h6>
                <h3 class="text-warning"><?php echo $stats['featured']; ?></h3>
            </div>
        </div>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible fade show">
    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <input type="text" class="form-control" name="search" 
                       placeholder="<?php echo __('search_products', 'Search products...'); ?>"
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2">
                <select name="category" class="form-select">
                    <option value=""><?php echo __('all_categories', 'All Categories'); ?></option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo $cat['name']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="brand" class="form-select">
                    <option value=""><?php echo __('all_brands', 'All Brands'); ?></option>
                    <?php foreach ($brands as $b): ?>
                    <option value="<?php echo $b['id']; ?>" <?php echo $brand == $b['id'] ? 'selected' : ''; ?>>
                        <?php echo $b['name']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="stock_status" class="form-select">
                    <option value=""><?php echo __('all_stock', 'All Stock'); ?></option>
                    <option value="in_stock" <?php echo $stock_status == 'in_stock' ? 'selected' : ''; ?>>
                        <?php echo __('in_stock', 'In Stock'); ?>
                    </option>
                    <option value="out_of_stock" <?php echo $stock_status == 'out_of_stock' ? 'selected' : ''; ?>>
                        <?php echo __('out_of_stock', 'Out of Stock'); ?>
                    </option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> <?php echo __('filter', 'Filter'); ?>
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> <?php echo __('reset', 'Reset'); ?>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Products Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th><?php echo __('image', 'Image'); ?></th>
                        <th><?php echo __('product', 'Product'); ?></th>
                        <th><?php echo __('sku', 'SKU'); ?></th>
                        <th><?php echo __('brand', 'Brand'); ?></th>
                        <th><?php echo __('price', 'Price'); ?></th>
                        <th><?php echo __('stock', 'Stock'); ?></th>
                        <th><?php echo __('status', 'Status'); ?></th>
                        <th><?php echo __('actions', 'Actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted"><?php echo __('no_products', 'No products found'); ?></p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <?php if ($product['main_image']): ?>
                                <img src="<?php echo UPLOADS_URL . 'products/' . $product['main_image']; ?>" 
                                     style="width: 60px; height: 60px; object-fit: cover;">
                                <?php else: ?>
                                <div class="bg-light" style="width: 60px; height: 60px;"></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($product['name'] ?? 'Untitled'); ?></strong>
                                <?php if ($product['is_featured']): ?>
                                <span class="badge bg-warning text-dark">
                                    <i class="fas fa-star"></i> <?php echo __('featured', 'Featured'); ?>
                                </span>
                                <?php endif; ?>
                            </td>
                            <td><code><?php echo $product['sku']; ?></code></td>
                            <td><?php echo $product['brand_name'] ?? '-'; ?></td>
                            <td>
                                <strong><?php echo $currency->format($product['price']); ?></strong>
                                <?php if ($product['compare_price']): ?>
                                <br><small class="text-muted"><del><?php echo $currency->format($product['compare_price']); ?></del></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $stock_class = $product['stock_status'] == 'in_stock' ? 'success' : 'danger';
                                ?>
                                <span class="badge bg-<?php echo $stock_class; ?>">
                                    <?php echo $product['stock_quantity']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($product['is_active']): ?>
                                <span class="badge bg-success"><?php echo __('active', 'Active'); ?></span>
                                <?php else: ?>
                                <span class="badge bg-danger"><?php echo __('inactive', 'Inactive'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="edit.php?id=<?php echo $product['id']; ?>" 
                                       class="btn btn-outline-primary" 
                                       title="<?php echo __('edit', 'Edit'); ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="index.php?delete=<?php echo $product['id']; ?>" 
                                       class="btn btn-outline-danger"
                                       title="<?php echo __('delete', 'Delete'); ?>"
                                       onclick="return confirm('<?php echo __('confirm_delete_product', 'Delete this product?'); ?>')">
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
    </div>
</div>

<?php include '../includes/footer.php'; ?>
