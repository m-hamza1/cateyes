<?php
// Helper to detect active link
function sidebarActive($path) {
    return strpos($_SERVER['PHP_SELF'], $path) !== false ? 'active' : '';
}
?>
<!-- Sidebar -->
<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
    <div class="position-sticky sidebar-inner">
        <div class="sidebar-mobile-user">
            <div class="sidebar-mobile-usercard">
                <small><?php echo __('admin_panel', 'Admin Panel'); ?></small>
                <strong><?php echo htmlspecialchars(trim(($admin['first_name'] ?? '') . ' ' . ($admin['last_name'] ?? '')) ?: 'Admin User'); ?></strong>
            </div>

            <ul class="nav flex-column mb-2">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo ADMIN_URL; ?>settings/general.php">
                        <i class="fas fa-cog"></i> <?php echo __('settings', 'Settings'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>" target="_blank">
                        <i class="fas fa-external-link-alt"></i> <?php echo __('view_site', 'View Site'); ?>
                    </a>
                </li>
            </ul>

            <div class="sidebar-section-label"><?php echo __('language', 'Language'); ?></div>
            <ul class="nav flex-column mb-2">
                <?php foreach ($lang->getAllLanguages() as $language): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $lang->getCurrentId() == $language['id'] ? 'active' : ''; ?>"
                       href="?change_lang=<?php echo $language['code']; ?>">
                        <i class="fas fa-language"></i> <?php echo $language['name']; ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>

            <ul class="nav flex-column mb-2">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo ADMIN_URL; ?>logout.php">
                        <i class="fas fa-sign-out-alt"></i> <?php echo __('logout', 'Logout'); ?>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Dashboard (only if role has dashboard permission) -->
        <?php if (hasPermission('dashboard')): ?>
        <ul class="nav flex-column mt-2">
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/admin/index.php') !== false || $_SERVER['PHP_SELF'] === '/Bug-Fix/Bug-Fix/admin/') ? 'active' : ''; ?>"
                   href="<?php echo ADMIN_URL; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <?php echo __('dashboard', 'Dashboard'); ?>
                </a>
            </li>
        </ul>
        <?php endif; ?>

        <!-- CATALOG -->
        <?php if (hasPermission('catalog') || hasPermission('products') || hasPermission('categories') || hasPermission('brands')): ?>
        <div class="sidebar-section-label"><?php echo __('catalog', 'Catalog'); ?></div>
        <ul class="nav flex-column mb-1">
            <?php if (hasPermission('catalog') || hasPermission('products')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo sidebarActive('/products/') && strpos($_SERVER['PHP_SELF'], 'attributes') === false ? 'active' : sidebarActive('/admin/products/'); ?>"
                   href="<?php echo ADMIN_URL; ?>products/">
                    <i class="fas fa-box"></i> <?php echo __('products', 'Products'); ?>
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasPermission('catalog') || hasPermission('categories')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo sidebarActive('/categories'); ?>"
                   href="<?php echo ADMIN_URL; ?>categories/">
                    <i class="fas fa-folder-open"></i> <?php echo __('categories', 'Categories'); ?>
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasPermission('catalog') || hasPermission('brands')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo sidebarActive('/brands'); ?>"
                   href="<?php echo ADMIN_URL; ?>brands/">
                    <i class="fas fa-tags"></i> <?php echo __('brands', 'Brands'); ?>
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasPermission('catalog') || hasPermission('products')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo sidebarActive('attributes'); ?>"
                   href="<?php echo ADMIN_URL; ?>products/attributes.php">
                    <i class="fas fa-list-ul"></i> <?php echo __('attributes', 'Attributes'); ?>
                </a>
            </li>
            <?php endif; ?>
        </ul>
        <?php endif; ?>

        <!-- SERVICES -->
        <?php if (hasPermission('catalog') || hasPermission('services') || hasPermission('bookings')): ?>
        <div class="sidebar-section-label"><?php echo __('services', 'Services'); ?></div>
        <ul class="nav flex-column mb-1">
            <?php if (hasPermission('catalog') || hasPermission('services')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo sidebarActive('/services/'); ?>"
                   href="<?php echo ADMIN_URL; ?>services/">
                    <i class="fas fa-concierge-bell"></i> <?php echo __('services', 'Services'); ?>
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasPermission('catalog') || hasPermission('services')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo sidebarActive('/service-categories'); ?>"
                   href="<?php echo ADMIN_URL; ?>service-categories/">
                    <i class="fas fa-folder"></i> <?php echo __('service_categories', 'Service Categories'); ?>
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasPermission('catalog') || hasPermission('bookings')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo sidebarActive('/bookings'); ?>"
                   href="<?php echo ADMIN_URL; ?>bookings/">
                    <i class="fas fa-calendar-check"></i> <?php echo __('bookings', 'Bookings'); ?>
                    <?php 
                    $br = $db->fetchOne("SELECT COUNT(*) as count FROM service_bookings WHERE status IN ('pending', 'confirmed')");
                    $booking_count = isset($br['count']) ? (int)$br['count'] : 0;
                    if ($booking_count > 0): ?>
                        <span class="badge bg-danger rounded-pill ms-auto"><?php echo $booking_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <?php endif; ?>
        </ul>
        <?php endif; ?>

        <!-- SALES -->
        <?php if (hasPermission('orders') || hasPermission('coupons') || hasPermission('shipping')): ?>
        <div class="sidebar-section-label"><?php echo __('sales', 'Sales'); ?></div>
        <ul class="nav flex-column mb-1">
            <?php if (hasPermission('orders')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo sidebarActive('/orders'); ?>"
                   href="<?php echo ADMIN_URL; ?>orders/">
                    <i class="fas fa-shopping-cart"></i> <?php echo __('orders', 'Orders'); ?>
                    <?php
                    // Ensure admin_viewed_at column exists (for unviewed count)
                    // Note: Database wrapper swallows PDO exceptions, so we verify via SHOW COLUMNS.
                    $col = $db->fetchOne("SHOW COLUMNS FROM orders LIKE 'admin_viewed_at'");
                    if (!$col) {
                        $db->query("ALTER TABLE orders ADD COLUMN admin_viewed_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at");
                        $col = $db->fetchOne("SHOW COLUMNS FROM orders LIKE 'admin_viewed_at'");
                    }

                    $unviewed_count = 0;
                    if ($col) {
                        $unviewed = $db->fetchOne("SELECT COUNT(*) as c FROM orders WHERE admin_viewed_at IS NULL");
                        $unviewed_count = (int)($unviewed['c'] ?? 0);
                    }
                    if ($unviewed_count > 0): ?>
                        <span class="badge bg-danger rounded-pill ms-auto"><?php echo $unviewed_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <?php endif; ?>

            <?php if (hasPermission('coupons')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo sidebarActive('/coupons'); ?>"
                   href="<?php echo ADMIN_URL; ?>coupons/">
                    <i class="fas fa-ticket-alt"></i> <?php echo __('coupons', 'Coupons'); ?>
                </a>
            </li>
            <?php endif; ?>

            <?php if (hasPermission('shipping')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo sidebarActive('/shipping/'); ?>"
                   href="<?php echo ADMIN_URL; ?>shipping/">
                    <i class="fas fa-shipping-fast"></i> <?php echo __('shipping_methods', 'Shipping Methods'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo sidebarActive('/shipping.php'); ?>"
                   href="<?php echo ADMIN_URL; ?>shipping.php">
                    <i class="fas fa-truck"></i> <?php echo __('shipping_settings', 'Shipping Settings'); ?>
                </a>
            </li>
            <?php endif; ?>
        </ul>
        <?php endif; ?>

        <!-- CUSTOMERS -->
        <?php if (hasPermission('customers')): ?>
        <div class="sidebar-section-label"><?php echo __('customers', 'Customers'); ?></div>
        <ul class="nav flex-column mb-1">
            <li class="nav-item">
                <a class="nav-link <?php echo sidebarActive('/customers'); ?>"
                   href="<?php echo ADMIN_URL; ?>customers/">
                    <i class="fas fa-users"></i> <?php echo __('customers', 'Customers'); ?>
                    <?php 
                    $ncr = $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE role = 'customer' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
                    $new_customers_count = isset($ncr['count']) ? (int)$ncr['count'] : 0;
                    if ($new_customers_count > 0): ?>
                        <span class="badge bg-danger rounded-pill ms-auto"><?php echo $new_customers_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo sidebarActive('/reviews'); ?>"
                   href="<?php echo ADMIN_URL; ?>reviews/">
                    <i class="fas fa-star"></i> <?php echo __('reviews', 'Reviews'); ?>
                </a>
            </li>
        </ul>
        <?php endif; ?>

        <!-- CONTENT -->
        <?php if (hasPermission('catalog') || hasPermission('blogs') || hasPermission('banners')): ?>
        <div class="sidebar-section-label"><?php echo __('content', 'Content'); ?></div>
        <ul class="nav flex-column mb-1">
            <?php if (hasPermission('catalog') || hasPermission('blogs')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo sidebarActive('/blog'); ?>"
                   href="<?php echo ADMIN_URL; ?>blog/">
                    <i class="fas fa-blog"></i> <?php echo __('blog', 'Blog'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo sidebarActive('/news'); ?>"
                   href="<?php echo ADMIN_URL; ?>news/">
                    <i class="fas fa-newspaper"></i> <?php echo __('news', 'News'); ?>
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasPermission('catalog') || hasPermission('banners')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo sidebarActive('/banners'); ?>"
                   href="<?php echo ADMIN_URL; ?>banners/">
                    <i class="fas fa-images"></i> <?php echo __('banners', 'Banners'); ?>
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasPermission('catalog') || hasPermission('blogs')): ?>

            <li class="nav-item">
                <a class="nav-link <?php echo sidebarActive('/certificates'); ?>"
                   href="<?php echo ADMIN_URL; ?>certificates/">
                    <i class="fas fa-award"></i> <?php echo __('certificates', 'Certificates'); ?>
                </a>
            </li>
            <?php endif; ?>
        </ul>
        <?php endif; ?>

        <!-- STOCK MANAGEMENT -->
        <?php if (hasPermission('inventory')): ?>
        <div class="sidebar-section-label"><?php echo __('stock_management', 'Stock Management'); ?></div>
        <ul class="nav flex-column mb-1">
            <li class="nav-item">
                <a class="nav-link <?php echo sidebarActive('/inventory/index'); ?>"
                   href="<?php echo ADMIN_URL; ?>inventory/">
                    <i class="fas fa-warehouse"></i> <?php echo __('inventory_overview', 'Inventory Overview'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo sidebarActive('/inventory/warehouses'); ?>"
                   href="<?php echo ADMIN_URL; ?>inventory/warehouses/">
                    <i class="fas fa-building"></i> <?php echo __('warehouses', 'Warehouses'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo sidebarActive('/inventory/purchase-orders'); ?>"
                   href="<?php echo ADMIN_URL; ?>inventory/purchase-orders/">
                    <i class="fas fa-file-invoice"></i> <?php echo __('purchase_orders', 'Purchase Orders'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo sidebarActive('/inventory/production-orders'); ?>"
                   href="<?php echo ADMIN_URL; ?>inventory/production-orders/">
                    <i class="fas fa-industry"></i> <?php echo __('production_orders', 'Production Orders'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo sidebarActive('/inventory/stock-transfers'); ?>"
                   href="<?php echo ADMIN_URL; ?>inventory/stock-transfers/">
                    <i class="fas fa-exchange-alt"></i> <?php echo __('stock_transfers', 'Stock Transfers'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo sidebarActive('/inventory/supplier-returns'); ?>"
                   href="<?php echo ADMIN_URL; ?>inventory/supplier-returns/">
                    <i class="fas fa-undo"></i> <?php echo __('supplier_returns', 'Supplier Returns'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo sidebarActive('/inventory/sales-returns'); ?>"
                   href="<?php echo ADMIN_URL; ?>inventory/sales-returns/">
                    <i class="fas fa-arrow-rotate-left"></i> <?php echo __('sales_returns', 'Sales Returns'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo sidebarActive('/inventory/stock-adjustments'); ?>"
                   href="<?php echo ADMIN_URL; ?>inventory/stock-adjustments/">
                    <i class="fas fa-sliders-h"></i> <?php echo __('stock_adjustments', 'Stock Adjustments'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo sidebarActive('/inventory/suppliers'); ?>"
                   href="<?php echo ADMIN_URL; ?>inventory/suppliers/">
                    <i class="fas fa-truck"></i> <?php echo __('suppliers', 'Suppliers'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo sidebarActive('/inventory/movements'); ?>"
                   href="<?php echo ADMIN_URL; ?>inventory/movements.php">
                    <i class="fas fa-history"></i> <?php echo __('stock_movements', 'Stock Movements'); ?>
                </a>
            </li>
        </ul>
        <?php endif; ?>

        <!-- ANALYTICS -->
        <?php if (hasPermission('reports')): ?>
        <div class="sidebar-section-label"><?php echo __('analytics', 'Analytics'); ?></div>
        <ul class="nav flex-column mb-1">
            <li class="nav-item">
                <a class="nav-link <?php echo sidebarActive('sales-report'); ?>"
                   href="<?php echo ADMIN_URL; ?>sales-report.php">
                    <i class="fas fa-chart-line"></i> <?php echo __('sales_report', 'Sales Report'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo sidebarActive('/inventory/inventory-report'); ?>"
                   href="<?php echo ADMIN_URL; ?>inventory/inventory-report.php">
                    <i class="fas fa-chart-bar"></i> <?php echo __('inventory_report', 'Inventory Report'); ?>
                </a>
            </li>
        </ul>
        <?php endif; ?>

        <!-- MARKETING -->
        <?php if (hasPermission('newsletter')): ?>
        <div class="sidebar-section-label"><?php echo __('marketing', 'Marketing'); ?></div>
        <ul class="nav flex-column mb-1">
            <li class="nav-item">
                <a class="nav-link <?php echo sidebarActive('newsletter'); ?>"
                   href="<?php echo ADMIN_URL; ?>newsletter.php">
                    <i class="fas fa-paper-plane"></i> <?php echo __('newsletter', 'Newsletter'); ?>
                </a>
            </li>
        </ul>
        <?php endif; ?>

        <!-- SETTINGS -->
        <?php if (hasPermission('settings') || hasPermission('appearance') || hasPermission('payment') || hasPermission('languages') || hasPermission('currencies') || hasPermission('email_settings') || hasPermission('social_settings') || hasPermission('users') || hasPermission('roles')): ?>
        <div class="sidebar-section-label"><?php echo __('settings', 'Settings'); ?></div>
        <ul class="nav flex-column mb-1">
            <?php if (hasPermission('settings')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo sidebarActive('/settings/general'); ?>"
                   href="<?php echo ADMIN_URL; ?>settings/general.php">
                    <i class="fas fa-cog"></i> <?php echo __('general', 'General'); ?>
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasPermission('settings') || hasPermission('social_settings')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo sidebarActive('/settings/social'); ?>"
                   href="<?php echo ADMIN_URL; ?>settings/social.php">
                    <i class="fas fa-share-alt"></i> <?php echo __('social_media', 'Social Media'); ?>
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasPermission('settings') || hasPermission('appearance')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo sidebarActive('appearance'); ?>"
                   href="<?php echo ADMIN_URL; ?>settings/appearance.php">
                    <i class="fas fa-paint-brush"></i> <?php echo __('appearance', 'Appearance'); ?>
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasPermission('settings') || hasPermission('languages')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo sidebarActive('languages'); ?>"
                   href="<?php echo ADMIN_URL; ?>settings/languages.php">
                    <i class="fas fa-language"></i> <?php echo __('languages', 'Languages'); ?>
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasPermission('settings') || hasPermission('currencies')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo sidebarActive('currencies'); ?>"
                   href="<?php echo ADMIN_URL; ?>settings/currencies.php">
                    <i class="fas fa-dollar-sign"></i> <?php echo __('currencies', 'Currencies'); ?>
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasPermission('settings') || hasPermission('email_settings')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo sidebarActive('/settings/email'); ?>"
                   href="<?php echo ADMIN_URL; ?>settings/email.php">
                    <i class="fas fa-envelope-open-text"></i> <?php echo __('email_settings', 'Email'); ?>
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasPermission('settings') || hasPermission('payment')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo sidebarActive('/settings/payment'); ?>"
                   href="<?php echo ADMIN_URL; ?>settings/payment.php">
                    <i class="fas fa-credit-card"></i> <?php echo __('payment_settings', 'Payment'); ?>
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasPermission('settings') || hasPermission('users')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo sidebarActive('users.php'); ?>"
                   href="<?php echo ADMIN_URL; ?>users.php">
                    <i class="fas fa-user-shield"></i> <?php echo __('admin_users', 'Admin Users'); ?>
                </a>
            </li>
            <?php endif; ?>
            <?php if (hasPermission('settings') || hasPermission('roles')): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo sidebarActive('/roles/'); ?>"
                   href="<?php echo ADMIN_URL; ?>roles/">
                    <i class="fas fa-shield-alt"></i> <?php echo __('roles_permissions', 'Roles & Permissions'); ?>
                </a>
            </li>
            <?php endif; ?>
        </ul>
        <?php endif; ?>

        <div class="pb-4"></div>
    </div>
</nav>
<div class="admin-sidebar-backdrop d-md-none" id="adminSidebarBackdrop"></div>

<!-- Main Content Area -->
<main class="main-content">
    <div class="content-wrapper">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['info'])): ?>
            <div class="alert alert-info alert-dismissible fade show shadow-sm" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                <?php echo $_SESSION['info']; unset($_SESSION['info']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
