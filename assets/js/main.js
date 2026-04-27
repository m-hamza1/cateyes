/**
 * Main JavaScript File
 * Dynamic functionality for the e-commerce website
 * Uses dynamic colors and fonts from database
 */

// Global variables
let cartCount = 0;
let wishlistCount = 0;
let currentLanguage = 'en';
let currentCurrency = 'USD';

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

/**
 * Initialize the application
 */
function initializeApp() {
    // Load dynamic settings
    loadDynamicSettings();
    
    // Initialize components
    initializeNavigation();
    initializeCart();
    initializeWishlist();
    initializeForms();
    initializeAnimations();
    initializeTooltips();
    initializeModals();
    
    // Setup event listeners
    setupEventListeners();
    
    // Initialize language and currency
    initializeLanguageCurrency();
}

/**
 * Load dynamic settings from database
 */
function loadDynamicSettings() {
    // These would normally be loaded via AJAX from the server
    // For now, we'll use the CSS variables that are set dynamically
    const rootStyles = getComputedStyle(document.documentElement);
    
    // Store dynamic colors
    window.dynamicColors = {
        primary: rootStyles.getPropertyValue('--primary-color').trim(),
        secondary: rootStyles.getPropertyValue('--secondary-color').trim(),
        footerBg: rootStyles.getPropertyValue('--footer-bg-color').trim(),
        footerText: rootStyles.getPropertyValue('--footer-text-color').trim(),
        footerLink: rootStyles.getPropertyValue('--footer-link-color').trim(),
        danger: rootStyles.getPropertyValue('--danger-color').trim(),
        warning: rootStyles.getPropertyValue('--warning-color').trim(),
        info: rootStyles.getPropertyValue('--info-color').trim(),
        success: rootStyles.getPropertyValue('--success-color').trim()
    };
    
    // Store dynamic fonts
    window.dynamicFonts = {
        family: rootStyles.getPropertyValue('--font-family').trim(),
        size: rootStyles.getPropertyValue('--font-size').trim()
    };
}

/**
 * Initialize navigation
 */
function initializeNavigation() {
    // Mobile menu toggle
    const mobileMenuToggle = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('.navbar-collapse');
    
    if (mobileMenuToggle && navbarCollapse) {
        mobileMenuToggle.addEventListener('click', function() {
            navbarCollapse.classList.toggle('show');
        });
    }
    
    // Close mobile menu when clicking outside
    document.addEventListener('click', function(e) {
        if (navbarCollapse && navbarCollapse.classList.contains('show')) {
            if (!navbarCollapse.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
                navbarCollapse.classList.remove('show');
            }
        }
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
}

/**
 * Initialize cart functionality
 */
function initializeCart() {
    // Add to cart buttons
    document.querySelectorAll('.add-to-cart-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.dataset.productId;
            addToCart(productId);
        });
    });
    
    // Update cart display
    updateCartDisplay();
}

/**
 * Add product to cart
 */
function addToCart(productId, quantity = 1) {
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    
    fetch(BASE_URL + 'includes/cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartCount(data.cart_count);
            showNotification(data.message || 'Product added to cart!', 'success');
        } else {
            showNotification(data.message || 'Error adding to cart', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

/**
 * Update cart count display
 */
function updateCartCount(count) {
    cartCount = count;
    const cartBadges = document.querySelectorAll('#navbar-cart-badge, .cart-badge');
    cartBadges.forEach(badge => {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'block' : 'none';
    });
}

/**
 * Update cart display
 */
function updateCartDisplay() {
    // Fetch cart data from server
    fetch(BASE_URL + 'includes/cart.php?action=get')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCartCount(data.cart_count);
            }
        })
        .catch(error => {
            console.error('Error fetching cart:', error);
        });
}

/**
 * Initialize wishlist functionality
 */
function initializeWishlist() {
    // Add to wishlist buttons
    document.querySelectorAll('.add-to-wishlist-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.dataset.productId;
            addToWishlist(productId);
        });
    });
    
    // Update wishlist display
    updateWishlistDisplay();
}

/**
 * Add product to wishlist
 */
function addToWishlist(productId) {
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    
    fetch(BASE_URL + 'includes/wishlist.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateWishlistCount(data.wishlist_count);
            showNotification(data.message || 'Product added to wishlist!', 'success');
        } else {
            showNotification(data.message || 'Error adding to wishlist', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

/**
 * Update wishlist count display
 */
function updateWishlistCount(count) {
    wishlistCount = count;
    const wishlistBadges = document.querySelectorAll('#navbar-wishlist-badge, .wishlist-badge');
    wishlistBadges.forEach(badge => {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'block' : 'none';
    });
}

/**
 * Update wishlist display
 */
function updateWishlistDisplay() {
    // Fetch wishlist data from server
    fetch(BASE_URL + 'includes/wishlist.php?action=get')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateWishlistCount(data.wishlist_count);
            }
        })
        .catch(error => {
            console.error('Error fetching wishlist:', error);
        });
}

/**
 * Initialize forms
 */
function initializeForms() {
    // Form validation
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(form)) {
                e.preventDefault();
            }
        });
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
}

/**
 * Validate form
 */
function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('is-invalid');
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

/**
 * Initialize animations
 */
function initializeAnimations() {
    // Fade in elements
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
            }
        });
    }, observerOptions);
    
    // Observe all elements with fade-in class
    document.querySelectorAll('.fade-in').forEach(el => {
        observer.observe(el);
    });
    
    // Product image zoom
    document.querySelectorAll('.product-image').forEach(img => {
        img.addEventListener('click', function() {
            openImageModal(this.src, this.alt);
        });
    });
}

/**
 * Open image modal
 */
function openImageModal(src, alt) {
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = `
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body p-0">
                    <img src="${src}" class="img-fluid" alt="${alt}">
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
    
    modal.addEventListener('hidden.bs.modal', () => {
        document.body.removeChild(modal);
    });
}

/**
 * Initialize tooltips
 */
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Initialize modals
 */
function initializeModals() {
    // Auto-focus on modal inputs
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('shown.bs.modal', function() {
            const input = this.querySelector('input[type="text"], input[type="email"], input[type="password"]');
            if (input) {
                input.focus();
            }
        });
    });
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Back to top button
    const backToTopButton = document.getElementById('backToTop');
    if (backToTopButton) {
        window.addEventListener('scroll', function() {
            if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
                backToTopButton.style.display = 'block';
            } else {
                backToTopButton.style.display = 'none';
            }
        });
        
        backToTopButton.addEventListener('click', function() {
            document.body.scrollTop = 0;
            document.documentElement.scrollTop = 0;
        });
    }
    
    // Newsletter form
    const newsletterForm = document.querySelector('.newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            subscribeNewsletter(this);
        });
    }
    
    // Search form
    const searchForm = document.querySelector('.search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            const searchInput = this.querySelector('input[type="search"]');
            if (searchInput && !searchInput.value.trim()) {
                e.preventDefault();
                searchInput.focus();
            }
        });
    }
}

/**
 * Subscribe to newsletter
 */
function subscribeNewsletter(form) {
    const formData = new FormData(form);
    
    fetch(BASE_URL + 'newsletter-ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message || 'Successfully subscribed to newsletter!', 'success');
            form.reset();
        } else {
            showNotification(data.message || 'Error subscribing to newsletter', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

/**
 * Initialize language and currency
 */
function initializeLanguageCurrency() {
    // Language switcher
    document.querySelectorAll('.language-switcher a').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const lang = this.dataset.lang;
            switchLanguage(lang);
        });
    });
    
    // Currency switcher
    document.querySelectorAll('.currency-switcher a').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const currency = this.dataset.currency;
            switchCurrency(currency);
        });
    });
}

/**
 * Switch language
 */
function switchLanguage(lang) {
    window.location.href = `${window.location.pathname}?lang=${lang}`;
}

/**
 * Switch currency
 */
function switchCurrency(currency) {
    window.location.href = `${window.location.pathname}?currency=${currency}`;
}

/**
 * Show notification
 */
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : 'success'} position-fixed top-0 start-50 translate-middle-x mt-3`;
    notification.style.zIndex = '9999';
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

/**
 * Format price with currency
 */
function formatPrice(price, currency = 'USD') {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency
    }).format(price);
}

/**
 * Debounce function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Throttle function
 */
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

/**
 * Check if element is in viewport
 */
function isInViewport(element) {
    const rect = element.getBoundingClientRect();
    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
}

/**
 * Get URL parameters
 */
function getUrlParams() {
    const params = {};
    const urlParams = new URLSearchParams(window.location.search);
    for (const pair of urlParams.entries()) {
        params[pair[0]] = pair[1];
    }
    return params;
}

/**
 * Set URL parameter
 */
function setUrlParam(param, value) {
    const url = new URL(window.location);
    url.searchParams.set(param, value);
    window.history.replaceState({}, '', url);
}

/**
 * Remove URL parameter
 */
function removeUrlParam(param) {
    const url = new URL(window.location);
    url.searchParams.delete(param);
    window.history.replaceState({}, '', url);
}

// Export functions for global use
window.addToCart = addToCart;
window.addToWishlist = addToWishlist;
window.showNotification = showNotification;
window.formatPrice = formatPrice;
window.openImageModal = openImageModal;
