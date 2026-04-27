<?php
/**
 * Sales Report
 */

require_once 'includes/auth.php';
requirePermission('reports');

$pageTitle = __('sales_report', 'Sales Report');

// Get date range from GET parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-d'); // Today

// Get report type
$report_type = $_GET['report_type'] ?? 'summary';

// Prepare end date for database (include the whole day)
$db_start_date = $start_date . ' 00:00:00';
$db_end_date = $end_date . ' 23:59:59';

// Get sales data
$salesData = [];
$ordersData = [];
$bookingsData = [];

if ($report_type == 'summary' || $report_type == 'orders') {
    $ordersData = $db->fetchAll(
        "SELECT 
            DATE(created_at) as date,
            COUNT(*) as order_count,
            SUM(total_amount / COALESCE(NULLIF(exchange_rate, 0), 1)) as order_revenue,
            AVG(total_amount / COALESCE(NULLIF(exchange_rate, 0), 1)) as avg_order_value
         FROM orders 
         WHERE created_at BETWEEN :start_date AND :end_date 
           AND payment_status = 'paid' 
           AND order_status != 'cancelled'
         GROUP BY DATE(created_at)
         ORDER BY date ASC",
        ['start_date' => $db_start_date, 'end_date' => $db_end_date]
    );
}

if ($report_type == 'summary' || $report_type == 'bookings') {
    $bookingsData = $db->fetchAll(
        "SELECT 
            DATE(created_at) as date,
            COUNT(*) as booking_count,
            SUM(total_amount) as booking_revenue,
            AVG(total_amount) as avg_booking_value
         FROM service_bookings 
         WHERE created_at BETWEEN :start_date AND :end_date 
           AND payment_status = 'paid'
           AND status IN ('confirmed', 'completed')
         GROUP BY DATE(created_at)
         ORDER BY date ASC",
        ['start_date' => $db_start_date, 'end_date' => $db_end_date]
    );
}

// Get top products by revenue
$topProducts = $db->fetchAll(
    "SELECT 
        p.id,
        COALESCE(pt.name, CONCAT('Product #', p.id)) as product_name,
        SUM(oi.quantity) as total_sold,
        SUM(oi.quantity * oi.product_price / COALESCE(NULLIF(o.exchange_rate, 0), 1)) as total_revenue
     FROM orders o
     JOIN order_items oi ON o.id = oi.order_id
     JOIN products p ON oi.product_id = p.id
     LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang
     WHERE o.created_at BETWEEN :start_date AND :end_date 
       AND o.payment_status = 'paid' AND o.order_status != 'cancelled'
     GROUP BY p.id, pt.name
     ORDER BY total_revenue DESC
     LIMIT 10",
    ['lang' => $lang->getCurrentId(), 'start_date' => $db_start_date, 'end_date' => $db_end_date]
);

// Inventory Cost: SUM(quantity x unit_cost) from all inventory_transactions
// quantity can be negative (outgoing) or positive (incoming)
$inventoryCost = $db->fetchOne(
    "SELECT COALESCE(SUM(quantity * unit_cost), 0) as total FROM inventory_transactions"
)['total'] ?? 0;


// Get top services
$topServices = $db->fetchAll(
    "SELECT 
        s.id,
        COALESCE(st.name, CONCAT('Service #', s.id)) as service_name,
        COUNT(*) as booking_count,
        SUM(sb.total_amount) as total_revenue
     FROM service_bookings sb
     JOIN services s ON sb.service_id = s.id
     LEFT JOIN service_translations st ON s.id = st.service_id AND st.language_id = :lang
     WHERE sb.created_at BETWEEN :start_date AND :end_date
       AND sb.payment_status = 'paid'
       AND sb.status IN ('confirmed', 'completed')
     GROUP BY s.id, st.name
     ORDER BY total_revenue DESC
     LIMIT 10",
    ['lang' => $lang->getCurrentId(), 'start_date' => $db_start_date, 'end_date' => $db_end_date]
);

// Get payment methods breakdown
$paymentMethods = $db->fetchAll(
    "SELECT 
        payment_method,
        COUNT(*) as count,
        SUM(base_total) as total
     FROM (
        SELECT payment_method, total_amount / COALESCE(NULLIF(exchange_rate, 0), 1) as base_total 
        FROM orders 
        WHERE created_at BETWEEN :start_date AND :end_date
          AND payment_status = 'paid'
          AND order_status != 'cancelled'
        UNION ALL
        SELECT payment_method, total_amount as base_total 
        FROM service_bookings 
        WHERE created_at BETWEEN :start_date AND :end_date
          AND payment_status = 'paid'
          AND status IN ('confirmed', 'completed')
    ) combined
     GROUP BY payment_method
     ORDER BY total DESC",
    ['start_date' => $db_start_date, 'end_date' => $db_end_date]
);

// Calculate filtered totals
$totalOrders = array_sum(array_column($ordersData, 'order_count'));
$totalOrderRevenue = array_sum(array_column($ordersData, 'order_revenue'));
$totalBookings = array_sum(array_column($bookingsData, 'booking_count'));
$totalBookingRevenue = array_sum(array_column($bookingsData, 'booking_revenue'));
$totalRevenue = $totalOrderRevenue + $totalBookingRevenue;

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Sales Report Content -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo __('sales_report', 'Sales Report'); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="window.print()">
                <i class="fas fa-print me-1"></i> <?php echo __('print', 'Print'); ?>
            </button>
            <button type="button" class="btn btn-sm btn-outline-success" onclick="exportReport()">
                <i class="fas fa-download me-1"></i> <?php echo __('export', 'Export'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Report Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-filter me-2"></i><?php echo __('report_filters', 'Report Filters'); ?>
        </h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="start_date" class="form-label"><?php echo __('start_date', 'Start Date'); ?></label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label"><?php echo __('end_date', 'End Date'); ?></label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
            </div>
            <div class="col-md-3">
                <label for="report_type" class="form-label"><?php echo __('report_type', 'Report Type'); ?></label>
                <select class="form-select" id="report_type" name="report_type">
                    <option value="summary" <?php echo $report_type == 'summary' ? 'selected' : ''; ?>><?php echo __('summary', 'Summary'); ?></option>
                    <option value="orders" <?php echo $report_type == 'orders' ? 'selected' : ''; ?>><?php echo __('orders_only', 'Orders Only'); ?></option>
                    <option value="bookings" <?php echo $report_type == 'bookings' ? 'selected' : ''; ?>><?php echo __('bookings_only', 'Bookings Only'); ?></option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i><?php echo __('generate_report', 'Generate Report'); ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-0 shadow-sm bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 mb-2"><?php echo __('total_revenue', 'Total Revenue'); ?></h6>
                        <h3 class="mb-0"><?php echo $currency->format($totalRevenue); ?></h3>
                    </div>
                    <div>
                        <i class="fas fa-chart-line fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Inventory Cost</h6>
                        <h3 class="mb-0 text-info"><?php echo $currency->format($inventoryCost); ?></h3>
                    </div>
                    <div class="text-info">
                        <i class="fas fa-warehouse fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Total Orders</h6>
                        <h3 class="mb-0 text-warning"><?php echo number_format($totalOrders); ?></h3>
                        <small class="text-muted">+ <?php echo number_format($totalBookings); ?> Bookings</small>
                    </div>
                    <div class="text-warning">
                        <i class="fas fa-shopping-cart fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="row mb-4">
    <div class="col-xl-8 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0">
                    <i class="fas fa-chart-line me-2 text-primary"></i>
                    <?php echo __('revenue_trend', 'Revenue Trend'); ?>
                </h5>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="100"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-xl-4 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0">
                    <i class="fas fa-chart-pie me-2 text-info"></i>
                    <?php echo __('payment_methods', 'Payment Methods'); ?>
                </h5>
            </div>
            <div class="card-body">
                <canvas id="paymentChart" height="120"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Top Products & Services -->
<div class="row mb-4">
    <div class="col-xl-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0">
                    <i class="fas fa-box me-2 text-success"></i>
                    <?php echo __('top_products', 'Top Products'); ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><?php echo __('product', 'Product'); ?></th>
                                <th class="text-end"><?php echo __('sold', 'Sold'); ?></th>
                                <th class="text-end"><?php echo __('revenue', 'Revenue'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($topProducts)): ?>
                                <?php foreach ($topProducts as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                    <td class="text-end"><?php echo number_format($product['total_sold']); ?></td>
                                    <td class="text-end"><?php echo $currency->format($product['total_revenue']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted"><?php echo __('no_data', 'No data available'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0">
                    <i class="fas fa-calendar-check me-2 text-warning"></i>
                    <?php echo __('top_services', 'Top Services'); ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><?php echo __('service', 'Service'); ?></th>
                                <th class="text-end"><?php echo __('bookings', 'Bookings'); ?></th>
                                <th class="text-end"><?php echo __('revenue', 'Revenue'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($topServices)): ?>
                                <?php foreach ($topServices as $service): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($service['service_name']); ?></td>
                                    <td class="text-end"><?php echo number_format($service['booking_count']); ?></td>
                                    <td class="text-end"><?php echo $currency->format($service['total_revenue']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted"><?php echo __('no_data', 'No data available'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Data Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0">
        <h5 class="mb-0">
            <i class="fas fa-table me-2 text-primary"></i>
            <?php echo __('daily_breakdown', 'Daily Breakdown'); ?>
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th><?php echo __('date', 'Date'); ?></th>
                        <?php if ($report_type == 'summary' || $report_type == 'orders'): ?>
                        <th class="text-end"><?php echo __('orders', 'Orders'); ?></th>
                        <th class="text-end"><?php echo __('order_revenue', 'Order Revenue'); ?></th>
                        <?php endif; ?>
                        <?php if ($report_type == 'summary' || $report_type == 'bookings'): ?>
                        <th class="text-end"><?php echo __('bookings', 'Bookings'); ?></th>
                        <th class="text-end"><?php echo __('booking_revenue', 'Booking Revenue'); ?></th>
                        <?php endif; ?>
                        <th class="text-end"><?php echo __('total_revenue', 'Total Revenue'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Merge and display daily data
                    $allDates = [];
                    $dateMap = [];
                    
                    // Collect all dates
                    foreach ($ordersData as $order) {
                        $allDates[] = $order['date'];
                        $dateMap[$order['date']]['orders'] = $order;
                    }
                    foreach ($bookingsData as $booking) {
                        $allDates[] = $booking['date'];
                        $dateMap[$booking['date']]['bookings'] = $booking;
                    }
                    $allDates = array_unique($allDates);
                    sort($allDates);
                    
                    foreach ($allDates as $date): 
                        $orderData = $dateMap[$date]['orders'] ?? ['order_count' => 0, 'order_revenue' => 0];
                        $bookingData = $dateMap[$date]['bookings'] ?? ['booking_count' => 0, 'booking_revenue' => 0];
                        $dayTotal = ($orderData['order_revenue'] ?? 0) + ($bookingData['booking_revenue'] ?? 0);
                    ?>
                    <tr>
                        <td><?php echo date('M d, Y', strtotime($date)); ?></td>
                        <?php if ($report_type == 'summary' || $report_type == 'orders'): ?>
                        <td class="text-end"><?php echo number_format($orderData['order_count'] ?? 0); ?></td>
                        <td class="text-end"><?php echo $currency->format($orderData['order_revenue'] ?? 0); ?></td>
                        <?php endif; ?>
                        <?php if ($report_type == 'summary' || $report_type == 'bookings'): ?>
                        <td class="text-end"><?php echo number_format($bookingData['booking_count'] ?? 0); ?></td>
                        <td class="text-end"><?php echo $currency->format($bookingData['booking_revenue'] ?? 0); ?></td>
                        <?php endif; ?>
                        <td class="text-end fw-bold"><?php echo $currency->format($dayTotal); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th><?php echo __('total', 'Total'); ?></th>
                        <?php if ($report_type == 'summary' || $report_type == 'orders'): ?>
                        <th class="text-end"><?php echo number_format($totalOrders); ?></th>
                        <th class="text-end"><?php echo $currency->format($totalOrderRevenue); ?></th>
                        <?php endif; ?>
                        <?php if ($report_type == 'summary' || $report_type == 'bookings'): ?>
                        <th class="text-end"><?php echo number_format($totalBookings); ?></th>
                        <th class="text-end"><?php echo $currency->format($totalBookingRevenue); ?></th>
                        <?php endif; ?>
                        <th class="text-end fw-bold"><?php echo $currency->format($totalRevenue); ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
// Revenue Trend Chart
const revenueCtx = document.getElementById('revenueChart');
if (revenueCtx) {
    const dates = <?php echo json_encode($allDates); ?>;
    const orderRevenue = <?php echo json_encode(array_column($ordersData, 'order_revenue')); ?>;
    const bookingRevenue = <?php echo json_encode(array_column($bookingsData, 'booking_revenue')); ?>;
    
    new Chart(revenueCtx.getContext('2d'), {
        type: 'line',
        data: {
            labels: dates.map(date => new Date(date).toLocaleDateString()),
            datasets: [
                <?php if ($report_type == 'summary' || $report_type == 'orders'): ?>
                {
                    label: '<?php echo __('order_revenue', 'Order Revenue'); ?>',
                    data: orderRevenue,
                    borderColor: 'rgb(54, 162, 235)',
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    tension: 0.4
                },
                <?php endif; ?>
                <?php if ($report_type == 'summary' || $report_type == 'bookings'): ?>
                {
                    label: '<?php echo __('booking_revenue', 'Booking Revenue'); ?>',
                    data: bookingRevenue,
                    borderColor: 'rgb(255, 159, 64)',
                    backgroundColor: 'rgba(255, 159, 64, 0.1)',
                    tension: 0.4
                }
                <?php endif; ?>
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            <?php if (in_array($currency->getCurrentCode(), ['AED', 'SAR', 'KWD', 'QAR', 'OMR', 'BHD', 'JOD', 'EGP', 'LBP'])): ?>
                            return value.toLocaleString() + ' <?php echo $currency->getCurrentCode(); ?>';
                            <?php else: ?>
                            return '<?php echo $currency->getSymbol(); ?>' + value.toLocaleString();
                            <?php endif; ?>
                        }
                    }
                }
            }
        }
    });
}

// Payment Methods Chart
const paymentCtx = document.getElementById('paymentChart');
if (paymentCtx) {
    const paymentData = <?php echo json_encode($paymentMethods); ?>;
    
    new Chart(paymentCtx.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: paymentData.map(item => item.payment_method),
            datasets: [{
                data: paymentData.map(item => item.total),
                backgroundColor: [
                    'rgb(54, 162, 235)',
                    'rgb(255, 159, 64)',
                    'rgb(75, 192, 192)',
                    'rgb(255, 99, 132)',
                    'rgb(153, 102, 255)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
}

// Export function
function exportReport() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    window.location.href = window.location.pathname + '?' + params.toString();
}
</script>

<style>
@media print {
    .btn-toolbar, .card-header, .no-print {
        display: none !important;
    }
    .card {
        border: 1px solid #dee2e6 !important;
        box-shadow: none !important;
    }
}
</style>
