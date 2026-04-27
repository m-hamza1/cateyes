<?php
// Get dynamic settings
$site_name = $settings->get('site_name', 'Your Store');
$contact_email = $settings->get('contact_email', 'contact@yourstore.com');
$contact_phone = $settings->get('contact_phone', '+1234567890');
$contact_address = $settings->get('contact_address', '123 Main Street, City, Country');
$primary_color = $settings->get('primary_color', '#007bff');
$secondary_color = $settings->get('secondary_color', '#6c757d');

// Get footer links and info
$footer_links = $db->fetchAll("
    SELECT * FROM footer_links 
    WHERE is_active = 1 
    ORDER BY sort_order
");

// Get social media links from settings table
$social_links = [
    [
        'name' => 'Facebook',
        'url' => $settings->get('social_facebook', ''),
        'icon' => 'fab fa-facebook-f'
    ],
    [
        'name' => 'SnapChat',
        'url' => $settings->get('social_twitter', ''),
        'icon' => 'fab fa-snapchat'
    ],
    [
        'name' => 'Instagram',
        'url' => $settings->get('social_instagram', ''),
        'icon' => 'fab fa-instagram'
    ],
    [
        'name' => 'Tiktok',
        'url' => $settings->get('social_linkedin', ''),
        'icon' => 'fab fa-tiktok'
    ],
    [
        'name' => 'YouTube',
        'url' => $settings->get('social_youtube', ''),
        'icon' => 'fab fa-youtube'
    ],
    [
        'name' => 'TikTok',
        'url' => $settings->get('social_tiktok', ''),
        'icon' => 'fab fa-tiktok'
    ]
];
?>

<!-- Footer -->
<footer class="pt-5 pb-4">
    <div class="container">
        <div class="row">
            <!-- Company Info -->
            <div class="col-lg-4 mb-4">
                <h5 class="mb-3"><?php echo htmlspecialchars($site_name); ?></h5>
                <p class="text-muted"><?php echo $settings->get('site_description', 'Your one-stop shop for all your needs'); ?></p>
                <div class="contact-info">
                    <p class="text-muted">
                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($contact_address); ?><br>
                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($contact_phone); ?><br>
                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($contact_email); ?>
                    </p>
                </div>
                
                <!-- Social Links -->
                <?php 
                // Filter out social links that don't have values in settings table
                $active_social_links = array_filter($social_links, function($link) {
                    $url = $link['url'];
                    // Only show if URL has a value (not empty)
                    return $url && $url !== '' && trim($url) !== '';
                });
                
                if (!empty($active_social_links)): 
                ?>
                <div class="social-links mt-3">
                    <?php foreach ($active_social_links as $social): ?>
                    <a href="<?php echo htmlspecialchars($social['url']); ?>" target="_blank" title="<?php echo htmlspecialchars($social['name']); ?>" class="social-link">
                        <i class="<?php echo htmlspecialchars($social['icon']); ?>"></i>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Customer Service -->
            <div class="col-lg-2 col-md-6 mb-4">
                <h5><?php echo __('customer_service', 'Customer Service'); ?></h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>faq.php" class="text-muted text-decoration-none"><?php echo __('faq', 'FAQ'); ?></a></li>
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>terms.php" class="text-muted text-decoration-none"><?php echo __('terms', 'Terms & Conditions'); ?></a></li>
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>privacy.php" class="text-muted text-decoration-none"><?php echo __('privacy', 'Privacy Policy'); ?></a></li>
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>shipping.php" class="text-muted text-decoration-none"><?php echo __('shipping', 'Shipping Info'); ?></a></li>
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>order-tracking.php" class="text-muted text-decoration-none"><?php echo __('track_order', 'Track Order'); ?></a></li>
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>returns.php" class="text-muted text-decoration-none"><?php echo __('returns', 'Returns'); ?></a></li>
                </ul>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6 mb-4">
                <h5><?php echo __('quick_links', 'Quick Links'); ?></h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>" class="text-muted text-decoration-none"><?php echo __('home', 'Home'); ?></a></li>
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>shop.php" class="text-muted text-decoration-none"><?php echo __('shop', 'Shop'); ?></a></li>
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>services.php" class="text-muted text-decoration-none"><?php echo __('services', 'Services'); ?></a></li>
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>news.php" class="text-muted text-decoration-none"><?php echo __('news', 'News'); ?></a></li>
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>about.php" class="text-muted text-decoration-none"><?php echo __('about', 'About'); ?></a></li>
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>contact.php" class="text-muted text-decoration-none"><?php echo __('contact', 'Contact'); ?></a></li>
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>certificates.php" class="text-muted text-decoration-none"><?php echo __('certificates', 'Certificates'); ?></a></li>
                </ul>
            </div>
            
            <!-- Newsletter -->
            <div class="col-lg-4 mb-4">
                <h5><?php echo __('newsletter', 'Newsletter'); ?></h5>
                <p class="text-muted"><?php echo __('newsletter_text', 'Subscribe to get special offers and updates'); ?></p>
                <form class="newsletter-form" method="POST" action="<?php echo BASE_URL; ?>newsletter-handler.php">
                    <div class="input-group mb-3">
                        <input type="email" class="form-control" name="email" placeholder="<?php echo __('your_email', 'Your Email'); ?>" required>
                        <button class="btn btn-primary" type="submit"><?php echo __('subscribe', 'Subscribe'); ?></button>
                    </div>
                </form>
                
                <!-- Payment Methods -->
                <div class="mt-4">
                    <h6 class="mb-3"><?php echo __('payment_methods', 'Payment Methods'); ?></h6>
                    <?php
                    // Payment method enable flags from settings
                    $footer_paypal_enabled = $settings->get('payment_paypal_enabled') === '1';
                    $footer_googlepay_enabled = $settings->get('payment_googlepay_enabled') === '1';
                    $footer_applepay_enabled = $settings->get('payment_applepay_enabled') === '1';

                    $footer_card_visa_enabled = $settings->get('payment_card_visa_enabled', '1') === '1';
                    $footer_card_master_enabled = $settings->get('payment_card_mastercard_enabled', '1') === '1';
                    $footer_card_tabby_enabled = $settings->get('payment_card_tabby_enabled', '1') === '1';
                    $footer_card_networks_enabled = $settings->get('payment_card_networks_enabled', '1') === '1';
                    ?>
                    <div class="payment-methods-grid d-flex flex-wrap align-items-center gap-2 mt-2">
                        <!-- Visa -->
                        <?php if ($footer_card_visa_enabled): ?>
                            <div class="payment-card-badge" title="Visa">
                                <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/visa.svg" alt="Visa">
                            </div>
                        <?php endif; ?>
                        <!-- Mastercard -->
                        <?php if ($footer_card_master_enabled): ?>
                            <div class="payment-card-badge" title="Mastercard">
                                <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/mastercard-clean.svg" alt="Mastercard">
                            </div>
                        <?php endif; ?>
                        <!-- Tabby -->
                        <?php if ($footer_card_tabby_enabled): ?>
                            <div class="payment-card-badge tabby-badge" title="Tabby - Buy Now, Pay Later">
                                <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/tabby.svg" alt="Tabby">
                            </div>
                        <?php endif; ?>
                        <!-- Networks -->
                        <?php if ($footer_card_networks_enabled): ?>
                            <div class="payment-card-badge" title="Networks">
                                <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/networks.svg" alt="Networks">
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($footer_paypal_enabled || $footer_googlepay_enabled || $footer_applepay_enabled): ?>
                        <div class="payment-methods-grid d-flex flex-wrap align-items-center gap-2 mt-2">
                            <?php if ($footer_paypal_enabled): ?>
                                <div class="payment-card-badge" title="PayPal">
                                    <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/paypall.png" alt="PayPal">
                                </div>
                            <?php endif; ?>
                            <?php if ($footer_googlepay_enabled): ?>
                                <div class="payment-card-badge" title="Google Pay">
                                    <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/googlepay.png" alt="Google Pay">
                                </div>
                            <?php endif; ?>
                            <?php if ($footer_applepay_enabled): ?>
                                <div class="payment-card-badge" title="Apple Pay">
                                    <img src="<?php echo BASE_URL; ?>assets/images/payment-icons/applepay.png" alt="Apple Pay">
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <hr class="my-4 bg-secondary">
        
        <!-- Bottom Footer -->
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex flex-wrap justify-content-between align-items-center">
                    <p class="text-muted mb-0">
                        &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($site_name); ?>. <?php echo __('all_rights_reserved', 'All rights reserved'); ?>.
                    </p>
                    <div class="footer-links">
                        <a href="privacy-policy.php" class="text-muted text-decoration-none me-3">Privacy Policy</a>
                        <a href="cookie-policy.php" class="text-muted text-decoration-none me-3">Cookie Policy</a>
                        <a href="return-policy.php" class="text-muted text-decoration-none me-3">Return Policy</a>
                        <a href="terms-and-conditions.php" class="text-muted text-decoration-none me-3">Terms and Conditions</a>
                        <a href="legal-notice.php" class="text-muted text-decoration-none me-3">Legal Notice</a>
                        <a href="payment-policy.php" class="text-muted text-decoration-none me-3">Payment Policy</a>
                        <a href="shipping-delivery-policy.php" class="text-muted text-decoration-none">Shipping & Delivery Policy</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Scroll to Top Button -->
<button id="backToTop" class="btn btn-danger back-to-top" 
        onclick="window.scrollTo({top: 0, behavior: 'smooth'})" 
        aria-label="Scroll to top">
    <i class="fas fa-arrow-up"></i>
</button>

<!-- WhatsApp Chat Button -->
<a href="https://wa.me/<?php echo str_replace(['+', '-', ' '], '', $contact_phone); ?>" 
   class="btn btn-danger whatsapp-chat" 
   target="_blank" 
   aria-label="Chat on WhatsApp">
    <i class="fab fa-whatsapp"></i>
</a>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Scroll to Top Button Script -->
<script>
// Show/hide scroll to top button based on scroll position
window.addEventListener('scroll', function() {
    const backToTopButton = document.getElementById('backToTop');
    if (window.pageYOffset > 300) {
        backToTopButton.style.display = 'block';
    } else {
        backToTopButton.style.display = 'none';
    }
});

// Smooth scroll to top when button is clicked
document.getElementById('backToTop').addEventListener('click', function() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
});
</script>

<!-- E-commerce Product Integration -->
<script src="<?php echo BASE_URL; ?>product-integration.js"></script>

<style>
/* WhatsApp and Back to Top positioning */
.whatsapp-chat {
    position: fixed;
    bottom: 20px;
    right: 80px; /* Moved to the left of back-to-top button */
    z-index: 9998;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    transition: all 0.3s ease;
}

.whatsapp-chat:hover {
    transform: scale(1.1);
}

.back-to-top {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9998;
    display: none;
    width: 50px;
    height: 50px;
    border-radius: 50%;
}

/* Mobile responsive adjustments */
@media (max-width: 768px) {
    .whatsapp-chat {
        bottom: 20px;
        right: 70px; /* Adjusted for mobile */
    }
    
    .back-to-top {
        bottom: 15px;
        right: 15px;
    }
}
</style>

<script>
// Notification system - now handled in product-integration.js
// updateCartCount - now handled in product-integration.js

// Initialize add to cart functionality - Handled in product-integration.js
document.addEventListener('DOMContentLoaded', function() {
    // Existing logic in product-integration.js is sufficient
});
</script>
</body>
</html>
