<?php
require_once 'config.php';

// Check if user came from checkout with payment method selected
if (!isset($_SESSION['checkout']['payment_method']) || !isset($_SESSION['checkout']['shipping_cost'])) {
    header('Location: checkout.php');
    exit;
}

$paymentMethod = $_SESSION['checkout']['payment_method'];
$checkoutData = $_SESSION['checkout'];
error_log("DEBUG PAYMENT: checkoutData: " . print_r($checkoutData, true));

// Calculate totals
$cart = new Cart($db);
$cartItems = $cart->getItems();
// Enrich product names - fix "Product 1" etc.
$langId = $_SESSION['language_id'] ?? 1;
foreach ($cartItems as $key => $item) {
    if (preg_match('/^Product\s+\d+$/i', trim($item['name'] ?? ''))) {
        $pid = $item['product_id'] ?? 0;
        $row = $db->fetchOne("SELECT name FROM product_translations WHERE product_id = :pid AND language_id = :lid AND name IS NOT NULL AND TRIM(name) != '' LIMIT 1", ['pid' => $pid, 'lid' => $langId]);
        if (!$row || empty($row['name'])) {
            $row = $db->fetchOne("SELECT name FROM product_translations WHERE product_id = :pid AND name IS NOT NULL AND TRIM(name) != '' ORDER BY (language_id = 1) DESC LIMIT 1", ['pid' => $pid]);
        }
        if ($row && !empty(trim($row['name'] ?? ''))) {
            $cartItems[$key]['name'] = trim($row['name']);
        }
    }
}
$cartSubtotal = 0;
foreach ($cartItems as $item) {
    $cartSubtotal += $item['price'] * $item['quantity'];
}

$shippingAmount = $checkoutData['shipping_cost'];
$appliedCoupon = $cart->getCoupon();
$discountAmount = $appliedCoupon ? $appliedCoupon['discount_amount'] : 0;
$taxRate = 0.1;
$taxSetting = $db->fetchOne("SELECT `value` FROM settings WHERE `key` = 'tax_rate'");
if ($taxSetting && is_numeric($taxSetting['value'])) {
    $taxRate = (float)$taxSetting['value'] / 100;
}
$taxAmount = $cartSubtotal * $taxRate;
$totalAmount = $cartSubtotal + $taxAmount + $shippingAmount - $discountAmount;

// Sub-card types (default to enabled for compatibility)
$cardVisaEnabled = $settings->get('payment_card_visa_enabled', '1') === '1';
$cardMasterEnabled = $settings->get('payment_card_mastercard_enabled', '1') === '1';
$cardTabbyEnabled = $settings->get('payment_card_tabby_enabled', '1') === '1';
$cardNetworksEnabled = $settings->get('payment_card_networks_enabled', '1') === '1';

// Process payment form submission BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentData = [];
    if ($paymentMethod === 'credit_card') {
        $paymentData = [
            'card_number' => $_POST['card_number'] ?? '',
            'expiry' => $_POST['expiry'] ?? '',
            'cvv' => $_POST['cvv'] ?? '',
            'cardholder_name' => $_POST['cardholder_name'] ?? '',
            'card_type' => $_POST['card_type'] ?? 'visa'
        ];
    } elseif ($paymentMethod === 'paypal') {
        $paymentData = [
            'paypal_email' => $_POST['paypal_email'] ?? '',
            'paypal_id' => $_POST['paypal_id'] ?? ''
        ];
    }

    $order = new Order($db);
    $shippingDataForOrder = $checkoutData['shipping'];
    $shippingDataForOrder['shipping_cost'] = $checkoutData['shipping_cost'];
    
    $orderResult = $order->create(
        $cartItems,
        $checkoutData['billing'],
        $shippingDataForOrder,
        $paymentMethod,
        $_SESSION['user_id'] ?? null,
        $appliedCoupon
    );
    
    if ($orderResult['success']) {
        $orderId = $orderResult['order_id'];
        
        // Process payment
        $payment = new Payment($db);
        $processResult = $payment->process($orderId, $paymentMethod, $paymentData);
        
        if (($paymentMethod === 'networks' || $paymentMethod === 'tabby') && $processResult['success'] && isset($processResult['redirect_url'])) {
            // Clear cart before redirecting
            $cart->clear();
            unset($_SESSION['checkout']);
            
            // Redirect to gateway
            header('Location: ' . $processResult['redirect_url']);
            exit;
        }

        if ($processResult['success'] || $paymentMethod === 'cod') {
            try {
                require_once INCLUDES_PATH . 'Email.php';
                $mailer = new Email($db);
                $mailer->orderConfirmation($orderId);
                $mailer->notifyAdminNewOrder($orderId);
            } catch (Exception $emailEx) {
                // Log but don't block
            }
            
            $cart->clear();
            unset($_SESSION['checkout']);
            $_SESSION['order_success'] = [
                'order_id' => $orderId,
                'order_number' => $orderResult['order_number'],
                'payment_method' => $paymentMethod
            ];
            header('Location: order-success.php');
            exit;
        } else {
            $_SESSION['payment_error'] = $processResult['message'] ?? 'Payment processing failed';
            header('Location: payment.php');
            exit;
        }
    } else {
        $_SESSION['payment_error'] = $orderResult['message'];
        header('Location: payment.php');
        exit;
    }
}

$pageTitle = 'Payment';
include 'includes/header.php';
?>

<!-- Standard E-commerce Payment Page -->
<section class="payment-page py-4 py-lg-5">
    <div class="container">
        <h1 class="h3 mb-4 fw-bold"><?php echo __('Payment'); ?></h1>
        <!-- Progress -->
        <nav class="payment-progress mb-4 mb-lg-5" aria-label="Checkout progress">
            <ol class="payment-steps list-unstyled d-flex flex-wrap align-items-center justify-content-center gap-2 gap-md-4">
                <li class="payment-step completed">
                    <span class="step-num"><i class="fas fa-check"></i></span>
                    <span class="step-label"><?php echo __('Shipping'); ?></span>
                </li>
                <li class="payment-step-connector d-none d-md-block"></li>
                <li class="payment-step active">
                    <span class="step-num">2</span>
                    <span class="step-label"><?php echo __('Payment'); ?></span>
                </li>
                <li class="payment-step-connector d-none d-md-block"></li>
                <li class="payment-step">
                    <span class="step-num">3</span>
                    <span class="step-label"><?php echo __('Complete'); ?></span>
                </li>
            </ol>
        </nav>

        <?php if (isset($_SESSION['payment_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_SESSION['payment_error']); unset($_SESSION['payment_error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Payment Form Column -->
            <div class="col-lg-8 order-2 order-lg-1">
                <a href="checkout.php" class="btn btn-outline-secondary mb-3"><i class="fas fa-arrow-left me-1"></i> <?php echo __('Back to Checkout'); ?></a>
                
                <div class="card payment-card shadow-sm">
                    <div class="card-body">
                        <h4 class="card-title mb-4">
                            <?php echo __('Payment Details'); ?>
                        </h4>

                        <!-- Payment Method Badge -->
                        <div class="payment-method-badge mb-4 p-3 rounded border d-flex align-items-center gap-2">
                            <?php if ($paymentMethod === 'credit_card'): ?>
                                <div class="d-flex gap-2">
                                    <?php if ($cardVisaEnabled): ?>
                                    <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/visa.svg" alt="Visa" style="height: 20px;">
                                    <?php endif; ?>
                                    <?php if ($cardMasterEnabled): ?>
                                    <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/mastercard-clean.svg" alt="Mastercard" style="height: 20px;">
                                    <?php endif; ?>
                                    <?php if ($cardTabbyEnabled): ?>
                                    <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/tabby.svg" alt="Tabby" style="height: 20px;">
                                    <?php endif; ?>
                                </div>
                                <?php
                                $card_labels = [];
                                if ($cardVisaEnabled) $card_labels[] = 'Visa';
                                if ($cardMasterEnabled) $card_labels[] = 'Mastercard';
                                if ($cardTabbyEnabled) $card_labels[] = 'Tabby';
                                ?>
                                <strong class="ms-2"><?php echo implode(', ', $card_labels); ?></strong>
                            <?php elseif ($paymentMethod === 'networks'): ?>
                                <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/networks.svg" alt="Networks" style="height: 25px;">
                                <strong class="ms-2">Network International (N-Genius)</strong>
                            <?php elseif ($paymentMethod === 'paypal'): ?>
                                <span class="payment-icons fs-4 text-primary"><i class="fab fa-paypal"></i></span>
                                <strong class="ms-2">PayPal</strong>
                            <?php elseif ($paymentMethod === 'googlepay'): ?>
                                <span class="payment-icons fs-4 text-dark"><i class="fab fa-google-pay"></i></span>
                                <strong class="ms-2">Google Pay</strong>
                            <?php elseif ($paymentMethod === 'applepay'): ?>
                                <span class="payment-icons fs-4 text-dark"><i class="fab fa-apple-pay"></i></span>
                                <strong class="ms-2">Apple Pay</strong>
                            <?php elseif ($paymentMethod === 'cod'): ?>
                                <span class="payment-icons fs-4 text-muted"><i class="fas fa-money-bill-wave"></i></span>
                                <strong class="ms-2">Cash on Delivery</strong>
                            <?php elseif ($paymentMethod === 'tabby'): ?>
                                <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/tabby.svg" alt="Tabby" style="height: 25px;">
                                <strong class="ms-2">Tabby (Installments)</strong>
                            <?php endif; ?>
                        </div>

                    <!-- Payment Form -->
                    <form method="post" id="payment_form">
                        <input type="hidden" name="payment_method" value="<?php echo htmlspecialchars($paymentMethod); ?>">
                        
                        <?php if ($paymentMethod === 'credit_card'): ?>
                            <!-- Credit Card Details - Professional layout -->
                            <div class="payment-form-fields">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold"><?php echo __('Card Number'); ?> *</label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text"><i class="fas fa-credit-card text-muted"></i></span>
                                        <input type="text" name="card_number" class="form-control form-control-lg" 
                                               placeholder="1234 5678 9012 3456" required
                                               maxlength="19" pattern="[0-9\s]{13,19}" autocomplete="cc-number">
                                    </div>
                                    <small class="text-muted"><?php echo __('Enter card number'); ?></small>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold"><?php echo __('Expiry'); ?> *</label>
                                        <input type="text" name="expiry" class="form-control form-control-lg" 
                                               placeholder="MM/YY" required
                                               maxlength="5" pattern="(0[1-9]|1[0-2])\/[0-9]{2}" autocomplete="cc-exp">
                                        <small class="text-muted">MM/YY</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold"><?php echo __('CVV'); ?> *</label>
                                        <input type="text" name="cvv" class="form-control form-control-lg" 
                                               placeholder="123" required
                                               maxlength="4" pattern="[0-9]{3,4}" autocomplete="cc-csc">
                                        <small class="text-muted"><?php echo __('3-4 digits'); ?></small>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold"><?php echo __('Cardholder Name'); ?> *</label>
                                    <input type="text" name="cardholder_name" class="form-control form-control-lg" 
                                           placeholder="John Doe" required autocomplete="cc-name">
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-semibold"><?php echo __('Card Type'); ?></label>
                                    <select name="card_type" class="form-select form-select-lg">
                                        <option value="visa">Visa</option>
                                        <option value="mastercard">Mastercard</option>
                                        <option value="amex">American Express</option>
                                        <option value="discover">Discover</option>
                                    </select>
                                </div>
                            </div>

                        <?php elseif ($paymentMethod === 'cod'): ?>
                            <div class="alert alert-info mb-4">
                                <i class="fas fa-money-bill-wave me-2"></i>
                                <?php echo __('You will pay when your order is delivered.'); ?>
                            </div>

                        <?php elseif ($paymentMethod === 'paypal'): ?>
                            <!-- PayPal Details -->
                            <div class="payment-form-fields mb-4">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold"><?php echo __('PayPal Email'); ?> *</label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text"><i class="fab fa-paypal text-primary"></i></span>
                                        <input type="email" name="paypal_email" class="form-control form-control-lg" 
                                               placeholder="your@email.com" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label"><?php echo __('PayPal Transaction ID'); ?></label>
                                    <input type="text" name="paypal_id" class="form-control" placeholder="<?php echo __('Optional'); ?>">
                                </div>
                            </div>
                        
                        <?php elseif ($paymentMethod === 'networks'): ?>
                            <div class="alert alert-primary mb-4">
                                <i class="fas fa-external-link-alt me-2"></i>
                                <?php echo __('You will be redirected to the secure Network International payment portal to complete your transaction.'); ?>
                            </div>

                        <?php endif; ?>

                        <!-- Address Summary - Collapsible on mobile -->
                        <div class="payment-addresses">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="address-block p-3 rounded border mb-3">
                                        <h6 class="text-muted mb-2"><i class="fas fa-truck me-1"></i> <?php echo __('Shipping'); ?></h6>
                                        <p class="mb-0 small">
                                            <strong><?php echo htmlspecialchars($checkoutData['shipping']['first_name'] . ' ' . $checkoutData['shipping']['last_name']); ?></strong><br>
                                            <?php echo htmlspecialchars($checkoutData['shipping']['address_line1']); ?><br>
                                            <?php if (!empty($checkoutData['shipping']['address_line2'])): ?><?php echo htmlspecialchars($checkoutData['shipping']['address_line2']); ?><br><?php endif; ?>
                                            <?php echo htmlspecialchars($checkoutData['shipping']['city'] . ', ' . $checkoutData['shipping']['state'] . ' ' . $checkoutData['shipping']['postal_code']); ?><br>
                                            <?php echo htmlspecialchars($checkoutData['shipping']['country']); ?><br>
                                            <?php echo htmlspecialchars($checkoutData['shipping']['phone']); ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <?php 
                                    $isSameAsShipping = $checkoutData['billing']['same_as_shipping'] ?? false;
                                    $hasBillingData = !empty($checkoutData['billing']['first_name']) || !empty($checkoutData['billing']['last_name']) || !empty($checkoutData['billing']['address_line1']);
                                    $billingDisplay = ($isSameAsShipping || !$hasBillingData) ? $checkoutData['shipping'] : $checkoutData['billing'];
                                    ?>
                                    <div class="address-block p-3 rounded border mb-3">
                                        <h6 class="text-muted mb-2"><i class="fas fa-file-invoice me-1"></i> <?php echo __('Billing'); ?> <?php if ($isSameAsShipping): ?><small>(<?php echo __('Same as shipping'); ?>)</small><?php endif; ?></h6>
                                        <p class="mb-0 small">
                                            <strong><?php echo htmlspecialchars($billingDisplay['first_name'] . ' ' . $billingDisplay['last_name']); ?></strong><br>
                                            <?php echo htmlspecialchars($billingDisplay['address_line1']); ?><br>
                                            <?php if (!empty($billingDisplay['address_line2'])): ?><?php echo htmlspecialchars($billingDisplay['address_line2']); ?><br><?php endif; ?>
                                            <?php echo htmlspecialchars($billingDisplay['city'] . ', ' . $billingDisplay['state'] . ' ' . $billingDisplay['postal_code']); ?><br>
                                            <?php echo htmlspecialchars($billingDisplay['country']); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pay / Place Order Button -->
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg py-3">
                                <?php if ($paymentMethod === 'cod'): ?>
                                    <i class="fas fa-check-circle me-2"></i>
                                    <?php echo __('Place Order'); ?> - <?php echo $currency->format($totalAmount); ?>
                                <?php else: ?>
                                    <i class="fas fa-lock me-2"></i>
                                    <?php echo __('Pay'); ?> <?php echo $currency->format($totalAmount); ?>
                                <?php endif; ?>
                            </button>
                            <div class="payment-trust text-center py-2">
                                <small class="text-muted">
                                    <i class="fas fa-shield-alt me-1"></i> <?php echo __('Secure payment'); ?>
                                </small>
                            </div>
                        </div>
                    </form>
                    </div>
                </div>
            </div>
            
            <!-- Order Summary Sidebar -->
            <div class="col-lg-4 order-1 order-lg-2">
                <div class="payment-sidebar position-lg-sticky top-lg-0">
                    <div class="card payment-summary-card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title mb-3"><?php echo __('Order Summary'); ?></h5>
                            <?php foreach ($cartItems as $item): 
                                $itemImg = !empty($item['image']) ? (rtrim(UPLOADS_URL, '/') . '/products/' . $item['image']) : ('data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48"><rect width="48" height="48" fill="%23eee"/></svg>'));
                            ?>
                            <?php $placeholderSvg = 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48"><rect width="48" height="48" fill="%23eee"/></svg>'); ?>
                            <div class="payment-item d-flex align-items-center gap-2 py-2 border-bottom">
                                <img src="<?php echo htmlspecialchars($itemImg); ?>" alt="<?php echo htmlspecialchars($item['name'] ?? ''); ?>" class="rounded" width="48" height="48" style="object-fit:cover" onerror="this.src='<?php echo $placeholderSvg; ?>'">
                                <div class="flex-grow-1 min-w-0">
                                    <span class="d-block text-truncate small"><?php echo htmlspecialchars($item['name'] ?? ''); ?></span>
                                    <span class="text-muted small"><?php echo (int)($item['quantity'] ?? 1); ?> × <?php echo $currency->format($item['price'] ?? 0); ?></span>
                                </div>
                                <span class="fw-semibold small"><?php echo $currency->format(($item['price'] ?? 0) * ($item['quantity'] ?? 1)); ?></span>
                            </div>
                            <?php endforeach; ?>
                            <div class="payment-totals mt-3">
                                <div class="d-flex justify-content-between mb-2 small"><span><?php echo __('Subtotal'); ?></span><span><?php echo $currency->format($cartSubtotal); ?></span></div>
                                <div class="d-flex justify-content-between mb-2 small"><span><?php echo __('Tax'); ?></span><span><?php echo $currency->format($taxAmount); ?></span></div>
                                <div class="d-flex justify-content-between mb-2 small"><span><?php echo __('Shipping'); ?></span><span><?php echo $currency->format($shippingAmount); ?></span></div>
                                <?php if ($discountAmount > 0): ?>
                                <div class="d-flex justify-content-between mb-2 small text-success"><span><?php echo __('Discount'); ?></span><span>-<?php echo $currency->format($discountAmount); ?></span></div>
                                <?php endif; ?>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <strong class="h5 mb-0"><?php echo __('Total'); ?></strong>
                                <strong class="h5 mb-0 text-primary"><?php echo $currency->format($totalAmount); ?></strong>
                            </div>
                             <div class="mt-2 d-flex flex-wrap justify-content-center gap-2">
                                <?php
                                $stripe_enabled = $settings->get('payment_stripe_enabled', '0') === '1';
                                $paypal_enabled = $settings->get('payment_paypal_enabled', '0') === '1';
                                $googlepay_enabled = $settings->get('payment_googlepay_enabled', '0') === '1';
                                $applepay_enabled = $settings->get('payment_applepay_enabled', '0') === '1';
                                ?>
                                <?php if ($stripe_enabled): ?>
                                    <?php if ($cardVisaEnabled): ?>
                                    <div class="payment-card-badge" title="VISA" style="padding: 4px 8px; height: 30px; min-width: 45px;">
                                        <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/visa.svg" alt="Visa" style="height: 15px;">
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($cardMasterEnabled): ?>
                                    <div class="payment-card-badge" title="Mastercard" style="padding: 4px 8px; height: 30px; min-width: 45px;">
                                        <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/mastercard-clean.svg" alt="Mastercard" style="height: 15px;">
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($cardTabbyEnabled): ?>
                                    <div class="payment-card-badge tabby-badge" title="Tabby" style="padding: 4px 8px; height: 30px; min-width: 45px;">
                                        <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/tabby.svg" alt="Tabby" style="height: 15px;">
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($cardNetworksEnabled): ?>
                                    <div class="payment-card-badge" title="Networks" style="padding: 4px 8px; height: 30px; min-width: 45px;">
                                        <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/networks.svg" alt="Networks" style="height: 15px;">
                                    </div>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if ($paypal_enabled): ?>
                                <div class="payment-card-badge" title="PayPal" style="padding: 4px 8px; height: 30px; min-width: 45px;">
                                    <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/paypall.png" alt="PayPal" style="height: 15px;">
                                </div>
                                <?php endif; ?>
                                <?php if ($googlepay_enabled): ?>
                                <div class="payment-card-badge" title="Google Pay" style="padding: 4px 8px; height: 30px; min-width: 45px;">
                                    <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/googlepay.png" alt="Google Pay" style="height: 15px;">
                                </div>
                                <?php endif; ?>
                                <?php if ($applepay_enabled): ?>
                                <div class="payment-card-badge" title="Apple Pay" style="padding: 4px 8px; height: 30px; min-width: 45px;">
                                    <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/applepay.png" alt="Apple Pay" style="height: 15px;">
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* Standard E-commerce Payment */
.payment-page { background: #f8f9fa; min-height: 65vh; }
.payment-progress { padding: 1rem 0; }
.payment-steps { margin: 0; gap: 0.5rem !important; }
.payment-step { display: flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1.2rem; background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.06); border: 1px solid #e9ecef; }
.payment-step .step-num { width: 32px; height: 32px; border-radius: 50%; background: #e9ecef; color: #6c757d; display: inline-flex; align-items: center; justify-content: center; font-size: 0.9rem; flex-shrink: 0; }
.payment-step.active .step-num { background: var(--bs-primary); color: #fff; }
.payment-step.completed .step-num { background: #198754; color: #fff; }
.payment-step .step-label { font-size: 0.95rem; color: #6c757d; font-weight: 500; }
.payment-step.active .step-label { color: var(--bs-primary); font-weight: 600; }
.payment-step-connector { width: 24px; height: 2px; background: #dee2e6; flex-shrink: 0; }
.payment-card { border: none; border-radius: 12px; overflow: hidden; }
.payment-summary-card { border: none; border-radius: 12px; }
.payment-sidebar { padding-top: 0; }
.payment-form-fields .form-control, .payment-form-fields .form-select { border-radius: 8px; }
@media (min-width: 992px) { .payment-sidebar { top: 1rem; } }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Format card number input
    const cardInput = document.querySelector('input[name="card_number"]');
    if (cardInput) {
        cardInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = formattedValue;
        });
    }

    // Format expiry date input
    const expiryInput = document.querySelector('input[name="expiry"]');
    if (expiryInput) {
        expiryInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.slice(0, 2) + '/' + value.slice(2, 4);
            }
            e.target.value = value;
        });
    }

    // Only allow numbers in CVV
    const cvvInput = document.querySelector('input[name="cvv"]');
    if (cvvInput) {
        cvvInput.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '');
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
