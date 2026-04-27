<?php
require_once 'config.php';
require_once 'includes/ServiceBooking.php';

$pageTitle = __('service_payment', 'Service Payment');

// Initialize service booking class
$serviceBooking = new ServiceBooking($db);

// Get and validate payment token
$token = $_GET['token'] ?? '';
$booking = null;
$token_valid = false;

if ($token) {
    try {
        // Decode token: booking_number:booking_id:hash
        $decoded = base64_decode($token);
        $parts = explode(':', $decoded);
        
        if (count($parts) === 3) {
            $booking_number = $parts[0];
            $booking_id = (int)$parts[1];
            $hash = $parts[2];
            
            // Get booking
            $booking = $serviceBooking->getBookingById($booking_id);
            
            // Validate token
            if ($booking && $booking['booking_number'] === $booking_number) {
                $expected_hash = md5($booking['created_at']);
                if ($hash === $expected_hash) {
                    $token_valid = true;
                }
            }
        }
    } catch (Exception $e) {
        $token_valid = false;
    }
}

// Also support legacy booking number parameter
if (!$token_valid && isset($_GET['booking'])) {
    $booking_number = $_GET['booking'];
    $booking = $serviceBooking->getBookingByNumber($booking_number);
    $token_valid = ($booking !== null);
}

// Redirect if invalid token
if (!$token_valid || !$booking) {
    $_SESSION['error'] = __('invalid_payment_link', 'Invalid or expired payment link');
    header('Location: ' . BASE_URL . 'services.php');
    exit;
}

// Check if booking is confirmed and payment is pending
if ($booking['status'] !== 'confirmed' || $booking['payment_status'] !== 'pending') {
    $_SESSION['error'] = __('payment_not_required', 'This booking does not require payment or payment has already been processed');
    header('Location: booking-confirmation.php?booking=' . urlencode($booking['booking_number']));
    exit;
}

// Handle payment processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'] ?? '';
    
    if ($payment_method) {
        // Process payment (simplified for demo)
        $payment_data = [
            'payment_method' => $payment_method,
            'payment_gateway' => 'stripe', // or other gateway
            'transaction_id' => 'TXN_' . time() . '_' . $booking['id'],
            'currency' => 'USD'
        ];
        
        $result = $serviceBooking->processPayment($booking['id'], $payment_data);
        
        if ($result['success']) {
            $_SESSION['success'] = __('payment_successful', 'Payment processed successfully! Your booking is now confirmed.');
            header('Location: booking-confirmation.php?booking=' . urlencode($booking['booking_number']));
            exit;
        } else {
            $_SESSION['error'] = $result['error'] ?? __('payment_failed', 'Payment failed. Please try again.');
        }
    }
}

include 'includes/header.php';
?>

<style>
.payment-method-card {
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.payment-method-card:hover {
    border-color: <?php echo $primary_color; ?>;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.payment-method-card.selected {
    border-color: <?php echo $primary_color; ?>;
    background-color: <?php echo $primary_color; ?>08;
}

.payment-method-card input[type="radio"] {
    position: absolute;
    opacity: 0;
}

.payment-method-card .checkmark {
    position: absolute;
    top: 15px;
    right: 15px;
    width: 24px;
    height: 24px;
    border: 2px solid #ddd;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
}

.payment-method-card.selected .checkmark {
    border-color: <?php echo $primary_color; ?>;
    background-color: <?php echo $primary_color; ?>;
}

.payment-method-card.selected .checkmark::after {
    content: '✓';
    color: white;
    font-weight: bold;
}

.payment-icon {
    font-size: 2.5rem;
    margin-bottom: 15px;
    color: <?php echo $primary_color; ?>;
}

.security-badges {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-top: 20px;
}

.security-badge {
    padding: 5px 10px;
    background: #f8f9fa;
    border-radius: 5px;
    font-size: 0.85rem;
    color: #6c757d;
}

.payment-summary {
    background: linear-gradient(135deg, <?php echo $primary_color; ?>15 0%, <?php echo $success_color; ?>15 100%);
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
}
</style>

<!-- Page Header -->
<section class="page-header py-5" style="background: linear-gradient(135deg, <?php echo $primary_color; ?> 0%, <?php echo $info_color; ?> 100%);">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center text-white">
                <i class="fas fa-lock fa-3x mb-3"></i>
                <h1 class="display-4 mb-3"><?php echo __('secure_payment', 'Secure Payment'); ?></h1>
                <p class="lead"><?php echo __('payment_subtitle', 'Complete your booking with our secure payment system'); ?></p>
            </div>
        </div>
    </div>
</section>

<section class="payment-section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Payment Summary -->
                <div class="payment-summary">
                    <h4 class="mb-4">
                        <i class="fas fa-receipt me-2"></i><?php echo __('booking_summary', 'Booking Summary'); ?>
                    </h4>
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h5><?php echo htmlspecialchars($booking['service_name']); ?></h5>
                            <p class="mb-1">
                                <i class="fas fa-calendar me-2"></i>
                                <?php echo date('l, F j, Y', strtotime($booking['booking_date'])); ?>
                            </p>
                            <p class="mb-1">
                                <i class="fas fa-clock me-2"></i>
                                <?php echo date('g:i A', strtotime($booking['booking_time'])); ?>
                            </p>
                            <p class="mb-0">
                                <i class="fas fa-user me-2"></i>
                                <?php echo htmlspecialchars($booking['customer_name']); ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="fs-2 fw-bold text-primary">
                                <?php echo $currency->format($booking['total_amount']); ?>
                            </div>
                            <small class="text-muted"><?php echo __('total_amount', 'Total Amount'); ?></small>
                        </div>
                    </div>
                </div>

                <!-- Payment Form -->
                <form method="POST" id="paymentForm">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-credit-card me-2"></i>
                                <?php echo __('select_payment_method', 'Select Payment Method'); ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Credit/Debit Card -->
                            <div class="payment-method-card" onclick="selectPaymentMethod('card')">
                                <input type="radio" name="payment_method" value="card" id="payment_card" required>
                                <div class="checkmark"></div>
                                <div class="text-center">
                                    <div class="payment-icon">
                                        <i class="fas fa-credit-card"></i>
                                    </div>
                                    <h6><?php echo __('credit_debit_card', 'Credit/Debit Card'); ?></h6>
                                    <p class="text-muted small mb-0"><?php echo __('visa_mastercard_amex', 'Visa, Mastercard, American Express'); ?></p>
                                </div>
                            </div>

                            <!-- PayPal -->
                            <div class="payment-method-card" onclick="selectPaymentMethod('paypal')">
                                <input type="radio" name="payment_method" value="paypal" id="payment_paypal">
                                <div class="checkmark"></div>
                                <div class="text-center">
                                    <div class="payment-icon">
                                        <i class="fab fa-paypal"></i>
                                    </div>
                                    <h6>PayPal</h6>
                                    <p class="text-muted small mb-0"><?php echo __('pay_with_paypal', 'Pay with your PayPal account'); ?></p>
                                </div>
                            </div>

                            <!-- Cash on Delivery -->
                            <div class="payment-method-card" onclick="selectPaymentMethod('cod')">
                                <input type="radio" name="payment_method" value="cod" id="payment_cod">
                                <div class="checkmark"></div>
                                <div class="text-center">
                                    <div class="payment-icon">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                    <h6><?php echo __('cash_on_delivery', 'Cash on Service'); ?></h6>
                                    <p class="text-muted small mb-0"><?php echo __('pay_at_service', 'Pay when you arrive for your service'); ?></p>
                                </div>
                            </div>

                            <!-- Bank Transfer -->
                            <div class="payment-method-card" onclick="selectPaymentMethod('bank_transfer')">
                                <input type="radio" name="payment_method" value="bank_transfer" id="payment_bank">
                                <div class="checkmark"></div>
                                <div class="text-center">
                                    <div class="payment-icon">
                                        <i class="fas fa-university"></i>
                                    </div>
                                    <h6><?php echo __('bank_transfer', 'Bank Transfer'); ?></h6>
                                    <p class="text-muted small mb-0"><?php echo __('direct_bank_transfer', 'Direct bank transfer'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Security Information -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body text-center">
                            <h6 class="mb-3"><?php echo __('secure_payment_processing', 'Secure Payment Processing'); ?></h6>
                            <div class="security-badges justify-content-center">
                                <span class="security-badge">
                                    <i class="fas fa-lock me-1"></i><?php echo __('ssl_encrypted', 'SSL Encrypted'); ?>
                                </span>
                                <span class="security-badge">
                                    <i class="fas fa-shield-alt me-1"></i><?php echo __('pci_compliant', 'PCI Compliant'); ?>
                                </span>
                                <span class="security-badge">
                                    <i class="fas fa-check-circle me-1"></i><?php echo __('fraud_protection', 'Fraud Protection'); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="text-center">
                        <button type="submit" class="btn btn-success btn-lg px-5" id="payButton" disabled>
                            <i class="fas fa-lock me-2"></i>
                            <?php echo __('complete_payment', 'Complete Payment'); ?> 
                            <?php echo $currency->format($booking['total_amount']); ?>
                        </button>
                        <p class="text-muted small mt-3">
                            <i class="fas fa-info-circle me-1"></i>
                            <?php echo __('payment_confirmation_note', 'By clicking "Complete Payment", you agree to our terms and conditions'); ?>
                        </p>
                    </div>
                </form>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Booking Details -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><?php echo __('booking_details', 'Booking Details'); ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted"><?php echo __('booking_number', 'Booking Number'); ?></small>
                            <div class="fw-bold"><?php echo htmlspecialchars($booking['booking_number']); ?></div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted"><?php echo __('service', 'Service'); ?></small>
                            <div class="fw-bold"><?php echo htmlspecialchars($booking['service_name']); ?></div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted"><?php echo __('date_time', 'Date & Time'); ?></small>
                            <div class="fw-bold">
                                <?php echo date('M j, Y', strtotime($booking['booking_date'])); ?> at 
                                <?php echo date('g:i A', strtotime($booking['booking_time'])); ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted"><?php echo __('customer', 'Customer'); ?></small>
                            <div class="fw-bold"><?php echo htmlspecialchars($booking['customer_name']); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Help Section -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-question-circle me-2"></i><?php echo __('need_help', 'Need Help?'); ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted"><?php echo __('payment_help_text', 'If you have any questions about payment or encounter any issues, our support team is here to help.'); ?></p>
                        <div class="d-grid gap-2">
                            <a href="<?php echo BASE_URL; ?>contact.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-envelope me-2"></i><?php echo __('contact_support', 'Contact Support'); ?>
                            </a>
                            <a href="tel:+1234567890" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-phone me-2"></i><?php echo __('call_support', 'Call Support'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function selectPaymentMethod(method) {
    // Remove selected class from all cards
    document.querySelectorAll('.payment-method-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    // Add selected class to clicked card
    event.currentTarget.classList.add('selected');
    
    // Check the radio button
    document.getElementById('payment_' + method).checked = true;
    
    // Enable pay button
    document.getElementById('payButton').disabled = false;
}

// Form submission with loading state
document.getElementById('paymentForm').addEventListener('submit', function(e) {
    const payButton = document.getElementById('payButton');
    const originalText = payButton.innerHTML;
    
    // Show loading state
    payButton.disabled = true;
    payButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i><?php echo __('processing_payment', 'Processing Payment...'); ?>';
    
    // Submit form normally
    // The form will submit and the page will reload with success/error message
});

// Auto-select first payment method on page load
document.addEventListener('DOMContentLoaded', function() {
    const firstCard = document.querySelector('.payment-method-card');
    if (firstCard) {
        firstCard.click();
    }
});
</script>

<?php include 'includes/footer.php'; ?>
