<?php
// Get dynamic settings
$site_name = $settings->get('site_name', 'Your Store');
$site_logo = $settings->get('site_logo', '');
$primary_color = $settings->get('primary_color', '#667eea');
$secondary_color = $settings->get('secondary_color', '#764ba2');
$font_family = $settings->get('font_family', 'Inter, sans-serif');
$font_size = $settings->get('font_size', '16');
$contact_email = $settings->get('contact_email', 'contact@yourstore.com');
$contact_phone = $settings->get('contact_phone', '+1234567890');

// Additional dynamic colors
$footer_bg_color = $settings->get('footer_bg_color', '#1f2937');
$footer_text_color = $settings->get('footer_text_color', '#ffffff');
$footer_link_color = $settings->get('footer_link_color', '#9ca3af');
$danger_color = $settings->get('danger_color', '#ef4444');
$warning_color = $settings->get('warning_color', '#f59e0b');
$info_color = $settings->get('info_color', '#3b82f6');

// Get cart count
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += (isset($item['quantity']) ? (int)$item['quantity'] : 0);
    }
}

// Get wishlist count
$wishlist_count = isset($_SESSION['wishlist']) ? count($_SESSION['wishlist']) : 0;
?>
<!DOCTYPE html>
<html lang="<?php echo $lang->getCurrentCode(); ?>" dir="<?php echo $lang->getCurrentDirection(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : ''; ?><?php echo htmlspecialchars($site_name); ?></title>
    <meta name="description" content="<?php echo $settings->get('site_description', 'Your one-stop shop for all your needs'); ?>">
    <meta name="keywords" content="<?php echo $settings->get('site_keywords', 'ecommerce, shopping, online store'); ?>">
    <meta name="author" content="<?php echo $site_name; ?>">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : $site_name; ?>">
    <meta property="og:description" content="<?php echo $settings->get('site_description', 'Your one-stop shop for all your needs'); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo BASE_URL; ?>">
    <meta property="og:image" content="<?php echo BASE_URL . UPLOADS_URL . 'logo.png'; ?>">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : $site_name; ?>">
    <meta name="twitter:description" content="<?php echo $settings->get('site_description', 'Your one-stop shop for all your needs'); ?>">
    <meta name="twitter:image" content="<?php echo BASE_URL . UPLOADS_URL . 'logo.png'; ?>">
    
    <!-- Favicon - use settings favicon when available, fallback to data URI to avoid 404 -->
    <?php 
    $site_favicon = $settings->get('site_favicon', '');
    $favicon_path = $site_favicon ? (UPLOADS_URL . 'settings/' . $site_favicon) : (ASSETS_URL . 'favicon.ico');
    $favicon_exists = $site_favicon ? file_exists(BASE_PATH . 'assets/uploads/settings/' . $site_favicon) : file_exists(BASE_PATH . 'assets/favicon.ico');
    $favicon_href = $favicon_exists ? $favicon_path : 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><rect fill="%23667eea" width="32" height="32" rx="4"/><text x="50%" y="50%" dy=".35em" fill="white" font-size="18" font-family="sans-serif" text-anchor="middle">S</text></svg>';
    ?>
    <link rel="icon" type="image/svg+xml" href="<?php echo htmlspecialchars($favicon_href); ?>">
    
    <!-- Preconnect to external domains -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Modern Design CSS -->
    <link href="<?php echo ASSETS_URL; ?>css/modern-design.css" rel="stylesheet">
    
</head>
<body>
    <!-- Loading Screen -->
    <div class="loading-screen" id="loadingScreen">
        <div class="loading-spinner"></div>
    </div>

    <!-- Top Bar -->
    <div class="top-bar bg-dark text-white py-2">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-4">
                        <span class="small">
                            <i class="fas fa-phone me-2"></i>
                            <?php echo htmlspecialchars($contact_phone); ?>
                        </span>
                        <span class="small">
                            <i class="fas fa-envelope me-2"></i>
                            <?php echo htmlspecialchars($contact_email); ?>
                        </span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-center justify-content-end gap-4">
                        <!-- Language Switcher -->
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-globe me-2"></i>
                                <?php echo strtoupper($lang->getCurrentCode()); ?>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="?lang=en">
                                        <i class="fas fa-flag-usa me-2"></i> English
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="?lang=ar">
                                        <i class="fas fa-flag me-2"></i> العربية
                                    </a>
                                </li>
                            </ul>
                        </div>
                        
                        <!-- Currency Switcher -->
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-coins me-2"></i>
                                <?php echo $currency->getCode(); ?>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="<?php echo buildCurrentUrlWithParam('currency', 'AED'); ?>">
                                        <i class="fas fa-coins me-2"></i> AED
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo buildCurrentUrlWithParam('currency', 'USD'); ?>">
                                        <i class="fas fa-dollar-sign me-2"></i> USD
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo buildCurrentUrlWithParam('currency', 'EUR'); ?>">
                                        <i class="fas fa-euro-sign me-2"></i> EUR
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo buildCurrentUrlWithParam('currency', 'GBP'); ?>">
                                        <i class="fas fa-pound-sign me-2"></i> GBP
                                    </a>
                                </li>
                            </ul>
                        </div>
                        
                        <!-- User Account -->
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-user me-2"></i>
                                    <?php echo htmlspecialchars($user['first_name'] ?? 'Account'); ?>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>account/dashboard.php">
                                            <i class="fas fa-user-circle me-2"></i> My Account
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>account/orders.php">
                                            <i class="fas fa-shopping-bag me-2"></i> My Orders
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>account/wishlist.php">
                                            <i class="fas fa-heart me-2"></i> Wishlist
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>account/logout.php">
                                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <a href="<?php echo BASE_URL; ?>account/login.php" class="btn btn-sm btn-outline-light">
                                <i class="fas fa-sign-in-alt me-2"></i> Login
                            </a>
                            <a href="<?php echo BASE_URL; ?>account/register.php" class="btn btn-sm btn-primary">
                                <i class="fas fa-user-plus me-2"></i> Register
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm">
        <div class="container">
            <!-- Brand -->
            <a class="navbar-brand d-flex align-items-center" href="<?php echo BASE_URL; ?>">
                <?php if ($site_logo): ?>
                    <img src="<?php echo UPLOADS_URL . $site_logo; ?>" alt="<?php echo htmlspecialchars($site_name); ?>" height="40" class="me-2">
                <?php else: ?>
                    <i class="fas fa-store fa-2x me-2" style="color: var(--primary-color);"></i>
                <?php endif; ?>
                <span class="fw-bold"><?php echo htmlspecialchars($site_name); ?></span>
            </a>

            <!-- Mobile menu button -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navigation Menu -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>">
                            <i class="fas fa-home me-1"></i> <?php echo __('home', 'Home'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>shop.php">
                            <i class="fas fa-shopping-bag me-1"></i> <?php echo __('shop', 'Shop'); ?>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-th-large me-1"></i> <?php echo __('categories', 'Categories'); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <?php
                            $categories = $db->fetchAll("
                                SELECT c.*, ct.name 
                                FROM categories c 
                                LEFT JOIN category_translations ct ON c.id = ct.category_id AND ct.language_id = :lang_id
                                WHERE c.is_active = 1 
                                ORDER BY c.sort_order
                            ", ['lang_id' => $lang->getCurrentId()]);
                            
                            foreach ($categories as $category):
                            ?>
                            <li>
                                <a class="dropdown-item" href="<?php echo BASE_URL; ?>shop.php?category=<?php echo $category['id']; ?>">
                                    <i class="fas fa-chevron-right me-2"></i> <?php echo htmlspecialchars($category['name']); ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>services.php">
                            <i class="fas fa-concierge-bell me-1"></i> <?php echo __('services', 'Services'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>about.php">
                            <i class="fas fa-info-circle me-1"></i> <?php echo __('about', 'About'); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>contact.php">
                            <i class="fas fa-envelope me-1"></i> <?php echo __('contact', 'Contact'); ?>
                        </a>
                    </li>
                </ul>

                <!-- Right Side Items -->
                <div class="d-flex align-items-center gap-3">
                    <!-- Search -->
                    <div class="search-container position-relative">
                        <button class="btn btn-outline-secondary search-toggle" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    
                    <!-- Wishlist -->
                    <a href="<?php echo BASE_URL; ?>wishlist.php" class="btn btn-outline-secondary position-relative" title="Wishlist">
                        <i class="fas fa-heart"></i>
                        <span id="navbar-wishlist-badge" class="wishlist-badge position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="<?php echo $wishlist_count > 0 ? '' : 'display:none!important'; ?>">
                            <?php echo $wishlist_count; ?>
                        </span>
                    </a>
                    
                    <!-- Cart -->
                    <a href="<?php echo BASE_URL; ?>cart.php" class="btn btn-warning position-relative" title="Shopping Cart">
                        <i class="fas fa-shopping-cart"></i>
                        <span id="navbar-cart-badge" class="cart-badge position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="<?php echo $cart_count > 0 ? '' : 'display:none!important'; ?>">
                            <?php echo $cart_count; ?>
                        </span>
                    </a>
                    
                    <!-- Theme Toggle -->
                    <button class="btn btn-outline-secondary theme-toggle" type="button">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Search Overlay -->
    <div class="search-overlay position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-90 d-none align-items-center justify-content-center" style="z-index: 9999;">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="search-form">
                        <div class="input-group input-group-lg">
                            <input type="text" class="form-control search-input" placeholder="<?php echo __('search_products', 'Search for products...'); ?>">
                            <button class="btn btn-primary" type="button">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        <div class="search-results mt-4"></div>
                    </div>
                    <button class="btn btn-light mt-3 search-close">
                        <i class="fas fa-times me-2"></i> <?php echo __('close', 'Close'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Breadcrumb (if defined) -->
    <?php if (isset($breadcrumb) && !empty($breadcrumb)): ?>
    <nav aria-label="breadcrumb" class="bg-light py-3">
        <div class="container">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="<?php echo BASE_URL; ?>">
                        <i class="fas fa-home me-1"></i> <?php echo __('home', 'Home'); ?>
                    </a>
                </li>
                <?php foreach ($breadcrumb as $key => $value): ?>
                    <?php if ($key === array_key_last($breadcrumb)): ?>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo $value; ?></li>
                    <?php else: ?>
                        <li class="breadcrumb-item">
                            <a href="<?php echo $key; ?>"><?php echo $value; ?></a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ol>
        </div>
    </nav>
    <?php endif; ?>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3" style="z-index: 9999;" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo htmlspecialchars($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 end-0 m-3" style="z-index: 9999;" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?php echo htmlspecialchars($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['warning'])): ?>
    <div class="alert alert-warning alert-dismissible fade show position-fixed top-0 end-0 m-3" style="z-index: 9999;" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo htmlspecialchars($_SESSION['warning']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['warning']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['info'])): ?>
    <div class="alert alert-info alert-dismissible fade show position-fixed top-0 end-0 m-3" style="z-index: 9999;" role="alert">
        <i class="fas fa-info-circle me-2"></i>
        <?php echo htmlspecialchars($_SESSION['info']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['info']); ?>
    <?php endif; ?>

    <!-- Main Content -->
    <main>
