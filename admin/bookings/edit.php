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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $update_data = [
        'customer_name' => trim($_POST['customer_name'] ?? ''),
        'customer_email' => trim($_POST['customer_email'] ?? ''),
        'customer_phone' => trim($_POST['customer_phone'] ?? ''),
        'booking_date' => trim($_POST['booking_date'] ?? ''),
        'booking_time' => trim($_POST['booking_time'] ?? ''),
        'total_amount' => floatval($_POST['total_amount'] ?? 0),
        'notes' => trim($_POST['notes'] ?? ''),
        'admin_notes' => trim($_POST['admin_notes'] ?? ''),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Validate required fields
    $errors = [];
    if (empty($update_data['customer_name'])) {
        $errors[] = 'Customer name is required';
    }
    if (empty($update_data['customer_email'])) {
        $errors[] = 'Customer email is required';
    }
    if (empty($update_data['customer_phone'])) {
        $errors[] = 'Customer phone is required';
    }
    if (empty($update_data['booking_date'])) {
        $errors[] = 'Booking date is required';
    }
    if (empty($update_data['booking_time'])) {
        $errors[] = 'Booking time is required';
    }
    
    if (empty($errors)) {
        try {
            $db->update('service_bookings', $update_data, 'id = :id', ['id' => $booking_id]);
            $_SESSION['success'] = 'Booking updated successfully';
            header('Location: view.php?id=' . $booking_id);
            exit;
        } catch (Exception $e) {
            $errors[] = 'Error updating booking: ' . $e->getMessage();
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
    }
}

$pageTitle = 'Edit Booking';
$status_badges = [
    'pending' => 'warning',
    'confirmed' => 'info',
    'completed' => 'success',
    'cancelled' => 'danger'
];

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <a href="view.php?id=<?php echo $booking_id; ?>" class="text-decoration-none text-muted"><i class="fas fa-arrow-left"></i></a>
        Edit Booking
    </h1>
    <div class="btn-toolbar">
        <a href="view.php?id=<?php echo $booking_id; ?>" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-eye"></i> View Booking
        </a>
    </div>
</div>

<?php if (isset($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <form method="POST">
            <!-- Basic Information -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Basic Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="customer_name" class="form-label">Customer Name *</label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name" 
                                   value="<?php echo htmlspecialchars($booking['customer_name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="customer_email" class="form-label">Customer Email *</label>
                            <input type="email" class="form-control" id="customer_email" name="customer_email" 
                                   value="<?php echo htmlspecialchars($booking['customer_email']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="customer_phone" class="form-label">Customer Phone *</label>
                            <input type="tel" class="form-control" id="customer_phone" name="customer_phone" 
                                   value="<?php echo htmlspecialchars($booking['customer_phone']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="total_amount" class="form-label">Total Amount *</label>
                            <div class="input-group">
                                <span class="input-group-text"><?php echo htmlspecialchars($currency->getSymbol()); ?></span>
                                <input type="number" class="form-control" id="total_amount" name="total_amount" 
                                       value="<?php echo htmlspecialchars($booking['total_amount']); ?>" step="0.01" min="0" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Booking Schedule -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar"></i> Booking Schedule</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="booking_date" class="form-label">Booking Date *</label>
                            <input type="date" class="form-control" id="booking_date" name="booking_date" 
                                   value="<?php echo htmlspecialchars($booking['booking_date']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="booking_time" class="form-label">Booking Time *</label>
                            <input type="time" class="form-control" id="booking_time" name="booking_time" 
                                   value="<?php echo htmlspecialchars(date('H:i', strtotime($booking['booking_time']))); ?>" required>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Notes -->
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-comment"></i> Notes</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="notes" class="form-label">Customer Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($booking['notes']); ?></textarea>
                        <small class="text-muted">Notes provided by the customer during booking</small>
                    </div>
                    <div class="mb-3">
                        <label for="admin_notes" class="form-label">Admin Notes</label>
                        <textarea class="form-control" id="admin_notes" name="admin_notes" rows="3"><?php echo htmlspecialchars($booking['admin_notes'] ?? ''); ?></textarea>
                        <small class="text-muted">Internal notes for admin use only</small>
                    </div>
                </div>
            </div>
            
            <!-- Service Information (Read-only) -->
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-concierge-bell"></i> Service Information</h5>
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
                            <small class="text-muted d-block mt-2">
                                <i class="fas fa-info-circle"></i> Service details cannot be edited here
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <a href="view.php?id=<?php echo $booking_id; ?>" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <div>
                            <button type="reset" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    
    <div class="col-md-4">
        <!-- Current Status -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-info-circle"></i> Current Status</h5>
            </div>
            <div class="card-body">
                <div class="text-center">
                    <span class="badge bg-<?php echo $status_badges[$booking['status']]; ?> fs-6 p-2">
                        <?php echo ucfirst($booking['status']); ?>
                    </span>
                    <p class="text-muted mt-2 mb-0">
                        Booking #<?php echo htmlspecialchars($booking['booking_number']); ?>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <a href="view.php?id=<?php echo $booking_id; ?>" class="btn btn-outline-primary w-100 mb-2">
                    <i class="fas fa-eye"></i> View Full Details
                </a>
                <a href="index.php" class="btn btn-outline-secondary w-100">
                    <i class="fas fa-list"></i> Back to Bookings
                </a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
