<?php

require_once 'config.php';

require_once 'includes/ServiceBooking.php';

require_once 'includes/MultiServiceBooking.php';



$pageTitle = __('service_payment', 'Service Payment');



// Initialize service booking classes

$serviceBooking = new ServiceBooking($db);

$multiServiceBooking = new MultiServiceBooking($db);



// Get booking number from URL (for single booking compatibility)

$booking_number = $_GET['booking'] ?? '';



// Get booking details (for single booking compatibility)

$booking = null;

if ($booking_number) {

    $booking = $serviceBooking->getBookingByNumber($booking_number);

}



// Get cart items

$cartItems = $multiServiceBooking->getDetailedCartItems();

$cartTotal = $multiServiceBooking->getCartTotal();

$cartCount = $multiServiceBooking->getCartCount();



// Redirect if no valid booking and cart is empty

if (!$booking && empty($cartItems)) {

    $_SESSION['error'] = __('no_booking_or_cart', 'No bookings found');

    header('Location: ' . BASE_URL . 'services.php');

    exit;

}



// Redirect if already paid (for single booking)

if ($booking && $booking['payment_status'] === 'paid') {

    $_SESSION['success'] = __('already_paid', 'This booking has already been paid');

    header('Location: ' . BASE_URL . 'booking-confirmation.php?booking=' . $booking_number);

    exit;

}



// Get enabled payment methods from settings

$payment_cod_enabled = $settings->get('payment_cod_enabled', '0');

$payment_stripe_enabled = $settings->get('payment_stripe_enabled', '0');

$payment_paypal_enabled = $settings->get('payment_paypal_enabled', '0');

$payment_googlepay_enabled = $settings->get('payment_googlepay_enabled', '0');

$payment_applepay_enabled = $settings->get('payment_applepay_enabled', '0');



// Process payment

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'process_payment') {

        $payment_method = $_POST['payment_method'] ?? '';

        

        // Handle coupon processing

        $couponData = null;

        $finalAmount = !empty($cartItems) ? $cartTotal : $booking['total_amount'];

        $originalAmount = $finalAmount;

        

        // Check if coupon was applied

        if (!empty($_POST['coupon_code'])) {

            require_once 'includes/CouponManager.php';

            $couponManager = new CouponManager($db, $user);
            $displayCurr = $currency->getCurrentCode();
            $defaultCurr = defined('DEFAULT_CURRENCY') ? DEFAULT_CURRENCY : 'AED';
            $rateToDisplay = ($displayCurr !== $defaultCurr) ? (float)$currency->convert(1, $defaultCurr) : 1.0;
            $couponResult = $couponManager->applyCoupon($_POST['coupon_code'], $originalAmount, $displayCurr, $rateToDisplay);

            

            if ($couponResult['success']) {

                $couponData = $couponResult;

                $finalAmount = $couponResult['final_amount'];

            }

        }

        

        $payment_data = [

            'payment_method' => $payment_method,

            'payment_gateway' => $_POST['payment_gateway'] ?? $payment_method,

            'transaction_id' => 'TXN-' . time() . '-' . mt_rand(1000, 9999),

            'currency' => $currency->getCurrentCode(),

            'amount' => $finalAmount

        ];

        

        // Add additional payment method specific data

        if ($payment_method === 'card') {

            $payment_data['card_last4'] = substr($_POST['card_number'] ?? '', -4);

            $payment_data['card_brand'] = $_POST['card_brand'] ?? 'visa';

        }

        

        // Persist coupon/discount on booking BEFORE payment, so totals/emails are correct
        if ($couponData) {
            $couponManager->applyCouponToBooking($booking['id'], [
                'coupon' => $couponData['coupon'],
                'discount' => $couponData['discount'],
                'original_amount' => $originalAmount,
                'final_amount' => $finalAmount
            ]);
        }

        // Process payment

        $result = $serviceBooking->processPayment($booking['id'], $payment_data);

        

        if ($result['success']) {

            // Update coupon usage count only after successful payment
            if ($couponData && !empty($couponData['coupon']['id'])) {
                $couponManager->updateCouponUsage($couponData['coupon']['id']);
            }

            

            $_SESSION['success'] = __('payment_successful', 'Payment completed successfully! Waiting for admin confirmation.');

            header('Location: ' . BASE_URL . 'booking-confirmation.php?booking=' . $booking_number);

            exit;

        } else {

            $error = $result['error'];

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

            <li class="breadcrumb-item active"><?php echo __('booking_cart', 'Booking Cart'); ?></li>

        </ol>

    </div>

</nav>



<!-- Page Header -->

<section class="py-5 bg-primary text-white">

    <div class="container">

        <div class="row align-items-center">

            <div class="col-12 text-center">

                <h1 class="display-4 mb-3"><?php echo __('booking_cart', 'Booking Cart'); ?></h1>

                <p class="lead mb-0"><?php echo __('booking_cart_description', 'Confirm below details and move to checkout'); ?></p>

            </div>

        </div>

    </div>

</section>



<section class="payment-section py-5 bg-white">

    <div class="container">

        <?php if (isset($error)): ?>

        <div class="alert alert-danger alert-dismissible fade show">

            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>

        </div>

        <?php endif; ?>



        <div class="row">

            <!-- Booking Summary -->

            <div class="col-lg-8">

                <div class="card shadow-sm mb-4">

                    <div class="card-header text-white" style="background-color: <?php echo $primary_color; ?>;">

                        <h4 class="mb-0">

                            <i class="fas fa-receipt me-2"></i><?php echo __('booking_summary', 'Booking Summary'); ?>

                        </h4>

                    </div>

                    <div class="card-body">

                        <?php if (!empty($cartItems)): ?>

                        <!-- Multi-Service Cart Summary -->

                        <div class="text-center py-3 border-bottom mb-3">

                            <h5 class="mb-2" style="color: <?php echo $primary_color; ?>;">

                                <i class="fas fa-shopping-cart me-2"></i><?php echo __('booking_cart', 'Booking Cart'); ?>

                            </h5>

                            <h3 class="fw-bold"><?php echo $cartCount; ?> <?php echo __('services', 'Services'); ?></h3>

                            <span class="badge bg-success">

                                <i class="fas fa-clock me-1"></i><?php echo __('ready_to_pay', 'Ready to Pay'); ?>

                            </span>

                        </div>



                        <!-- Cart Items -->

                        <div class="mb-3">

                            <?php foreach ($cartItems as $index => $item): ?>

                            <div class="alert alert-light border mb-2 position-relative">

                                <button type="button" class="btn-close position-absolute top-0 end-0 m-2" 

                                        onclick="removeFromCart('<?php echo $item['id']; ?>')"

                                        title="<?php echo __('remove_service', 'Remove Service'); ?>"></button>

                                

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



                        <?php else: ?>

                        <!-- Single Booking Summary (for compatibility) -->

                        <div class="text-center py-3 border-bottom mb-3">

                            <h5 class="mb-2" style="color: <?php echo $primary_color; ?>;">

                                <?php echo __('booking_number', 'Booking Number'); ?>

                            </h5>

                            <h3 class="fw-bold"><?php echo htmlspecialchars($booking['booking_number']); ?></h3>

                            <span class="badge" style="background-color: <?php echo $warning_color; ?>;">

                                <i class="fas fa-clock me-1"></i><?php echo ucfirst($booking['status']); ?>

                            </span>

                        </div>



                        <!-- Service Details -->

                        <?php if ($booking['service_image']): ?>

                        <div class="alert alert-light border">

                            <div class="row align-items-center">

                                <div class="col-md-3">

                                    <img src="<?php echo BASE_URL; ?>assets/uploads/services/<?php echo htmlspecialchars($booking['service_image']); ?>" 

                                         alt="<?php echo htmlspecialchars($booking['service_name']); ?>" 

                                         class="img-fluid rounded">

                                </div>

                                <div class="col-md-9">

                                    <h5 class="mb-2"><?php echo htmlspecialchars($booking['service_name']); ?></h5>

                                    <p class="text-muted mb-0">

                                        <i class="fas fa-clock me-2"></i><?php echo $booking['service_duration']; ?> minutes

                                    </p>

                                </div>

                            </div>

                        </div>

                        <?php endif; ?>



                        <!-- Booking Details -->

                        <div class="row g-3 mb-3">

                            <div class="col-md-6">

                                <div class="border rounded p-3">

                                    <small class="text-muted d-block mb-1">

                                        <i class="fas fa-user me-1"></i><?php echo __('customer_name', 'Customer Name'); ?>

                                    </small>

                                    <strong><?php echo htmlspecialchars($booking['customer_name']); ?></strong>

                                </div>

                            </div>

                            <div class="col-md-6">

                                <div class="border rounded p-3">

                                    <small class="text-muted d-block mb-1">

                                        <i class="fas fa-envelope me-1"></i><?php echo __('email', 'Email'); ?>

                                    </small>

                                    <strong><?php echo htmlspecialchars($booking['customer_email']); ?></strong>

                                </div>

                            </div>

                            <div class="col-md-6">

                                <div class="border rounded p-3">

                                    <small class="text-muted d-block mb-1">

                                        <i class="fas fa-calendar me-1"></i><?php echo __('date', 'Date'); ?>

                                    </small>

                                    <strong><?php echo date('F j, Y', strtotime($booking['booking_date'])); ?></strong>

                                </div>

                            </div>

                            <div class="col-md-6">

                                <div class="border rounded p-3">

                                    <small class="text-muted d-block mb-1">

                                        <i class="fas fa-clock me-1"></i><?php echo __('time', 'Time'); ?>

                                    </small>

                                    <strong><?php echo date('g:i A', strtotime($booking['booking_time'])); ?></strong>

                                </div>

                            </div>

                        </div>

                        <?php endif; ?>



                        <!-- Price Summary -->

                        <div class="border rounded p-3 mb-3" style="background-color: <?php echo $primary_color; ?>15;">

                            <?php if (!empty($cartItems)): ?>

                            <?php foreach ($cartItems as $item): ?>

                            <div class="d-flex justify-content-between align-items-center mb-2">

                                <span><?php echo htmlspecialchars($item['service']['name']); ?></span>

                                <span class="fw-bold"><?php echo $currency->format($item['total_price']); ?></span>

                            </div>

                            <?php endforeach; ?>

                            <?php else: ?>

                            <div class="d-flex justify-content-between align-items-center mb-2">

                                <span><?php echo __('service_price', 'Service Price'); ?></span>

                                <span class="fw-bold"><?php echo $currency->format($booking['total_amount']); ?></span>

                            </div>

                            <?php endif; ?>

                            <hr class="my-2">

                            <div class="d-flex justify-content-between align-items-center">

                                <strong><?php echo __('total_amount', 'Total Amount'); ?></strong>

                                <h4 class="mb-0 fw-bold" id="display-total" style="color: <?php echo $success_color; ?>;">

                                    <?php echo $currency->format(!empty($cartItems) ? $cartTotal : $booking['total_amount']); ?>

                                </h4>

                            </div>

                            <input type="hidden" name="amount" id="actual-total" value="<?php echo !empty($cartItems) ? $cartTotal : $booking['total_amount']; ?>">

                            <input type="hidden" id="pay-amount" value="<?php echo $currency->format(!empty($cartItems) ? $cartTotal : $booking['total_amount']); ?>">

                        </div>



                        <!-- Coupon Code Section -->

                        <div class="card shadow-sm mb-4">

                            <div class="card-header bg-white">

                                <h5 class="mb-0">

                                    <i class="fas fa-ticket-alt me-2 text-primary"></i>

                                    <?php echo __('coupon_code', 'Coupon Code'); ?>

                                </h5>

                            </div>

                            <div class="card-body">

                                <div class="row g-3">

                                    <div class="col-md-8">

                                        <label for="coupon_code" class="form-label">

                                            <?php echo __('enter_coupon_code', 'Enter coupon code'); ?>

                                        </label>

                                        <input type="text" 

                                               class="form-control form-control-lg" 

                                               id="coupon_code" 

                                               name="coupon_code" 

                                               placeholder="<?php echo __('coupon_placeholder', 'Enter coupon code...'); ?>"

                                               value="<?php echo htmlspecialchars($_POST['coupon_code'] ?? ''); ?>">

                                        <small class="form-text text-muted">

                                            <?php echo __('coupon_help', 'Enter a valid coupon code to get discount'); ?>

                                        </small>

                                    </div>

                                    <div class="col-md-4">

                                        <label class="form-label d-block">&nbsp;</label>

                                        <button type="button" 

                                                class="btn btn-outline-primary btn-lg w-100" 

                                                onclick="applyCoupon()">

                                            <i class="fas fa-check me-2"></i><?php echo __('apply_coupon', 'Apply'); ?>

                                        </button>

                                    </div>

                                </div>

                                

                                <!-- Coupon Result Display -->

                                <div id="coupon-result" class="mt-3" style="display: none;">

                                    <!-- Coupon result will be displayed here -->

                                </div>

                                

                                <!-- Applied Coupon Display -->

                                <div id="applied-coupon" class="mt-3" style="display: none;">

                                    <div class="alert alert-success border-0" style="background-color: #d4edda;">

                                        <div class="d-flex justify-content-between align-items-center">

                                            <div>

                                                <strong><i class="fas fa-tag me-2"></i><span id="applied-coupon-code"></span></strong>

                                                <div class="small mt-1" id="applied-coupon-description"></div>

                                            </div>

                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeCoupon()">

                                                <i class="fas fa-times"></i> <?php echo __('remove', 'Remove'); ?>

                                            </button>

                                        </div>

                                        <div class="mt-2 pt-2 border-top">

                                            <div class="d-flex justify-content-between">

                                                <span><?php echo __('discount_amount', 'Discount Amount'); ?>:</span>

                                                <strong class="text-success" id="discount-amount"></strong>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>



                    </div>

                </div>

            </div>

            <!-- Customer Information & Checkout -->

            <div class="col-lg-4">

                <div class="card shadow-sm">

                    <div class="card-header text-white" style="background-color: <?php echo $primary_color; ?>;">

                        <h4 class="mb-0">

                            <i class="fas fa-user me-2"></i><?php echo __('customer_information', 'Customer Information'); ?>

                        </h4>

                    </div>

                    <div class="card-body">

                        <form id="customer-form" action="<?php echo BASE_URL; ?>servicescheckout.php" method="post">

                            <input type="hidden" id="coupon_code_input" name="coupon_code" value="">

                            <div class="row g-3">

                                <div class="col-12">

                                    <label for="customer_name" class="form-label"><?php echo __('name', 'Name'); ?> *</label>

                                    <input type="text" id="customer_name" name="customer_name" class="form-control" required
                                           value="<?php echo $user ? htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) : ''; ?>">

                                </div>

                                <div class="col-12">

                                    <label for="customer_email" class="form-label"><?php echo __('email', 'Email'); ?> *</label>

                                    <input type="email" id="customer_email" name="customer_email" class="form-control" required
                                           value="<?php echo $user ? htmlspecialchars($user['email']) : ''; ?>">

                                </div>

                                <div class="col-12">

                                    <label for="customer_phone" class="form-label"><?php echo __('phone', 'Phone'); ?></label>

                                    <input type="tel" id="customer_phone" name="customer_phone" class="form-control"
                                           value="<?php echo $user ? htmlspecialchars($user['phone'] ?? '') : ''; ?>">

                                </div>

                                <div class="col-12">

                                    <label for="customer_notes" class="form-label"><?php echo __('notes', 'Notes'); ?></label>

                                    <textarea id="customer_notes" name="customer_notes" class="form-control" rows="3"></textarea>

                                </div>

                            </div>

                            

                            <hr class="my-3">

                            

                            <div class="text-center">

                                <p class="mb-3"><?php echo __('ready_to_checkout', 'Ready to proceed to checkout?'); ?></p>

                                

                                <button type="submit" id="checkout-btn" class="btn btn-secondary btn-lg w-100 py-3" disabled>

                                    <i class="fas fa-arrow-right me-2"></i><?php echo __('proceed_to_checkout', 'Proceed to Checkout'); ?>

                                </button>

                            </div>

                            <div class="text-center mt-4">

                                <a href="<?php echo BASE_URL; ?>book-service.php" class="btn btn-outline-primary">

                                    <i class="fas fa-plus me-2"></i><?php echo __('add_more_services', 'Add More Services'); ?>

                                </a>

                            </div>

                        </form>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>



<script>

    // Apply coupon function

    function applyCoupon() {

        const couponCode = document.getElementById('coupon_code').value;

        const couponResult = document.getElementById('coupon-result');

        const appliedCoupon = document.getElementById('applied-coupon');

        const displayTotal = document.getElementById('display-total');

        const actualTotal = document.getElementById('actual-total');

        const payAmount = document.getElementById('pay-amount');

        const couponInput = document.getElementById('coupon_code_input');

        const applyButton = document.querySelector('button[onclick="applyCoupon()"]');

        

        console.log('Coupon code:', couponCode);

        console.log('Actual total element:', actualTotal);

        console.log('Actual total value:', actualTotal ? actualTotal.value : 'not found');

        

        if (!couponCode.trim()) {

            showCouponResult('<?php echo __('enter_valid_coupon', 'Please enter a coupon code'); ?>', 'danger');

            return;

        }

        

        if (!actualTotal || !actualTotal.value || parseFloat(actualTotal.value) <= 0) {

            showCouponResult('No valid amount found for coupon application', 'danger');

            return;

        }

        

        // Disable button during processing

        applyButton.disabled = true;

        applyButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i><?php echo __('applying', 'Applying...'); ?>';

        

        // Use the original total (in AED) for coupon validation to handle percentages correctly
        const originalTotalBase = <?php echo !empty($cartItems) ? $cartTotal : ($booking['total_amount'] ?? 0); ?>;
        const currencyCode = '<?php echo $currency->getCurrentCode(); ?>';
        const symbol = '<?php echo $currency->getSymbol(); ?>';
        const isMiddleEast = <?php echo in_array($currency->getCurrentCode(), ['AED', 'SAR', 'KWD', 'QAR', 'OMR', 'BHD', 'JOD', 'EGP', 'LBP']) ? 'true' : 'false'; ?>;

        function formatCurrencyJS(amount) {
            const formatted = amount.toFixed(2);
            return isMiddleEast ? formatted + ' ' + currencyCode : symbol + formatted;
        }

        // Make AJAX call to validate coupon
        fetch('<?php echo BASE_URL; ?>validate-coupon.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'coupon_code=' + encodeURIComponent(couponCode) + '&amount=' + originalTotalBase
        })

        .then(response => {

            console.log('Response status:', response.status);

            if (!response.ok) {

                throw new Error('Network response was not ok');

            }

            return response.json();

        })

        .then(data => {
            if (data.success) {
                const discountDisplay = parseFloat(data.discount);
                const discountBase = parseFloat(data.discount_base);
                const finalTotalDisplay = parseFloat(data.final_amount);
                const finalTotalBase = parseFloat(data.final_amount_base);
                
                // Show applied coupon
                document.getElementById('applied-coupon-code').textContent = data.coupon_code.toUpperCase();
                document.getElementById('applied-coupon-description').textContent = data.description;
                document.getElementById('discount-amount').textContent = formatCurrencyJS(discountDisplay);
                
                // Create detailed success message based on coupon type
                let successMessage = data.message;
                if (data.coupon_type === 'percentage') {
                    successMessage = `Coupon applied! You saved ${data.coupon_value}% discount (${data.coupon_code.toUpperCase()})`;
                } else if (data.coupon_type === 'fixed') {
                    successMessage = `Coupon applied! You saved ${formatCurrencyJS(discountDisplay)} (${data.coupon_code.toUpperCase()})`;
                }
                
                // Update totals
                displayTotal.textContent = formatCurrencyJS(finalTotalDisplay);
                payAmount.textContent = formatCurrencyJS(finalTotalDisplay);
                actualTotal.value = finalTotalBase;

                

                // Hide coupon result and show applied coupon

                couponResult.style.display = 'none';

                appliedCoupon.style.display = 'block';

                couponInput.value = couponCode;

                

                // Change color to indicate discount

                displayTotal.style.color = '<?php echo $success_color; ?>';

                

                // Disable apply button and change text

                applyButton.disabled = true;

                applyButton.innerHTML = '<i class="fas fa-check me-2"></i><?php echo __('applied', 'Applied'); ?>';

                applyButton.classList.remove('btn-outline-primary');

                applyButton.classList.add('btn-success');

                

                // Disable coupon code input

                document.getElementById('coupon_code').disabled = true;

                

                showCouponResult(successMessage, 'success');

            } else {

                // Re-enable button on failure

                applyButton.disabled = false;

                applyButton.innerHTML = '<i class="fas fa-check me-2"></i><?php echo __('apply_coupon', 'Apply'); ?>';

                applyButton.classList.remove('btn-success');

                applyButton.classList.add('btn-outline-primary');

                

                showCouponResult(data.message, 'danger');

            }

        })

        .catch(error => {

            console.error('Error:', error);

            // Re-enable button on error

            applyButton.disabled = false;

            applyButton.innerHTML = '<i class="fas fa-check me-2"></i><?php echo __('apply_coupon', 'Apply'); ?>';

            applyButton.classList.remove('btn-success');

            applyButton.classList.add('btn-outline-primary');

            

            showCouponResult('<?php echo __('error_validating_coupon', 'Error validating coupon. Please try again.'); ?>', 'danger');

        });

    }



    // Remove coupon function

    function removeCoupon() {

        const couponResult = document.getElementById('coupon-result');

        const appliedCoupon = document.getElementById('applied-coupon');

        const displayTotal = document.getElementById('display-total');

        const actualTotal = document.getElementById('actual-total');

        const payAmount = document.getElementById('pay-amount');

        const couponInput = document.getElementById('coupon_code_input');

        const applyButton = document.querySelector('button[onclick="applyCoupon()"]');

        const originalTotalBase = <?php echo !empty($cartItems) ? $cartTotal : ($booking['total_amount'] ?? 0); ?>;
        const originalTotalDisplay = '<?php echo $currency->format(!empty($cartItems) ? $cartTotal : ($booking['total_amount'] ?? 0)); ?>';
        
        // Reset to original total
        displayTotal.textContent = originalTotalDisplay;
        payAmount.textContent = originalTotalDisplay;
        actualTotal.value = originalTotalBase;

        

        // Reset color

        displayTotal.style.color = '<?php echo $primary_color; ?>';

        

        // Hide applied coupon

        appliedCoupon.style.display = 'none';

        couponInput.value = '';

        

        // Clear coupon code field and re-enable it

        const couponCodeField = document.getElementById('coupon_code');

        couponCodeField.value = '';

        couponCodeField.disabled = false;

        

        // Re-enable apply button and reset its appearance

        applyButton.disabled = false;

        applyButton.innerHTML = '<i class="fas fa-check me-2"></i><?php echo __('apply_coupon', 'Apply'); ?>';

        applyButton.classList.remove('btn-success');

        applyButton.classList.add('btn-outline-primary');

    }



    // Show coupon result message

    function showCouponResult(message, type) {

        const couponResult = document.getElementById('coupon-result');

        couponResult.innerHTML = `

            <div class="alert alert-${type} alert-dismissible fade show">

                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>${message}

                <button type="button" class="btn-close" onclick="this.parentElement.style.display='none'"></button>

            </div>

        `;

        couponResult.style.display = 'block';

    }



    // Validate customer form before checkout

    function validateCustomerForm() {

        const customerName = document.getElementById('customer_name').value.trim();

        const customerEmail = document.getElementById('customer_email').value.trim();

        const checkoutBtn = document.getElementById('checkout-btn');

        

        if (customerName && customerEmail) {

            checkoutBtn.disabled = false;

            checkoutBtn.classList.remove('btn-secondary');

            checkoutBtn.classList.add('btn-success');

        } else {

            checkoutBtn.disabled = true;

            checkoutBtn.classList.remove('btn-success');

            checkoutBtn.classList.add('btn-secondary');

        }

    }



    // Remove item from cart

    function removeFromCart(itemId) {

        if (confirm('<?php echo __('confirm_remove_service', 'Are you sure you want to remove this service from your cart?'); ?>')) {

            window.location.href = '<?php echo BASE_URL; ?>remove-from-cart.php?id=' + itemId;

        }

    }



    // Add event listeners for form validation

    document.addEventListener('DOMContentLoaded', function() {

        const customerName = document.getElementById('customer_name');

        const customerEmail = document.getElementById('customer_email');

        

        if (customerName) customerName.addEventListener('input', validateCustomerForm);

        if (customerEmail) customerEmail.addEventListener('input', validateCustomerForm);

        

        // Initial validation

        validateCustomerForm();

    });

</script>



<?php include 'includes/footer.php'; ?>