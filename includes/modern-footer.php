</main>

    <!-- Modern Footer -->
    <footer class="footer" style="background-color: var(--footer-bg-color); color: var(--footer-text-color);">
        <!-- Newsletter Section -->
        <section class="py-5 border-bottom border-dark">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h3 class="mb-3"><?php echo __('newsletter_title', 'Stay Updated'); ?></h3>
                        <p class="mb-0"><?php echo __('newsletter_subtitle', 'Subscribe to our newsletter for exclusive offers and new product updates.'); ?></p>
                    </div>
                    <div class="col-md-6">
                        <form class="newsletter-form d-flex gap-2" method="POST" action="<?php echo BASE_URL; ?>newsletter-handler.php">
                            <input type="email" name="email" class="form-control" placeholder="<?php echo __('enter_email', 'Enter your email'); ?>" required>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i> <?php echo __('subscribe', 'Subscribe'); ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main Footer Content -->
        <section class="py-5">
            <div class="container">
                <div class="row">
                    <!-- Company Info -->
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="footer-brand">
                            <?php if ($site_logo): ?>
                                <img src="<?php echo UPLOADS_URL . $site_logo; ?>" alt="<?php echo htmlspecialchars($site_name); ?>" height="40" class="mb-3">
                            <?php else: ?>
                                <i class="fas fa-store fa-2x mb-3"></i>
                            <?php endif; ?>
                            <h4><?php echo htmlspecialchars($site_name); ?></h4>
                            <p class="mb-4"><?php echo $settings->get('site_description', 'Your one-stop shop for all your needs'); ?></p>
                            
                            <!-- Social Media -->
                            <div class="social-links d-flex gap-3">
                                <a href="#" class="social-link" aria-label="Facebook">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a href="#" class="social-link" aria-label="Twitter">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <a href="#" class="social-link" aria-label="Instagram">
                                    <i class="fab fa-instagram"></i>
                                </a>
                                <a href="#" class="social-link" aria-label="LinkedIn">
                                    <i class="fab fa-linkedin-in"></i>
                                </a>
                                <a href="#" class="social-link" aria-label="YouTube">
                                    <i class="fab fa-youtube"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Links -->
                    <div class="col-lg-2 col-md-6 mb-4">
                        <h5 class="mb-4"><?php echo __('quick_links', 'Quick Links'); ?></h5>
                        <ul class="footer-links list-unstyled">
                            <li class="mb-2">
                                <a href="<?php echo BASE_URL; ?>" class="footer-link">
                                    <i class="fas fa-chevron-right me-2"></i> <?php echo __('home', 'Home'); ?>
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="<?php echo BASE_URL; ?>shop.php" class="footer-link">
                                    <i class="fas fa-chevron-right me-2"></i> <?php echo __('shop', 'Shop'); ?>
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="<?php echo BASE_URL; ?>services.php" class="footer-link">
                                    <i class="fas fa-chevron-right me-2"></i> <?php echo __('services', 'Services'); ?>
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="<?php echo BASE_URL; ?>about.php" class="footer-link">
                                    <i class="fas fa-chevron-right me-2"></i> <?php echo __('about', 'About'); ?>
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="<?php echo BASE_URL; ?>contact.php" class="footer-link">
                                    <i class="fas fa-chevron-right me-2"></i> <?php echo __('contact', 'Contact'); ?>
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="<?php echo BASE_URL; ?>certificates.php" class="footer-link">
                                    <i class="fas fa-chevron-right me-2"></i> <?php echo __('certificates', 'Certificates'); ?>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Customer Service -->
                    <div class="col-lg-2 col-md-6 mb-4">
                        <h5 class="mb-4"><?php echo __('customer_service', 'Customer Service'); ?></h5>
                        <ul class="footer-links list-unstyled">
                            <li class="mb-2">
                                <a href="<?php echo BASE_URL; ?>faq.php" class="footer-link">
                                    <i class="fas fa-chevron-right me-2"></i> <?php echo __('faq', 'FAQ'); ?>
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="<?php echo BASE_URL; ?>shipping.php" class="footer-link">
                                    <i class="fas fa-chevron-right me-2"></i> <?php echo __('shipping', 'Shipping'); ?>
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="<?php echo BASE_URL; ?>returns.php" class="footer-link">
                                    <i class="fas fa-chevron-right me-2"></i> <?php echo __('returns', 'Returns'); ?>
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="<?php echo BASE_URL; ?>size-guide.php" class="footer-link">
                                    <i class="fas fa-chevron-right me-2"></i> <?php echo __('size_guide', 'Size Guide'); ?>
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="<?php echo BASE_URL; ?>track-order.php" class="footer-link">
                                    <i class="fas fa-chevron-right me-2"></i> <?php echo __('track_order', 'Track Order'); ?>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Contact Info -->
                    <div class="col-lg-4 col-md-6 mb-4">
                        <h5 class="mb-4"><?php echo __('contact_info', 'Contact Info'); ?></h5>
                        <div class="contact-info">
                            <div class="contact-item mb-3">
                                <i class="fas fa-map-marker-alt me-3"></i>
                                <span><?php echo $settings->get('address', '123 Main Street, City, Country'); ?></span>
                            </div>
                            <div class="contact-item mb-3">
                                <i class="fas fa-phone me-3"></i>
                                <span><?php echo htmlspecialchars($contact_phone); ?></span>
                            </div>
                            <div class="contact-item mb-3">
                                <i class="fas fa-envelope me-3"></i>
                                <span><?php echo htmlspecialchars($contact_email); ?></span>
                            </div>
                            <div class="contact-item mb-3">
                                <i class="fas fa-clock me-3"></i>
                                <span><?php echo $settings->get('business_hours', 'Mon-Fri: 9AM-6PM, Sat: 10AM-4PM'); ?></span>
                            </div>
                        </div>

                        <!-- Payment Methods -->
                        <div class="payment-methods mt-4">
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
            </div>
        </section>

        <!-- Bottom Footer -->
        <section class="py-4 border-top border-dark">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <p class="mb-0">
                            &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($site_name); ?>. 
                            <?php echo __('all_rights_reserved', 'All rights reserved.'); ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <div class="footer-bottom-links d-flex justify-content-md-end gap-4">
                            <a href="<?php echo BASE_URL; ?>terms.php" class="footer-link">
                                <?php echo __('terms_conditions', 'Terms & Conditions'); ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>privacy.php" class="footer-link">
                                <?php echo __('privacy_policy', 'Privacy Policy'); ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>sitemap.php" class="footer-link">
                                <?php echo __('sitemap', 'Sitemap'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </footer>

    <!-- Back to Top Button -->
    <button class="btn btn-primary back-to-top" id="backToTop">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- WhatsApp Chat Button -->
    <a href="https://wa.me/<?php echo str_replace(['+', '-', ' '], '', $contact_phone); ?>" 
       class="btn btn-success whatsapp-chat" 
       target="_blank" 
       aria-label="Chat on WhatsApp">
        <i class="fab fa-whatsapp"></i>
    </a>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Modern Interactions JS -->
    <script src="<?php echo ASSETS_URL; ?>js/modern-interactions.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Initialize modern e-commerce when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            // Hide loading screen
            setTimeout(function() {
                const loadingScreen = document.getElementById('loadingScreen');
                if (loadingScreen) {
                    loadingScreen.style.opacity = '0';
                    setTimeout(function() {
                        loadingScreen.style.display = 'none';
                    }, 300);
                }
            }, 1000);

            // Back to top button
            const backToTop = document.getElementById('backToTop');
            if (backToTop) {
                window.addEventListener('scroll', function() {
                    if (window.pageYOffset > 300) {
                        backToTop.style.display = 'block';
                    } else {
                        backToTop.style.display = 'none';
                    }
                });

                backToTop.addEventListener('click', function() {
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                });
            }

            // Newsletter form - AJAX submission with real backend
            const newsletterForm = document.querySelector('.newsletter-form');
            if (newsletterForm) {
                newsletterForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const emailInput = this.querySelector('input[type="email"]');
                    const btn = this.querySelector('button[type="submit"]');
                    const origHtml = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Subscribing...';
                    btn.disabled = true;

                    const formData = new FormData();
                    formData.append('email', emailInput.value);

                    fetch((typeof BASE_URL !== 'undefined' ? BASE_URL : '/') + 'newsletter-ajax.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (window.modernEcommerce) {
                            window.modernEcommerce.showNotification(data.message, data.success ? 'success' : 'error');
                        } else {
                            alert(data.message);
                        }
                        if (data.success) this.reset();
                    })
                    .catch(() => {
                        if (window.modernEcommerce) {
                            window.modernEcommerce.showNotification('An error occurred. Please try again.', 'error');
                        }
                    })
                    .finally(() => {
                        btn.innerHTML = origHtml;
                        btn.disabled = false;
                    });
                });
            }

            // Search overlay
            const searchToggle = document.querySelector('.search-toggle');
            const searchOverlay = document.querySelector('.search-overlay');
            const searchClose = document.querySelector('.search-close');
            const searchInput = document.querySelector('.search-input');

            if (searchToggle && searchOverlay) {
                searchToggle.addEventListener('click', function() {
                    searchOverlay.classList.remove('d-none');
                    searchOverlay.classList.add('d-flex');
                    setTimeout(() => {
                        searchInput.focus();
                    }, 100);
                });
            }

            if (searchClose && searchOverlay) {
                searchClose.addEventListener('click', function() {
                    searchOverlay.classList.add('d-none');
                    searchOverlay.classList.remove('d-flex');
                    searchInput.value = '';
                });
            }

            // Close search on escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && searchOverlay) {
                    searchOverlay.classList.add('d-none');
                    searchOverlay.classList.remove('d-flex');
                    searchInput.value = '';
                }
            });

            // Theme toggle
            const themeToggle = document.querySelector('.theme-toggle');
            if (themeToggle) {
                themeToggle.addEventListener('click', function() {
                    if (window.modernEcommerce) {
                        window.modernEcommerce.theme.toggle();
                    }
                });
            }

            // Add to cart functionality
            const addToCartButtons = document.querySelectorAll('.add-to-cart');
            addToCartButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const productId = this.dataset.productId;
                    
                    if (window.modernEcommerce) {
                        // Simulate product data
                        const product = {
                            id: productId,
                            name: this.dataset.productName || 'Product',
                            price: parseFloat(this.dataset.price || '0')
                        };
                        
                        window.modernEcommerce.cart.add(product, 1);
                    }
                });
            });

            // Wishlist toggle
            const wishlistButtons = document.querySelectorAll('.wishlist-toggle');
            wishlistButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    this.classList.toggle('active');
                    
                    const isActive = this.classList.contains('active');
                    const message = isActive ? 'Added to wishlist!' : 'Removed from wishlist!';
                    
                    if (window.modernEcommerce) {
                        window.modernEcommerce.showNotification(message, isActive ? 'success' : 'info');
                    }
                });
            });

            // Product quick view
            const quickViewButtons = document.querySelectorAll('.quick-view');
            quickViewButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const productId = this.dataset.productId;
                    
                    if (window.modernEcommerce) {
                        window.modernEcommerce.showNotification('Quick view coming soon!', 'info');
                    }
                });
            });

            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Initialize popovers
            const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            const popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });

            // Form validation
            const forms = document.querySelectorAll('.needs-validation');
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });

            // Smooth scroll for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // Lazy loading for images
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.classList.remove('lazy');
                            imageObserver.unobserve(img);
                        }
                    });
                });

                document.querySelectorAll('img[data-src]').forEach(img => {
                    imageObserver.observe(img);
                });
            }

            console.log('🚀 Modern E-Commerce Frontend Loaded Successfully!');
        });

        // Performance monitoring
        window.addEventListener('load', function() {
            const loadTime = performance.now();
            console.log(`Page load time: ${loadTime.toFixed(2)}ms`);
        });
    </script>
</body>
</html>
