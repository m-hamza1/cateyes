/**
 * Cart JavaScript Module
 * Shopping cart functionality
 * Uses dynamic colors and fonts from database
 */

// Cart module
const Cart = {
    items: [],
    total: 0,
    count: 0,
    currency: 'USD',
    
    /**
     * Initialize cart
     */
    init() {
        this.loadCart();
        this.bindEvents();
        this.updateDisplay();
    },
    
    /**
     * Load cart from server
     */
    loadCart() {
        fetch(BASE_URL + 'includes/cart.php?action=get')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.items = data.items || [];
                    this.total = data.total || 0;
                    this.count = data.cart_count || 0;
                    this.currency = data.currency || 'USD';
                    this.updateDisplay();
                }
            })
            .catch(error => {
                console.error('Error loading cart:', error);
            });
    },
    
    /**
     * Add item to cart
     */
    addItem(productId, quantity = 1, options = {}) {
        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('product_id', productId);
        formData.append('quantity', quantity);
        
        // Add options if any
        Object.keys(options).forEach(key => {
            formData.append(key, options[key]);
        });
        
        return fetch(BASE_URL + 'includes/cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.loadCart();
                this.showNotification(data.message || 'Product added to cart!', 'success');
                this.animateCartIcon();
            } else {
                this.showNotification(data.message || 'Error adding to cart', 'error');
            }
            return data;
        })
        .catch(error => {
            console.error('Error adding to cart:', error);
            this.showNotification('An error occurred', 'error');
            throw error;
        });
    },
    
    /**
     * Update item quantity
     */
    updateItem(itemId, quantity) {
        const formData = new FormData();
        formData.append('action', 'update');
        formData.append('item_id', itemId);
        formData.append('quantity', quantity);
        
        return fetch(BASE_URL + 'includes/cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.loadCart();
            } else {
                this.showNotification(data.message || 'Error updating cart', 'error');
            }
            return data;
        })
        .catch(error => {
            console.error('Error updating cart:', error);
            this.showNotification('An error occurred', 'error');
            throw error;
        });
    },
    
    /**
     * Remove item from cart
     */
    removeItem(itemId) {
        const formData = new FormData();
        formData.append('action', 'remove');
        formData.append('item_id', itemId);
        
        return fetch(BASE_URL + 'includes/cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.loadCart();
                this.showNotification(data.message || 'Item removed from cart', 'success');
            } else {
                this.showNotification(data.message || 'Error removing from cart', 'error');
            }
            return data;
        })
        .catch(error => {
            console.error('Error removing from cart:', error);
            this.showNotification('An error occurred', 'error');
            throw error;
        });
    },
    
    /**
     * Clear cart
     */
    clearCart() {
        const formData = new FormData();
        formData.append('action', 'clear');
        
        return fetch(BASE_URL + 'includes/cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.loadCart();
                this.showNotification(data.message || 'Cart cleared', 'success');
            } else {
                this.showNotification(data.message || 'Error clearing cart', 'error');
            }
            return data;
        })
        .catch(error => {
            console.error('Error clearing cart:', error);
            this.showNotification('An error occurred', 'error');
            throw error;
        });
    },
    
    /**
     * Update cart display
     */
    updateDisplay() {
        // Update cart count badges
        const cartBadges = document.querySelectorAll('#navbar-cart-badge, .cart-badge');
        cartBadges.forEach(badge => {
            badge.textContent = this.count;
            badge.style.display = this.count > 0 ? 'block' : 'none';
        });
        
        // Update cart total
        const cartTotals = document.querySelectorAll('.cart-total');
        cartTotals.forEach(total => {
            total.textContent = this.formatPrice(this.total);
        });
        
        // Update cart items list
        this.updateCartItemsList();
        
        // Update cart summary
        this.updateCartSummary();
    },
    
    /**
     * Update cart items list
     */
    updateCartItemsList() {
        const cartItemsList = document.getElementById('cart-items-list');
        if (!cartItemsList) return;
        
        if (this.items.length === 0) {
            cartItemsList.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Your cart is empty</p>
                    <a href="${BASE_URL}shop.php" class="btn btn-primary">Continue Shopping</a>
                </div>
            `;
            return;
        }
        
        let html = '';
        this.items.forEach(item => {
            html += `
                <div class="cart-item d-flex align-items-center mb-3 p-3 border rounded">
                    <img src="${UPLOAD_URL}products/${item.image}" 
                         alt="${item.name}" 
                         class="me-3" 
                         style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px;">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">${item.name}</h6>
                        <p class="text-muted mb-1">${item.options ? this.formatOptions(item.options) : ''}</p>
                        <div class="d-flex align-items-center">
                            <span class="text-primary fw-bold me-3">${this.formatPrice(item.price * item.quantity)}</span>
                            <div class="input-group input-group-sm" style="width: 120px;">
                                <button class="btn btn-outline-secondary" onclick="Cart.updateItem(${item.id}, ${item.quantity - 1})" 
                                        ${item.quantity <= 1 ? 'disabled' : ''}>-</button>
                                <input type="number" class="form-control text-center" value="${item.quantity}" 
                                       min="1" max="${item.stock}" onchange="Cart.updateItem(${item.id}, this.value)">
                                <button class="btn btn-outline-secondary" onclick="Cart.updateItem(${item.id}, ${item.quantity + 1})"
                                        ${item.quantity >= item.stock ? 'disabled' : ''}>+</button>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-sm btn-outline-danger" onclick="Cart.removeItem(${item.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
        });
        
        cartItemsList.innerHTML = html;
    },
    
    /**
     * Update cart summary
     */
    updateCartSummary() {
        const cartSummary = document.getElementById('cart-summary');
        if (!cartSummary) return;
        
        const subtotal = this.calculateSubtotal();
        const shipping = this.calculateShipping();
        const tax = this.calculateTax(subtotal);
        const total = subtotal + shipping + tax;
        
        cartSummary.innerHTML = `
            <div class="d-flex justify-content-between mb-2">
                <span>Subtotal:</span>
                <span>${this.formatPrice(subtotal)}</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span>Shipping:</span>
                <span>${this.formatPrice(shipping)}</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span>Tax:</span>
                <span>${this.formatPrice(tax)}</span>
            </div>
            <hr>
            <div class="d-flex justify-content-between fw-bold">
                <span>Total:</span>
                <span>${this.formatPrice(total)}</span>
            </div>
        `;
    },
    
    /**
     * Calculate subtotal
     */
    calculateSubtotal() {
        return this.items.reduce((total, item) => {
            return total + (item.price * item.quantity);
        }, 0);
    },
    
    /**
     * Calculate shipping
     */
    calculateShipping() {
        const subtotal = this.calculateSubtotal();
        // Free shipping for orders over $100
        return subtotal >= 100 ? 0 : 10;
    },
    
    /**
     * Calculate tax
     */
    calculateTax(subtotal) {
        // 8% tax rate
        return subtotal * 0.08;
    },
    
    /**
     * Format price
     */
    formatPrice(price) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: this.currency
        }).format(price);
    },
    
    /**
     * Format options
     */
    formatOptions(options) {
        return Object.entries(options)
            .map(([key, value]) => `${key}: ${value}`)
            .join(', ');
    },
    
    /**
     * Show notification
     */
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'error' ? 'danger' : 'success'} position-fixed top-0 start-50 translate-middle-x mt-3`;
        notification.style.zIndex = '9999';
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    },
    
    /**
     * Animate cart icon
     */
    animateCartIcon() {
        const cartIcons = document.querySelectorAll('.fa-shopping-cart');
        cartIcons.forEach(icon => {
            icon.classList.add('animate-bounce');
            setTimeout(() => {
                icon.classList.remove('animate-bounce');
            }, 1000);
        });
    },
    
    /**
     * Bind events
     */
    bindEvents() {
        // Add to cart buttons
        document.querySelectorAll('.add-to-cart-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const productId = button.dataset.productId;
                const quantity = parseInt(button.dataset.quantity || 1);
                this.addItem(productId, quantity);
            });
        });
        
        // Quick add to cart
        document.querySelectorAll('.quick-add-to-cart').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const productId = button.dataset.productId;
                this.addItem(productId, 1);
            });
        });
        
        // Clear cart button
        document.querySelectorAll('.clear-cart-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                if (confirm('Are you sure you want to clear your cart?')) {
                    this.clearCart();
                }
            });
        });
        
        // Checkout button
        document.querySelectorAll('.checkout-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                if (this.items.length === 0) {
                    this.showNotification('Your cart is empty', 'error');
                    return;
                }
                window.location.href = BASE_URL + 'checkout.php';
            });
        });
    }
};

// Initialize cart when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    Cart.init();
});

// Add bounce animation
const style = document.createElement('style');
style.textContent = `
    @keyframes bounce {
        0%, 20%, 53%, 80%, 100% {
            transform: translate3d(0, 0, 0);
        }
        40%, 43% {
            transform: translate3d(0, -30px, 0);
        }
        70% {
            transform: translate3d(0, -15px, 0);
        }
        90% {
            transform: translate3d(0, -4px, 0);
        }
    }
    
    .animate-bounce {
        animation: bounce 1s;
    }
`;
document.head.appendChild(style);

// Export Cart object for global use
window.Cart = Cart;
