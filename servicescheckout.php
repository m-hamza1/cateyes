<?php
require_once 'config.php';
require_once 'includes/ServiceBooking.php';
require_once 'includes/MultiServiceBooking.php';
require_once 'includes/CouponManager.php';

$pageTitle = __('service_checkout', 'Service Checkout');

// Initialize service booking classes
$serviceBooking = new ServiceBooking($db);
$multiServiceBooking = new MultiServiceBooking($db);

// Get cart items
$cartItems = $multiServiceBooking->getDetailedCartItems();
$cartTotal = $multiServiceBooking->getCartTotal();
$cartCount = $multiServiceBooking->getCartCount();

// Redirect if cart is empty
if (empty($cartItems)) {
    $_SESSION['error'] = __('no_cart_items', 'No items in cart');
    header('Location: ' . BASE_URL . 'services.php');
    exit;
}

// Get user data if logged in - FIXED
$user = null;
$userId = null;
$user_data = [];

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $user = $db->fetchOne(
        "SELECT * FROM users WHERE id = :id AND is_active = 1",
        ['id' => $userId]
    );
    
    if ($user) {
        $user_data = [
            'name' => trim($user['first_name'] . ' ' . $user['last_name']),
            'email' => $user['email'],
            'phone' => $user['phone'] ?? ''
        ];
    }
}

// Get customer information from previous page (service-payment.php)
$customer_name = trim($_POST['customer_name'] ?? $user_data['name'] ?? '');
$customer_email = trim($_POST['customer_email'] ?? $user_data['email'] ?? '');
$customer_phone = trim($_POST['customer_phone'] ?? $user_data['phone'] ?? '');
$customer_notes = trim($_POST['customer_notes'] ?? '');

// Get coupon code from previous page
$applied_coupon_code = trim($_POST['coupon_code'] ?? '');

// Calculate final amount with coupon if applied
$finalAmount = $cartTotal;
$originalAmount = $cartTotal;
$couponData = null;

if (!empty($applied_coupon_code)) {
    $couponManager = new CouponManager($db, $user); // $user can be null
    $couponResult = $couponManager->applyCoupon($applied_coupon_code, $originalAmount);
    
    if ($couponResult['success']) {
        $couponData = $couponResult;
        $finalAmount = $couponResult['final_amount'];
    }
}

// Get enabled payment methods from settings (only these are shown - admin controls in Settings > Payment)
$payment_cod_enabled = $settings->get('payment_cod_enabled', '0');
$payment_stripe_enabled = $settings->get('payment_stripe_enabled', '0');
$payment_paypal_enabled = $settings->get('payment_paypal_enabled', '0');
$payment_googlepay_enabled = $settings->get('payment_googlepay_enabled', '0');
$payment_applepay_enabled = $settings->get('payment_applepay_enabled', '0');
$payment_networks_enabled = $settings->get('payment_networks_enabled', '0');
$payment_tabby_enabled = $settings->get('payment_tabby_enabled', '0');

// Sub-card types (default to enabled for compatibility)
$payment_card_visa_enabled = $settings->get('payment_card_visa_enabled', '1') == '1';
$payment_card_mastercard_enabled = $settings->get('payment_card_mastercard_enabled', '1') == '1';
$payment_card_tabby_enabled = $settings->get('payment_card_tabby_enabled', '1') == '1';
$payment_card_networks_enabled = $settings->get('payment_card_networks_enabled', '1') == '1';

// Build list of enabled payment method values for validation
require_once INCLUDES_PATH . 'Payment.php';
$payment = new Payment($db);
$paymentMethods = $payment->getPaymentMethods();
$enabled_payment_methods = array_keys($paymentMethods);

// Map "card" to "credit_card" if that's what the frontend expects or vice versa
// Actually, checkout.php uses 'credit_card', 'networks', etc.
// But Payment::process now handles 'card' as well.
// We'll use the keys from getPaymentMethods() directly.
$has_any_payment_method = !empty($enabled_payment_methods);

// Process payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'process_payment') {
        $payment_method = $_POST['payment_method'] ?? '';
        
        if (empty($enabled_payment_methods) || !in_array($payment_method, $enabled_payment_methods)) {
            // Check for alias 'card'
            if ($payment_method !== 'card' || !in_array('credit_card', $enabled_payment_methods) && !in_array('networks', $enabled_payment_methods)) {
                $error = __('invalid_payment_method', 'Please select a valid payment method.');
            }
        }
        
        if (!isset($error)) {
        // Get customer information
        $customer_name = trim($_POST['customer_name'] ?? '');
        $customer_email = trim($_POST['customer_email'] ?? '');
        $customer_phone = trim($_POST['customer_phone'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        
        // Re-validate coupon on payment submission
        $couponCode = trim($_POST['coupon_code'] ?? '');
        $couponData = null;
        $totalDiscount = 0;
        
        if (!empty($couponCode)) {
            $couponManager = new CouponManager($db, $user);
            $couponResult = $couponManager->applyCoupon($couponCode, $cartTotal);
            
            if ($couponResult['success']) {
                $couponData = $couponResult;
                $totalDiscount = $couponResult['discount'];
                error_log("DEBUG: Coupon validated - Code: {$couponCode}, Discount: {$totalDiscount}");
            } else {
                error_log("DEBUG: Coupon validation failed - Code: {$couponCode}");
            }
        }
        
        // Validate customer information
        if (empty($customer_name) || empty($customer_email)) {
            $error = __('customer_info_required', 'Customer name and email are required');
        } else {
            // Start transaction for atomic booking creation
            $db->beginTransaction();
            
            try {
                $bookingIds = [];
                $allBookingNumbers = [];
                $timestamp = time();
                $randSuffix = mt_rand(100, 999);
                
                $discountPerItem = [];
                if ($totalDiscount > 0 && $couponData) {
                    foreach ($cartItems as $item) {
                        $itemPercentage = $item['total_price'] / $cartTotal;
                        $discountPerItem[$item['id']] = round($totalDiscount * $itemPercentage, 2);
                    }
                }
                
                $is_online_payment = in_array($payment_method, ['card', 'networks', 'tabby', 'paypal', 'googlepay', 'applepay', 'stripe']);
                $totalCartAmount = 0;

                foreach ($cartItems as $index => $item) {
                    $current_booking_number = 'BK-' . $timestamp . '-' . $randSuffix . '-' . ($index + 1);
                    $allBookingNumbers[] = $current_booking_number;
                    
                    $itemDiscount = isset($discountPerItem[$item['id']]) ? $discountPerItem[$item['id']] : 0;
                    $itemFinalAmount = $item['total_price'] - $itemDiscount;
                    $totalCartAmount += $itemFinalAmount;
                    
                    $booking_data = [
                        'service_id' => $item['service_id'],
                        'user_id' => $userId, 
                        'customer_name' => $customer_name,
                        'customer_email' => $customer_email,
                        'customer_phone' => $customer_phone,
                        'booking_date' => $item['date'],
                        'booking_time' => $item['time'],
                        'time_slot_id' => $item['time_slot_id'],
                        'notes' => $notes,
                        'total_amount' => $itemFinalAmount,
                        'booking_number' => $current_booking_number,
                        'status' => $is_online_payment ? 'pending' : 'confirmed',
                        'payment_status' => 'pending',
                        'payment_method' => $payment_method,
                        'currency' => $currency->getCurrentCode(),
                        'confirmed_at' => !$is_online_payment ? date('Y-m-d H:i:s') : null,
                        'silent' => true,
                        'silent_admin' => true
                    ];

                    if ($couponData && $itemDiscount > 0) {
                        $coupon = $couponData['coupon'] ?? [];
                        $booking_data['coupon_code'] = $couponCode;
                        $booking_data['coupon_discount_amount'] = (float)$itemDiscount;
                        $booking_data['coupon_discount_type'] = $coupon['type'] ?? null;
                        $booking_data['original_amount'] = (float)$item['total_price'];
                    }
                    
                    $booking_result = $serviceBooking->createBooking($booking_data);
                    
                    if ($booking_result && $booking_result['success']) {
                        $booking_id = $booking_result['booking_id'];
                        $bookingIds[] = $booking_id;
                        
                        if ($couponData && $itemDiscount > 0) {
                            $db->insert('coupon_usage', [
                                'coupon_id' => $couponData['coupon']['id'],
                                'user_id' => $userId,
                                'booking_id' => $booking_id,
                                'discount_amount' => $itemDiscount,
                                'used_at' => date('Y-m-d H:i:s')
                            ]);
                        }
                    } else {
                        throw new Exception($booking_result['error'] ?? 'Failed to create booking for item ' . ($index + 1));
                    }
                }
                
                $db->commit();
                
                if ($couponData) {
                    $db->query("UPDATE coupons SET usage_count = usage_count + 1 WHERE id = :id", ['id' => $couponData['coupon']['id']]);
                }

                // Handle Online Payment Redirection
                if ($is_online_payment && !empty($bookingIds)) {
                    require_once INCLUDES_PATH . 'Payment.php';
                    $payment = new Payment($db);
                    
                    // We use the first booking as the primary reference for the payment session
                    $processResult = $payment->process($bookingIds[0], $payment_method, [], 'booking');
                    
                    if ($processResult['success']) {
                        $paymentId = $processResult['payment_id'] ?? null;
                        if ($paymentId) {
                            foreach ($bookingIds as $bid) {
                                $db->update('service_bookings', ['payment_id' => $paymentId], 'id = :id', ['id' => $bid]);
                            }
                        }
                        
                        if (isset($processResult['redirect_url'])) {
                            $multiServiceBooking->clearCart();
                            header('Location: ' . $processResult['redirect_url']);
                            exit;
                        }
                    } else {
                        $error = $processResult['message'] ?? 'Payment initialization failed.';
                    }
                }

                if (!isset($error)) {
                    // Send confirmations for non-online or successful process
                    if (!empty($bookingIds)) {
                        if (count($bookingIds) > 1) {
                            $serviceBooking->sendMultiBookingConfirmation($bookingIds);
                            $serviceBooking->notifyAdminMultiBooking($bookingIds);
                        } else {
                            $serviceBooking->sendConfirmationEmail(['id' => $bookingIds[0]]); 
                            $serviceBooking->notifyAdminNewBooking($bookingIds[0]);
                        }
                    }

                    $multiServiceBooking->clearCart();
                    
                    if (count($allBookingNumbers) > 1) {
                        $bookingDetails = sprintf(__('multiple_bookings_success', 'Successful! Numbers: %s'), implode(', ', $allBookingNumbers));
                    } else {
                        $bookingDetails = sprintf(__('booking_success_details', 'Successful! Number: %s'), $allBookingNumbers[0]);
                    }
                    
                    $_SESSION['success'] = $bookingDetails;
                    $_SESSION['booking_number'] = $allBookingNumbers[0];
                    header('Location: ' . BASE_URL . 'services.php');
                    exit;
                }

            } catch (Exception $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                $error = __('booking_creation_failed', 'Failed to create bookings') . ": " . $e->getMessage();
                error_log("BOOKING ERROR: " . $e->getMessage());
            }
        }
    }
}
}

include 'includes/header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="bg-light py-3">
    <div class="container">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>"><?php echo __('home', 'Home'); ?></a></li>
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>services.php"><?php echo __('services', 'Services'); ?></a></li>
            <li class="breadcrumb-item active"><?php echo __('checkout', 'Checkout'); ?></li>
        </ol>
    </div>
</nav>

<!-- Page Header -->
<section class="py-5 bg-primary text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-12 text-center">
                <h1 class="display-4 mb-3"><?php echo __('checkout', 'Checkout'); ?></h1>
                <p class="lead mb-0"><?php echo __('checkout_description', 'Review your booking details and complete payment'); ?></p>
            </div>
        </div>
    </div>
</section>

<section class="checkout-section py-5 bg-white">
    <div class="container">
        <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Booking Summary -->
            <div class="col-lg-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header text-white" style="background-color: <?php echo $primary_color; ?>;">
                        <h4 class="mb-0">
                            <i class="fas fa-receipt me-2"></i><?php echo __('booking_summary', 'Booking Summary'); ?>
                        </h4>
                    </div>
                    <div class="card-body">
                        <!-- Cart Items -->
                        <div class="mb-3">
                            <?php foreach ($cartItems as $index => $item): ?>
                            <div class="alert alert-light border mb-2">
                                <div class="row align-items-center">
                                    <div class="col-md-2">
                                        <?php if ($item['service']['image']): ?>
                                        <img src="<?php echo BASE_URL; ?>assets/uploads/services/<?php echo htmlspecialchars($item['service']['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['service']['name']); ?>" 
                                             class="img-fluid rounded">
                                        <?php else: ?>
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 60px;">
                                            <i class="fas fa-spa text-muted"></i>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-7">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($item['service']['name']); ?></h6>
                                        <p class="text-muted mb-1 small">
                                            <i class="fas fa-calendar me-1"></i><?php echo $item['formatted_date']; ?>
                                            <i class="fas fa-clock ms-2 me-1"></i><?php echo $item['formatted_time']; ?>
                                        </p>
                                        <p class="text-muted mb-0 small">
                                            <i class="fas fa-hourglass-half me-1"></i><?php echo $item['service']['duration']; ?> minutes
                                        </p>
                                    </div>
                                    <div class="col-md-3 text-end">
                                        <div class="fw-bold text-primary"><?php echo $currency->format($item['total_price']); ?></div>
                                        <?php if ($item['quantity'] > 1): ?>
                                        <small class="text-muted">× <?php echo $item['quantity']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Price Summary -->
                        <div class="border rounded p-3 mb-3" style="background-color: <?php echo $primary_color; ?>15;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span><?php echo __('subtotal', 'Subtotal'); ?></span>
                                <span class="fw-bold"><?php echo $currency->format($cartTotal); ?></span>
                            </div>
                            <?php if ($couponData): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-success">
                                    <?php echo __('discount', 'Discount'); ?> 
                                    <span class="badge bg-success"><?php echo htmlspecialchars(strtoupper($applied_coupon_code)); ?></span>
                                </span>
                                <span class="text-success fw-bold">-<?php echo $currency->format($couponData['discount']); ?></span>
                            </div>
                            <?php endif; ?>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong><?php echo __('total_amount', 'Total Amount'); ?></strong>
                                <h4 class="mb-0 fw-bold" style="color: <?php echo $success_color; ?>;">
                                    <?php echo $currency->format($finalAmount); ?>
                                </h4>
                            </div>
                        </div>

                        <!-- Customer Information (Read-only) -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header text-white" style="background-color: <?php echo $primary_color; ?>;">
                                <h4 class="mb-0">
                                    <i class="fas fa-user me-2"></i><?php echo __('customer_information', 'Customer Information'); ?>
                                </h4>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small"><?php echo __('name', 'Name'); ?></label>
                                        <div class="fw-bold"><?php echo htmlspecialchars($customer_name); ?></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small"><?php echo __('email', 'Email'); ?></label>
                                        <div class="fw-bold"><?php echo htmlspecialchars($customer_email); ?></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small"><?php echo __('phone', 'Phone'); ?></label>
                                        <div class="fw-bold"><?php echo htmlspecialchars($customer_phone ?: '-'); ?></div>
                                    </div>
                                    <?php if ($customer_notes): ?>
                                    <div class="col-12">
                                        <label class="form-label text-muted small"><?php echo __('notes', 'Notes'); ?></label>
                                        <div><?php echo nl2br(htmlspecialchars($customer_notes)); ?></div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Applied Coupon Display -->
                        <?php if ($couponData): ?>
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-ticket-alt me-2 text-success"></i>
                                    <?php echo __('applied_coupon', 'Applied Coupon'); ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-success border-0 mb-0" style="background-color: #d4edda;">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-grow-1">
                                            <strong class="d-block mb-1">
                                                <i class="fas fa-tag me-2"></i><?php echo htmlspecialchars(strtoupper($applied_coupon_code)); ?>
                                            </strong>
                                            <div class="small text-muted">
                                                <?php 
                                                if ($couponData['coupon']['type'] === 'percentage') {
                                                    echo $couponData['coupon']['value'] . '% ' . __('discount_applied', 'discount applied');
                                                } else {
                                                    echo $currency->format($couponData['coupon']['value']) . ' ' . __('discount_applied', 'discount applied');
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                    <hr class="my-2">
                                    <div class="d-flex justify-content-between">
                                        <span class="small"><?php echo __('you_saved', 'You saved'); ?>:</span>
                                        <strong class="text-success"><?php echo $currency->format($couponData['discount']); ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Payment & Information -->
            <div class="col-lg-6">
                <!-- Payment Method Selection -->
                <form method="POST" id="checkoutForm">
                    <input type="hidden" name="action" value="process_payment">
                    <!-- Customer Data Hidden Fields -->
                    <input type="hidden" name="customer_name" value="<?php echo htmlspecialchars($customer_name); ?>">
                    <input type="hidden" name="customer_email" value="<?php echo htmlspecialchars($customer_email); ?>">
                    <input type="hidden" name="customer_phone" value="<?php echo htmlspecialchars($customer_phone); ?>">
                    <input type="hidden" name="notes" value="<?php echo htmlspecialchars($customer_notes); ?>">
                    <!-- Coupon Hidden Field - THIS IS CRITICAL -->
                    <input type="hidden" name="coupon_code" value="<?php echo htmlspecialchars($applied_coupon_code); ?>">
                    
                    <div class="card shadow-sm mb-3">
                        <div class="card-header bg-white">
                            <h4 class="mb-0">
                                <i class="fas fa-money-check-alt me-2" style="color: <?php echo $primary_color; ?>;"></i>
                                <?php echo __('select_payment_method', 'Select Payment Method'); ?>
                            </h4>
                        </div>
                        <div class="card-body">
                            <?php if (!$has_any_payment_method): ?>
                            <div class="alert alert-warning mb-3">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo __('no_payment_methods_enabled', 'No payment methods are currently enabled. Please contact the administrator or try again later.'); ?>
                            </div>
                            <a href="<?php echo BASE_URL; ?>service-payment.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-arrow-left me-2"></i><?php echo __('back_to_cart', 'Back to Cart'); ?>
                            </a>
                            <?php else: ?>
                            <div class="payment-methods-list">
                                <?php 
                                $first_method = true;
                                foreach ($paymentMethods as $method => $info): 
                                ?>
                                    <div class="payment-method-item mb-3">
                                        <input type="radio" name="payment_method" value="<?php echo $method; ?>" 
                                               id="pm_<?php echo $method; ?>" class="btn-check" <?php echo $first_method ? 'checked' : ''; ?> required>
                                        <label class="payment-gateway-card d-flex align-items-center p-3 border rounded-3 cursor-pointer transition-all" 
                                               for="pm_<?php echo $method; ?>">
                                            
                                            <div class="gateway-icon-wrapper me-3 d-flex align-items-center justify-content-center bg-light rounded-circle" style="width: 50px; height: 50px;">
                                                <?php if ($method === 'credit_card' || $method === 'card'): ?>
                                                    <i class="fas fa-credit-card text-primary fs-4"></i>
                                                <?php elseif ($method === 'networks'): ?>
                                                    <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/networks.svg" alt="Networks" style="width: 30px;">
                                                <?php elseif ($method === 'paypal'): ?>
                                                    <i class="fab fa-paypal text-primary fs-4"></i>
                                                <?php elseif ($method === 'cod' || $method === 'cash_on_delivery'): ?>
                                                    <i class="fas fa-money-bill-wave text-success fs-4"></i>
                                                <?php elseif ($method === 'tabby'): ?>
                                                    <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/tabby.svg" alt="Tabby" style="width: 30px;">
                                                <?php else: ?>
                                                    <i class="fas fa-wallet text-secondary fs-4"></i>
                                                <?php endif; ?>
                                            </div>

                                            <div class="gateway-info flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <strong class="h6 mb-0"><?php echo $info['name']; ?></strong>
                                                    <div class="method-badges d-flex gap-1">
                                                        <?php if ($method === 'credit_card' || $method === 'card'): ?>
                                                            <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/visa.svg" height="15" alt="Visa">
                                                            <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/mastercard-clean.svg" height="15" alt="Mastercard">
                                                        <?php elseif ($method === 'networks'): ?>
                                                            <span class="badge bg-primary-subtle text-primary small"><?php echo __('Fast & Secure'); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <small class="text-muted d-block mt-1"><?php echo $info['description']; ?></small>
                                            </div>
                                            
                                            <div class="selection-indicator ms-3">
                                                <div class="outer-circle border rounded-circle d-flex align-items-center justify-content-center" style="width: 20px; height: 20px;">
                                                    <div class="inner-dot rounded-circle bg-primary d-none" style="width: 10px; height: 10px;"></div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                <?php 
                                $first_method = false;
                                endforeach; 
                                ?>
                            </div>

                            <div id="payment-actions" class="mt-4">
                                <div class="text-center mt-2">
                                    <button type="submit" class="btn btn-lg text-white w-100 mb-2" 
                                            style="background-color: <?php echo $success_color; ?>;">
                                        <i class="fas fa-lock me-2"></i>
                                        <?php echo __('complete_payment', 'Complete Payment'); ?> - 
                                        <?php echo $currency->format($finalAmount); ?>
                                    </button>
                                    <a href="<?php echo BASE_URL; ?>service-payment.php" class="btn btn-outline-secondary w-100">
                                        <i class="fas fa-arrow-left me-2"></i><?php echo __('back_to_cart', 'Back to Cart'); ?>
                                    </a>
                                </div>

                                <div class="text-center mt-3">
                                    <small class="text-muted">
                                        <i class="fas fa-shield-alt me-1" style="color: <?php echo $success_color; ?>;"></i>
                                        <?php echo __('secure_payment', 'Your payment information is secure and encrypted'); ?>
                                    </small>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>

                <!-- Help Card -->
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="fas fa-info-circle me-2" style="color: <?php echo $info_color; ?>;"></i>
                            <?php echo __('payment_info', 'Payment Information'); ?>
                        </h6>
                        <ul class="list-unstyled small mb-0">
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                <?php echo __('secure_checkout', 'Secure checkout process'); ?>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                <?php echo __('instant_confirmation', 'Instant booking confirmation'); ?>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                <?php echo __('email_receipt', 'Email receipt sent'); ?>
                            </li>
                            <li class="mb-0">
                                <i class="fas fa-check text-success me-2"></i>
                                <?php echo __('flexible_payment', 'Multiple payment options'); ?>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Contact Card -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="fas fa-headset me-2" style="color: <?php echo $primary_color; ?>;"></i>
                            <?php echo __('need_help', 'Need Help?'); ?>
                        </h6>
                        <p class="card-text small mb-3">
                            <?php echo __('payment_help_text', 'If you have any questions about payment, please contact us.'); ?>
                        </p>
                        <a href="<?php echo BASE_URL; ?>contact.php" class="btn btn-sm btn-outline-primary w-100">
                            <i class="fas fa-phone me-1"></i><?php echo __('contact_us', 'Contact Us'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.cursor-pointer { cursor: pointer; }
.transition-all { transition: all 0.3s ease; }

.payment-gateway-card {
    border: 2px solid #eee !important;
    background-color: #fff;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.payment-gateway-card:hover {
    border-color: <?php echo $primary_color; ?> !important;
    background-color: #f8f9fa;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.btn-check:checked + .payment-gateway-card {
    border-color: <?php echo $primary_color; ?> !important;
    background-color: <?php echo $primary_color; ?>05;
}

.btn-check:checked + .payment-gateway-card .inner-dot {
    display: block !important;
}

.btn-check:checked + .payment-gateway-card .outer-circle {
    border-color: <?php echo $primary_color; ?> !important;
}

.gateway-icon-wrapper {
    background-color: #f8f9fa !important;
    transition: all 0.3s ease;
}

.btn-check:checked + .payment-gateway-card .gateway-icon-wrapper {
    background-color: #fff !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.method-badges img {
    opacity: 0.8;
    transition: opacity 0.3s ease;
}

.payment-gateway-card:hover .method-badges img {
    opacity: 1;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('checkoutForm').addEventListener('submit', function(e) {
        console.log('Form submitting with coupon code:', document.querySelector('input[name="coupon_code"]').value);
    });
});
</script>

<?php include 'includes/footer.php'; ?>