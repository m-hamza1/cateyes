<?php
require_once '../includes/auth.php';
requirePermission('payment');
require_once INCLUDES_PATH . 'Settings.php';

$settings = new Settings($db);
$pageTitle = __('payment_settings', 'Payment Settings');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updates = [
        // Cash on Delivery
        'payment_cod_enabled' => isset($_POST['payment_cod_enabled']) ? '1' : '0',
        
        // Stripe
        'payment_stripe_enabled' => isset($_POST['payment_stripe_enabled']) ? '1' : '0',
        'payment_stripe_public_key' => sanitize($_POST['payment_stripe_public_key']),
        'payment_stripe_secret_key' => sanitize($_POST['payment_stripe_secret_key']),
        // Stripe card logos under "Credit Card" option
        'payment_card_visa_enabled' => isset($_POST['payment_card_visa_enabled']) ? '1' : '0',
        'payment_card_mastercard_enabled' => isset($_POST['payment_card_mastercard_enabled']) ? '1' : '0',
        'payment_card_tabby_enabled' => isset($_POST['payment_card_tabby_enabled']) ? '1' : '0',
        'payment_card_networks_enabled' => isset($_POST['payment_card_networks_enabled']) ? '1' : '0',
        
        // PayPal
        'payment_paypal_enabled' => isset($_POST['payment_paypal_enabled']) ? '1' : '0',
        'payment_paypal_mode' => sanitize($_POST['payment_paypal_mode']),
        'payment_paypal_client_id' => sanitize($_POST['payment_paypal_client_id']),
        'payment_paypal_secret' => sanitize($_POST['payment_paypal_secret']),
        
        // Google Pay
        'payment_googlepay_enabled' => isset($_POST['payment_googlepay_enabled']) ? '1' : '0',
        'payment_googlepay_merchant_id' => sanitize($_POST['payment_googlepay_merchant_id']),
        'payment_googlepay_merchant_name' => sanitize($_POST['payment_googlepay_merchant_name']),
        
        // Apple Pay
        'payment_applepay_enabled' => isset($_POST['payment_applepay_enabled']) ? '1' : '0',
        'payment_applepay_merchant_id' => sanitize($_POST['payment_applepay_merchant_id']),
        'payment_applepay_merchant_name' => sanitize($_POST['payment_applepay_merchant_name']),
        
        // Network International (N-Genius)
        'payment_networks_enabled' => isset($_POST['payment_networks_enabled']) ? '1' : '0',
        'payment_networks_api_key' => sanitize($_POST['payment_networks_api_key']),
        'payment_networks_outlet_id' => sanitize($_POST['payment_networks_outlet_id']),
        'payment_networks_mode' => sanitize($_POST['payment_networks_mode']),

        // Tabby
        'payment_tabby_enabled' => isset($_POST['payment_tabby_enabled']) ? '1' : '0',
        'payment_tabby_public_key' => sanitize($_POST['payment_tabby_public_key']),
        'payment_tabby_secret_key' => sanitize($_POST['payment_tabby_secret_key']),
        'payment_tabby_mode' => sanitize($_POST['payment_tabby_mode'])
    ];
    
    $settings->updateMultiple($updates);
    $_SESSION['success'] = __('payment_settings_updated', 'Payment settings updated successfully!');
    header('Location: payment.php');
    exit;
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-credit-card"></i> <?php echo __('payment_settings', 'Payment Settings'); ?></h1>
</div>

<!-- Settings Navigation -->
<ul class="nav nav-tabs mb-4">
    <li class="nav-item"><a class="nav-link" href="general.php"><?php echo __('general', 'General'); ?></a></li>
    <li class="nav-item"><a class="nav-link" href="appearance.php"><?php echo __('appearance', 'Appearance'); ?></a></li>
    <li class="nav-item"><a class="nav-link" href="languages.php"><?php echo __('languages', 'Languages'); ?></a></li>
    <li class="nav-item"><a class="nav-link" href="currencies.php"><?php echo __('currencies', 'Currencies'); ?></a></li>
    <li class="nav-item"><a class="nav-link" href="email.php"><?php echo __('email', 'Email'); ?></a></li>
    <li class="nav-item"><a class="nav-link active" href="payment.php"><?php echo __('payment', 'Payment'); ?></a></li>
    <li class="nav-item"><a class="nav-link" href="social.php"><?php echo __('social_media', 'Social'); ?></a></li>
</ul>

<?php if (isset($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST">
    <!-- Cash on Delivery -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-money-bill-wave text-success"></i> 
                <?php echo __('cash_on_delivery', 'Cash on Delivery'); ?>
            </h5>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="payment_cod_enabled" 
                       <?php echo $settings->get('payment_cod_enabled') == '1' ? 'checked' : ''; ?>>
                <label class="form-check-label"><?php echo __('enabled', 'Enabled'); ?></label>
            </div>
        </div>
        <div class="card-body">
            <p class="text-muted"><?php echo __('cod_description', 'Accept cash payments upon delivery. No additional configuration required.'); ?></p>
        </div>
    </div>
    
    <!-- Stripe -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fab fa-stripe text-primary"></i> 
                <?php echo __('stripe', 'Stripe'); ?>
            </h5>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="payment_stripe_enabled" id="stripe_enabled"
                       <?php echo $settings->get('payment_stripe_enabled') == '1' ? 'checked' : ''; ?>>
                <label class="form-check-label"><?php echo __('enabled', 'Enabled'); ?></label>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label><?php echo __('publishable_key', 'Publishable Key'); ?></label>
                    <input type="text" name="payment_stripe_public_key" class="form-control"
                           value="<?php echo htmlspecialchars($settings->get('payment_stripe_public_key')); ?>"
                           placeholder="pk_live_...">
                    <small class="text-muted"><?php echo __('stripe_public_hint', 'Start with pk_test_ or pk_live_'); ?></small>
                </div>
                <div class="col-md-6 mb-3">
                    <label><?php echo __('secret_key', 'Secret Key'); ?></label>
                    <input type="password" name="payment_stripe_secret_key" class="form-control"
                           value="<?php echo htmlspecialchars($settings->get('payment_stripe_secret_key')); ?>"
                           placeholder="sk_live_...">
                    <small class="text-muted"><?php echo __('stripe_secret_hint', 'Start with sk_test_ or sk_live_'); ?></small>
                </div>
            </div>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                <?php echo __('stripe_info', 'Get your API keys from'); ?> 
                <a href="https://dashboard.stripe.com/apikeys" target="_blank">Stripe Dashboard</a>
            </div>

            <hr>
            <h6 class="mb-3"><?php echo __('card_logos', 'Card Logos under Credit Card'); ?></h6>
            <div class="row">
                <div class="col-md-3 col-6 mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="payment_card_visa_enabled" id="card_visa_enabled"
                               <?php echo $settings->get('payment_card_visa_enabled', '1') == '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="card_visa_enabled">Visa</label>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="payment_card_mastercard_enabled" id="card_mastercard_enabled"
                               <?php echo $settings->get('payment_card_mastercard_enabled', '1') == '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="card_mastercard_enabled">Mastercard</label>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="payment_card_tabby_enabled" id="card_tabby_enabled"
                               <?php echo $settings->get('payment_card_tabby_enabled', '1') == '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="card_tabby_enabled">Tabby</label>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="payment_card_networks_enabled" id="card_networks_enabled"
                               <?php echo $settings->get('payment_card_networks_enabled', '1') == '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="card_networks_enabled">Networks</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- PayPal -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fab fa-paypal text-info"></i> 
                <?php echo __('paypal', 'PayPal'); ?>
            </h5>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="payment_paypal_enabled"
                       <?php echo $settings->get('payment_paypal_enabled') == '1' ? 'checked' : ''; ?>>
                <label class="form-check-label"><?php echo __('enabled', 'Enabled'); ?></label>
            </div>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label><?php echo __('mode', 'Mode'); ?></label>
                <select name="payment_paypal_mode" class="form-select">
                    <option value="sandbox" <?php echo $settings->get('payment_paypal_mode') == 'sandbox' ? 'selected' : ''; ?>>
                        <?php echo __('sandbox', 'Sandbox (Test)'); ?>
                    </option>
                    <option value="live" <?php echo $settings->get('payment_paypal_mode') == 'live' ? 'selected' : ''; ?>>
                        <?php echo __('live', 'Live (Production)'); ?>
                    </option>
                </select>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label><?php echo __('client_id', 'Client ID'); ?></label>
                    <input type="text" name="payment_paypal_client_id" class="form-control"
                           value="<?php echo htmlspecialchars($settings->get('payment_paypal_client_id')); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label><?php echo __('secret', 'Secret'); ?></label>
                    <input type="password" name="payment_paypal_secret" class="form-control"
                           value="<?php echo htmlspecialchars($settings->get('payment_paypal_secret')); ?>">
                </div>
            </div>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                <?php echo __('paypal_info', 'Get your credentials from'); ?> 
                <a href="https://developer.paypal.com/" target="_blank">PayPal Developer</a>
            </div>
        </div>
    </div>
    
    <!-- Google Pay -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fab fa-google text-danger"></i> 
                <?php echo __('google_pay', 'Google Pay'); ?>
            </h5>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="payment_googlepay_enabled"
                       <?php echo $settings->get('payment_googlepay_enabled') == '1' ? 'checked' : ''; ?>>
                <label class="form-check-label"><?php echo __('enabled', 'Enabled'); ?></label>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label><?php echo __('merchant_id', 'Merchant ID'); ?></label>
                    <input type="text" name="payment_googlepay_merchant_id" class="form-control"
                           value="<?php echo htmlspecialchars($settings->get('payment_googlepay_merchant_id')); ?>"
                           placeholder="BCR2DN...">
                </div>
                <div class="col-md-6 mb-3">
                    <label><?php echo __('merchant_name', 'Merchant Name'); ?></label>
                    <input type="text" name="payment_googlepay_merchant_name" class="form-control"
                           value="<?php echo htmlspecialchars($settings->get('payment_googlepay_merchant_name')); ?>"
                           placeholder="Your Store">
                </div>
            </div>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                <?php echo __('googlepay_info', 'Register at'); ?> 
                <a href="https://pay.google.com/business/console" target="_blank">Google Pay Business Console</a>
            </div>
        </div>
    </div>
    
    <!-- Apple Pay -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fab fa-apple text-dark"></i> 
                <?php echo __('apple_pay', 'Apple Pay'); ?>
            </h5>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="payment_applepay_enabled"
                       <?php echo $settings->get('payment_applepay_enabled') == '1' ? 'checked' : ''; ?>>
                <label class="form-check-label"><?php echo __('enabled', 'Enabled'); ?></label>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label><?php echo __('merchant_id', 'Merchant ID'); ?></label>
                    <input type="text" name="payment_applepay_merchant_id" class="form-control"
                           value="<?php echo htmlspecialchars($settings->get('payment_applepay_merchant_id')); ?>"
                           placeholder="merchant.com.yourstore">
                </div>
                <div class="col-md-6 mb-3">
                    <label><?php echo __('merchant_name', 'Merchant Name'); ?></label>
                    <input type="text" name="payment_applepay_merchant_name" class="form-control"
                           value="<?php echo htmlspecialchars($settings->get('payment_applepay_merchant_name')); ?>"
                           placeholder="Your Store">
                </div>
            </div>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                <?php echo __('applepay_info', 'Configure at'); ?> 
                <a href="https://developer.apple.com/apple-pay/" target="_blank">Apple Developer</a>
            </div>
        </div>
    </div>
    
    <!-- Network International (N-Genius) -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-network-wired text-primary"></i> 
                Network International (N-Genius)
            </h5>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="payment_networks_enabled" id="networks_enabled"
                       <?php echo $settings->get('payment_networks_enabled') == '1' ? 'checked' : ''; ?>>
                <label class="form-check-label"><?php echo __('enabled', 'Enabled'); ?></label>
            </div>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label>Environment Mode</label>
                <select name="payment_networks_mode" class="form-select">
                    <option value="sandbox" <?php echo $settings->get('payment_networks_mode') == 'sandbox' ? 'selected' : ''; ?>>
                        Sandbox (Test)
                    </option>
                    <option value="live" <?php echo $settings->get('payment_networks_mode') == 'live' ? 'selected' : ''; ?>>
                        Live (Production)
                    </option>
                </select>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Outlet Reference ID</label>
                    <input type="text" name="payment_networks_outlet_id" class="form-control"
                           value="<?php echo htmlspecialchars($settings->get('payment_networks_outlet_id')); ?>"
                           placeholder="Outlet ID">
                </div>
                <div class="col-md-6 mb-3">
                    <label>API Key</label>
                    <input type="password" name="payment_networks_api_key" class="form-control"
                           value="<?php echo htmlspecialchars($settings->get('payment_networks_api_key')); ?>"
                           placeholder="Service Account API Key">
                </div>
            </div>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                Get your credentials from 
                <a href="https://portal.ngenius-payments.com/" target="_blank">N-Genius Portal</a>.
            </div>
        </div>
    </div>

    <!-- Tabby -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/tabby.svg" alt="Tabby" style="height: 20px;" class="me-2">
                Tabby (Buy Now Pay Later)
            </h5>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="payment_tabby_enabled" id="tabby_enabled"
                       <?php echo $settings->get('payment_tabby_enabled') == '1' ? 'checked' : ''; ?>>
                <label class="form-check-label"><?php echo __('enabled', 'Enabled'); ?></label>
            </div>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label>Environment Mode</label>
                <select name="payment_tabby_mode" class="form-select">
                    <option value="test" <?php echo $settings->get('payment_tabby_mode') == 'test' ? 'selected' : ''; ?>>
                        Test
                    </option>
                    <option value="live" <?php echo $settings->get('payment_tabby_mode') == 'live' ? 'selected' : ''; ?>>
                        Live
                    </option>
                </select>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Public Key (pk_...)</label>
                    <input type="text" name="payment_tabby_public_key" class="form-control"
                           value="<?php echo htmlspecialchars($settings->get('payment_tabby_public_key')); ?>"
                           placeholder="pk_test_...">
                </div>
                <div class="col-md-6 mb-3">
                    <label>Secret Key (sk_...)</label>
                    <input type="password" name="payment_tabby_secret_key" class="form-control"
                           value="<?php echo htmlspecialchars($settings->get('payment_tabby_secret_key')); ?>"
                           placeholder="sk_test_...">
                </div>
            </div>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                Get your Tabby credentials from the 
                <a href="https://dashboard.tabby.ai/" target="_blank">Tabby Merchant Dashboard</a>.
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-save"></i> <?php echo __('save_payment_settings', 'Save Payment Settings'); ?>
            </button>
        </div>
    </div>
</form>

<?php include '../includes/footer.php'; ?>
