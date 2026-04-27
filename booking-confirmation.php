<?php
require_once 'config.php';
require_once 'includes/ServiceBooking.php';

$pageTitle = __('booking_confirmation', 'Booking Confirmation');

// Initialize service booking class
$serviceBooking = new ServiceBooking($db);

// Get booking number from URL
$booking_number = $_GET['booking'] ?? '';

// Get booking details
$booking = null;
if ($booking_number) {
    $booking = $serviceBooking->getBookingByNumber($booking_number);
}

// Redirect if no valid booking
if (!$booking) {
    $_SESSION['error'] = __('booking_not_found', 'Booking not found');
    header('Location: ' . BASE_URL . 'services.php');
    exit;
}

// Generate payment link if booking is confirmed and payment is pending
$payment_link = '';
if ($booking['status'] === 'confirmed' && $booking['payment_status'] === 'pending') {
    $payment_token = base64_encode($booking['booking_number'] . ':' . $booking['id'] . ':' . md5($booking['created_at']));
    $payment_link = BASE_URL . 'service-payment.php?token=' . urlencode($payment_token);
}

include 'includes/header.php';
?>

<!-- Page Header -->
<section class="page-header py-5" style="background: linear-gradient(135deg, <?php echo $success_color; ?> 0%, <?php echo $primary_color; ?> 100%);">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center text-white">
                <div class="success-icon mb-3">
                    <i class="fas fa-check-circle" style="font-size: 4rem; animation: scaleIn 0.5s ease-out;"></i>
                </div>
                <h1 class="display-4 mb-3"><?php echo __('booking_confirmed_title', 'Booking Confirmed!'); ?></h1>
                <p class="lead"><?php echo __('booking_confirmed_message', 'Thank you for your booking. We look forward to serving you.'); ?></p>
            </div>
        </div>
    </div>
</section>

<section class="confirmation-section py-5">
    <div class="container">
        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- Booking Details Card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header text-white text-center" style="background-color: <?php echo $primary_color; ?>;">
                        <h4 class="mb-2">
                            <i class="fas fa-receipt me-2"></i><?php echo __('booking_details', 'Booking Details'); ?>
                        </h4>
                        <h3 class="fw-bold mb-0"><?php echo htmlspecialchars($booking['booking_number']); ?></h3>
                    </div>
                    <div class="card-body">
                        <!-- Status Badge -->
                        <div class="text-center mb-4">
                            <?php
                            $status_colors = [
                                'pending' => $warning_color,
                                'confirmed' => $success_color,
                                'completed' => $info_color,
                                'cancelled' => $danger_color
                            ];
                            $status_color = $status_colors[$booking['status']] ?? $secondary_color;
                            $status_icons = [
                                'pending' => 'fa-clock',
                                'confirmed' => 'fa-check-circle',
                                'completed' => 'fa-check-double',
                                'cancelled' => 'fa-times-circle'
                            ];
                            $status_icon = $status_icons[$booking['status']] ?? 'fa-info-circle';
                            ?>
                            <span class="badge px-4 py-2" style="background-color: <?php echo $status_color; ?>; font-size: 1rem;">
                                <i class="fas <?php echo $status_icon; ?> me-2"></i>
                                <?php echo ucfirst($booking['status']); ?>
                            </span>
                            
                            <?php if ($booking['status'] === 'pending'): ?>
                            <div class="alert alert-warning mt-3">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong><?php echo __('pending_confirmation', 'Pending Confirmation'); ?></strong>
                                <p class="mb-0 small"><?php echo __('pending_message', 'Your booking is pending admin confirmation. You will receive an email once confirmed.'); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Service Display -->
                        <?php if ($booking['service_image']): ?>
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <img src="<?php echo BASE_URL; ?>assets/uploads/services/<?php echo htmlspecialchars($booking['service_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($booking['service_name']); ?>" 
                                     class="img-fluid rounded shadow-sm">
                            </div>
                            <div class="col-md-8">
                                <h4 class="mb-3" style="color: <?php echo $primary_color; ?>;">
                                    <?php echo htmlspecialchars($booking['service_name']); ?>
                                </h4>
                                <div class="d-flex gap-3 mb-3">
                                    <span class="badge bg-primary">
                                        <i class="fas fa-clock me-1"></i><?php echo $booking['service_duration']; ?> minutes
                                    </span>
                                    <span class="badge" style="background-color: <?php echo $success_color; ?>;">
                                        <i class="fas fa-tag me-1"></i><?php echo $currency->format($booking['service_price']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Appointment Details Grid -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="icon-box me-3" style="background-color: <?php echo $primary_color; ?>15;">
                                            <i class="fas fa-calendar-day fa-2x" style="color: <?php echo $primary_color; ?>;"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block"><?php echo __('appointment_date', 'Appointment Date'); ?></small>
                                            <strong><?php echo date('l, F j, Y', strtotime($booking['booking_date'])); ?></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="icon-box me-3" style="background-color: <?php echo $info_color; ?>15;">
                                            <i class="fas fa-clock fa-2x" style="color: <?php echo $info_color; ?>;"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block"><?php echo __('appointment_time', 'Appointment Time'); ?></small>
                                            <strong><?php echo date('g:i A', strtotime($booking['booking_time'])); ?></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="icon-box me-3" style="background-color: <?php echo $success_color; ?>15;">
                                            <i class="fas fa-user fa-2x" style="color: <?php echo $success_color; ?>;"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block"><?php echo __('customer_name', 'Customer Name'); ?></small>
                                            <strong><?php echo htmlspecialchars($booking['customer_name']); ?></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="icon-box me-3" style="background-color: <?php echo $warning_color; ?>15;">
                                            <i class="fas fa-envelope fa-2x" style="color: <?php echo $warning_color; ?>;"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block"><?php echo __('email', 'Email'); ?></small>
                                            <strong class="text-break"><?php echo htmlspecialchars($booking['customer_email']); ?></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="icon-box me-3" style="background-color: <?php echo $primary_color; ?>15;">
                                            <i class="fas fa-phone fa-2x" style="color: <?php echo $primary_color; ?>;"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block"><?php echo __('phone', 'Phone'); ?></small>
                                            <strong><?php echo htmlspecialchars($booking['customer_phone']); ?></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="icon-box me-3" style="background-color: <?php echo $success_color; ?>15;">
                                            <i class="fas fa-dollar-sign fa-2x" style="color: <?php echo $success_color; ?>;"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block"><?php echo __('total_amount', 'Total Amount'); ?></small>
                                            <strong class="fs-5" style="color: <?php echo $success_color; ?>;">
                                                <?php echo $currency->format($booking['total_amount']); ?>
                                            </strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Summary -->
                        <div class="border rounded p-4 mb-4" style="background-color: <?php echo $primary_color; ?>08;">
                            <h5 class="mb-3">
                                <i class="fas fa-file-invoice-dollar me-2"></i>
                                <?php echo __('payment_summary', 'Payment Summary'); ?>
                            </h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span><?php echo __('service_charge', 'Service Charge'); ?></span>
                                        <strong><?php echo $currency->format($booking['total_amount']); ?></strong>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span><?php echo __('payment_status', 'Payment Status'); ?>:</span>
                                        <strong class="text-<?php echo $booking['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                                            <i class="fas fa-<?php echo $booking['payment_status'] === 'paid' ? 'check-circle' : 'clock'; ?> me-1"></i>
                                            <?php echo ucfirst($booking['payment_status']); ?>
                                        </strong>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <strong class="fs-5"><?php echo __('total_paid', 'Total Amount'); ?></strong>
                                <span class="fs-4 fw-bold" style="color: <?php echo $success_color; ?>;">
                                    <?php echo $currency->format($booking['total_amount']); ?>
                                </span>
                            </div>
                        </div>

                        <?php if ($booking['notes']): ?>
                        <div class="alert alert-info">
                            <h6 class="alert-heading">
                                <i class="fas fa-sticky-note me-2"></i><?php echo __('your_notes', 'Your Notes'); ?>
                            </h6>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($booking['notes'])); ?></p>
                        </div>
                        <?php endif; ?>

                        <!-- Payment Section -->
                        <?php if ($booking['payment_status'] === 'pending' && $payment_link): ?>
                        <div class="alert alert-warning border-0 shadow-sm mb-4">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-credit-card fa-2x text-warning"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="alert-heading mb-2"><?php echo __('payment_required', 'Payment Required'); ?></h5>
                                    <p class="mb-3"><?php echo __('payment_message', 'Please complete your payment to confirm your booking. Multiple payment options are available.'); ?></p>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a href="<?php echo $payment_link; ?>" class="btn btn-success btn-lg">
                                            <i class="fas fa-lock me-2"></i><?php echo __('pay_now', 'Pay Now'); ?> <?php echo $currency->format($booking['total_amount']); ?>
                                        </a>
                                        <button type="button" class="btn btn-outline-secondary" onclick="showPaymentInfo()">
                                            <i class="fas fa-info-circle me-2"></i><?php echo __('payment_options', 'Payment Options'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php elseif ($booking['payment_status'] === 'paid'): ?>
                        <div class="alert alert-success border-0 shadow-sm mb-4">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check-circle fa-2x text-success"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="alert-heading mb-2"><?php echo __('payment_completed', 'Payment Completed'); ?></h5>
                                    <p class="mb-0"><?php echo __('payment_thank_you', 'Thank you! Your payment has been successfully processed.'); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="alert" style="background-color: <?php echo $info_color; ?>15; border-left: 4px solid <?php echo $info_color; ?>;">
                            <h5 class="alert-heading">
                                <i class="fas fa-list-check me-2"></i>
                                <?php echo __('next_steps', 'What Happens Next?'); ?>
                            </h5>
                            <ul class="mb-0">
                                <li><?php echo __('step_1', 'Our team will review your booking request shortly'); ?></li>
                                <li><?php echo __('step_2', 'You will receive a reminder call about booking information'); ?></li>
                                <li><?php echo __('step_3', 'Please save this email and your booking number for reference'); ?></li>
                                <li><?php echo __('step_4', 'If you need to reschedule, please contact us at least 24 hours in advance.'); ?></li>
                            </ul>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-2 justify-content-center flex-wrap mt-4">
                            <button onclick="window.print()" class="btn btn-outline-primary">
                                <i class="fas fa-print me-2"></i><?php echo __('print_confirmation', 'Print Confirmation'); ?>
                            </button>
                            <a href="<?php echo BASE_URL; ?>services.php" class="btn text-white" style="background-color: <?php echo $primary_color; ?>;">
                                <i class="fas fa-spa me-2"></i><?php echo __('back_to_services', 'Back to Services'); ?>
                            </a>
                            <?php if ($user): ?>
                            <a href="<?php echo BASE_URL; ?>account/mybookings.php" class="btn btn-outline-secondary">
                                <i class="fas fa-list me-2"></i><?php echo __('my_bookings', 'My Bookings'); ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Contact Information Card -->
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <h6 class="mb-3">
                            <i class="fas fa-headset me-2" style="color: <?php echo $primary_color; ?>;"></i>
                            <?php echo __('need_help', 'Need Help?'); ?>
                        </h6>
                        <p class="mb-3"><?php echo __('contact_help_text', 'If you have any questions or need assistance, feel free to contact us.'); ?></p>
                        <div class="d-flex gap-3 justify-content-center flex-wrap">
                            <a href="tel:<?php echo $contact_phone; ?>" class="text-decoration-none">
                                <i class="fas fa-phone me-1" style="color: <?php echo $primary_color; ?>;"></i>
                                <?php echo htmlspecialchars($contact_phone); ?>
                            </a>
                            <span class="text-muted">|</span>
                            <a href="mailto:<?php echo $contact_email; ?>" class="text-decoration-none">
                                <i class="fas fa-envelope me-1" style="color: <?php echo $primary_color; ?>;"></i>
                                <?php echo htmlspecialchars($contact_email); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
@keyframes scaleIn {
    0% { transform: scale(0); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.icon-box {
    width: 60px;
    height: 60px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

@media print {
    .no-print, .btn, nav, footer {
        display: none !important;
    }
    .page-header {
        background: white !important;
        color: black !important;
    }
    .card {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
    }
}
</style>

<?php include 'includes/footer.php'; ?>