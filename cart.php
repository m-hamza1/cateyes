<?php
require_once 'config.php';

// Ensure session is properly started and consistent
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize cart
$cart = new Cart($db);

// Fix session inconsistency - sync cart with session
$sessionCart = $_SESSION['cart'] ?? [];
$classCart = $cart->getItems();

// If session has cart but class doesn't, restore from session
if (!empty($sessionCart) && empty($classCart)) {
    $cart->setItems($sessionCart);
    $cart->saveCart();
}
// If class has cart but session doesn't, save to session
elseif (empty($sessionCart) && !empty($classCart)) {
    $_SESSION['cart'] = $classCart;
}

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            $productId = $_POST['product_id'] ?? 0;
            $quantity = $_POST['quantity'] ?? 1;
            $result = $cart->add($productId, $quantity);
            
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
            
            // Sync session after cart operation
            $_SESSION['cart'] = $cart->getItems();
            header('Location: cart.php');
            exit;
            
        case 'update':
            $key = $_POST['key'] ?? '';
            $quantity = $_POST['quantity'] ?? 1;
            $result = $cart->update($key, $quantity);
            
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
            
            // Sync session after cart operation
            $_SESSION['cart'] = $cart->getItems();
            header('Location: cart.php');
            exit;
            
        case 'remove':
            $key = $_POST['key'] ?? '';
            $result = $cart->remove($key);
            
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
            
            // Sync session after cart operation
            $_SESSION['cart'] = $cart->getItems();
            header('Location: cart.php');
            exit;
            
        case 'clear':
            $result = $cart->clear();
            
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
            
            // Sync session after cart operation
            $_SESSION['cart'] = $cart->getItems();
            header('Location: cart.php');
            exit;
            
        case 'apply_coupon':
            $couponCode = $_POST['coupon_code'] ?? '';
            $result = $cart->applyCoupon($couponCode);
            
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
            
            // Sync session after coupon operation
            $_SESSION['cart'] = $cart->getItems();
            $_SESSION['coupon'] = $cart->getCoupon();
            
            header('Location: cart.php');
            exit;
            
        case 'remove_coupon':
            $result = $cart->removeCoupon();
            
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
            
            // Sync session after coupon operation
            $_SESSION['cart'] = $cart->getItems();
            $_SESSION['coupon'] = $cart->getCoupon();
            
            header('Location: cart.php');
            exit;
    }
}

// Get cart items
$cartItems = $cart->getItems();
// Enrich product names - fix "Product 1" etc. by fetching real names from DB
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
$cartSubtotal = $cart->getSubtotal();
$cartCount = $cart->getCount();

// Get tax rate
$taxRate = 0;
$taxSetting = $db->fetchOne("SELECT value FROM settings WHERE `key` = 'tax_rate' AND `group` = 'shipping'");
if ($taxSetting) {
    $taxRate = (float)$taxSetting['value'];
}

$taxAmount = $cart->getTax($taxRate);

// Get applied coupon
$appliedCoupon = $cart->getCoupon();
$discountAmount = 0;
if ($appliedCoupon) {
    $discountAmount = $appliedCoupon['discount_amount'];
}

// Calculate total
$totalAmount = $cartSubtotal + $taxAmount - $discountAmount;

$pageTitle = 'Shopping Cart';
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8">
            <h1 class="h3 mb-4"><?php echo __('Shopping Cart'); ?></h1>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (empty($cartItems)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                    <h4><?php echo __('Your cart is empty'); ?></h4>
                    <p class="text-muted"><?php echo __('Add some products to get started'); ?></p>
                    <a href="shop.php" class="btn btn-primary"><?php echo __('Continue Shopping'); ?></a>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><?php echo __('Product'); ?></th>
                                        <th><?php echo __('Price'); ?></th>
                                        <th><?php echo __('Quantity'); ?></th>
                                        <th><?php echo __('Total'); ?></th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cartItems as $key => $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php 
                                                    $productImage = $item['image'] ?? '';
                                                    $hasImage = !empty($productImage) && file_exists(BASE_PATH . 'assets/uploads/products/' . $productImage);
                                                    ?>
                                                    <?php if ($hasImage): ?>
                                                        <img src="<?php echo UPLOADS_URL . 'products/' . htmlspecialchars($productImage); ?>" 
                                                             alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                                             class="img-thumbnail me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                                    <?php endif; ?>
                                                    <div>
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                        <?php if (!empty($item['variation_name'])): ?>
                                                            <div class="small text-muted mb-1"><?php echo htmlspecialchars($item['variation_name']); ?></div>
                                                        <?php endif; ?>
                                                        <small class="text-muted"><?php echo __('SKU'); ?>: <?php echo htmlspecialchars($item['sku'] ?? ''); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo $currency->format($item['price']); ?></td>
                                            <td>
                                                <form method="post" class="d-flex">
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="key" value="<?php echo $key; ?>">
                                                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                                           min="1" max="99" class="form-control form-control-sm me-2" style="width: 70px;">
                                                    <button type="submit" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-sync"></i>
                                                    </button>
                                                </form>
                                            </td>
                                            <td><?php echo $currency->format($item['price'] * $item['quantity']); ?></td>
                                            <td>
                                                <form method="post">
                                                    <input type="hidden" name="action" value="remove">
                                                    <input type="hidden" name="key" value="<?php echo $key; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                            onclick="return confirm('<?php echo __('Remove this item?'); ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <form method="post">
                                <input type="hidden" name="action" value="clear">
                                <button type="submit" class="btn btn-outline-danger" 
                                        onclick="return confirm('<?php echo __('Clear entire cart?'); ?>')">
                                    <i class="fas fa-trash-alt"></i> <?php echo __('Clear Cart'); ?>
                                </button>
                            </form>
                            
                            <a href="shop.php" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left"></i> <?php echo __('Continue Shopping'); ?>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Coupon Section -->
                <div class="card mt-3">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo __('Coupon Code'); ?></h5>
                        
                        <?php if ($appliedCoupon): ?>
                            <div class="alert alert-success">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($appliedCoupon['code']); ?></strong>
                                        <span class="badge bg-success ms-2">
                                            -<?php echo $currency->format($appliedCoupon['discount_amount']); ?>
                                        </span>
                                    </div>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="action" value="remove_coupon">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <?php echo __('Remove'); ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php else: ?>
                            <form method="post" class="d-flex">
                                <input type="hidden" name="action" value="apply_coupon">
                                <input type="text" name="coupon_code" class="form-control me-2" 
                                       placeholder="<?php echo __('Enter coupon code'); ?>" required>
                                <button type="submit" class="btn btn-primary"><?php echo __('Apply'); ?></button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="col-lg-4">
            <?php if (!empty($cartItems)): ?>
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo __('Order Summary'); ?></h5>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span><?php echo __('Subtotal'); ?></span>
                            <span><?php echo $currency->format($cartSubtotal); ?></span>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span><?php echo __('Tax'); ?> (<?php echo $taxRate; ?>%)</span>
                            <span><?php echo $currency->format($taxAmount); ?></span>
                        </div>
                        
                        <?php if ($discountAmount > 0): ?>
                            <div class="d-flex justify-content-between mb-2 text-success">
                                <span><?php echo __('Discount'); ?></span>
                                <span>-<?php echo $currency->format($discountAmount); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <strong><?php echo __('Total'); ?></strong>
                            <strong><?php echo $currency->format($totalAmount); ?></strong>
                        </div>
                        
                        <a href="checkout.php" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-lock"></i> <?php echo __('Proceed to Checkout'); ?>
                        </a>
                        
                        <div class="mt-3 text-center">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt"></i> <?php echo __('Secure checkout'); ?>
                            </small>
                        </div>
                    </div>
                </div>
                
                <!-- Security badges -->
                <div class="card mt-3">
                    <div class="card-body text-center">
                        <h6><?php echo __('We Accept'); ?></h6>
                        <?php
                        $stripe_enabled = $settings->get('payment_stripe_enabled', '0') === '1';
                        $cardVisaEnabled = $settings->get('payment_card_visa_enabled', '1') === '1';
                        $cardMasterEnabled = $settings->get('payment_card_mastercard_enabled', '1') === '1';
                        $cardTabbyEnabled = $settings->get('payment_card_tabby_enabled', '1') === '1';
                        $cardNetworksEnabled = $settings->get('payment_card_networks_enabled', '1') === '1';
                        
                        $paypal_enabled = $settings->get('payment_paypal_enabled', '0') === '1';
                        $googlepay_enabled = $settings->get('payment_googlepay_enabled', '0') === '1';
                        $applepay_enabled = $settings->get('payment_applepay_enabled', '0') === '1';
                        ?>
                        <div class="d-flex flex-wrap justify-content-center gap-2 align-items-center mt-2">
                            <?php if ($stripe_enabled): ?>
                                <?php if ($cardVisaEnabled): ?>
                                    <div class="payment-card-badge" title="VISA">
                                        <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/visa.svg" alt="Visa" style="height: 20px;">
                                    </div>
                                <?php endif; ?>
                                <?php if ($cardMasterEnabled): ?>
                                    <div class="payment-card-badge" title="Mastercard">
                                        <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/mastercard-clean.svg" alt="Mastercard" style="height: 20px;">
                                    </div>
                                <?php endif; ?>
                                <?php if ($cardTabbyEnabled): ?>
                                    <div class="payment-card-badge tabby-badge" title="Tabby">
                                        <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/tabby.svg" alt="Tabby" style="height: 20px;">
                                    </div>
                                <?php endif; ?>
                                <?php if ($cardNetworksEnabled): ?>
                                    <div class="payment-card-badge" title="Networks">
                                        <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/networks.svg" alt="Networks" style="height: 20px;">
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if ($paypal_enabled): ?>
                                <div class="payment-card-badge" title="PayPal">
                                    <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/paypall.png" alt="PayPal" style="height: 20px;">
                                </div>
                            <?php endif; ?>
                            <?php if ($googlepay_enabled): ?>
                                <div class="payment-card-badge" title="Google Pay">
                                    <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/googlepay.png" alt="Google Pay" style="height: 20px;">
                                </div>
                            <?php endif; ?>
                            <?php if ($applepay_enabled): ?>
                                <div class="payment-card-badge" title="Apple Pay">
                                    <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/applepay.png" alt="Apple Pay" style="height: 20px;">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
