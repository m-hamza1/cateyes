<?php
/**
 * Admin Dashboard
 */

require_once 'includes/auth.php';
requirePermission('dashboard');

// ── Cleanup: Auto-delete Unpaid Online Orders older than 1 hour ──
try {
    $cleanupSql = "payment_status = 'pending' AND payment_method NOT IN ('cod', 'cash_on_delivery', 'cash') AND created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)";
    $unpaidOrders = $db->fetchAll("SELECT id FROM orders WHERE $cleanupSql");
    foreach ($unpaidOrders as $uo) {
        $db->query("DELETE FROM order_items WHERE order_id = :id", ['id' => $uo['id']]);
        $db->query("DELETE FROM payments WHERE order_id = :id", ['id' => $uo['id']]);
        $db->query("DELETE FROM orders WHERE id = :id", ['id' => $uo['id']]);
    }
} catch (Exception $e) {
    error_log("Order cleanup error: " . $e->getMessage());
}

$pageTitle = 'Dashboard';

// ── Ensure order_items.unit_cost column exists (self-healing migration) ──
try {
    $colCheck = $db->fetchOne("SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'order_items' AND COLUMN_NAME = 'unit_cost'");
    if (($colCheck['cnt'] ?? 0) == 0) {
        $db->query("ALTER TABLE order_items ADD COLUMN unit_cost DECIMAL(10,2) DEFAULT 0.00 AFTER product_price");
        $db->query("UPDATE order_items oi JOIN products p ON oi.product_id = p.id SET oi.unit_cost = COALESCE(p.cost_price, 0) WHERE oi.unit_cost = 0 OR oi.unit_cost IS NULL");
    }
} catch (Exception $e) {
    error_log("Dashboard migration: " . $e->getMessage());
}

// ── Get accurate statistics (only paid + non-cancelled orders, or COD orders) ──
$orderRevenue = $db->fetchOne("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE (payment_status = 'paid' OR payment_method IN ('cod', 'cash_on_delivery', 'cash')) AND order_status != 'cancelled'")['total'] ?? 0;
$bookingRevenue = $db->fetchOne("SELECT COALESCE(SUM(total_amount), 0) as total FROM service_bookings WHERE status IN ('confirmed','completed') AND payment_status = 'paid'")['total'] ?? 0;

// ── Inventory Cost: SUM(quantity x unit_cost) from all inventory_transactions ──
// quantity can be negative (outgoing) or positive (incoming)
$inventoryCost = $db->fetchOne(
    "SELECT COALESCE(SUM(quantity * unit_cost), 0) as total FROM inventory_transactions"
)['total'] ?? 0;

$stats = [
    'total_orders' => $db->fetchOne("SELECT COUNT(*) as c FROM orders WHERE (payment_status = 'paid' OR payment_method IN ('cod', 'cash_on_delivery', 'cash')) AND order_status != 'cancelled'")['c'] ?? 0,
    'total_orders_all' => $db->count('orders', "(payment_status = 'paid' OR payment_method IN ('cod', 'cash_on_delivery', 'cash'))"),
    'pending_orders' => $db->count('orders', "order_status = 'pending' AND (payment_status = 'paid' OR payment_method IN ('cod', 'cash_on_delivery', 'cash'))"),
    'total_products' => $db->count('products', 'is_active = 1'),
    'total_customers' => $db->count('users', 'role = :role', ['role' => 'customer']),
    'total_revenue' => $orderRevenue + $bookingRevenue,
    'order_revenue' => $orderRevenue,
    'booking_revenue' => $bookingRevenue,
    'active_coupons' => $db->count('coupons', 'is_active = 1 AND (end_date IS NULL OR end_date >= NOW())'),
    'wishlist_items' => $db->count('wishlists'),
    'cart_items' => $db->count('cart'),
    'low_stock_products' => $db->count('products', 'stock_quantity <= low_stock_threshold AND track_inventory = 1 AND is_active = 1'),
    'pending_bookings' => $db->count('service_bookings', 'status = :status', ['status' => 'pending']),
    'total_bookings' => $db->count('service_bookings'),
    'inventory_cost' => $inventoryCost,
    'today_orders' => $db->fetchOne("SELECT COUNT(*) as c FROM orders WHERE DATE(created_at) = CURDATE()")['c'] ?? 0,
    'today_revenue' => $db->fetchOne("SELECT COALESCE(SUM(total_amount), 0) as t FROM orders WHERE DATE(created_at) = CURDATE() AND payment_status = 'paid' AND order_status != 'cancelled'")['t'] ?? 0,
];

// Get recent orders
$recentOrders = $db->fetchAll(
    "SELECT o.*, u.first_name, u.last_name, u.email as user_email
     FROM orders o
     LEFT JOIN users u ON o.user_id = u.id
     WHERE (o.payment_status = 'paid' OR o.payment_method IN ('cod', 'cash_on_delivery', 'cash'))
     ORDER BY o.created_at DESC
     LIMIT 10"
);

// Get recent bookings
$recentBookings = $db->fetchAll(
    "SELECT sb.*, u.first_name, u.last_name, u.email as user_email, 
            COALESCE(st.name, CONCAT('Service #', sb.service_id)) as service_name, 
            s.image as service_image
     FROM service_bookings sb
     LEFT JOIN users u ON sb.user_id = u.id
     LEFT JOIN services s ON sb.service_id = s.id
     LEFT JOIN service_translations st ON s.id = st.service_id AND st.language_id = :lang
     ORDER BY sb.created_at DESC
     LIMIT 10",
    ['lang' => $lang->getCurrentId()]
);


// Get low stock products
$lowStockProducts = $db->fetchAll(
    "SELECT p.*, pt.name 
     FROM products p
     LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang_id
     WHERE p.stock_quantity <= p.low_stock_threshold 
     AND p.track_inventory = 1 
     AND p.is_active = 1
     ORDER BY p.stock_quantity ASC
     LIMIT 10",
    ['lang_id' => $lang->getCurrentId()]
);

// Get bookings status data
$bookingsStatusData = $db->fetchAll(
    "SELECT status, COUNT(*) as count
     FROM service_bookings
     GROUP BY status"
);

// Get leaderboard data (top 5 clients by total spending)
$leaderboardData = $db->fetchAll(
    "SELECT 
        u.id,
        u.first_name,
        u.last_name,
        u.email,
        COALESCE(order_totals.order_amount, 0) as order_amount,
        COALESCE(booking_totals.booking_amount, 0) as booking_amount,
        (COALESCE(order_totals.order_amount, 0) + COALESCE(booking_totals.booking_amount, 0)) as total_amount
    FROM users u
    LEFT JOIN (
        SELECT user_id, SUM(total_amount) as order_amount
        FROM orders 
        WHERE payment_status = 'paid'
        GROUP BY user_id
    ) order_totals ON u.id = order_totals.user_id
    LEFT JOIN (
        SELECT 
            CASE 
                WHEN user_id IS NOT NULL THEN user_id
                ELSE (SELECT id FROM users WHERE email = customer_email LIMIT 1)
            END as user_id,
            SUM(total_amount) as booking_amount
        FROM service_bookings 
        GROUP BY 
            CASE 
                WHEN user_id IS NOT NULL THEN user_id
                ELSE (SELECT id FROM users WHERE email = customer_email LIMIT 1)
            END
    ) booking_totals ON u.id = booking_totals.user_id
    WHERE (COALESCE(order_totals.order_amount, 0) + COALESCE(booking_totals.booking_amount, 0)) > 0
    ORDER BY total_amount DESC
    LIMIT 5"
);


// Get sales data for chart (last 30 days)
$ordersData = $db->fetchAll(
    "SELECT DATE(created_at) as date, COUNT(*) as orders, SUM(total_amount) as revenue
     FROM orders
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
     GROUP BY DATE(created_at)
     ORDER BY date ASC"
);

$bookingsData = $db->fetchAll(
    "SELECT DATE(created_at) as date, COUNT(*) as bookings, SUM(total_amount) as revenue
     FROM service_bookings
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
     GROUP BY DATE(created_at)
     ORDER BY date ASC"
);

// Merge data for all dates in the last 30 days
$chartDates = [];
$chartOrders = [];
$chartBookings = [];
$chartOrdersRevenue = [];
$chartBookingsRevenue = [];

// Generate all dates for the last 30 days
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chartDates[] = date('M d', strtotime($date));
    
    // Find orders data for this date
    $ordersRevenue = 0;
    $ordersCount = 0;
    foreach ($ordersData as $data) {
        if ($data['date'] == $date) {
            $ordersRevenue = $data['revenue'];
            $ordersCount = $data['orders'];
            break;
        }
    }
    $chartOrders[] = $ordersCount;
    $chartOrdersRevenue[] = $ordersRevenue;
    
    // Find bookings data for this date
    $bookingsRevenue = 0;
    $bookingsCount = 0;
    foreach ($bookingsData as $data) {
        if ($data['date'] == $date) {
            $bookingsRevenue = $data['revenue'];
            $bookingsCount = $data['bookings'];
            break;
        }
    }
    $chartBookings[] = $bookingsCount;
    $chartBookingsRevenue[] = $bookingsRevenue;
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Dashboard Content -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('dashboard', 'Dashboard'); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <!-- Buttons removed -->
    </div>
</div>

<!-- Key Metrics Overview -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm bg-white">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-lg-4">
                        <h3 class="mb-1 text-primary"><?php echo __('welcome_admin', 'Welcome to Admin Dashboard'); ?></h3>
                        <p class="mb-0 text-muted"><?php echo __('dashboard_overview', 'Complete overview of your business performance'); ?></p>
                        <?php if ($stats['today_orders'] > 0): ?>
                        <div class="mt-2"><span class="badge bg-primary"><?php echo $stats['today_orders']; ?> orders today</span>
                        <span class="badge bg-success"><?php echo $currency->format($stats['today_revenue']); ?> today</span></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-lg-8 text-end">
                        <div class="row g-3">
                            <div class="col-6 border-end">
                                <div class="h4 mb-0 fw-bold text-dark"><?php echo $currency->format($stats['total_revenue']); ?></div>
                                <small class="text-muted"><?php echo __('total_revenue', 'Total Revenue'); ?></small>
                                <div class="small text-muted">Orders: <?php echo $currency->format($stats['order_revenue']); ?> | Bookings: <?php echo $currency->format($stats['booking_revenue']); ?></div>
                            </div>
                            <div class="col-6">
                                <div class="h4 mb-0 fw-bold text-info"><?php echo $currency->format($stats['inventory_cost']); ?></div>
                                <small class="text-muted"><?php echo __('inventory_cost', 'Inventory Cost'); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
        <div class="card stats-card primary border-0 shadow-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Pending Bookings</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo number_format($stats['pending_bookings']); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-calendar-check fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
        <div class="card stats-card success border-0 shadow-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1"><?php echo __('total_products', 'Total Products'); ?></div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo number_format($stats['total_products']); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-box fa-2x text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
        <div class="card stats-card info border-0 shadow-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1"><?php echo __('total_customers', 'Total Customers'); ?></div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo number_format($stats['total_customers']); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
        <div class="card stats-card warning border-0 shadow-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1"><?php echo __('out_of_stock', 'Out of Stock'); ?></div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo number_format($stats['low_stock_products']); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <div class="col-xl-8 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line me-2 text-primary"></i>
                        <?php echo __('sales_overview', 'Sales Overview'); ?> (<?php echo __('last_30_days', 'Last 30 Days'); ?>)
                    </h5>
                    <div class="btn-group btn-group-sm">
                        <button type="button" id="btnOrders" class="btn btn-primary active" onclick="toggleChart('orders')">Orders</button>
                        <button type="button" id="btnBookings" class="btn btn-outline-warning" onclick="toggleChart('bookings')">Bookings</button>
                        <button type="button" id="btnBoth" class="btn btn-outline-secondary" onclick="toggleChart('both')">Both</button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <canvas id="salesChart" height="100"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-xl-4 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0">
                    <i class="fas fa-trophy me-2 text-warning"></i>
                    <?php echo __('leaderboard', 'Leaderboard'); ?>
                    <small class="text-muted">(Top 5 Clients)</small>
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($leaderboardData)): ?>
                    <div class="leaderboard-list">
                        <?php foreach ($leaderboardData as $index => $client): ?>
                            <div class="d-flex justify-content-between align-items-center mb-3 p-2 rounded hover-bg">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <?php if ($index === 0): ?>
                                            <i class="fas fa-trophy text-warning fa-lg"></i>
                                        <?php elseif ($index === 1): ?>
                                            <i class="fas fa-medal text-secondary fa-lg"></i>
                                        <?php elseif ($index === 2): ?>
                                            <i class="fas fa-award text-bronze fa-lg" style="color: #CD7F32;"></i>
                                        <?php else: ?>
                                            <span class="badge bg-secondary me-2"><?php echo $index + 1; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($client['email']); ?></small>
                                        <div class="small text-muted">
                                            Orders: <?php echo $currency->format($client['order_amount']); ?> | 
                                            Bookings: <?php echo $currency->format($client['booking_amount']); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold text-primary"><?php echo $currency->format($client['total_amount']); ?></div>
                                    <small class="text-muted">Total</small>
                                </div>
                            </div>
                            <?php if ($index < count($leaderboardData) - 1): ?>
                                <hr class="my-2">
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-trophy fa-3x text-muted mb-3"></i>
                        <p class="text-muted"><?php echo __('no_clients', 'No client data available'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Status Charts Row -->
<div class="row mb-4">
    <div class="col-xl-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0">
                    <i class="fas fa-shopping-cart me-2 text-primary"></i>
                    <?php echo __('order_status', 'Order Status'); ?>
                </h5>
            </div>
            <div class="card-body">
                <canvas id="orderStatusChart" height="120"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-xl-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0">
                    <i class="fas fa-calendar-check me-2 text-warning"></i>
                    <?php echo __('bookings_status', 'Bookings Status'); ?>
                </h5>
            </div>
            <div class="card-body">
                <canvas id="bookingsStatusChart" height="120"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Bookings -->
<div class="row">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-calendar-check me-1"></i>
                <?php echo __('recent_bookings', 'Recent Bookings'); ?>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><?php echo __('booking_number', 'Booking #'); ?></th>
                                <th><?php echo __('customer', 'Customer'); ?></th>
                                <th><?php echo __('service', 'Service'); ?></th>
                                <th><?php echo __('date', 'Date'); ?></th>
                                <th><?php echo __('status', 'Status'); ?></th>
                                <th><?php echo __('action', 'Action'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recentBookings)): ?>
                                <?php foreach ($recentBookings as $booking): ?>
                                <tr>
                                    <td><strong>#<?php echo htmlspecialchars($booking['booking_number']); ?></strong></td>
                                    <td>
                                        <?php if ($booking['first_name'] || $booking['last_name']): ?>
                                            <?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?>
                                        <?php elseif ($booking['user_email']): ?>
                                            <?php echo htmlspecialchars($booking['user_email']); ?>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($booking['customer_email'] ?? 'Guest'); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if ($booking['service_image']): ?>
                                                <img src="<?php echo BASE_URL; ?>assets/uploads/services/<?php echo htmlspecialchars($booking['service_image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($booking['service_name']); ?>" 
                                                     class="me-2" style="width: 30px; height: 30px; object-fit: cover; border-radius: 4px;">
                                            <?php endif; ?>
                                            <span><?php echo htmlspecialchars($booking['service_name'] ?: 'Service #' . $booking['service_id']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
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
                                        <a href="<?php echo BASE_URL; ?>admin/bookings/view.php?id=<?php echo $booking['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <?php echo __('view', 'View'); ?>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                                        <p class="text-muted"><?php echo __('no_bookings', 'No recent bookings'); ?></p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders & Low Stock -->
<div class="row">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-shopping-cart me-1"></i>
                <?php echo __('recent_orders', 'Recent Orders'); ?>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><?php echo __('order', 'Order'); ?> #</th>
                                <th><?php echo __('customer', 'Customer'); ?></th>
                                <th><?php echo __('date', 'Date'); ?></th>
                                <th><?php echo __('total', 'Total'); ?></th>
                                <th><?php echo __('status', 'Status'); ?></th>
                                <th><?php echo __('action', 'Action'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['order_number']; ?></td>
                                <td><?php echo $order['first_name'] . ' ' . $order['last_name']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td><?php echo $currency->format($order['total_amount']); ?></td>
                                <td>
                                    <?php
                                    $statusColors = [
                                        'pending' => 'warning',
                                        'processing' => 'info',
                                        'shipped' => 'primary',
                                        'delivered' => 'success',
                                        'cancelled' => 'danger'
                                    ];
                                    $badgeColor = $statusColors[$order['order_status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $badgeColor; ?>">
                                        <?php echo __($order['order_status'], ucfirst($order['order_status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="orders/view.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-exclamation-triangle me-1"></i>
                <?php echo __('out_of_stock', 'Out of Stock'); ?>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <?php foreach ($lowStockProducts as $product): ?>
                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1"><?php echo $product['name']; ?></h6>
                                <small class="text-muted"><?php echo __('sku', 'SKU'); ?>: <?php echo $product['sku']; ?></small>
                            </div>
                            <span class="badge bg-danger"><?php echo $product['stock_quantity']; ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <a href="products/" class="btn btn-sm btn-outline-primary w-100 mt-3">
                    <?php echo __('view_all_products', 'View All Products'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Wait for page to fully load including Chart.js
document.addEventListener('DOMContentLoaded', function() {
    // Check if Chart is defined
    if (typeof Chart === 'undefined') {
        console.error('Chart.js is not loaded!');
        return;
    }

// Sales Chart
const salesCtx = document.getElementById('salesChart');
let salesChart = null;
if (salesCtx) {
    salesChart = new Chart(salesCtx.getContext('2d'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($chartDates); ?>,
        datasets: [{
            label: '<?php echo __("orders", "Orders"); ?>',
            data: <?php echo json_encode($chartOrders); ?>,
            borderColor: 'rgb(54, 162, 235)',
            backgroundColor: 'rgba(54, 162, 235, 0.1)',
            tension: 0.4,
            yAxisID: 'y',
            hidden: false
        }, {
            label: '<?php echo __("bookings", "Bookings"); ?>',
            data: <?php echo json_encode($chartBookings); ?>,
            borderColor: 'rgb(255, 159, 64)',
            backgroundColor: 'rgba(255, 159, 64, 0.1)',
            tension: 0.4,
            yAxisID: 'y',
            hidden: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: '<?php echo __("count", "Count"); ?>'
                }
            }
        }
    }
});
}

// Toggle Sales Chart function
window.toggleChart = function(mode) {
    if (!salesChart) return;

    const ordersDataset = salesChart.data.datasets[0];
    const bookingsDataset = salesChart.data.datasets[1];

    const btnOrders   = document.getElementById('btnOrders');
    const btnBookings = document.getElementById('btnBookings');
    const btnBoth     = document.getElementById('btnBoth');

    // Reset button styles
    btnOrders.className   = 'btn btn-outline-primary btn-sm';
    btnBookings.className = 'btn btn-outline-warning btn-sm';
    btnBoth.className     = 'btn btn-outline-secondary btn-sm';

    if (mode === 'orders') {
        ordersDataset.hidden   = false;
        bookingsDataset.hidden = true;
        btnOrders.className = 'btn btn-primary btn-sm active';
    } else if (mode === 'bookings') {
        ordersDataset.hidden   = true;
        bookingsDataset.hidden = false;
        btnBookings.className = 'btn btn-warning btn-sm active';
    } else {
        ordersDataset.hidden   = false;
        bookingsDataset.hidden = false;
        btnBoth.className = 'btn btn-secondary btn-sm active';
    }

    salesChart.update();
};


// Order Status Chart
const statusCtx = document.getElementById('orderStatusChart');
if (statusCtx) {
    const statusChart = new Chart(statusCtx.getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: [
            '<?php echo __("pending", "Pending"); ?>', 
            '<?php echo __("processing", "Processing"); ?>', 
            '<?php echo __("shipped", "Shipped"); ?>', 
            '<?php echo __("delivered", "Delivered"); ?>', 
            '<?php echo __("cancelled", "Cancelled"); ?>'
        ],
        datasets: [{
            data: [
                <?php echo $db->count('orders', 'order_status = "pending"'); ?>,
                <?php echo $db->count('orders', 'order_status = "processing"'); ?>,
                <?php echo $db->count('orders', 'order_status = "shipped"'); ?>,
                <?php echo $db->count('orders', 'order_status = "delivered"'); ?>,
                <?php echo $db->count('orders', 'order_status = "cancelled"'); ?>
            ],
            backgroundColor: [
                'rgb(255, 205, 86)',
                'rgb(54, 162, 235)',
                'rgb(153, 102, 255)',
                'rgb(75, 192, 192)',
                'rgb(255, 99, 132)'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true
    }
});
}

// Bookings Status Chart
const bookingsStatusCtx = document.getElementById('bookingsStatusChart');
if (bookingsStatusCtx) {
    // Prepare bookings status data
    const bookingsStatusData = <?php echo json_encode($bookingsStatusData); ?>;
    const bookingLabels = [];
    const bookingCounts = [];
    
    // Extract labels and counts from data
    bookingsStatusData.forEach(item => {
        bookingLabels.push(item.status);
        bookingCounts.push(item.count);
    });
    
    const bookingsStatusChart = new Chart(bookingsStatusCtx.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: bookingLabels,
            datasets: [{
                data: bookingCounts,
                backgroundColor: [
                    'rgb(255, 205, 86)',  // pending - yellow
                    'rgb(54, 162, 235)',  // confirmed - blue
                    'rgb(75, 192, 192)',  // completed - green
                    'rgb(255, 99, 132)'   // cancelled - red
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true
        }
    });
}

}); // End DOMContentLoaded
</script>

<?php include 'includes/footer.php'; ?>
