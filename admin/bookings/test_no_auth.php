<?php
// Test admin bookings without authentication
echo "=== ADMIN BOOKINGS - NO AUTH TEST ===\n\n";

// Set up minimal environment
require_once dirname(dirname(__DIR__)) . '/includes/Database.php';

// Database config
define('DB_HOST', 'localhost');
define('DB_NAME', 'ecommerce_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Language setup
require_once dirname(dirname(__DIR__)) . '/includes/Language.php';
require_once dirname(dirname(__DIR__)) . '/includes/Currency.php';

// Create language and currency objects
$lang = new Language($db);
$currency = new Currency($db);

// Set up admin session variables
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_id'] = 1;
$_SESSION['admin_email'] = 'admin@test.com';
$_SESSION['admin_first_name'] = 'Admin';
$_SESSION['admin_last_name'] = 'User';

// Get filter parameters (same as original)
$status_filter = $_GET['status'] ?? 'all';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$service_filter = $_GET['service'] ?? '';

// Build query (same as original)
$where_conditions = [];
$params = [];

if ($status_filter !== 'all') {
    $where_conditions[] = 'sb.status = :status';
    $params['status'] = $status_filter;
}

if ($date_from) {
    $where_conditions[] = 'sb.booking_date >= :date_from';
    $params['date_from'] = $date_from;
}

if ($date_to) {
    $where_conditions[] = 'sb.booking_date <= :date_to';
    $params['date_to'] = $date_to;
}

if ($service_filter) {
    $where_conditions[] = 'sb.service_id = :service_id';
    $params['service_id'] = $service_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get bookings (same as original)
$bookings = $db->fetchAll("
    SELECT sb.*, 
           st.name as service_name,
           s.image as service_image,
           sb.customer_name as customer_display_name,
           sb.customer_email,
           sb.customer_phone,
           sb.booking_time as booking_time_slot,
           sb.notes as customer_notes,
           sb.admin_notes,
           CASE 
               WHEN sb.time_slot_id IS NOT NULL THEN ts.start_time
               ELSE sb.booking_time
           END as display_start_time,
           CASE 
               WHEN sb.time_slot_id IS NOT NULL THEN ts.end_time
               ELSE DATE_ADD(sb.booking_time, INTERVAL 1 HOUR)
           END as display_end_time
    FROM service_bookings sb
    LEFT JOIN services s ON sb.service_id = s.id
    LEFT JOIN service_translations st ON s.id = st.service_id AND st.language_id = 1
    LEFT JOIN service_time_slots ts ON sb.time_slot_id = ts.id
    $where_clause
    ORDER BY sb.booking_date DESC, sb.booking_time DESC
", $params);

echo "Bookings retrieved: " . count($bookings) . "\n";

// Generate the table HTML directly
echo "\n=== GENERATING TABLE HTML ===\n";

$pageTitle = 'Service Bookings Test';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container mt-4">
        <h1 class="h2"><i class="fas fa-calendar-check"></i> Service Bookings Test</h1>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Booking List (<?php echo count($bookings); ?> results)</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($bookings)): ?>
                <div class="text-center p-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No bookings found</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Booking #</th>
                                <th>Service</th>
                                <th>Customer</th>
                                <th>Date & Time</th>
                                <th>Notes</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td>
                                    <strong>#<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?></strong><br>
                                    <small class="text-muted"><?php echo date('M j, Y H:i', strtotime($booking['created_at'])); ?></small>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($booking['service_image']): ?>
                                        <img src="assets/uploads/services/<?php echo htmlspecialchars($booking['service_image']); ?>" 
                                             alt="" class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                        <?php endif; ?>
                                        <div>
                                            <?php echo htmlspecialchars($booking['service_name']); ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($booking['customer_display_name']); ?></strong><br>
                                    <small class="text-muted">
                                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($booking['customer_email']); ?><br>
                                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($booking['customer_phone']); ?>
                                    </small>
                                </td>
                                <td>
                                    <i class="far fa-calendar"></i> <?php echo date('M j, Y', strtotime($booking['booking_date'])); ?><br>
                                    <i class="far fa-clock"></i> <?php echo date('g:i A', strtotime($booking['display_start_time'])); ?> - <?php echo date('g:i A', strtotime($booking['display_end_time'])); ?>
                                </td>
                                <td>
                                    <?php if (!empty($booking['customer_notes'])): ?>
                                    <small class="text-muted" title="<?php echo htmlspecialchars($booking['customer_notes']); ?>">
                                        <?php echo strlen($booking['customer_notes']) > 30 ? substr(htmlspecialchars($booking['customer_notes']), 0, 30) . '...' : htmlspecialchars($booking['customer_notes']); ?>
                                    </small>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong class="text-success">$<?php echo number_format($booking['total_amount'], 2); ?></strong>
                                </td>
                                <td>
                                    <?php
                                    $status_badges = [
                                        'pending' => 'warning',
                                        'confirmed' => 'info',
                                        'completed' => 'success',
                                        'cancelled' => 'danger'
                                    ];
                                    $badge_class = $status_badges[$booking['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $badge_class; ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="view.php?id=<?php echo $booking['id']; ?>" class="btn btn-outline-primary" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($booking['status'] === 'pending'): ?>
                                        <a href="confirm.php?id=<?php echo $booking['id']; ?>" class="btn btn-outline-success" title="Confirm">
                                            <i class="fas fa-check"></i>
                                        </a>
                                        <?php endif; ?>
                                        <a href="edit.php?id=<?php echo $booking['id']; ?>" class="btn btn-outline-secondary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php

echo "\n✅ Test page generated successfully!\n";
echo "Open this file in browser to see the complete booking information.\n";
echo "File: admin/bookings/test_no_auth.php\n";
?>
