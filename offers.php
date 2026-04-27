<?php
require_once 'config.php';
$pageTitle = __('offers', 'Offers & Deals');

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

// Get search query
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get category filter
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Build query
$where_conditions = ["p.is_active = 1 AND p.compare_price > p.price AND p.stock_quantity > 0"];
$params = ['lang' => $lang->getCurrentId()];

if (!empty($search)) {
    $where_conditions[] = "(pt.name LIKE :search OR pt.description LIKE :search)";
    $params['search'] = '%' . $search . '%';
}

if ($category_filter > 0) {
    $where_conditions[] = "pc.category_id = :category_id";
    $params['category_id'] = $category_filter;
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get total count
$total_query = "
    SELECT COUNT(DISTINCT p.id) as total
    FROM products p
    LEFT JOIN product_categories pc ON p.id = pc.product_id
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang
    $where_clause
";
$total_result = $db->fetchOne($total_query, $params);
$total_offers = $total_result !== false ? $total_result['total'] : 0;
$total_pages = $total_offers > 0 ? ceil($total_offers / $per_page) : 1;

// Get offers
$offers = $db->fetchAll("
    SELECT p.*, pt.name, pt.description, pt.short_description
    FROM products p
    LEFT JOIN product_categories pc ON p.id = pc.product_id
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang
    $where_clause
    GROUP BY p.id
    ORDER BY p.created_at DESC
    LIMIT :limit OFFSET :offset
", array_merge($params, [
    'limit' => $per_page,
    'offset' => $offset
]));

// Get categories for filter
$categories = $db->fetchAll("
    SELECT c.*, ct.name
    FROM categories c
    LEFT JOIN category_translations ct ON c.id = ct.category_id AND ct.language_id = :lang
    WHERE c.is_active = 1
    ORDER BY c.sort_order
", ['lang' => $lang->getCurrentId()]);

// Get featured offers
$featured_offers = $db->fetchAll("
    SELECT p.*, pt.name, pt.short_description
    FROM products p
    LEFT JOIN product_categories pc ON p.id = pc.product_id
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang
    WHERE p.is_featured = 1 AND p.is_active = 1 AND p.compare_price > p.price AND p.stock_quantity > 0
    GROUP BY p.id
    ORDER BY p.created_at DESC
    LIMIT 6
", ['lang' => $lang->getCurrentId()]);

// Get services with offers (services that have compare_price > price)
$services_offers = $db->fetchAll("
    SELECT s.*, st.name, st.description
    FROM services s
    LEFT JOIN service_translations st ON s.id = st.service_id AND st.language_id = :lang
    WHERE s.is_active = 1 AND s.compare_price > s.price AND s.compare_price IS NOT NULL
    ORDER BY s.created_at DESC
", ['lang' => $lang->getCurrentId()]);

// Get featured services with offers
$featured_services_offers = $db->fetchAll("
    SELECT s.*, st.name, st.description
    FROM services s
    LEFT JOIN service_translations st ON s.id = st.service_id AND st.language_id = :lang
    WHERE s.is_active = 1 AND s.compare_price > s.price AND s.compare_price IS NOT NULL
    ORDER BY s.created_at DESC
    LIMIT 6
", ['lang' => $lang->getCurrentId()]);

include 'includes/header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="bg-light py-3">
    <div class="container">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>"><?php echo __('home', 'Home'); ?></a></li>
            <li class="breadcrumb-item active"><?php echo __('offers', 'Offers & Deals'); ?></li>
        </ol>
    </div>
</nav>

<!-- Offers Hero Section -->
<section class="py-5 text-white" style="background-color: #ff5490 !important; background-image: none !important;">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <h1 class="display-4 text-white fw-bold mb-4"><?php echo __('offers_deals', 'Offers & Deals'); ?></h1>
                <p class="lead"><?php echo __('offers_description', 'Discover amazing deals and special offers on our products. Save big on your favorite items!'); ?></p>
            </div>
            <div class="col-lg-4">
                <form method="GET" action="<?php echo BASE_URL; ?>offers.php" class="d-flex">
                    <input type="text" 
                           class="form-control me-2" 
                           name="search" 
                           placeholder="<?php echo __('search_offers', 'Search offers...'); ?>"
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-warning" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Featured Offers -->
<?php if (!empty($featured_offers)): ?>
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="mb-4"><?php echo __('featured_offers', 'Featured Offers'); ?></h2>
        <div class="row">
            <?php foreach ($featured_offers as $product): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <?php if (!empty($product['main_image'])): ?>
                    <a href="<?php echo BASE_URL; ?>product.php?id=<?php echo $product['id']; ?>">
                        <img src="<?php echo UPLOADS_URL . 'products/' . $product['main_image']; ?>" 
                             class="card-img-top" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             style="height: 200px; object-fit: cover;">
                    </a>
                    <?php endif; ?>
                    <div class="card-body">
                        <div class="mb-2">
                            <?php if ($product['compare_price'] > $product['price']): ?>
                                <span class="badge bg-danger text-white"><?php echo __('sale', 'SALE'); ?></span>
                            <?php endif; ?>
                        </div>
                        <h5 class="card-title">
                            <a href="<?php echo BASE_URL; ?>product.php?id=<?php echo $product['id']; ?>" class="text-decoration-none text-dark">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </a>
                        </h5>
                        <p class="card-text"><?php echo htmlspecialchars(substr($product['short_description'], 0, 80)); ?>...</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <?php if ($product['compare_price'] > $product['price']): ?>
                                    <span class="text-muted text-decoration-line-through"><?php echo $currency->format($product['compare_price']); ?></span>
                                    <span class="text-danger fw-bold ms-2"><?php echo $currency->format($product['price']); ?></span>
                                <?php else: ?>
                                    <span class="text-danger fw-bold"><?php echo $currency->format($product['price']); ?></span>
                                <?php endif; ?>
                            </div>
                            <a href="<?php echo BASE_URL; ?>product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm">
                                <?php echo __('view_offer', 'View Offer'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Category Filter -->
<?php if (!empty($categories)): ?>
<section class="py-3 bg-white">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="d-flex flex-wrap align-items-center">
                    <span class="me-3 fw-bold"><?php echo __('categories', 'Categories'); ?>:</span>
                    <a href="<?php echo BASE_URL; ?>offers.php" 
                       class="btn <?php echo $category_filter == 0 ? 'btn-primary' : 'btn-outline-primary'; ?> btn-sm me-2 mb-2">
                        <?php echo __('all_categories', 'All Categories'); ?>
                    </a>
                    <?php foreach ($categories as $category): ?>
                    <a href="<?php echo BASE_URL; ?>offers.php?category=<?php echo $category['id']; ?>" 
                       class="btn <?php echo $category_filter == $category['id'] ? 'btn-primary' : 'btn-outline-primary'; ?> btn-sm me-2 mb-2">
                        <?php echo htmlspecialchars($category['name']); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- All Offers -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <?php if (!empty($search)): ?>
                    <h3 class="mb-4"><?php echo __('search_results', 'Search Results'); ?>: "<?php echo htmlspecialchars($search); ?>"</h3>
                    <p class="text-muted mb-4"><?php echo $total_offers; ?> <?php echo __('offers_found', 'offers found'); ?></p>
                <?php else: ?>
                    <h2 class="mb-4"><?php echo __('products_offers', 'Products Offers'); ?></h2>
                <?php endif; ?>
                
                <?php if (!empty($offers)): ?>
                    <div class="row">
                        <?php foreach ($offers as $product): ?>
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                            <div class="card border-0 shadow-sm h-100">
                                <?php if (!empty($product['main_image'])): ?>
                                <a href="<?php echo BASE_URL; ?>product.php?id=<?php echo $product['id']; ?>">
                                    <img src="<?php echo UPLOADS_URL . 'products/' . $product['main_image']; ?>" 
                                         class="card-img-top" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                         style="height: 200px; object-fit: cover;">
                                </a>
                                <?php endif; ?>
                                <div class="card-body">
                                    <div class="mb-2">
                                        <?php if ($product['compare_price'] > $product['price']): ?>
                                            <span class="badge bg-danger text-white"><?php echo __('sale', 'SALE'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <h6 class="card-title">
                                        <a href="<?php echo BASE_URL; ?>product.php?id=<?php echo $product['id']; ?>" class="text-decoration-none text-dark">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </a>
                                    </h6>
                                    <p class="card-text small"><?php echo htmlspecialchars(substr($product['short_description'], 0, 60)); ?>...</p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <?php if ($product['compare_price'] > $product['price']): ?>
                                                <span class="text-muted text-decoration-line-through small"><?php echo $currency->format($product['compare_price']); ?></span>
                                                <span class="text-danger fw-bold"><?php echo $currency->format($product['price']); ?></span>
                                            <?php else: ?>
                                                <span class="text-danger fw-bold"><?php echo $currency->format($product['price']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <a href="<?php echo BASE_URL; ?>product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm">
                                            <?php echo __('view_offer', 'View Offer'); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <nav aria-label="Offers pagination" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $category_filter > 0 ? '&category=' . $category_filter : ''; ?>">
                                    <?php echo __('previous', 'Previous'); ?>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $category_filter > 0 ? '&category=' . $category_filter : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $category_filter > 0 ? '&category=' . $category_filter : ''; ?>">
                                    <?php echo __('next', 'Next'); ?>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <?php echo __('no_offers_found', 'No offers found. Please check back later for new deals!'); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Section Separator -->
<section class="py-4 bg-white border-top border-bottom">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center">
                <div class="d-flex align-items-center justify-content-center">
                    <div class="border-top flex-grow-1"></div>
                    <div class="px-4">
                        <i class="fas fa-concierge-bell fa-2x text-primary"></i>
                    </div>
                    <div class="border-top flex-grow-1"></div>
                </div>
                <h4 class="mt-3 mb-0 text-muted"><?php echo __('service_deals', 'Service Special Deals'); ?></h4>
                <p class="text-muted small"><?php echo __('service_deals_desc', 'Exclusive discounts on our premium services'); ?></p>
            </div>
        </div>
    </div>
</section>

<!-- Services Offers -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <h2 class="mb-4"><?php echo __('services_offers', 'Services Offers'); ?></h2>
                
                <?php if (!empty($services_offers)): ?>
                    <div class="row">
                        <?php foreach ($services_offers as $service): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card border-0 shadow-sm h-100">
                                <?php if (!empty($service['image'])): ?>
                                <img src="<?php echo UPLOADS_URL . 'services/' . $service['image']; ?>" 
                                     class="card-img-top" 
                                     alt="<?php echo htmlspecialchars($service['name']); ?>"
                                     style="height: 200px; object-fit: cover;">
                                <?php endif; ?>
                                <div class="card-body">
                                    <div class="mb-2">
                                        <span class="badge bg-success text-white"><?php echo __('service_offer', 'SERVICE OFFER'); ?></span>
                                        <?php if ($service['is_package']): ?>
                                            <span class="badge bg-info text-white ms-1"><?php echo __('package', 'PACKAGE'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <h6 class="card-title"><?php echo htmlspecialchars($service['name']); ?></h6>
                                    <p class="card-text small"><?php echo htmlspecialchars(substr($service['description'], 0, 80)); ?>...</p>
                                    <?php if ($service['duration']): ?>
                                        <p class="card-text small text-muted">
                                            <i class="fas fa-clock"></i> <?php echo $service['duration']; ?> <?php echo __('minutes', 'minutes'); ?>
                                        </p>
                                    <?php endif; ?>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <?php if ($service['compare_price'] > $service['price']): ?>
                                                <span class="text-muted text-decoration-line-through small"><?php echo $currency->format($service['compare_price']); ?></span>
                                                <span class="text-success fw-bold"><?php echo $currency->format($service['price']); ?></span>
                                            <?php else: ?>
                                                <span class="text-success fw-bold"><?php echo $currency->format($service['price']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <a href="<?php echo BASE_URL; ?>book-service.php?service=<?php echo $service['id']; ?>" class="btn btn-success btn-sm">
                                            <?php echo __('book_service', 'Book Service'); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <?php echo __('no_service_offers_found', 'No service offers found. Please check back later for new service deals!'); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
