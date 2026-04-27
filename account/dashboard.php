<?php
require_once '../config.php';
$pageTitle = __('dashboard', 'Dashboard');

// Redirect if not logged in
if (!$user) {
    $_SESSION['flash_message'] = __('login_required', 'Please login to access your dashboard');
    $_SESSION['flash_type'] = 'warning';
    header('Location: ' . BASE_URL . 'account/login.php?redirect=' . urlencode(BASE_URL . 'account/dashboard.php'));
    exit;
}

// Get user statistics
$recent_orders = $db->fetchAll("
    SELECT o.*, COUNT(oi.id) as item_count
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = :user_id
    AND o.total_amount > 0
    AND o.created_at IS NOT NULL
    AND o.created_at != '0000-00-00 00:00:00'
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT 5
", ['user_id' => $user['id']]);

// Ensure recent_orders is an array
if (!is_array($recent_orders)) {
    $recent_orders = [];
}

$wishlist_items = $db->fetchAll("
    SELECT w.*, p.main_image, pt.name, pt.price
    FROM wishlists w
    LEFT JOIN products p ON w.product_id = p.id
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang
    WHERE w.user_id = :user_id AND p.is_active = 1
    ORDER BY w.created_at DESC
    LIMIT 5
", [
    'user_id' => $user['id'],
    'lang' => $lang->getCurrentId()
]);

// Ensure wishlist_items is an array
if (!is_array($wishlist_items)) {
    $wishlist_items = [];
}

$addresses = $db->fetchAll("
    SELECT * FROM user_addresses 
    WHERE user_id = :user_id 
    ORDER BY is_default DESC, created_at DESC
    LIMIT 3
", ['user_id' => $user['id']]);

// Ensure addresses is an array
if (!is_array($addresses)) {
    $addresses = [];
}

// Get order statistics
$order_stats = $db->fetchOne("
    SELECT 
        COUNT(*) as total_orders,
        SUM(total_amount) as total_spent,
        AVG(total_amount) as avg_order_value
    FROM orders 
    WHERE user_id = :user_id AND order_status != 'cancelled'
", ['user_id' => $user['id']]);

// Get recent activity
$recent_activity = $db->fetchAll("
    SELECT 'order' as type, o.id, o.created_at, o.order_status as activity_type, 
           CONCAT('Order #', o.order_number) as description
    FROM orders o
    WHERE o.user_id = :user_id
    UNION ALL
    SELECT 'wishlist' as type, w.id, w.created_at, 'added' as activity_type,
           CONCAT('Added to wishlist: ', pt.name) as description
    FROM wishlists w
    LEFT JOIN products p ON w.product_id = p.id
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang
    WHERE w.user_id = :user_id
    ORDER BY created_at DESC
    LIMIT 10
", [
    'user_id' => $user['id'],
    'lang' => $lang->getCurrentId()
]);

// Ensure recent_activity is an array
if (!is_array($recent_activity)) {
    $recent_activity = [];
}

// Get booking statistics
$booking_stats = $db->fetchOne("
    SELECT 
        COUNT(*) as total_bookings,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_bookings,
        COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed_bookings,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_bookings
    FROM service_bookings 
    WHERE user_id = :user_id OR customer_email = :email
", [
    'user_id' => $user['id'],
    'email' => $user['email']
]);

// Get recent bookings
$recent_bookings = $db->fetchAll("
    SELECT 
        sb.id,
        sb.booking_number,
        sb.booking_date,
        sb.booking_time,
        sb.status,
        sb.total_amount,
        sb.created_at,
        COALESCE(st.name, 'Service #' || sb.service_id) as service_name,
        s.image as service_image
    FROM service_bookings sb
    LEFT JOIN services s ON sb.service_id = s.id
    LEFT JOIN service_translations st ON s.id = st.service_id AND st.language_id = :lang
    WHERE sb.user_id = :user_id OR sb.customer_email = :email
    ORDER BY sb.created_at DESC
    LIMIT 5
", [
    'user_id' => $user['id'],
    'email' => $user['email'],
    'lang' => $lang->getCurrentId()
]);

// Ensure recent_bookings is an array
if ($recent_bookings === false || !is_array($recent_bookings)) {
    $recent_bookings = [];
}

include '../includes/header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="bg-light py-3">
    <div class="container">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>"><?php echo __('home', 'Home'); ?></a></li>
            <li class="breadcrumb-item active"><?php echo __('dashboard', 'Dashboard'); ?></li>
        </ol>
    </div>
</nav>

<!-- Dashboard Section -->
<section class="py-5">
    <div class="container">
        <!-- Welcome Header -->
        <div class="row mb-4">
            <div class="col-lg-12">
                <div class="card bg-primary text-white">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h1 class="display-5 fw-bold mb-2">
                                    <?php echo __('welcome_back', 'Welcome back'); ?>, <?php echo htmlspecialchars($user['first_name']); ?>!
                                </h1>
                                <p class="lead mb-0"><?php echo __('dashboard_welcome', 'Here\'s what\'s happening with your account today.'); ?></p>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="user-avatar mb-2">
                                    <div class="bg-white text-primary rounded-circle d-inline-flex align-items-center justify-content-center" 
                                         style="width: 80px; height: 80px; font-size: 32px; font-weight: bold;">
                                        <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
                                    </div>
                                </div>
                                <p class="mb-0"><?php echo __('member_since', 'Member since'); ?> <?php echo date('M Y', strtotime($user['created_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="stat-icon mb-3">
                            <i class="fas fa-shopping-bag fa-3x text-primary"></i>
                        </div>
                        <h3><?php echo $order_stats['total_orders'] ?? 0; ?></h3>
                        <p class="text-muted"><?php echo __('total_orders', 'Total Orders'); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="stat-icon mb-3">
                            <i class="fas fa-calendar-check fa-3x text-info"></i>
                        </div>
                        <h3><?php echo $booking_stats['total_bookings'] ?? 0; ?></h3>
                        <p class="text-muted"><?php echo __('total_bookings', 'Total Bookings'); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="stat-icon mb-3">
                            <i class="fas fa-heart fa-3x text-danger"></i>
                        </div>
                        <h3><?php echo count($wishlist_items); ?></h3>
                        <p class="text-muted"><?php echo __('wishlist_items', 'Wishlist Items'); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="stat-icon mb-3">
                            <i class="fas fa-map-marker-alt fa-3x text-success"></i>
                        </div>
                        <h3><?php echo count($addresses); ?></h3>
                        <p class="text-muted"><?php echo __('saved_addresses', 'Saved Addresses'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="card shadow-sm mb-4">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-user-circle fa-5x text-primary"></i>
                        </div>
                        <h5 class="card-title"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                        <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title mb-3"><?php echo __('quick_links', 'Quick Links'); ?></h6>
                        <div class="list-group list-group-flush">
                            <a href="<?php echo BASE_URL; ?>account/dashboard.php" class="list-group-item list-group-item-action active">
                                <i class="fas fa-tachometer-alt me-2"></i> <?php echo __('dashboard', 'Dashboard'); ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>account/profile.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-user me-2"></i> <?php echo __('profile', 'Profile'); ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>account/mybookings.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-calendar-check me-2"></i> <?php echo __('my_bookings', 'My Bookings'); ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>account/orders.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-shopping-bag me-2"></i> <?php echo __('orders', 'Orders'); ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>account/wishlist.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-heart me-2"></i> <?php echo __('wishlist', 'Wishlist'); ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>account/addresses.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-map-marker-alt me-2"></i> <?php echo __('addresses', 'Addresses'); ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>account/logout.php" class="list-group-item list-group-item-action text-danger">
                                <i class="fas fa-sign-out-alt me-2"></i> <?php echo __('logout', 'Logout'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9">
                <!-- Recent Orders -->
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><?php echo __('recent_orders', 'Recent Orders'); ?></h5>
                            <a href="<?php echo BASE_URL; ?>account/orders.php" class="btn btn-outline-primary btn-sm">
                                <?php echo __('view_all', 'View All'); ?>
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recent_orders)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th><?php echo __('order_id', 'Order ID'); ?></th>
                                            <th><?php echo __('date', 'Date'); ?></th>
                                            <th><?php echo __('items', 'Items'); ?></th>
                                            <th><?php echo __('total', 'Total'); ?></th>
                                            <th><?php echo __('status', 'Status'); ?></th>
                                            <th><?php echo __('action', 'Action'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_orders as $order): ?>
                                        <tr>
                                            <td><strong>#<?php echo $order['id']; ?></strong></td>
                                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                            <td><?php echo $order['item_count']; ?></td>
                                            <td><?php echo $currency->format($order['total_amount']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo match($order['order_status'] ?? 'pending') {
                                                        'pending' => 'warning',
                                                        'processing' => 'info',
                                                        'shipped' => 'primary',
                                                        'delivered' => 'success',
                                                        'cancelled' => 'danger',
                                                        default => 'secondary'
                                                    };
                                                ?>">
                                                    <?php echo __(ucfirst($order['order_status'] ?? 'pending'), ucfirst($order['order_status'] ?? 'pending')); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>account/order-details.php?id=<?php echo $order['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <?php echo __('view', 'View'); ?>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                                <h5>No orders yet</h5>
                                <p class="text-muted">Start shopping to see your orders here</p>
                                <a href="<?php echo BASE_URL; ?>shop.php" class="btn btn-primary">
                                    Start Shopping
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Bookings -->
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><?php echo __('recent_bookings', 'Recent Bookings'); ?></h5>
                            <a href="<?php echo BASE_URL; ?>account/mybookings.php" class="btn btn-outline-info btn-sm">
                                <?php echo __('view_all', 'View All'); ?>
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recent_bookings)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th><?php echo __('booking_number', 'Booking #'); ?></th>
                                            <th><?php echo __('service', 'Service'); ?></th>
                                            <th><?php echo __('date', 'Date'); ?></th>
                                            <th><?php echo __('time', 'Time'); ?></th>
                                            <th><?php echo __('status', 'Status'); ?></th>
                                            <th><?php echo __('action', 'Action'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_bookings as $booking): ?>
                                        <tr>
                                            <td><strong>#<?php echo htmlspecialchars($booking['booking_number']); ?></strong></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if ($booking['service_image']): ?>
                                                        <img src="<?php echo BASE_URL; ?>assets/uploads/services/<?php echo htmlspecialchars($booking['service_image']); ?>" 
                                                             alt="<?php echo htmlspecialchars($booking['service_name']); ?>" 
                                                             class="me-2" style="width: 30px; height: 30px; object-fit: cover; border-radius: 4px;">
                                                    <?php endif; ?>
                                                    <span><?php echo htmlspecialchars($booking['service_name'] ?: 'Service #' . $booking['id']); ?></span>
                                                </div>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
                                            <td><?php echo date('g:i A', strtotime($booking['booking_time'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo match($booking['status'] ?? 'pending') {
                                                        'pending' => 'warning',
                                                        'confirmed' => 'info',
                                                        'completed' => 'success',
                                                        'cancelled' => 'danger',
                                                        default => 'secondary'
                                                    };
                                                ?>">
                                                    <?php echo __(ucfirst($booking['status'] ?? 'pending'), ucfirst($booking['status'] ?? 'pending')); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>account/mybookings.php?id=<?php echo $booking['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <?php echo __('view', 'View'); ?>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                                <h5><?php echo __('no_bookings', 'No bookings yet'); ?></h5>
                                <p class="text-muted"><?php echo __('start_booking', 'Book a service to see your appointments here'); ?></p>
                                <a href="<?php echo BASE_URL; ?>services.php" class="btn btn-info">
                                    <?php echo __('book_service', 'Book a Service'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><?php echo __('quick_actions', 'Quick Actions'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <a href="<?php echo BASE_URL; ?>account/profile.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-user me-2"></i><?php echo __('edit_profile', 'Edit Profile'); ?>
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <a href="<?php echo BASE_URL; ?>account/mybookings.php" class="btn btn-outline-info w-100">
                                    <i class="fas fa-calendar-check me-2"></i><?php echo __('my_bookings', 'My Bookings'); ?>
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <a href="<?php echo BASE_URL; ?>account/addresses.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-map-marker-alt me-2"></i><?php echo __('manage_addresses', 'Manage Addresses'); ?>
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <a href="<?php echo BASE_URL; ?>account/wishlist.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-heart me-2"></i><?php echo __('view_wishlist', 'View Wishlist'); ?>
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <a href="<?php echo BASE_URL; ?>account/orders.php" class="btn btn-outline-success w-100">
                                    <i class="fas fa-shopping-bag me-2"></i><?php echo __('my_orders', 'My Orders'); ?>
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <a href="<?php echo BASE_URL; ?>shop.php" class="btn btn-primary w-100">
                                    <i class="fas fa-shopping-cart me-2"></i><?php echo __('start_shopping', 'Start Shopping'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><?php echo __('recent_activity', 'Recent Activity'); ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recent_activity)): ?>
                            <div class="timeline">
                                <?php foreach ($recent_activity as $activity): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-marker">
                                            <i class="fas <?php echo getActivityIcon($activity['type']); ?>"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <h6><?php echo getActivityTitle($activity); ?></h6>
                                            <p class="text-muted small"><?php echo htmlspecialchars($activity['description']); ?></p>
                                            <small class="text-muted"><?php echo dashboard_timeAgo($activity['created_at']); ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                <h5><?php echo __('no_activity', 'No recent activity'); ?></h5>
                                <p class="text-muted"><?php echo __('activity_message', 'Your recent account activity will appear here'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
            </div>
        </div>
    </div>
</section>

<style>
.timeline {
    position: relative;
    padding-left: 40px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background-color: #dee2e6;
}

.timeline-item {
    position: relative;
    margin-bottom: 30px;
}

.timeline-marker {
    position: absolute;
    left: -30px;
    top: 0;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background-color: #fff;
    border: 2px solid #dee2e6;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    color: #6c757d;
}

.timeline-content h6 {
    margin-bottom: 5px;
    color: #495057;
}

.stat-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background-color: #f8f9fa;
}
</style>

<?php
// Helper functions for activity display
function getActivityIcon($type) {
    $icons = [
        'order' => 'fa-shopping-bag',
        'wishlist' => 'fa-heart',
        'booking' => 'fa-calendar-check'
    ];
    return $icons[$type] ?? 'fa-circle';
}

function getActivityTitle($activity) {
    $titles = [
        'order' => __('New Order'),
        'wishlist' => __('Added to Wishlist'),
        'booking' => __('New Booking')
    ];
    return $titles[$activity['type']] ?? __('Activity');
}

function dashboard_timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) return __('just_now', 'Just now');
    if ($diff < 3600) return floor($diff / 60) . ' ' . __('minutes_ago', 'minutes ago');
    if ($diff < 86400) return floor($diff / 3600) . ' ' . __('hours_ago', 'hours ago');
    if ($diff < 604800) return floor($diff / 86400) . ' ' . __('days_ago', 'days ago');
    
    return date('M d, Y', $time);
}
?>

<?php include '../includes/footer.php'; ?>
