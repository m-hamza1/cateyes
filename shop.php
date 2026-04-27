<?php
require_once 'config.php';
$pageTitle = __('shop', 'Shop');

// Handle language switching
if (isset($_GET['lang']) && !empty($_GET['lang'])) {
    $lang->changeLanguage($_GET['lang']);
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// Handle currency switching
if (isset($_GET['currency']) && !empty($_GET['currency'])) {
    $currency->changeCurrency($_GET['currency']);
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Get filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$brand_id = isset($_GET['brand']) ? (int)$_GET['brand'] : 0;
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Get categories
$categories = $db->fetchAll("
    SELECT c.*, ct.name, COUNT(pc.product_id) as product_count
    FROM categories c
    LEFT JOIN category_translations ct ON c.id = ct.category_id AND ct.language_id = :lang
    LEFT JOIN product_categories pc ON c.id = pc.category_id
    LEFT JOIN products p ON pc.product_id = p.id AND p.is_active = 1
    WHERE c.is_active = 1
    GROUP BY c.id
    ORDER BY c.sort_order
", ['lang' => $lang->getCurrentId()]);

// Ensure categories is an array
if ($categories === false) $categories = [];

// Get brands
$brands = $db->fetchAll("
    SELECT b.*, COUNT(p.id) as product_count
    FROM brands b
    LEFT JOIN products p ON b.id = p.brand_id AND p.is_active = 1
    WHERE b.is_active = 1
    GROUP BY b.id
    ORDER BY b.name
");

// Ensure brands is an array
if ($brands === false) $brands = [];

// Build query conditions
$where_conditions = ["p.is_active = 1"];
$params = ['lang' => $lang->getCurrentId()];

if (!empty($search)) {
    $where_conditions[] = "(pt.name LIKE :search OR pt.description LIKE :search OR p.sku LIKE :search)";
    $params['search'] = '%' . $search . '%';
}

if ($category_id > 0) {
    $where_conditions[] = "EXISTS (SELECT 1 FROM product_categories pc WHERE pc.product_id = p.id AND pc.category_id = :category_id)";
    $params['category_id'] = $category_id;
}

if ($brand_id > 0) {
    $where_conditions[] = "p.brand_id = :brand_id";
    $params['brand_id'] = $brand_id;
}

if ($min_price > 0) {
    $where_conditions[] = "p.price >= :min_price";
    $params['min_price'] = $min_price;
}

if ($max_price > 0) {
    $where_conditions[] = "p.price <= :max_price";
    $params['max_price'] = $max_price;
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get price range for filters
$price_range = $db->fetchOne("
    SELECT MIN(p.price) as min_price, MAX(p.price) as max_price
    FROM products p
    WHERE p.is_active = 1
");

// Sort options
$sort_options = [
    'newest' => 'p.created_at DESC',
    'oldest' => 'p.created_at ASC',
    'price_low' => 'p.price ASC',
    'price_high' => 'p.price DESC',
    'name_asc' => 'pt.name ASC',
    'name_desc' => 'pt.name DESC',
    'popular' => 'p.view_count DESC'
];

$order_by = $sort_options[$sort] ?? 'p.created_at DESC';

// Get total count
$total_query = "
    SELECT COUNT(*) as total
    FROM products p
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang
    $where_clause
";
$total_result = $db->fetchOne($total_query, $params);
$total_products = $total_result['total'];
$total_pages = ceil($total_products / $per_page);

// Get products
$products = $db->fetchAll("
    SELECT p.*, pt.name, pt.short_description, pt.description, b.name as brand_name
    FROM products p
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang
    LEFT JOIN brands b ON p.brand_id = b.id
    $where_clause
    ORDER BY $order_by
    LIMIT :limit OFFSET :offset
", array_merge($params, [
    'limit' => $per_page,
    'offset' => $offset
]));

// Get featured products for sidebar
$featured_products = $db->fetchAll("
    SELECT p.*, pt.name, pt.short_description, b.name as brand_name
    FROM products p
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang
    LEFT JOIN brands b ON p.brand_id = b.id
    WHERE p.is_featured = 1 AND p.is_active = 1
    ORDER BY p.created_at DESC
    LIMIT 5
", ['lang' => $lang->getCurrentId()]);

include 'includes/header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="bg-light py-3">
    <div class="container">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>"><?php echo __('home', 'Home'); ?></a></li>
            <li class="breadcrumb-item active"><?php echo __('shop', 'Shop'); ?></li>
        </ol>
    </div>
</nav>

<!-- Shop Hero Section -->
<section class="py-5 shop-hero-section text-white" style="background-color: #604d5e !important; background-image: none !important;">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-4"><?php echo __('shop', 'Shop'); ?></h1>
                <p class="lead"><?php echo __('shop_description', 'Order your favorite products online, and we shall deliver them to your doorstep!

'); ?></p>
            </div>
            <div class="col-lg-4">
                <form method="GET" action="<?php echo BASE_URL; ?>shop.php" class="d-flex">
                    <input type="text" 
                           class="form-control me-2" 
                           name="search" 
                           placeholder="<?php echo __('search_products', 'Search products...'); ?>"
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-warning" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Shop Content -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <!-- Sidebar Filters -->
            <div class="col-lg-3">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?php echo __('filters', 'Filters'); ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="<?php echo BASE_URL; ?>shop.php" id="filterForm">
                            <!-- Hidden fields to preserve other filters -->
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                            
                            <!-- Categories -->
                            <div class="mb-4">
                                <h6><?php echo __('categories', 'Categories'); ?></h6>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="category" id="cat-all" value="0" <?php echo $category_id == 0 ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="cat-all">
                                        <?php echo __('all_categories', 'All Categories'); ?>
                                    </label>
                                </div>
                                <?php if (!empty($categories)): foreach ($categories as $category): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="category" id="cat-<?php echo $category['id']; ?>" value="<?php echo $category['id']; ?>" <?php echo $category_id == $category['id'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="cat-<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?> (<?php echo $category['product_count']; ?>)
                                    </label>
                                </div>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Brands -->
                            <?php if (!empty($brands)): ?>
                            <div class="mb-4">
                                <h6><?php echo __('brands', 'Brands'); ?></h6>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="brand" id="brand-all" value="0" <?php echo $brand_id == 0 ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="brand-all">
                                        <?php echo __('all_brands', 'All Brands'); ?>
                                    </label>
                                </div>
                                <?php foreach ($brands as $brand): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="brand" id="brand-<?php echo $brand['id']; ?>" value="<?php echo $brand['id']; ?>" <?php echo $brand_id == $brand['id'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="brand-<?php echo $brand['id']; ?>">
                                        <?php echo htmlspecialchars($brand['name']); ?> (<?php echo $brand['product_count']; ?>)
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Price Range -->
                            <div class="mb-4">
                                <h6><?php echo __('price_range', 'Price Range'); ?></h6>
                                <div class="row">
                                    <div class="col-6">
                                        <input type="number" class="form-control" name="min_price" placeholder="<?php echo __('min', 'Min'); ?>" value="<?php echo $min_price; ?>" step="0.01">
                                    </div>
                                    <div class="col-6">
                                        <input type="number" class="form-control" name="max_price" placeholder="<?php echo __('max', 'Max'); ?>" value="<?php echo $max_price; ?>" step="0.01">
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-warning w-100"><?php echo __('apply_filters', 'Apply Filters'); ?></button>
                            <a href="<?php echo BASE_URL; ?>shop.php" class="btn btn-outline-secondary w-100 mt-2"><?php echo __('clear_filters', 'Clear Filters'); ?></a>
                        </form>
                    </div>
                </div>
                
                <!-- Featured Products -->
                <?php if (!empty($featured_products)): ?>
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?php echo __('featured_products', 'Featured Products'); ?></h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($featured_products as $product): ?>
                        <div class="d-flex mb-3">
                            <img src="<?php echo UPLOADS_URL . 'products/' . $product['main_image']; ?>" 
                                 class="me-3" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 style="width: 60px; height: 60px; object-fit: cover; border-radius: 5px;">
                            <div>
                                <h6 class="mb-1">
                                    <a href="<?php echo BASE_URL; ?>product.php?id=<?php echo $product['id']; ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars(substr($product['name'], 0, 30)); ?>...
                                    </a>
                                </h6>
                                <p class="text-primary fw-bold mb-0"><?php echo $currency->format($product['price']); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9">
                <!-- Results Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <?php if (!empty($search)): ?>
                            <h4><?php echo __('search_results', 'Search Results'); ?>: "<?php echo htmlspecialchars($search); ?>"</h4>
                            <p class="text-muted"><?php echo $total_products; ?> <?php echo __('products_found', 'products found'); ?></p>
                        <?php else: ?>
                            <h4><?php echo __('all_products', 'All Products'); ?></h4>
                            <p class="text-muted"><?php echo $total_products; ?> <?php echo __('products_available', 'products available'); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Sort Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <?php echo __('sort_by', 'Sort by'); ?>: <?php echo __('sort_' . $sort, $sort); ?>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'newest'])); ?>"><?php echo __('sort_newest', 'Newest'); ?></a></li>
                            <li><a class="dropdown-item" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'oldest'])); ?>"><?php echo __('sort_oldest', 'Oldest'); ?></a></li>
                            <li><a class="dropdown-item" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_low'])); ?>"><?php echo __('sort_price_low', 'Price: Low to High'); ?></a></li>
                            <li><a class="dropdown-item" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_high'])); ?>"><?php echo __('sort_price_high', 'Price: High to Low'); ?></a></li>
                            <li><a class="dropdown-item" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'name_asc'])); ?>"><?php echo __('sort_name_asc', 'Name: A to Z'); ?></a></li>
                            <li><a class="dropdown-item" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'name_desc'])); ?>"><?php echo __('sort_name_desc', 'Name: Z to A'); ?></a></li>
                            <li><a class="dropdown-item" href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'popular'])); ?>"><?php echo __('sort_popular', 'Most Popular'); ?></a></li>
                        </ul>
                    </div>
                </div>
                
                <!-- Products Grid -->
                <?php if (!empty($products)): ?>
                <div class="row">
                    <?php foreach ($products as $product): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card h-100 product-card">
                            <?php if ($product['is_featured']): ?>
                            <span class="badge bg-warning position-absolute top-0 end-0 m-2"><?php echo __('featured', 'Featured'); ?></span>
                            <?php endif; ?>
                            
                            <a href="<?php echo BASE_URL; ?>product.php?id=<?php echo $product['id']; ?>">
                                <img src="<?php echo UPLOADS_URL . 'products/' . (!empty($product['main_image']) ? $product['main_image'] : 'placeholder.jpg'); ?>" 
                                     class="card-img-top product-image" 
                                     alt="<?php echo htmlspecialchars($product['name'] ?? $product['slug'] ?? 'Product'); ?>"
                                     style="height: 250px; object-fit: cover;"
                                     onerror="this.src='<?php echo ASSETS_URL; ?>images/placeholder.jpg'; this.onerror=null;">
                            </a>
                            
                            <div class="card-body">
                                <div class="mb-2">
                                    <?php if ($product['brand_name']): ?>
                                    <small class="text-muted"><?php echo htmlspecialchars($product['brand_name']); ?></small>
                                    <?php endif; ?>
                                </div>
                                
                                <h5 class="card-title">
                                    <a href="<?php echo BASE_URL; ?>product.php?id=<?php echo $product['id']; ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </a>
                                </h5>
                                
                                <p class="card-text text-muted small">
                                    <?php echo htmlspecialchars(substr($product['short_description'] ?? '', 0, 60)); ?>...
                                </p>
                                
                                <div class="price-section mb-3">
                                    <?php if ($product['compare_price'] > 0): ?>
                                        <span class="text-decoration-line-through text-muted"><?php echo $currency->format($product['compare_price']); ?></span>
                                        <span class="text-primary fw-bold ms-2"><?php echo $currency->format($product['price']); ?></span>
                                    <?php else: ?>
                                        <span class="text-primary fw-bold"><?php echo $currency->format($product['price']); ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="btn-group" role="group">
                                        <a href="<?php echo BASE_URL; ?>product.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye"></i> <?php echo __('view', 'View'); ?>
                                        </a>
                                        <?php if ($product['stock_quantity'] > 0): ?>
                                            <button class="btn btn-primary btn-sm" onclick="addToCart(<?php echo $product['id']; ?>)">
                                                <i class="fas fa-shopping-cart"></i> <?php echo __('add_to_cart', 'Add to Cart'); ?>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-secondary btn-sm" disabled>
                                                <i class="fas fa-shopping-cart"></i> <?php echo __('out_of_stock', 'Out of Stock'); ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <button class="btn btn-outline-danger btn-sm" onclick="addToWishlist(<?php echo $product['id']; ?>)">
                                        <i class="fas fa-heart"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Products pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                <?php echo __('previous', 'Previous'); ?>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                <?php echo __('next', 'Next'); ?>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
                
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h4><?php echo __('no_products_found', 'No products found'); ?></h4>
                    <p class="text-muted"><?php echo __('try_different_filters', 'Try adjusting your filters or search terms'); ?></p>
                    <a href="<?php echo BASE_URL; ?>shop.php" class="btn btn-primary"><?php echo __('view_all_products', 'View All Products'); ?></a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<style>
    section.shop-hero-section {
        background-color: #604d5e !important;
        background-image: none !important;
        color: #ffffff !important;
    }

    section.shop-hero-section h1, 
    section.shop-hero-section p,
    section.shop-hero-section i {
        color: #ffffff !important;
    }
</style>

<script>
// Add to cart function
function addToCart(productId) {
    fetch('<?php echo BASE_URL; ?>product-ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=add_to_cart&product_id=${productId}&quantity=1`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart count in header
            const cartBadge = document.querySelector('.cart-badge');
            if (cartBadge) {
                cartBadge.textContent = data.cart_count;
                cartBadge.style.display = data.cart_count > 0 ? 'inline' : 'none';
            }
            
            // Show success message
            showNotification('<?php echo __('added_to_cart', 'Product added to cart!'); ?>', 'success');
            
            // Show side cart popup (if function exists)
            if (typeof showSideCartPopup === 'function') {
                showSideCartPopup();
            }
        } else {
            showNotification(data.message || '<?php echo __('error_adding_cart', 'Error adding product to cart'); ?>', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('<?php echo __('error_occurred', 'An error occurred'); ?>', 'error');
    });
}

// Add to wishlist function
function addToWishlist(productId) {
    <?php if (isset($_SESSION['user_id'])): ?>
    fetch('<?php echo BASE_URL; ?>product-ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=add_to_wishlist&product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('<?php echo __('added_to_wishlist', 'Product added to wishlist!'); ?>', 'success');
        } else {
            showNotification(data.message || '<?php echo __('error_adding_wishlist', 'Error adding to wishlist'); ?>', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('<?php echo __('error_occurred', 'An error occurred'); ?>', 'error');
    });
    <?php else: ?>
    window.location.href = '<?php echo BASE_URL; ?>account/login.php?redirect=' + encodeURIComponent(window.location.href);
    <?php endif; ?>
}

// Notification function
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : 'success'} position-fixed top-0 start-50 translate-middle-x mt-3`;
    notification.style.zIndex = '9999';
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Auto-submit filter form on change
document.querySelectorAll('#filterForm input[type="radio"]').forEach(input => {
    input.addEventListener('change', function() {
        document.getElementById('filterForm').submit();
    });
});

// Price range validation
document.querySelectorAll('input[name="min_price"], input[name="max_price"]').forEach(input => {
    input.addEventListener('change', function() {
        const minPrice = parseFloat(document.querySelector('input[name="min_price"]').value) || 0;
        const maxPrice = parseFloat(document.querySelector('input[name="max_price"]').value) || 0;
        
        if (minPrice > maxPrice && maxPrice > 0) {
            this.setCustomValidity('<?php echo __('min_price_error', 'Minimum price cannot be greater than maximum price'); ?>');
        } else {
            this.setCustomValidity('');
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
