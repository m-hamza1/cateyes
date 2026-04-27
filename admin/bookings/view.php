<?php
require_once '../includes/auth.php';
requirePermission('bookings');

$booking_id = (int)($_GET['id'] ?? 0);
$booking = $db->fetchOne("
    SELECT sb.*, 
           st.name as service_name,
           s.price as service_price,
           s.duration as service_duration,
           s.image as service_image,
           u.first_name, u.last_name, u.email as user_email,
           CASE 
               WHEN sb.time_slot_id IS NOT NULL THEN ts.start_time
               ELSE sb.booking_time
           END as start_time,
           CASE 
               WHEN sb.time_slot_id IS NOT NULL THEN ts.end_time
               ELSE DATE_ADD(sb.booking_time, INTERVAL 1 HOUR)
           END as end_time,
           ts.day_of_week,
           admin.first_name as confirmed_by_first_name,
           admin.last_name as confirmed_by_last_name
    FROM service_bookings sb
    LEFT JOIN services s ON sb.service_id = s.id
    LEFT JOIN service_translations st ON s.id = st.service_id AND st.language_id = 1
    LEFT JOIN users u ON sb.user_id = u.id
    LEFT JOIN service_time_slots ts ON sb.time_slot_id = ts.id
    LEFT JOIN users admin ON sb.confirmed_by = admin.id
    WHERE sb.id = :id
", ['id' => $booking_id]);

if (!$booking) {
    $_SESSION['error'] = 'Booking not found';
    header('Location: index.php');
    exit;
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $new_status = '';
    
    switch ($action) {
        case 'complete':
            $new_status = 'completed';
            break;
        case 'cancel':
            $new_status = 'cancelled';
            break;
    }
    
    if ($new_status) {
        $db->update('service_bookings', [
            'status' => $new_status,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $booking_id]);
        
        $_SESSION['success'] = 'Booking status updated to ' . ucfirst($new_status);
        header('Location: view.php?id=' . $booking_id);
        exit;
    }
}

$pageTitle = 'View Booking';
$status_badges = [
    'pending' => 'warning',
    'confirmed' => 'info',
    'completed' => 'success',
    'cancelled' => 'danger'
];

// Coupon display helpers (support both newer and legacy schemas)
$couponCode = !empty($booking['coupon_code']) ? (string)$booking['coupon_code'] : '';
$discountAmount = 0.0;
if (isset($booking['coupon_discount_amount']) && $booking['coupon_discount_amount'] !== null) {
    $discountAmount = (float)$booking['coupon_discount_amount'];
} elseif (isset($booking['coupon_discount']) && $booking['coupon_discount'] !== null) {
    $discountAmount = (float)$booking['coupon_discount'];
}

$totalAmount = isset($booking['total_amount']) ? (float)$booking['total_amount'] : 0.0;
$originalAmount = isset($booking['original_amount']) ? (float)$booking['original_amount'] : 0.0;
if ($originalAmount <= 0 && $totalAmount > 0 && $discountAmount > 0) {
    $originalAmount = $totalAmount + $discountAmount;
}

$discountPercent = 0.0;
if (isset($booking['coupon_discount_percentage']) && $booking['coupon_discount_percentage'] !== null) {
    $discountPercent = (float)$booking['coupon_discount_percentage'];
}
if ($discountPercent <= 0 && $originalAmount > 0 && $discountAmount > 0) {
    $discountPercent = round(($discountAmount / $originalAmount) * 100, 2);
}

$discountPercentText = '';
if ($discountPercent > 0) {
    $discountPercentText = rtrim(rtrim(number_format($discountPercent, 2, '.', ''), '0'), '.');
}

$hasCouponInfo = ($discountAmount > 0) || ($couponCode !== '');

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <a href="index.php" class="text-decoration-none text-muted"><i class="fas fa-arrow-left"></i></a>
        Booking Details
    </h1>
    <div class="btn-toolbar">
        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-print"></i> Print
        </button>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <!-- Booking Information -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Booking Information</h5>
                    <span class="badge bg-<?php echo $status_badges[$booking['status']]; ?> fs-6">
                        <?php echo ucfirst($booking['status']); ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p class="mb-2"><strong>Booking Number:</strong></p>
                        <p class="h4 text-primary"><?php echo htmlspecialchars($booking['booking_number']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-2"><strong>Booking Date & Time:</strong></p>
                        <p class="h5">
                            <i class="far fa-calendar"></i> <?php echo date('l, F j, Y', strtotime($booking['booking_date'])); ?><br>
                            <i class="far fa-clock"></i> <?php echo date('g:i A', strtotime($booking['start_time'])); ?> - <?php echo date('g:i A', strtotime($booking['end_time'])); ?>
                        </p>
                    </div>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <p class="mb-1"><strong>Created At:</strong></p>
                        <p><?php echo date('M j, Y g:i A', strtotime($booking['created_at'])); ?></p>
                    </div>
                    <?php if ($booking['confirmed_at']): ?>
                    <div class="col-md-6 mb-3">
                        <p class="mb-1"><strong>Confirmed At:</strong></p>
                        <p><?php echo date('M j, Y g:i A', strtotime($booking['confirmed_at'])); ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($booking['confirmed_by_first_name']): ?>
                    <div class="col-md-6 mb-3">
                        <p class="mb-1"><strong>Confirmed By:</strong></p>
                        <p><?php echo htmlspecialchars($booking['confirmed_by_first_name'] . ' ' . $booking['confirmed_by_last_name']); ?></p>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-6 mb-3">
                        <p class="mb-1"><strong>Total Amount:</strong></p>
                        <p class="h4 text-success"><?php echo $currency->format($booking['total_amount']); ?></p>
                    </div>

                    <?php if ($hasCouponInfo): ?>
                    <?php if ($couponCode !== ''): ?>
                    <div class="col-md-6 mb-3">
                        <p class="mb-1"><strong>Coupon Code:</strong></p>
                        <p class="mb-0"><span class="badge bg-dark"><?php echo htmlspecialchars($couponCode); ?></span></p>
                    </div>
                    <?php endif; ?>

                    <?php if ($discountAmount > 0): ?>
                    <?php if ($originalAmount > 0): ?>
                    <div class="col-md-6 mb-3">
                        <p class="mb-1"><strong>Original Amount:</strong></p>
                        <p class="mb-0"><?php echo $currency->format($originalAmount); ?></p>
                    </div>
                    <?php endif; ?>

                    <div class="col-md-6 mb-3">
                        <p class="mb-1"><strong>Discount:</strong></p>
                        <p class="mb-0 text-danger">
                            -<?php echo $currency->format($discountAmount); ?>
                            <?php if ($discountPercentText !== ''): ?>
                                <span class="text-muted">(<?php echo htmlspecialchars($discountPercentText); ?>%)</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Service Details -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-concierge-bell"></i> Service Details</h5>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <?php if ($booking['service_image']): ?>
                    <div class="col-md-3">
                        <img src="../../assets/uploads/services/<?php echo htmlspecialchars($booking['service_image']); ?>" 
                             alt="Service" class="img-fluid rounded">
                    </div>
                    <?php endif; ?>
                    <div class="<?php echo $booking['service_image'] ? 'col-md-9' : 'col-12'; ?>">
                        <h4><?php echo htmlspecialchars($booking['service_name']); ?></h4>
                        <?php if ($booking['service_duration']): ?>
                        <p class="mb-1"><i class="far fa-clock"></i> Duration: <?php echo $booking['service_duration']; ?> minutes</p>
                        <?php endif; ?>
                        <p class="mb-0"><i class="fas fa-tag"></i> Price: <?php echo $currency->format($booking['service_price']); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Customer Notes -->
        <?php if ($booking['notes']): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-comment"></i> Customer Notes</h5>
            </div>
            <div class="card-body">
                <p class="mb-0"><?php echo nl2br(htmlspecialchars($booking['notes'])); ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Admin Notes -->
        <?php if ($booking['admin_notes']): ?>
        <div class="card mb-4 border-info">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-sticky-note"></i> Admin Notes</h5>
            </div>
            <div class="card-body">
                <p class="mb-0"><?php echo nl2br(htmlspecialchars($booking['admin_notes'])); ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-md-4">
        <!-- Customer Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-user"></i> Customer Information</h5>
            </div>
            <div class="card-body">
                <p class="mb-2"><strong>Name:</strong></p>
                <p><?php echo htmlspecialchars($booking['customer_name']); ?></p>
                
                <p class="mb-2 mt-3"><strong>Email:</strong></p>
                <p><a href="mailto:<?php echo htmlspecialchars($booking['customer_email']); ?>">
                    <?php echo htmlspecialchars($booking['customer_email']); ?>
                </a></p>
                
                <p class="mb-2 mt-3"><strong>Phone:</strong></p>
                <p><a href="tel:<?php echo htmlspecialchars($booking['customer_phone']); ?>">
                    <?php echo htmlspecialchars($booking['customer_phone']); ?>
                </a></p>
                
                <?php if ($booking['user_id']): ?>
                <p class="mb-2 mt-3"><strong>Registered User:</strong></p>
                <p>
                    <i class="fas fa-check-circle text-success"></i> Yes<br>
                    <small class="text-muted"><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></small>
                </p>
                <?php else: ?>
                <p class="mb-2 mt-3"><strong>Registered User:</strong></p>
                <p><i class="fas fa-times-circle text-muted"></i> Guest Booking</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-cog"></i> Actions</h5>
            </div>
            <div class="card-body">
                <?php if ($booking['status'] === 'pending'): ?>
                <a href="confirm.php?id=<?php echo $booking['id']; ?>" class="btn btn-success w-100 mb-2">
                    <i class="fas fa-check"></i> Confirm Booking
                </a>
                <?php endif; ?>
                
                <?php if ($booking['status'] === 'confirmed'): ?>
                <form method="POST" class="mb-2">
                    <input type="hidden" name="action" value="complete">
                    <button type="submit" class="btn btn-primary w-100" 
                            onclick="return confirm('Mark this booking as completed?')">
                        <i class="fas fa-check-double"></i> Mark as Completed
                    </button>
                </form>
                <?php endif; ?>
                
                <?php if (in_array($booking['status'], ['pending', 'confirmed'])): ?>
                <form method="POST" class="mb-2">
                    <input type="hidden" name="action" value="cancel">
                    <button type="submit" class="btn btn-danger w-100" 
                            onclick="return confirm('Are you sure you want to cancel this booking?')">
                        <i class="fas fa-times"></i> Cancel Booking
                    </button>
                </form>
                <?php endif; ?>
                
                <a href="edit.php?id=<?php echo $booking['id']; ?>" class="btn btn-outline-secondary w-100 mb-2">
                    <i class="fas fa-edit"></i> Edit Booking
                </a>
                
                <a href="index.php" class="btn btn-outline-secondary w-100">
                    <i class="fas fa-list"></i> Back to List
                </a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
