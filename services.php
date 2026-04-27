<?php
require_once 'config.php';

// Helper function for translations
if (!function_exists('__')) {
    function __($key, $default = '')
    {
        global $lang;
        return $lang ? $lang->get($key, $default) : $default;
    }
}

// Handle currency switching
if (isset($_GET['currency']) && !empty($_GET['currency'])) {
    $currency->changeCurrency($_GET['currency']);

    // Remove currency parameter from URL but keep everything else
    $query_string = $_SERVER['QUERY_STRING'];
    $query_string = preg_replace('/[&?]currency=[^&]*/', '', $query_string);
    $query_string = preg_replace('/^currency=[^&]*&?/', '', $query_string);
    $query_string = rtrim($query_string, '&');

    $redirect_url = $_SERVER['PHP_SELF'] . ($query_string ? '?' . $query_string : '');
    header('Location: ' . $redirect_url);
    exit;
}

$pageTitle = __('services', 'Our Services');

// Get selected category from URL (using shop.php approach)
$category_id = isset($_GET['category']) ? (int) $_GET['category'] : 0;
$min_price = isset($_GET['min_price']) ? (float) $_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float) $_GET['max_price'] : 0;

// Get service categories
try {
    $current_lang_id = $lang->getCurrentId();

    $categories = $db->fetchAll("
        SELECT sc.*, sct.name as category_name, COUNT(s.id) as service_count
        FROM service_categories sc
        LEFT JOIN service_category_translations sct ON sc.id = sct.category_id AND sct.language_id = :lang_id
        LEFT JOIN services s ON sc.id = s.category_id AND s.is_active = 1
        WHERE sc.is_active = 1
        GROUP BY sc.id
        ORDER BY sc.sort_order, sct.name
    ", ['lang_id' => $current_lang_id]);

    // Ensure categories is an array
    if ($categories === false)
        $categories = [];

    // Build query conditions (shop.php approach)
    $where_conditions = ["s.is_active = 1"];
    $params = ['lang_id' => $current_lang_id, 'cat_lang_id' => $current_lang_id];

    if ($category_id > 0) {
        $where_conditions[] = "s.category_id = :category_id";
        $params['category_id'] = $category_id;
    }

    if ($min_price > 0) {
        $where_conditions[] = "s.price >= :min_price";
        $params['min_price'] = $min_price;
    }

    if ($max_price > 0) {
        $where_conditions[] = "s.price <= :max_price";
        $params['max_price'] = $max_price;
    }

    $where_clause = "WHERE " . implode(" AND ", $where_conditions);

    // Get price range for filters
    $price_range = $db->fetchOne("
        SELECT MIN(s.price) as min_price, MAX(s.price) as max_price
        FROM services s
        WHERE s.is_active = 1
    ");

    // Get services with category filtering
    $services = $db->fetchAll("
        SELECT s.*, 
               st.name as title, 
               st.description,
               st.long_description,
               sct.name as category_name,
               sc.slug as category_slug
        FROM services s
        LEFT JOIN service_translations st ON s.id = st.service_id AND st.language_id = :lang_id
        LEFT JOIN service_categories sc ON s.category_id = sc.id
        LEFT JOIN service_category_translations sct ON sc.id = sct.category_id AND sct.language_id = :cat_lang_id
        $where_clause
        ORDER BY sc.sort_order, s.created_at DESC
    ", $params);

    // Group services by category (for display purposes)
    $services_by_category = [];
    if (is_array($services)) {
        foreach ($services as $service) {
            $cat_id = $service['category_id'] ?: 0;
            $services_by_category[$cat_id][] = $service;
        }
    }

} catch (Exception $e) {
    $categories = [];
    $services_by_category = [];
}

include 'includes/header.php';
?>

<style>
    /* Services Page Styles - Matching Shop.php */
    .breadcrumb {
        background-color: #f8f9fa;
        padding: 0.75rem 0;
    }

    .service-card {
        transition: transform 0.15s ease-in-out;
    }

    .service-card:hover {
        transform: translateY(-2px);
    }

    .service-image {
        position: relative;
        overflow: hidden;
    }

    .service-image img {
        height: 200px;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .service-card:hover .service-image img {
        transform: scale(1.05);
    }

    .service-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background: var(--bs-primary);
        color: white;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        font-size: 0.75rem;
    }

    .service-price {
        font-size: 1.25rem;
        font-weight: bold;
        color: var(--bs-primary);
    }

    /* Service Hero Image Animation */
    .service-hero-image {
        opacity: 0;
        transform: translateX(50px);
        transition: all 0.8s ease-out;
    }

    .service-hero-image.animate {
        opacity: 1;
        transform: translateX(0);
    }

    .service-actions {
        display: flex;
        gap: 0.5rem;
    }

    @media (max-width: 768px) {
        .service-card {
            margin-bottom: 1rem;
        }
    }
</style>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="bg-light py-3">
    <div class="container">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>"><?php echo __('home', 'Home'); ?></a></li>
            <li class="breadcrumb-item active"><?php echo __('services', 'Services'); ?></li>
        </ol>
    </div>
</nav>

<!-- Services Hero Section -->
<section class="py-5 text-white" style="background-color: #604d5e !important; background-image: none !important;">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-4"><?php echo __('our_services', 'Our Services'); ?></h1>
                <p class="lead mb-4">
                    <?php echo __('services_description', 'Discover our comprehensive range of professional services designed to meet your every need.'); ?></p>
                    <a href="<?php echo BASE_URL; ?>book-service.php" class="btn btn-light btn-lg">
                        <i class="fas fa-calendar-plus me-2"></i>
                        <?php echo __('book_service', 'Book a Service'); ?>
                    </a>
            </div>
            <div class="col-lg-4">
                <div class="text-center">
                    <img src="<?php echo ASSETS_URL; ?>images/servicepage.png" class="service-hero-image mb-3"
                        alt="<?php echo __('our_services', 'Our Services'); ?>" style="max-height: 220px;">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Services Content -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="row">
            <!-- Category Sidebar -->
            <div class="col-lg-3 col-md-4">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?php echo __('categories', 'Categories'); ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="<?php echo BASE_URL; ?>services.php" id="filterForm">
                            <!-- Categories -->
                            <div class="mb-4">
                                <h6><?php echo __('categories', 'Categories'); ?></h6>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="category" id="cat-all" value="0"
                                        <?php echo $category_id == 0 ? 'checked' : ''; ?>
                                        onchange="document.getElementById('filterForm').submit();">
                                    <label class="form-check-label" for="cat-all">
                                        <?php echo __('all_services', 'All Services'); ?>
                                    </label>
                                </div>
                                <?php if (!empty($categories)):
                                    foreach ($categories as $category): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="category"
                                                id="cat-<?php echo $category['id']; ?>" value="<?php echo $category['id']; ?>"
                                                <?php echo $category_id == $category['id'] ? 'checked' : ''; ?>
                                                onchange="document.getElementById('filterForm').submit();">
                                            <label class="form-check-label" for="cat-<?php echo $category['id']; ?>">
                                                <?php echo htmlspecialchars($category['category_name'] ?: 'Category ' . $category['id']); ?> (<?php echo $category['service_count']; ?>)
                                        
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <!-- Price Range -->
                            <div class="mb-4">
                                <h6><?php echo __('price_range', 'Price Range'); ?></h6>
                                <div class="row">
                                    <div class="col-6">
                                        <input type="number" class="form-control" name="min_price"
                                            placeholder="<?php echo __('min', 'Min'); ?>"
                                            value="<?php echo $min_price; ?>" step="0.01">
                                    </div>
                                    <div class="col-6">
                                        <input type="number" class="form-control" name="max_price"
                                            placeholder="<?php echo __('max', 'Max'); ?>"
                                            value="<?php echo $max_price; ?>" step="0.01">
                                    </div>
                                </div>
                            </div>

                            <button type="submit"
                                class="btn btn-warning w-100"><?php echo __('apply_filters', 'Apply Filters'); ?></button>
                            <a href="<?php echo BASE_URL; ?>services.php"
                                class="btn btn-outline-secondary w-100 mt-2"><?php echo __('clear_filters', 'Clear Filters'); ?></a>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Services Content -->
            <div class="col-lg-9 col-md-8">
                <!-- Results Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <?php if ($category_id > 0): ?>
                            <h4><?php echo __('services_in_category', 'Services in Category'); ?></h4>
                            <p class="text-muted"><?php echo count($services); ?>     <?php echo __('services_found', 'services found'); ?></p>
                        <?php else: ?>
                                <h4><?php echo __('all_services', 'All Services'); ?></h4>
                                <p class="text-muted"><?php echo count($services); ?>     <?php echo __('services_available', 'services available'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Services Grid -->
                <?php if (!empty($services)): ?>
                    <div class="row">
                        <?php foreach ($services as $service): ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card h-100">
                                    <?php if ($service['image']): ?>
                                        <div class="position-relative">
                                            <img src="<?php echo UPLOADS_URL; ?>services/<?php echo htmlspecialchars($service['image']); ?>" 
                                                 class="card-img-top" 
                                                 alt="<?php echo htmlspecialchars($service['title']); ?>"
                                                 style="height: 200px; object-fit: cover;">
                                            <span class="badge bg-warning position-absolute top-0 end-0 m-2"><?php echo __('popular', 'Popular'); ?></span>
                                        </div>
                                    <?php else: ?>
                                        <div class="card-img-top d-flex align-items-center justify-content-center bg-light" 
                                             style="height: 200px;">
                                            <i class="fas fa-spa fa-3x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                            
                                    <div class="card-body">
                                        <?php if ($service['category_name']): ?>
                                            <small class="text-muted"><?php echo htmlspecialchars($service['category_name']); ?></small>
                                        <?php endif; ?>
                                
                                        <h5 class="card-title">
                                            <a href="<?php echo BASE_URL; ?>service-details.php?slug=<?php echo $service['slug']; ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($service['title']); ?>
                                            </a>
                                        </h5>
                                
                                        <p class="card-text text-muted small">
                                            <?php echo htmlspecialchars(substr($service['description'] ?: 'Professional service designed to meet your needs.', 0, 60)); ?>...
                                        </p>
                                
                                        <div class="mb-3">
                                            <span class="text-primary fw-bold"><?php echo $currency->format($service['price']); ?></span>
                                            <?php if ($service['duration']): ?>
                                                <small class="text-muted ms-2">
                                                    <i class="fas fa-clock"></i> <?php echo $service['duration']; ?>             <?php echo __('minutes', 'min'); ?>
                                        </small>
                                    <?php endif; ?>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <div class="btn-group" role="group">
                                    <a href="<?php echo BASE_URL; ?>service-details.php?slug=<?php echo $service['slug']; ?>"
                                        class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-eye"></i> <?php echo __('view', 'View'); ?>
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>book-service.php?service=<?php echo $service['id']; ?>"
                                        class="btn btn-primary btn-sm">
                                        <i class="fas fa-calendar-check"></i> <?php echo __('book_now', 'Book Now'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-search fa-3x text-muted mb-3"></i>
            <h4><?php echo __('no_services_found', 'No services found'); ?></h4>
            <p class="text-muted"><?php echo __('try_different_category', 'Try selecting a different category'); ?></p>
            <a href="<?php echo BASE_URL; ?>services.php"
                class="btn btn-primary"><?php echo __('view_all_services', 'View All Services'); ?></a>
        </div>
    <?php endif; ?>
    </div>
    </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-5 bg-white">
    <div class="container text-center">
        <h2 class="mb-4"><?php echo __('need_custom_service', 'Need a Custom Service?'); ?></h2>
        <p class="lead mb-4">
            <?php echo __('custom_service_description', 'Contact us to discuss your specific requirements and we\'ll create a tailored solution just for you.'); ?></p>
        <div class="d-flex justify-content-center gap-3">
            <a href="<?php echo BASE_URL; ?>book-service.php" class="btn btn-primary btn-lg">
                <i class="fas fa-calendar-plus me-2"></i>
                <?php echo __('book_consultation', 'Book Consultation'); ?>
            </a>
            <a href="<?php echo BASE_URL; ?>contact.php" class="btn btn-outline-primary btn-lg">
                <i class="fas fa-phone me-2"></i>
                <?php echo __('contact_us', 'Contact Us'); ?>
            </a>
        </div>
    </div>
</section>

<script>
    // Service Hero Image Animation on Scroll
    function animateServiceHeroImage() {
        const serviceHeroImage = document.querySelector('.service-hero-image');

        if (serviceHeroImage) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        serviceHeroImage.classList.add('animate');
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            });

            observer.observe(serviceHeroImage);
        }
    }

    // Initialize animation when DOM is loaded
    document.addEventListener('DOMContentLoaded', function () {
        animateServiceHeroImage();
    });
</script>

<?php include 'includes/footer.php'; ?>