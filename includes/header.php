<?php
/**
 * Header Component
 * Contains navigation, meta tags, and common header elements
 */

// Helper function to build current URL with parameter
if (!function_exists('buildCurrentUrlWithParam')) {
    function buildCurrentUrlWithParam($param, $value) {
        $url = $_SERVER['REQUEST_URI'] ?? '';
        $url_parts = parse_url($url, PHP_URL_PATH);
        $query = [];
        if (isset($_SERVER['QUERY_STRING'])) {
            parse_str($_SERVER['QUERY_STRING'], $query);
        }
        $query[$param] = $value;
        $path = $url_parts['path'] ?? '';
        $query_string = http_build_query($query);
        return $path . ($query_string ? '?' . $query_string : '');
    }
}

// Get dynamic settings
$site_name = $settings->get('site_name', 'Your Store');
$site_logo = $settings->get('site_logo', '');
$primary_color = $settings->get('primary_color', '#007bff');
$secondary_color = $settings->get('secondary_color', '#6c757d');
$font_family = $settings->get('font_family', 'Arial, sans-serif');
$font_size = $settings->get('font_size', '16');
$contact_email = $settings->get('contact_email', 'contact@yourstore.com');
$contact_phone = $settings->get('contact_phone', '+1234567890');

// Additional dynamic colors for footer
$footer_bg_color = $settings->get('footer_bg_color', '#343a40');
$footer_text_color = $settings->get('footer_text_color', '#ffffff');
$footer_link_color = $settings->get('footer_link_color', '#adb5bd');
$danger_color = $settings->get('danger_color', '#dc3545');
$warning_color = $settings->get('warning_color', '#ffc107');
$info_color = $settings->get('info_color', '#17a2b8');
$success_color = $settings->get('success_color', '#28a745');
?>
<!DOCTYPE html>
<html lang="<?php echo $lang->getCurrentCode(); ?>" dir="<?php echo $lang->getCurrentDirection(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : ''; ?><?php echo htmlspecialchars($site_name); ?></title>
    <meta name="description" content="<?php echo $settings->get('site_description', 'Your one-stop shop for all your needs'); ?>">
    
    <!-- Favicon - avoid 404: use data URI when no favicon file exists -->
    <?php
    $site_favicon = $settings->get('site_favicon', '');
    $favicon_path = $site_favicon ? (UPLOADS_URL . 'settings/' . $site_favicon) : (ASSETS_URL . 'favicon.ico');
    $favicon_exists = $site_favicon ? file_exists(BASE_PATH . 'assets/uploads/settings/' . $site_favicon) : file_exists(BASE_PATH . 'assets/favicon.ico');
    $favicon_href = $favicon_exists ? $favicon_path : 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><rect fill="%23007bff" width="32" height="32" rx="4"/><text x="50%" y="50%" dy=".35em" fill="white" font-size="16" font-family="sans-serif" text-anchor="middle">C</text></svg>';
    ?>
    <link rel="icon" type="image/svg+xml" href="<?php echo htmlspecialchars($favicon_href); ?>">
    
    <!-- Bootstrap CSS -->
    <?php if ($lang->getCurrentDirection() === 'rtl'): ?>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <?php else: ?>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php endif; ?>
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Dynamic CSS -->
    <link href="<?php echo BASE_URL; ?>dynamic-styles.php?v=<?php echo time(); ?>" rel="stylesheet">
</head>
<body>
    <!-- Top Bar -->
    <div class="bg-light py-2 top-bar">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-7 col-md-6">
                    <small class="text-muted top-bar-contact d-none d-md-inline">
                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($contact_email); ?>
                        <span class="ms-3"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($contact_phone); ?></span>
                    </small>
                    <small class="text-muted d-md-none">
                        <a href="tel:<?php echo preg_replace('/\s+/', '', $contact_phone); ?>" class="text-muted"><i class="fas fa-phone"></i></a>
                        <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>" class="text-muted ms-2"><i class="fas fa-envelope"></i></a>
                    </small>
                </div>
                <div class="col-5 col-md-6 text-end">
                    <!-- Language Switcher -->
                    <div class="language-switcher d-inline-block">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-globe"></i> <?php echo strtoupper($lang->getCurrentCode()); ?>
                            </button>
                            <ul class="dropdown-menu">
                                <?php 
                                $languages = $lang->getAllLanguages();
                                foreach ($languages as $language): 
                                ?>
                                <li>
                                    <a class="dropdown-item" href="<?php echo buildCurrentUrlWithParam('lang', $language['code']); ?>">
                                        <?php echo $language['name']; ?> (<?php echo strtoupper($language['code']); ?>)
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Currency Switcher -->
                    <div class="currency-switcher d-inline-block">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-money-bill"></i> <?php echo $currency->getCurrentCode(); ?>
                            </button>
                            <ul class="dropdown-menu">
                                <?php 
                                $currencies = $currency->getAllCurrencies();
                                foreach ($currencies as $currency_data): 
                                ?>
                                <li>
                                    <a class="dropdown-item" href="<?php echo buildCurrentUrlWithParam('currency', $currency_data['code']); ?>">
                                        <?php echo $currency_data['name']; ?> (<?php echo $currency_data['code']; ?>)
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Navigation -->
    <nav class="navbar navbar-expand-lg bg-info shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>">
                <?php if ($site_logo): ?>
                    <img src="<?php echo UPLOADS_URL . 'settings/' . $site_logo; ?>" alt="<?php echo htmlspecialchars($site_name); ?>" height="80">
                <?php else: ?>
                    <?php echo htmlspecialchars($site_name); ?>
                <?php endif; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>" style="font-size: 1.25rem !important; padding: 0.5rem 0.75rem !important;"><?php echo __('home', 'Home'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>about.php" style="font-size: 1.25rem !important; padding: 0.5rem 0.75rem !important;"><?php echo __('about', 'About'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>services.php" style="font-size: 1.25rem !important; padding: 0.5rem 0.75rem !important;"><?php echo __('services', 'Services'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>shop.php" style="font-size: 1.25rem !important; padding: 0.5rem 0.75rem !important;"><?php echo __('shop', 'Shop'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>offers.php" style="font-size: 1.25rem !important; padding: 0.5rem 0.75rem !important;"><?php echo __('offers', 'Offers'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>blog.php" style="font-size: 1.25rem !important; padding: 0.5rem 0.75rem !important;"><?php echo __('blog', 'Blog'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>contact.php" style="font-size: 1.25rem !important; padding: 0.5rem 0.75rem !important;"><?php echo __('contact', 'Contact'); ?></a>
                    </li>

                </ul>
                
                <ul class="navbar-nav">
                    <!-- Search -->
                    <li class="nav-item">
                        <form class="d-flex me-2" action="<?php echo BASE_URL; ?>shop.php" method="GET">
                            <input class="form-control form-control-sm" type="search" name="search" placeholder="<?php echo __('search', 'Search'); ?>" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                            <button class="btn btn-sm btn-outline-primary ms-1" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </li>
                    
                    <!-- Wishlist -->
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="<?php echo BASE_URL; ?>wishlist.php" title="Wishlist">
                            <i class="fas fa-heart"></i>
                            <span id="navbar-wishlist-badge" class="wishlist-badge" style="<?php echo $wishlist_count > 0 ? '' : 'display:none!important'; ?>"><?php echo $wishlist_count; ?></span>
                        </a>
                    </li>
                    
                    <!-- Cart -->
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="<?php echo BASE_URL; ?>cart.php">
                            <i class="fas fa-shopping-cart"></i>
                            <span id="navbar-cart-badge" class="cart-badge" style="<?php echo $cart_count > 0 ? '' : 'display:none!important'; ?>"><?php echo $cart_count; ?></span>
                        </a>
                    </li>
                    
                    <!-- Service Booking Cart -->
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="<?php echo BASE_URL; ?>service-payment.php" title="Service Booking Cart">
                            <i class="fas fa-wallet"></i>
                            <span id="navbar-service-cart-badge" class="service-badge" style="<?php echo $service_cart_count > 0 ? '' : 'display:none!important'; ?>"><?php echo $service_cart_count; ?></span>
                        </a>
                    </li>
                    
                    <!-- Account -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo $user ? htmlspecialchars($user['first_name']) : __('', ''); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php if ($user): ?>
                                <?php if (isset($user['role']) && $user['role'] === 'admin'): ?>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>admin/"><i class="fas fa-user-shield me-2"></i><?php echo __('admin_panel', 'Admin Panel'); ?></a></li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>account/dashboard.php"><?php echo __('dashboard', 'Dashboard'); ?></a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>account/orders.php"><?php echo __('orders', 'Orders'); ?></a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>account/profile.php"><?php echo __('profile', 'Profile'); ?></a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>account/addresses.php"><?php echo __('addresses', 'Addresses'); ?></a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>account/logout.php"><?php echo __('logout', 'Logout'); ?></a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>account/login.php"><?php echo __('login', 'Login'); ?></a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>account/register.php"><?php echo __('register', 'Register'); ?></a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Success/Error Messages -->
    <?php 
    // Handle all types of session messages
    $sessionMessages = [];
    
    // Check flash_message
    if (isset($_SESSION['flash_message'])) {
        $sessionMessages[] = [
            'message' => $_SESSION['flash_message'],
            'type' => $_SESSION['flash_type'] ?? 'info',
            'key' => 'flash_message'
        ];
    }
    
    // Check success message
    if (isset($_SESSION['success'])) {
        $sessionMessages[] = [
            'message' => $_SESSION['success'],
            'type' => 'success',
            'key' => 'success'
        ];
    }
    
    // Check error message
    if (isset($_SESSION['error'])) {
        $sessionMessages[] = [
            'message' => $_SESSION['error'],
            'type' => 'danger',
            'key' => 'error'
        ];
    }
    
    // Display messages with filtering
    foreach ($sessionMessages as $msgData):
        // Service-related messages that should only show on service pages
        $serviceMessages = [
            'No booking or cart items found',
            'Service removed from cart successfully',
            'Service added to booking cart',
            'service_added_to_cart',
            'Failed to remove service from cart',
            'Error removing service'
        ];
        
        $currentFile = basename($_SERVER['PHP_SELF']);
        $isServicePage = in_array($currentFile, ['service-payment.php', 'services.php', 'servicescheckout.php', 'remove-from-cart.php', 'book-service.php']);
        $message = is_array($msgData['message']) ? implode(' ', $msgData['message']) : (string)($msgData['message'] ?? '');
        
        // Check if message contains service-related keywords
        $isServiceMessage = false;
        foreach ($serviceMessages as $serviceMsg) {
            if (stripos($message, $serviceMsg) !== false || stripos($serviceMsg, $message) !== false) {
                $isServiceMessage = true;
                break;
            }
        }
        
        // Also check for common service-related keywords
        $serviceKeywords = ['service', 'booking cart', 'removed from cart'];
        foreach ($serviceKeywords as $keyword) {
            if (stripos($message, $keyword) !== false) {
                $isServiceMessage = true;
                break;
            }
        }
        
        // Only show service messages on service pages, show all other messages everywhere
        $shouldShow = !$isServiceMessage || $isServicePage;
        
        if ($shouldShow):
    ?>
        <div class="alert alert-<?php echo $msgData['type']; ?> alert-dismissible fade show" role="alert">
            <?php 
            echo $message;
            // Clear the session message
            unset($_SESSION[$msgData['key']]);
            if ($msgData['key'] === 'flash_message') {
                unset($_SESSION['flash_type']);
            }
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php 
        endif;
    endforeach; 
    ?>

<?php
// Helper function to build current URL with parameter
if (!function_exists('buildCurrentUrlWithParam')) {
    function buildCurrentUrlWithParam($param, $value) {
        $url = $_SERVER['REQUEST_URI'];
        $url_parts = parse_url($url, PHP_URL_PATH);
        $query = [];
        if (isset($_SERVER['QUERY_STRING'])) {
            parse_str($_SERVER['QUERY_STRING'], $query);
        }
        $query[$param] = $value;
        return $url_parts['path'] . '?' . http_build_query($query);
    }
}
?>
