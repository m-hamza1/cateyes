/**
 * Admin JavaScript Module
 * Admin panel functionality
 * Uses dynamic colors and fonts from database
 */

// Admin module
const Admin = {
    currentTheme: 'light',
    notifications: [],
    
    /**
     * Initialize admin panel
     */
    init() {
        this.loadSettings();
        this.bindEvents();
        this.initializeSidebar();
        this.initializeCharts();
        this.initializeDataTables();
        this.initializeForms();
        this.initializeNotifications();
        this.initializeTheme();
    },
    
    /**
     * Load admin settings
     */
    loadSettings() {
        // Load dynamic colors from CSS variables
        const rootStyles = getComputedStyle(document.documentElement);
        
        window.adminColors = {
            primary: rootStyles.getPropertyValue('--admin-primary').trim(),
            secondary: rootStyles.getPropertyValue('--admin-secondary').trim(),
            success: rootStyles.getPropertyValue('--admin-success').trim(),
            danger: rootStyles.getPropertyValue('--admin-danger').trim(),
            warning: rootStyles.getPropertyValue('--admin-warning').trim(),
            info: rootStyles.getPropertyValue('--admin-info').trim(),
            dark: rootStyles.getPropertyValue('--admin-dark').trim(),
            light: rootStyles.getPropertyValue('--admin-light').trim()
        };
        
        window.adminFonts = {
            family: rootStyles.getPropertyValue('--admin-font-family').trim(),
            size: rootStyles.getPropertyValue('--admin-font-size').trim()
        };
    },
    
    /**
     * Bind events
     */
    bindEvents() {
        // Sidebar toggle
        document.querySelectorAll('.sidebar-toggle').forEach(button => {
            button.addEventListener('click', () => this.toggleSidebar());
        });
        
        // Mobile menu
        document.querySelectorAll('.mobile-menu-toggle').forEach(button => {
            button.addEventListener('click', () => this.toggleMobileMenu());
        });
        
        // Logout
        document.querySelectorAll('.logout-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.logout();
            });
        });
        
        // Delete confirmations
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const message = button.dataset.confirmMessage || 'Are you sure you want to delete this item?';
                this.confirmAction(message, () => {
                    this.deleteItem(button.dataset.id, button.dataset.type);
                });
            });
        });
        
        // Bulk actions
        document.querySelectorAll('.bulk-action-btn').forEach(button => {
            button.addEventListener('click', () => this.handleBulkAction());
        });
        
        // Search
        document.querySelectorAll('.admin-search').forEach(input => {
            input.addEventListener('input', this.debounce((e) => {
                this.handleSearch(e.target.value);
            }, 300));
        });
        
        // Filter
        document.querySelectorAll('.admin-filter').forEach(select => {
            select.addEventListener('change', (e) => {
                this.handleFilter(e.target.value, e.target.dataset.filterType);
            });
        });
    },
    
    /**
     * Initialize sidebar
     */
    initializeSidebar() {
        // Active menu item
        const currentPath = window.location.pathname;
        document.querySelectorAll('.admin-sidebar-nav .nav-link').forEach(link => {
            if (link.getAttribute('href') === currentPath) {
                link.classList.add('active');
            }
        });
        
        // Submenu toggles
        document.querySelectorAll('.submenu-toggle').forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleSubmenu(toggle);
            });
        });
    },
    
    /**
     * Initialize charts
     */
    initializeCharts() {
        // Sales chart
        const salesChart = document.getElementById('sales-chart');
        if (salesChart) {
            this.createSalesChart(salesChart);
        }
        
        // Products chart
        const productsChart = document.getElementById('products-chart');
        if (productsChart) {
            this.createProductsChart(productsChart);
        }
        
        // Orders chart
        const ordersChart = document.getElementById('orders-chart');
        if (ordersChart) {
            this.createOrdersChart(ordersChart);
        }
    },
    
    /**
     * Create sales chart
     */
    createSalesChart(element) {
        const ctx = element.getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Sales',
                    data: [12000, 19000, 15000, 25000, 22000, 30000],
                    borderColor: window.adminColors.primary,
                    backgroundColor: window.adminColors.primary + '20',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    },
    
    /**
     * Create products chart
     */
    createProductsChart(element) {
        const ctx = element.getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Electronics', 'Clothing', 'Books', 'Home', 'Sports'],
                datasets: [{
                    data: [30, 25, 20, 15, 10],
                    backgroundColor: [
                        window.adminColors.primary,
                        window.adminColors.success,
                        window.adminColors.warning,
                        window.adminColors.info,
                        window.adminColors.secondary
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
    },
    
    /**
     * Create orders chart
     */
    createOrdersChart(element) {
        const ctx = element.getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Orders',
                    data: [12, 19, 15, 25, 22, 30, 28],
                    backgroundColor: window.adminColors.primary
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    },
    
    /**
     * Initialize data tables
     */
    initializeDataTables() {
        document.querySelectorAll('.admin-data-table').forEach(table => {
            this.createDataTable(table);
        });
    },
    
    /**
     * Create data table
     */
    createDataTable(element) {
        // Simple table enhancement
        const headers = element.querySelectorAll('th');
        const rows = element.querySelectorAll('tbody tr');
        
        // Add sorting functionality
        headers.forEach((header, index) => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => {
                this.sortTable(element, index);
            });
        });
        
        // Add row hover effects
        rows.forEach(row => {
            row.addEventListener('mouseenter', () => {
                row.style.backgroundColor = window.adminColors.light;
            });
            row.addEventListener('mouseleave', () => {
                row.style.backgroundColor = '';
            });
        });
    },
    
    /**
     * Sort table
     */
    sortTable(table, columnIndex) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        rows.sort((a, b) => {
            const aValue = a.cells[columnIndex].textContent;
            const bValue = b.cells[columnIndex].textContent;
            
            if (!isNaN(aValue) && !isNaN(bValue)) {
                return parseFloat(aValue) - parseFloat(bValue);
            }
            
            return aValue.localeCompare(bValue);
        });
        
        rows.forEach(row => tbody.appendChild(row));
    },
    
    /**
     * Initialize forms
     */
    initializeForms() {
        document.querySelectorAll('.admin-form').forEach(form => {
            this.setupFormValidation(form);
            this.setupFormSubmission(form);
        });
    },
    
    /**
     * Setup form validation
     */
    setupFormValidation(form) {
        const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
        
        inputs.forEach(input => {
            input.addEventListener('blur', () => {
                this.validateField(input);
            });
            
            input.addEventListener('input', () => {
                if (input.classList.contains('is-invalid')) {
                    this.validateField(input);
                }
            });
        });
    },
    
    /**
     * Validate field
     */
    validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        let message = '';
        
        if (!value) {
            isValid = false;
            message = 'This field is required';
        } else if (field.type === 'email' && !this.isValidEmail(value)) {
            isValid = false;
            message = 'Please enter a valid email address';
        } else if (field.type === 'number' && !this.isValidNumber(value)) {
            isValid = false;
            message = 'Please enter a valid number';
        }
        
        if (isValid) {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
        } else {
            field.classList.remove('is-valid');
            field.classList.add('is-invalid');
            
            // Show error message
            this.showFieldError(field, message);
        }
        
        return isValid;
    },
    
    /**
     * Show field error
     */
    showFieldError(field, message) {
        let errorDiv = field.parentNode.querySelector('.invalid-feedback');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback';
            field.parentNode.appendChild(errorDiv);
        }
        errorDiv.textContent = message;
    },
    
    /**
     * Setup form submission
     */
    setupFormSubmission(form) {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            
            if (this.validateForm(form)) {
                this.submitForm(form);
            }
        });
    },
    
    /**
     * Validate form
     */
    validateForm(form) {
        const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
        let isValid = true;
        
        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });
        
        return isValid;
    },
    
    /**
     * Submit form
     */
    submitForm(form) {
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        
        fetch(form.action, {
            method: form.method,
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showNotification(data.message || 'Form submitted successfully!', 'success');
                if (data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1500);
                } else {
                    form.reset();
                    form.querySelectorAll('.is-valid').forEach(field => {
                        field.classList.remove('is-valid');
                    });
                }
            } else {
                this.showNotification(data.message || 'Form submission failed', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showNotification('An error occurred', 'error');
        })
        .finally(() => {
            // Reset button state
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Submit';
        });
    },
    
    /**
     * Initialize notifications
     */
    initializeNotifications() {
        // Auto-hide notifications after 5 seconds
        setInterval(() => {
            this.hideOldNotifications();
        }, 1000);
    },
    
    /**
     * Show notification
     */
    showNotification(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `admin-alert admin-alert-${type}`;
        notification.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-${this.getNotificationIcon(type)} me-2"></i>
                <span>${message}</span>
                <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
            </div>
        `;
        
        // Add to container
        const container = document.getElementById('admin-notifications');
        if (container) {
            container.appendChild(notification);
        } else {
            document.body.appendChild(notification);
        }
        
        // Auto-hide after duration
        setTimeout(() => {
            notification.remove();
        }, duration);
        
        // Animate in
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            notification.style.transition = 'all 0.3s ease';
            notification.style.opacity = '1';
            notification.style.transform = 'translateX(0)';
        }, 10);
    },
    
    /**
     * Get notification icon
     */
    getNotificationIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    },
    
    /**
     * Hide old notifications
     */
    hideOldNotifications() {
        const notifications = document.querySelectorAll('.admin-alert');
        notifications.forEach(notification => {
            const createdTime = notification.dataset.created;
            if (createdTime && Date.now() - createdTime > 5000) {
                notification.remove();
            }
        });
    },
    
    /**
     * Initialize theme
     */
    initializeTheme() {
        // Load saved theme
        const savedTheme = localStorage.getItem('admin-theme') || 'light';
        this.setTheme(savedTheme);
        
        // Theme toggle
        document.querySelectorAll('.theme-toggle').forEach(button => {
            button.addEventListener('click', () => {
                this.toggleTheme();
            });
        });
    },
    
    /**
     * Set theme
     */
    setTheme(theme) {
        document.body.setAttribute('data-theme', theme);
        this.currentTheme = theme;
        localStorage.setItem('admin-theme', theme);
        
        // Update theme toggle button
        document.querySelectorAll('.theme-toggle').forEach(button => {
            button.innerHTML = theme === 'dark' ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
        });
    },
    
    /**
     * Toggle theme
     */
    toggleTheme() {
        const newTheme = this.currentTheme === 'light' ? 'dark' : 'light';
        this.setTheme(newTheme);
    },
    
    /**
     * Toggle sidebar
     */
    toggleSidebar() {
        const sidebar = document.querySelector('.admin-sidebar');
        const main = document.querySelector('.admin-main');
        
        if (sidebar) {
            sidebar.classList.toggle('collapsed');
            if (main) {
                main.classList.toggle('expanded');
            }
        }
    },
    
    /**
     * Toggle mobile menu
     */
    toggleMobileMenu() {
        const sidebar = document.querySelector('.admin-sidebar');
        if (sidebar) {
            sidebar.classList.toggle('show');
        }
    },
    
    /**
     * Toggle submenu
     */
    toggleSubmenu(toggle) {
        const submenu = toggle.nextElementSibling;
        if (submenu) {
            submenu.classList.toggle('show');
            toggle.querySelector('i').classList.toggle('fa-chevron-down');
            toggle.querySelector('i').classList.toggle('fa-chevron-right');
        }
    },
    
    /**
     * Confirm action
     */
    confirmAction(message, callback) {
        if (confirm(message)) {
            callback();
        }
    },
    
    /**
     * Delete item
     */
    deleteItem(id, type) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);
        formData.append('type', type);
        
        fetch(BASE_URL + 'admin/includes/delete.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showNotification(data.message || 'Item deleted successfully', 'success');
                // Remove row from table
                const row = document.querySelector(`[data-id="${id}"]`);
                if (row) {
                    row.remove();
                }
            } else {
                this.showNotification(data.message || 'Error deleting item', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showNotification('An error occurred', 'error');
        });
    },
    
    /**
     * Handle bulk action
     */
    handleBulkAction() {
        const selectedItems = document.querySelectorAll('.bulk-checkbox:checked');
        const action = document.querySelector('.bulk-action-select').value;
        
        if (selectedItems.length === 0) {
            this.showNotification('Please select items to perform bulk action', 'warning');
            return;
        }
        
        const ids = Array.from(selectedItems).map(item => item.value);
        
        this.confirmAction(`Are you sure you want to ${action} ${ids.length} items?`, () => {
            this.performBulkAction(action, ids);
        });
    },
    
    /**
     * Perform bulk action
     */
    performBulkAction(action, ids) {
        const formData = new FormData();
        formData.append('action', 'bulk');
        formData.append('bulk_action', action);
        formData.append('ids', JSON.stringify(ids));
        
        fetch(BASE_URL + 'admin/includes/bulk.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showNotification(data.message || 'Bulk action completed successfully', 'success');
                location.reload();
            } else {
                this.showNotification(data.message || 'Error performing bulk action', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showNotification('An error occurred', 'error');
        });
    },
    
    /**
     * Handle search
     */
    handleSearch(query) {
        const url = new URL(window.location);
        url.searchParams.set('search', query);
        window.location.href = url.toString();
    },
    
    /**
     * Handle filter
     */
    handleFilter(value, type) {
        const url = new URL(window.location);
        url.searchParams.set(type, value);
        window.location.href = url.toString();
    },
    
    /**
     * Logout
     */
    logout() {
        this.confirmAction('Are you sure you want to logout?', () => {
            fetch(BASE_URL + 'admin/includes/logout.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = BASE_URL + 'admin/login.php';
                } else {
                    this.showNotification('Error logging out', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.showNotification('An error occurred', 'error');
            });
        });
    },
    
    /**
     * Validation helpers
     */
    isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    },
    
    isValidNumber(number) {
        return !isNaN(number) && !isNaN(parseFloat(number));
    },
    
    /**
     * Utility functions
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    throttle(func, limit) {
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
};

// Initialize admin when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    Admin.init();
});

// Export Admin object for global use
window.Admin = Admin;
