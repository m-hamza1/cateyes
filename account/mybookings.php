<?php
require_once '../config.php';
$pageTitle = __('my_bookings', 'My Bookings');

// Redirect if not logged in
if (!$user) {
    $_SESSION['flash_message'] = __('login_required', 'Please login to access your bookings');
    $_SESSION['flash_type'] = 'warning';
    header('Location: ' . BASE_URL . 'account/login.php?redirect=' . urlencode(BASE_URL . 'account/mybookings.php'));
    exit;
}

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get total bookings count
$total_bookings = $db->fetchOne("
    SELECT COUNT(*) as total 
    FROM service_bookings 
    WHERE user_id = :user_id OR customer_email = :email
", [
    'user_id' => $user['id'],
    'email' => $user['email']
])['total'] ?? 0;

$total_pages = ceil($total_bookings / $per_page);

// Get bookings with service details
$bookings = $db->fetchAll("
    SELECT sb.*, 
           s.image as service_image,
           st.name as service_name,
           s.duration as service_duration,
           s.price as service_price
    FROM service_bookings sb
    LEFT JOIN services s ON sb.service_id = s.id
    LEFT JOIN service_translations st ON s.id = st.service_id AND st.language_id = :lang
    WHERE sb.user_id = :user_id OR sb.customer_email = :email
    ORDER BY sb.created_at DESC
    LIMIT $offset, $per_page
", [
    'user_id' => $user['id'],
    'email' => $user['email'],
    'lang' => $lang->getCurrentId()
]);

// Ensure bookings is an array
if (!is_array($bookings)) {
    $bookings = [];
}

// Get booking details for view
$booking_id = $_GET['view'] ?? 0;
$booking_details = null;

if ($booking_id > 0) {
    $booking_details = $db->fetchOne("
        SELECT sb.*, 
               s.image as service_image,
               st.name as service_name,
               s.duration as service_duration,
               s.price as service_price,
               CASE 
                   WHEN sb.time_slot_id IS NOT NULL THEN ts.start_time
                   ELSE sb.booking_time
               END as start_time,
               CASE 
                   WHEN sb.time_slot_id IS NOT NULL THEN ts.end_time
                   ELSE DATE_ADD(sb.booking_time, INTERVAL s.duration MINUTE)
               END as end_time
        FROM service_bookings sb
        LEFT JOIN services s ON sb.service_id = s.id
        LEFT JOIN service_translations st ON s.id = st.service_id AND st.language_id = :lang
        LEFT JOIN service_time_slots ts ON sb.time_slot_id = ts.id
        WHERE sb.id = :id AND (sb.user_id = :user_id OR sb.customer_email = :email)
    ", [
        'id' => $booking_id,
        'user_id' => $user['id'],
        'email' => $user['email'],
        'lang' => $lang->getCurrentId()
    ]);
}

include '../includes/header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="bg-light py-3">
    <div class="container">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>"><?php echo __('home', 'Home'); ?></a></li>
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>account/dashboard.php"><?php echo __('dashboard', 'Dashboard'); ?></a></li>
            <li class="breadcrumb-item active"><?php echo __('my_bookings', 'My Bookings'); ?></li>
        </ol>
    </div>
</nav>

<!-- Main content -->
<section class="content py-5">
    <div class="container">
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
                            <a href="<?php echo BASE_URL; ?>account/dashboard.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-tachometer-alt me-2"></i> <?php echo __('dashboard', 'Dashboard'); ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>account/profile.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-user me-2"></i> <?php echo __('profile', 'Profile'); ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>account/mybookings.php" class="list-group-item list-group-item-action active">
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
                <?php if ($booking_details): ?>
                    <!-- Booking Details View -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">
                                <i class="fas fa-calendar-check me-2"></i>
                                <?php echo __('booking_details', 'Booking Details'); ?> #<?php echo $booking_details['booking_number']; ?>
                            </h4>
                            <a href="<?php echo BASE_URL; ?>account/mybookings.php" class="btn btn-warning btn-sm">
                                <i class="fas fa-arrow-left me-1"></i>
                                <?php echo __('back_to_bookings', 'Back to Bookings'); ?>
                            </a>
                        </div>
                        <div class="card-body">
                            <!-- Booking Info -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6><?php echo __('booking_information', 'Booking Information'); ?></h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong><?php echo __('booking_number', 'Booking Number'); ?>:</strong></td>
                                            <td>#<?php echo $booking_details['booking_number']; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong><?php echo __('booking_date', 'Booking Date'); ?>:</strong></td>
                                            <td><?php echo date('M d, Y H:i', strtotime($booking_details['created_at'])); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong><?php echo __('appointment_date', 'Appointment Date'); ?>:</strong></td>
                                            <td><?php echo date('l, F j, Y', strtotime($booking_details['booking_date'])); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong><?php echo __('appointment_time', 'Appointment Time'); ?>:</strong></td>
                                            <td>
                                                <?php echo date('g:i A', strtotime($booking_details['start_time'])); ?> - 
                                                <?php echo date('g:i A', strtotime($booking_details['end_time'])); ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong><?php echo __('booking_status', 'Booking Status'); ?>:</strong></td>
                                            <td>
                                                <span class="badge bg-<?php echo getBookingStatusColor($booking_details['status']); ?>">
                                                    <?php echo __(ucfirst($booking_details['status']), ucfirst($booking_details['status'])); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong><?php echo __('payment_status', 'Payment Status'); ?>:</strong></td>
                                            <td>
                                                <span class="badge bg-<?php echo getPaymentStatusColor($booking_details['payment_status']); ?>">
                                                    <?php echo __(ucfirst($booking_details['payment_status']), ucfirst($booking_details['payment_status'])); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6><?php echo __('customer_information', 'Customer Information'); ?></h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong><?php echo __('name', 'Name'); ?>:</strong></td>
                                            <td><?php echo htmlspecialchars($booking_details['customer_name']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong><?php echo __('email', 'Email'); ?>:</strong></td>
                                            <td><?php echo htmlspecialchars($booking_details['customer_email']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong><?php echo __('phone', 'Phone'); ?>:</strong></td>
                                            <td><?php echo htmlspecialchars($booking_details['customer_phone']); ?></td>
                                        </tr>
                                        <?php if ($booking_details['confirmed_at']): ?>
                                        <tr>
                                            <td><strong><?php echo __('confirmed_at', 'Confirmed At'); ?>:</strong></td>
                                            <td><?php echo date('M d, Y H:i', strtotime($booking_details['confirmed_at'])); ?></td>
                                        </tr>
                                        <?php endif; ?>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Service Details -->
                            <h6><?php echo __('service_details', 'Service Details'); ?></h6>
                            <div class="row align-items-center mb-4">
                                <?php if ($booking_details['service_image']): ?>
                                <div class="col-md-3">
                                    <img src="<?php echo BASE_URL; ?>assets/uploads/services/<?php echo htmlspecialchars($booking_details['service_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($booking_details['service_name']); ?>" 
                                         class="img-fluid rounded">
                                </div>
                                <?php endif; ?>
                                <div class="<?php echo $booking_details['service_image'] ? 'col-md-9' : 'col-12'; ?>">
                                    <h5><?php echo htmlspecialchars($booking_details['service_name']); ?></h5>
                                    <?php if ($booking_details['service_duration']): ?>
                                    <p class="mb-1"><i class="far fa-clock"></i> <?php echo __('duration', 'Duration'); ?>: <?php echo $booking_details['service_duration']; ?> <?php echo __('minutes', 'minutes'); ?></p>
                                    <?php endif; ?>
                                    <p class="mb-1"><i class="fas fa-tag"></i> <?php echo __('price', 'Price'); ?>: <?php echo $currency->format($booking_details['service_price']); ?></p>
                                    <p class="mb-0"><strong><?php echo __('total_amount', 'Total Amount'); ?>: <?php echo $currency->format($booking_details['total_amount']); ?></strong></p>
                                </div>
                            </div>
                            
                            <!-- Notes -->
                            <?php if ($booking_details['notes']): ?>
                            <h6><?php echo __('customer_notes', 'Customer Notes'); ?></h6>
                            <div class="alert alert-light mb-4">
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($booking_details['notes'])); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Booking Actions -->
                            <div class="d-flex justify-content-between">
                                <div>
                                    <?php if ($booking_details['status'] === 'pending'): ?>
                                        <button class="btn btn-warning" onclick="cancelBooking(<?php echo $booking_details['id']; ?>)">
                                            <i class="fas fa-times me-1"></i>
                                            <?php echo __('cancel_booking', 'Cancel Booking'); ?>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($booking_details['status'] === 'confirmed' && $booking_details['payment_status'] === 'pending'): ?>
                                        <a href="<?php echo BASE_URL; ?>service-payment.php?booking=<?php echo $booking_details['booking_number']; ?>" 
                                           class="btn btn-success ms-2">
                                            <i class="fas fa-credit-card me-1"></i>
                                            <?php echo __('complete_payment', 'Complete Payment'); ?>
                                        </a>
                                    <?php endif; ?>
                                    <button class="btn btn-outline-primary ms-2" onclick="rebookService(<?php echo $booking_details['service_id']; ?>)">
                                        <i class="fas fa-redo me-1"></i>
                                        <?php echo __('book_again', 'Book Again'); ?>
                                    </button>
                                </div>
                                <button class="btn btn-outline-secondary" onclick="window.print()">
                                    <i class="fas fa-print me-1"></i>
                                    <?php echo __('print_booking', 'Print Booking'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Bookings List -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0">
                                <i class="fas fa-calendar-check me-2"></i>
                                <?php echo __('my_bookings', 'My Bookings'); ?>
                                <span class="badge bg-light text-primary ms-2"><?php echo $total_bookings; ?></span>
                            </h4>
                        </div>
                        <div class="card-body">
                            <?php if (empty($bookings)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-calendar-check fa-4x text-muted mb-3"></i>
                                    <h5><?php echo __('no_bookings', 'No bookings yet'); ?></h5>
                                    <p class="text-muted"><?php echo __('start_booking_message', 'Book a service to see your appointments here'); ?></p>
                                    <a href="<?php echo BASE_URL; ?>services.php" class="btn btn-primary">
                                        <i class="fas fa-calendar-plus me-2"></i>
                                        <?php echo __('book_service', 'Book a Service'); ?>
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th><?php echo __('booking_number', 'Booking Number'); ?></th>
                                                <th><?php echo __('service', 'Service'); ?></th>
                                                <th><?php echo __('date', 'Date'); ?></th>
                                                <th><?php echo __('time', 'Time'); ?></th>
                                                <th><?php echo __('amount', 'Amount'); ?></th>
                                                <th><?php echo __('status', 'Status'); ?></th>
                                                <th><?php echo __('actions', 'Actions'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($bookings as $booking): ?>
                                                <tr>
                                                    <td>
                                                        <strong>#<?php echo $booking['booking_number']; ?></strong>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <?php if ($booking['service_image']): ?>
                                                                <img src="<?php echo BASE_URL; ?>assets/uploads/services/<?php echo htmlspecialchars($booking['service_image']); ?>" 
                                                                     alt="<?php echo htmlspecialchars($booking['service_name']); ?>" 
                                                                     class="me-2" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                                                            <?php endif; ?>
                                                            <div>
                                                                <strong><?php echo htmlspecialchars($booking['service_name']); ?></strong>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
                                                    <td><?php echo date('g:i A', strtotime($booking['booking_time'])); ?></td>
                                                    <td><strong><?php echo $currency->format($booking['total_amount']); ?></strong></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo getBookingStatusColor($booking['status']); ?>">
                                                            <?php echo __(ucfirst($booking['status']), ucfirst($booking['status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="?view=<?php echo $booking['id']; ?>" class="btn btn-outline-primary">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <?php if ($booking['status'] === 'confirmed' && $booking['payment_status'] === 'pending'): ?>
                                                                <a href="<?php echo BASE_URL; ?>service-payment.php?booking=<?php echo $booking['booking_number']; ?>" 
                                                                   class="btn btn-outline-success">
                                                                    <i class="fas fa-credit-card"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Pagination -->
                                <?php if ($total_pages > 1): ?>
                                    <nav aria-label="Bookings pagination">
                                        <ul class="pagination justify-content-center">
                                            <?php if ($page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                                                        <i class="fas fa-chevron-left"></i>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                <?php if ($i == $page): ?>
                                                    <li class="page-item active">
                                                        <span class="page-link"><?php echo $i; ?></span>
                                                    </li>
                                                <?php elseif ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                                    <li class="page-item">
                                                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                                    </li>
                                                <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                                    <li class="page-item disabled">
                                                        <span class="page-link">...</span>
                                                    </li>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                            
                                            <?php if ($page < $total_pages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>">
                                                        <i class="fas fa-chevron-right"></i>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php
// Helper functions for status colors
function getBookingStatusColor($status) {
    switch($status) {
        case 'pending': return 'warning';
        case 'confirmed': return 'info';
        case 'completed': return 'success';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}

function getPaymentStatusColor($status) {
    switch($status) {
        case 'pending': return 'warning';
        case 'paid': return 'success';
        case 'failed': return 'danger';
        case 'refunded': return 'info';
        default: return 'secondary';
    }
}
?>

<script>
function cancelBooking(bookingId) {
    if (confirm('<?php echo __('confirm_cancel_booking', 'Are you sure you want to cancel this booking?'); ?>')) {
        // In a real implementation, you would make an AJAX call to cancel the booking
        alert('<?php echo __('feature_coming_soon', 'This feature is coming soon!'); ?>');
    }
}

function rebookService(serviceId) {
    window.location.href = '<?php echo BASE_URL; ?>book-service.php?service=' + serviceId;
}
</script>

<?php include '../includes/footer.php'; ?>
