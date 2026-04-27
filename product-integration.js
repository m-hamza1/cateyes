// E-commerce Integration JavaScript
// Add this to your product pages to enable cart and wishlist functionality

// Add to cart functionality
function addToCart(productId, quantity = null, variationId = 0) {
    // Get quantity from input if not provided
    if (quantity === null) {
        const quantityInput = document.getElementById('quantity');
        if (quantityInput) {
            quantity = quantityInput.value;
        } else {
            quantity = 1;
        }
    }

    // Get variation from checked radio if not provided
    if (variationId === 0) {
        const variationInput = document.querySelector('input[name="variation"]:checked');
        if (variationInput) {
            variationId = variationInput.value;
        }
    }

    console.log('PRODUCT-INTEGRATION: Adding to cart - productId:', productId, 'quantity:', quantity, 'variationId:', variationId);

    const body = new URLSearchParams();
    body.append('action', 'add_to_cart');
    body.append('product_id', productId);
    body.append('quantity', quantity);
    body.append('variation_id', variationId);

    fetch('product-ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: body.toString()
    })
        .then(response => {
            console.log('PRODUCT-INTEGRATION: Raw response:', response);

            // Check if response is OK
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return response.text();
        })
        .then(text => {
            console.log('PRODUCT-INTEGRATION: Raw response text:', text);

            // Check if response looks like HTML (error page)
            if (text.trim().startsWith('<')) {
                console.error('PRODUCT-INTEGRATION: Server returned HTML instead of JSON');
                showNotification('Server error occurred. Please try again.', 'error');
                return;
            }

            // Try to parse JSON
            try {
                const data = JSON.parse(text);
                console.log('PRODUCT-INTEGRATION: Parsed response:', data);

                if (data.success) {
                    // Update cart count in header
                    updateCartCount(data.cart_count);

                    // Show success message
                    showNotification('Product added to cart! Quantity: ' + quantity, 'success');

                    // Show side cart popup
                    showSideCartPopup();

                    // Reset quantity to 1 after successful add
                    const quantityInput = document.getElementById('quantity');
                    if (quantityInput) {
                        quantityInput.value = 1;
                    }

                    console.log('SUCCESS: Product added with quantity', quantity);
                } else {
                    console.error('Cart add failed:', data.message);
                    showNotification(data.message || 'Error adding product to cart', 'error');
                }
            } catch (jsonError) {
                console.error('PRODUCT-INTEGRATION: JSON parse error:', jsonError);
                console.error('Response text was:', text);
                showNotification('Invalid response from server. Please try again.', 'error');
            }
        })
        .catch(error => {
            console.error('PRODUCT-INTEGRATION: Network error:', error);
            showNotification('Network error: ' + error.message, 'error');
        });
}

// Show side cart popup
function showSideCartPopup() {
    // Create side cart element if it doesn't exist
    let sideCart = document.getElementById('side-cart-popup');
    if (!sideCart) {
        sideCart = createSideCartPopup();
        document.body.appendChild(sideCart);

        // Add backdrop click handler
        const backdrop = sideCart.querySelector('.side-cart-backdrop');
        if (backdrop) {
            backdrop.addEventListener('click', hideSideCartPopup);
        }
    }

    // Load cart data
    loadCartData();

    // Show the popup with animation
    sideCart.classList.add('show');

    // Auto-hide after 30 seconds
    setTimeout(() => {
        if (sideCart.classList.contains('show')) {
            hideSideCartPopup();
        }
    }, 30000);
}

// Create side cart popup HTML
function createSideCartPopup() {
    const sideCart = document.createElement('div');
    sideCart.id = 'side-cart-popup';
    sideCart.className = 'side-cart-popup';

    sideCart.innerHTML = `
        <div class="side-cart-backdrop"></div>
        <div class="side-cart-content">
            <div class="side-cart-header">
                <h5><i class="fas fa-shopping-cart me-2"></i><span class="cart-title">Shopping Cart</span></h5>
                <button class="btn-close ms-auto" onclick="hideSideCartPopup()"></button>
            </div>
            
            <div class="side-cart-body">
                <div id="side-cart-items" class="side-cart-items">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted"><span class="loading-text">Loading cart...</span></p>
                    </div>
                </div>
                
                <div class="side-cart-summary">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="subtotal-label">Subtotal:</span>
                        <span id="side-cart-subtotal">0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="total-label"><strong>Total:</strong></span>
                        <span id="side-cart-total" class="fw-bold text-primary">0.00</span>
                    </div>
                </div>
            </div>
            
            <div class="side-cart-footer">
                <button class="btn btn-outline-secondary w-100 mb-2" onclick="showWishlistQuickView()">
                    <i class="fas fa-heart me-2"></i><span class="wishlist-text">View Wishlist</span>
                </button>
                <button class="btn btn-primary w-100" onclick="goToCart()">
                    <i class="fas fa-shopping-cart me-2"></i><span class="cart-text">Go to Cart</span>
                </button>
            </div>
        </div>
    `;

    return sideCart;
}

// Hide side cart popup
function hideSideCartPopup() {
    const sideCart = document.getElementById('side-cart-popup');
    if (sideCart) {
        sideCart.classList.remove('show');
    }
}

// Load cart data from session
function loadCartData() {
    fetch('product-ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_cart_data'
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const currencyFormat = data.currency_format || '${price}';
                const exchangeRate = data.exchange_rate || 1.0;
                const uploadsUrl = data.uploads_products_url || '';
                const placeholder = data.placeholder_url || 'assets/images/placeholder-product.jpg';
                renderCartItems(data.items, data.is_arabic, data.translations, currencyFormat, exchangeRate, uploadsUrl, placeholder);
                updateCartSummary(data.items, data.is_arabic, data.translations, currencyFormat, exchangeRate);
            } else {
                showEmptyCart(data.is_arabic, data.translations);
            }
        })
        .catch(error => {
            console.error('Error loading cart:', error);
            showEmptyCart(false, null);
        });
}

// Render cart items (uploadsUrl and placeholderUrl from get_cart_data)
function renderCartItems(items, isArabic = false, translations = null, currencyFormat = '${price}', exchangeRate = 1.0, uploadsProductsUrl = '', placeholderUrl = '') {
    const container = document.getElementById('side-cart-items');
    const sideCart = document.getElementById('side-cart-popup');

    // Apply RTL/LTR class to popup
    if (sideCart) {
        if (isArabic) {
            sideCart.classList.add('rtl');
            sideCart.classList.remove('ltr');
        } else {
            sideCart.classList.add('ltr');
            sideCart.classList.remove('rtl');
        }
    }

    if (!items || items.length === 0) {
        showEmptyCart(isArabic, translations);
        return;
    }

    let html = '';
    items.forEach(item => {
        // Use server-provided URLs for correct domain
        const imagePath = item.image && uploadsProductsUrl ? (uploadsProductsUrl + item.image) : (placeholderUrl || 'assets/images/placeholder-product.jpg');

        // Unit price formatting
        const unitPrice = parseFloat(item.price);
        const displayUnitPrice = (unitPrice * exchangeRate).toFixed(2);
        const formattedUnitPrice = currencyFormat.replace('{price}', displayUnitPrice);
        const unitPriceLabel = isArabic ? 'سعر الوحدة:' : 'Unit Price:';

        // Store base price (without exchange rate) in data attribute
        const basePrice = item.price * item.quantity;
        const displayPrice = (basePrice * exchangeRate).toFixed(2);
        const formattedPrice = currencyFormat.replace('{price}', displayPrice);

        html += `
            <div class="side-cart-item">
                <div class="d-flex align-items-center">
                    <div class="side-cart-item-image">
                        <img src="${imagePath}" 
                             alt="${item.name}" 
                             class="img-fluid rounded"
                             onerror="this.src='${placeholderUrl || 'assets/images/placeholder-product.jpg'}'">
                    </div>
                    <div class="side-cart-item-details flex-grow-1">
                        <h6 class="mb-1">${item.name}</h6>
                        ${item.variation_name ? `<div class="small text-muted mb-1">${item.variation_name}</div>` : ''}
                        <div class="unit-price-display mb-1 small text-muted">
                            ${unitPriceLabel} <span data-unit-price="${unitPrice}">${formattedUnitPrice}</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <span class="text-primary fw-bold" data-base-price="${basePrice}">${formattedPrice}</span>
                            <div class="d-flex align-items-center flex-nowrap gap-1">
                                <button class="btn btn-sm btn-outline-secondary" onclick="updateCartItem(${item.product_id}, ${item.quantity - 1})" title="-">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <span class="mx-2">${item.quantity}</span>
                                <button class="btn btn-sm btn-outline-secondary" onclick="updateCartItem(${item.product_id}, ${item.quantity + 1})" title="+">
                                    <i class="fas fa-plus"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="removeCartItem(${item.product_id})" title="Remove">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;

    // Update UI text based on language
    if (translations) {
        updateUIText(translations);
    }

    // Load proper currency formatting
    loadCurrencyFormatting();
}

// Load proper currency formatting from server
function loadCurrencyFormatting() {
    fetch('product-ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_currency_format'
    })
        .then(response => response.json())
        .then(data => {
            console.log('Current currency format data:', data);
            if (data.success && data.currency_format) {
                // Update all price displays with proper currency format and exchange rate
                const priceElements = document.querySelectorAll('[data-base-price]');
                priceElements.forEach(element => {
                    const basePrice = parseFloat(element.dataset.basePrice);
                    const convertedPrice = basePrice * (data.exchange_rate || 1.0);
                    element.textContent = data.currency_format.replace('{price}', convertedPrice.toFixed(2));
                });

                // Update unit price displays
                const unitPriceElements = document.querySelectorAll('[data-unit-price]');
                unitPriceElements.forEach(element => {
                    const unitPrice = parseFloat(element.dataset.unitPrice);
                    const convertedPrice = unitPrice * (data.exchange_rate || 1.0);
                    element.textContent = data.currency_format.replace('{price}', convertedPrice.toFixed(2));
                });

                // Update cart summary with exchange rate
                updateCartSummaryWithFormat(data.currency_format, data.exchange_rate);

                // Store current currency info for future use
                window.currentCurrency = {
                    code: data.currency_code,
                    symbol: data.currency_symbol,
                    format: data.currency_format,
                    exchangeRate: data.exchange_rate || 1.0,
                    isDefault: data.is_default || false
                };

                console.log('Using current currency:', data.currency_code, 'with symbol:', data.currency_symbol, 'exchange rate:', data.exchange_rate);
            }
        })
        .catch(error => {
            console.error('Error loading currency format:', error);
        });
}

// Update cart summary with proper currency format
function updateCartSummaryWithFormat(format, exchangeRate = 1.0) {
    const subtotalElement = document.getElementById('side-cart-subtotal');
    const totalElement = document.getElementById('side-cart-total');

    if (subtotalElement && totalElement) {
        const subtotal = parseFloat(subtotalElement.dataset.basePrice || 0);
        const total = parseFloat(totalElement.dataset.basePrice || 0);

        // Apply exchange rate conversion
        const convertedSubtotal = subtotal * exchangeRate;
        const convertedTotal = total * exchangeRate;

        subtotalElement.textContent = format.replace('{price}', convertedSubtotal.toFixed(2));
        totalElement.textContent = format.replace('{price}', convertedTotal.toFixed(2));
    }
}

// Update UI text based on language
function updateUIText(translations) {
    // Update header title
    const cartTitle = document.querySelector('.cart-title');
    if (cartTitle && translations.cart_title) {
        cartTitle.textContent = translations.cart_title;
    }

    // Update loading text
    const loadingText = document.querySelector('.loading-text');
    if (loadingText && translations.loading) {
        loadingText.textContent = translations.loading;
    }

    // Update button texts
    const wishlistText = document.querySelector('.wishlist-text');
    if (wishlistText && translations.view_wishlist) {
        wishlistText.textContent = translations.view_wishlist;
    }

    const cartText = document.querySelector('.cart-text');
    if (cartText && translations.go_to_cart) {
        cartText.textContent = translations.go_to_cart;
    }
}

// Format price like the site's Currency class
function formatPrice(price) {
    // Use the stored currency format if available
    if (window.currentCurrency && window.currentCurrency.format) {
        // Apply exchange rate conversion
        const convertedPrice = parseFloat(price) * (window.currentCurrency.exchangeRate || 1.0);
        return window.currentCurrency.format.replace('{price}', convertedPrice.toFixed(2));
    }

    // Fallback to AED format
    return parseFloat(price).toFixed(2) + ' AED';
}

// Show empty cart message
function showEmptyCart(isArabic = false, translations = null) {
    const container = document.getElementById('side-cart-items');
    const sideCart = document.getElementById('side-cart-popup');

    // Apply RTL/LTR class to popup
    if (sideCart) {
        if (isArabic) {
            sideCart.classList.add('rtl');
            sideCart.classList.remove('ltr');
        } else {
            sideCart.classList.add('ltr');
            sideCart.classList.remove('rtl');
        }
    }

    const emptyTitle = translations && translations.empty_cart ? translations.empty_cart : 'Your cart is empty';
    const emptyMessage = translations && translations.empty_message ? translations.empty_message : 'Add some products to get started!';
    const continueText = translations && translations.continue_shopping ? translations.continue_shopping : 'Continue Shopping';

    container.innerHTML = `
        <div class="text-center py-5">
            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
            <h6 class="text-muted">${emptyTitle}</h6>
            <p class="text-muted small">${emptyMessage}</p>
            <button class="btn btn-primary btn-sm mt-3" onclick="hideSideCartPopup()">
                ${continueText}
            </button>
        </div>
    `;

    // Update UI text based on language
    if (translations) {
        updateUIText(translations);
    }
}

// Update cart summary
function updateCartSummary(items, isArabic = false, translations = null, currencyFormat = '${price}', exchangeRate = 1.0) {
    let subtotal = 0;
    items.forEach(item => {
        subtotal += item.price * item.quantity;
    });

    // Use currency format from server (AED, $, etc.)
    const displaySubtotal = (subtotal * exchangeRate).toFixed(2);
    const formattedSubtotal = currencyFormat.replace('{price}', displaySubtotal);
    const formattedTotal = formattedSubtotal;

    const subtotalElement = document.getElementById('side-cart-subtotal');
    const totalElement = document.getElementById('side-cart-total');

    if (subtotalElement) {
        subtotalElement.textContent = formattedSubtotal;
        subtotalElement.dataset.basePrice = subtotal; // Store base price for exchange rate conversion
    }

    if (totalElement) {
        totalElement.textContent = formattedTotal;
        totalElement.dataset.basePrice = subtotal; // Store base price for exchange rate conversion
    }

    // Update labels based on language
    if (translations) {
        const subtotalLabel = document.querySelector('.subtotal-label');
        const totalLabel = document.querySelector('.total-label');

        if (subtotalLabel) {
            subtotalLabel.textContent = translations.subtotal || 'Subtotal:';
        }

        if (totalLabel) {
            totalLabel.innerHTML = `<strong>${translations.total || 'Total:'}</strong>`;
        }
    }
}

// Update cart item quantity
function updateCartItem(productId, quantity) {
    if (quantity <= 0) {
        removeCartItem(productId);
        return;
    }

    fetch('product-ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update_cart_item&product_id=${productId}&quantity=${quantity}`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadCartData(); // Reload cart data
                updateCartCount(data.cart_count);
            }
        })
        .catch(error => {
            console.error('Error updating cart:', error);
        });
}

// Remove cart item
function removeCartItem(productId) {
    fetch('product-ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=remove_cart_item&product_id=${productId}`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadCartData(); // Reload cart data
                updateCartCount(data.cart_count);
                showNotification('Item removed from cart', 'info');
            }
        })
        .catch(error => {
            console.error('Error removing item:', error);
        });
}

// Go to cart page
function goToCart() {
    window.location.href = 'cart.php';
}

// Show wishlist quick view
function showWishlistQuickView() {
    // For now, just go to wishlist page
    // You can enhance this later to show a similar popup for wishlist
    window.location.href = 'wishlist.php';
}

// Add to wishlist functionality
function addToWishlist(productId) {
    fetch('product-ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=add_to_wishlist&product_id=${productId}`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Product added to wishlist!', 'success');
                // Update wishlist button state
                updateWishlistButton(productId, true);
                // Update wishlist count in header
                if (data.wishlist_count !== undefined) {
                    updateWishlistCount(data.wishlist_count);
                }
            } else {
                showNotification(data.message || 'Failed to add to wishlist', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred', 'error');
        });
}

// Add CSS styles for side cart popup
const sideCartStyles = `
<style>
.side-cart-popup {
    position: fixed;
    top: 0;
    right: 0;
    width: 100%;
    height: 100%;
    z-index: 9999;
    pointer-events: none;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.side-cart-popup.show {
    opacity: 1;
    visibility: visible;
    pointer-events: all;
}

.side-cart-backdrop {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(5px);
}

.side-cart-content {
    position: absolute;
    top: 0;
    right: -400px;
    width: 400px;
    height: 100%;
    background: white;
    box-shadow: -5px 0 20px rgba(0, 0, 0, 0.1);
    transition: right 0.3s ease;
    display: flex;
    flex-direction: column;
}

.side-cart-popup.show .side-cart-content {
    right: 0;
}

.side-cart-header {
    padding: 20px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8f9fa;
}

.side-cart-header h5 {
    margin: 0;
    color: #333;
    font-weight: 600;
}

.side-cart-body {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
}

.side-cart-items {
    margin-bottom: 20px;
}

.side-cart-item {
    padding: 15px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    margin-bottom: 10px;
    background: white;
    transition: all 0.2s ease;
}

.side-cart-item:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.side-cart-item-image {
    width: 60px;
    height: 60px;
    margin-right: 15px;
    overflow: hidden;
    border-radius: 8px;
}

.side-cart-item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.side-cart-item-details h6 {
    margin: 0 0 8px 0;
    font-size: 14px;
    font-weight: 600;
    color: #333;
    line-height: 1.3;
}

.side-cart-summary {
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.side-cart-footer {
    padding: 20px;
    border-top: 1px solid #e9ecef;
    background: white;
}

/* RTL Support */
.side-cart-popup.rtl .side-cart-content {
    right: auto;
    left: -400px;
}

.side-cart-popup.rtl.show .side-cart-content {
    left: 0;
}

.side-cart-popup.rtl .side-cart-item-image {
    margin-right: 0;
    margin-left: 15px;
}

.side-cart-popup.rtl .ms-auto {
    margin-left: auto !important;
    margin-right: 0 !important;
}

.side-cart-popup.rtl .me-2 {
    margin-right: 0.5rem !important;
    margin-left: 0.5rem !important;
}

.side-cart-popup.rtl .ms-2 {
    margin-right: 0.5rem !important;
    margin-left: 0.5rem !important;
}

.side-cart-popup.rtl .fas {
    transform: scaleX(-1);
}

.side-cart-popup.rtl .btn-close {
    transform: scaleX(-1);
}

/* Responsive design */
@media (max-width: 576px) {
    .side-cart-content {
        width: 100%;
        right: -100%;
    }
    
    .side-cart-popup.show .side-cart-content {
        right: 0;
    }
    
    .side-cart-popup.rtl .side-cart-content {
        left: -100%;
    }
    
    .side-cart-popup.rtl.show .side-cart-content {
        left: 0;
    }
}

/* Animation for cart items */
@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.side-cart-item {
    animation: slideInRight 0.3s ease;
}

/* Quantity buttons styling */
.side-cart-item .btn-sm {
    width: 28px;
    height: 28px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
}

.side-cart-item .btn-sm:hover {
    transform: scale(1.1);
    transition: transform 0.2s ease;
}

/* Loading spinner */
.spinner-border {
    width: 2rem;
    height: 2rem;
}

/* Close button hover effect */
.btn-close:hover {
    transform: rotate(90deg);
    transition: transform 0.2s ease;
}

/* RTL Animation */
@keyframes slideInLeft {
    from {
        transform: translateX(-100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.side-cart-popup.rtl .side-cart-item {
    animation: slideInLeft 0.3s ease;
}
</style>
`;

// Inject styles into the page
document.head.insertAdjacentHTML('beforeend', sideCartStyles);

// Remove from wishlist functionality
function removeFromWishlist(productId) {
    fetch('product-ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=remove_from_wishlist&product_id=${productId}`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Product removed from wishlist!', 'success');

                // Update wishlist button state
                updateWishlistButton(productId, false);

                // Update wishlist count in header
                if (data.wishlist_count !== undefined) {
                    updateWishlistCount(data.wishlist_count);
                }
            } else {
                showNotification(data.message || 'Failed to remove from wishlist', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred', 'error');
        });
}

// Update cart count in header (target cart icon badge specifically)
// Update cart count in header
function updateCartCount(count) {
    console.log('UPDATING CART COUNT:', count);
    
    // Select all possible cart badges aggressively
    const targets = document.querySelectorAll('#navbar-cart-badge, a[href*="cart.php"] .cart-badge, .cart-count');
    
    if (targets.length === 0) {
        console.warn('No cart badges found in DOM');
    }

    targets.forEach(badge => {
        badge.textContent = count;
        if (parseInt(count) > 0) {
            // Force visibility using multiple properties and !important
            badge.style.setProperty('display', 'inline-block', 'important');
            badge.style.setProperty('visibility', 'visible', 'important');
            badge.style.setProperty('opacity', '1', 'important');
            // Remove any hidden classes if they exist
            badge.classList.remove('d-none');
            badge.classList.remove('hidden');
        } else {
            badge.style.setProperty('display', 'none', 'important');
        }
    });

    // Also update side cart loading message if empty
    if (count === 0 && typeof showEmptyCart === 'function') {
        showEmptyCart();
    }
}

// Update wishlist count in header
function updateWishlistCount(count) {
    console.log('UPDATING WISHLIST COUNT:', count);
    const wishlistBadges = document.querySelectorAll('#navbar-wishlist-badge');
    
    let targets = Array.from(wishlistBadges);
    if (targets.length === 0) {
        targets = Array.from(document.querySelectorAll('.wishlist-badge, .wishlist-count, a[href*="wishlist.php"] .badge'));
    }

    targets.forEach(badge => {
        badge.textContent = count;
        if (count > 0) {
            badge.style.setProperty('display', 'inline', 'important');
            badge.style.setProperty('display', 'block', 'important');
            badge.style.display = 'inline-block';
        } else {
            badge.style.setProperty('display', 'none', 'important');
            badge.style.display = 'none';
        }
    });
}

// Update wishlist button state
function updateWishlistButton(productId, isInWishlist) {
    const wishlistBtn = document.querySelector(`[data-wishlist-btn="${productId}"]`);
    if (wishlistBtn) {
        if (isInWishlist) {
            wishlistBtn.classList.remove('btn-outline-danger');
            wishlistBtn.classList.add('btn-danger');
            wishlistBtn.innerHTML = '<i class="fas fa-heart"></i> In Wishlist';
            wishlistBtn.onclick = () => removeFromWishlist(productId);
        } else {
            wishlistBtn.classList.remove('btn-danger');
            wishlistBtn.classList.add('btn-outline-danger');
            wishlistBtn.innerHTML = '<i class="far fa-heart"></i> Add to Wishlist';
            wishlistBtn.onclick = () => addToWishlist(productId);
        }
    }
}

// Show notification
function showNotification(message, type = 'info') {
    console.log('SHOW NOTIFICATION:', message, type);
    // Remove existing notifications to avoid stacking too many
    const existingNotifications = document.querySelectorAll('.notification-toast');
    if (existingNotifications.length > 2) {
        existingNotifications[0].remove();
    }

    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification-toast alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); border-left: 5px solid;';
    
    // Add color accent based on type
    if (type === 'success') notification.style.borderLeftColor = '#28a745';
    else if (type === 'error' || type === 'danger') notification.style.borderLeftColor = '#dc3545';
    else if (type === 'warning') notification.style.borderLeftColor = '#ffc107';
    else notification.style.borderLeftColor = '#17a2b8';

    notification.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas fa-${type === 'success' ? 'check-circle' : (type === 'error' || type === 'danger' ? 'exclamation-circle' : 'info-circle')} me-2"></i>
            <div>${message}</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

    // Add to page
    document.body.appendChild(notification);

    // Auto remove after 4 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.classList.remove('show');
            setTimeout(() => { if (notification.parentNode) notification.remove(); }, 500);
        }
    }, 4000);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function () {
    // Get current cart count
    fetch('product-ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_cart_count'
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCartCount(data.cart_count);
            }
        })
        .catch(error => console.error('Error getting cart count:', error));

    // Add event listeners to add to cart buttons
    const addToCartButtons = document.querySelectorAll('[data-add-to-cart], .add-to-cart');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            const productId = this.dataset.addToCart || this.dataset.productId || this.getAttribute('data-product-id');
            if (productId) {
                const quantityInput = document.querySelector(`[data-quantity-${productId}]`) || document.getElementById('quantity');
                const quantity = quantityInput?.value || 1;
                addToCart(productId, quantity);
            }
        });
    });

    // Add event listeners to wishlist buttons
    const wishlistButtons = document.querySelectorAll('[data-wishlist-btn]');
    wishlistButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            const productId = this.dataset.wishlistBtn;

            if (this.classList.contains('btn-danger')) {
                removeFromWishlist(productId);
            } else {
                addToWishlist(productId);
            }
        });
    });

    // Add quantity change handlers
    const quantityInputs = document.querySelectorAll('[data-quantity]');
    quantityInputs.forEach(input => {
        input.addEventListener('change', function () {
            const productId = this.dataset.quantity;
            const addToCartBtn = document.querySelector(`[data-add-to-cart="${productId}"]`);
            if (addToCartBtn) {
                // Update button text or state based on quantity
                const quantity = parseInt(this.value) || 1;
                if (quantity <= 0) {
                    addToCartBtn.disabled = true;
                    addToCartBtn.textContent = 'Invalid Quantity';
                } else {
                    addToCartBtn.disabled = false;
                    addToCartBtn.textContent = 'Add to Cart';
                }
            }
        });
    });
});

// Export functions for global use
window.addToCart = addToCart;
window.addToWishlist = addToWishlist;
window.removeFromWishlist = removeFromWishlist;
