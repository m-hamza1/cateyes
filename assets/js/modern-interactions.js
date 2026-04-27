/**
 * 🚀 Amazing Modern E-Commerce Interactions
 * Advanced JavaScript for dynamic user experience
 */

class ModernEcommerce {
  constructor() {
    this.init();
    this.setupEventListeners();
    this.setupAnimations();
    this.setupLazyLoading();
    this.setupScrollEffects();
    this.setupFormValidation();
    this.setupNotifications();
    this.setupCart();
    this.setupSearch();
    this.setupTheme();
  }

  init() {
    // Initialize all modern components
    this.components = {
      navbar: new ModernNavbar(),
      productCards: new ModernProductCards(),
      hero: new ModernHero(),
      gallery: new ModernGallery(),
      forms: new ModernForms(),
      cart: new ModernCart(),
      search: new ModernSearch(),
      theme: new ModernTheme()
    };

    // Global state
    this.state = {
      cart: [],
      wishlist: [],
      notifications: [],
      theme: localStorage.getItem('theme') || 'light',
      language: localStorage.getItem('language') || 'en',
      currency: localStorage.getItem('currency') || 'USD'
    };

    // Performance monitoring
    this.performance = {
      startTime: performance.now(),
      interactions: 0,
      errors: 0
    };
  }

  setupEventListeners() {
    // Global event listeners
    document.addEventListener('DOMContentLoaded', () => this.onDOMReady());
    window.addEventListener('scroll', () => this.onScroll(), { passive: true });
    window.addEventListener('resize', () => this.onResize(), { passive: true });
    window.addEventListener('load', () => this.onLoad());

    // Keyboard shortcuts
    document.addEventListener('keydown', (e) => this.handleKeyboard(e));

    // Touch gestures
    this.setupTouchGestures();

    // Intersection Observer for animations
    this.setupIntersectionObserver();
  }

  onDOMReady() {
    console.log('🚀 Modern E-Commerce Initialized');
    this.animateOnLoad();
    this.setupSmoothScrolling();
    this.setupTooltips();
    this.setupModals();
  }

  onScroll() {
    this.updateNavbar();
    this.updateScrollProgress();
    this.lazyLoadImages();
    this.animateOnScroll();
  }

  onResize() {
    this.updateLayout();
    this.updateResponsiveElements();
  }

  onLoad() {
    this.hideLoadingScreen();
    this.trackPageLoad();
  }

  // 🎨 Advanced Animations
  setupAnimations() {
    // GSAP-like animation system
    this.animations = {
      fadeIn: (element, duration = 300) => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
          element.style.transition = `all ${duration}ms cubic-bezier(0.4, 0, 0.2, 1)`;
          element.style.opacity = '1';
          element.style.transform = 'translateY(0)';
        }, 100);
      },

      slideIn: (element, direction = 'left', duration = 400) => {
        const transforms = {
          left: 'translateX(-100px)',
          right: 'translateX(100px)',
          top: 'translateY(-100px)',
          bottom: 'translateY(100px)'
        };

        element.style.transform = transforms[direction];
        element.style.opacity = '0';

        setTimeout(() => {
          element.style.transition = `all ${duration}ms cubic-bezier(0.4, 0, 0.2, 1)`;
          element.style.transform = 'translate(0)';
          element.style.opacity = '1';
        }, 100);
      },

      scale: (element, scale = 1.1, duration = 200) => {
        element.style.transform = `scale(${scale})`;
        element.style.transition = `transform ${duration}ms cubic-bezier(0.4, 0, 0.2, 1)`;
        
        setTimeout(() => {
          element.style.transform = 'scale(1)';
        }, 100);
      },

      bounce: (element, duration = 600) => {
        element.style.animation = `bounce ${duration}ms cubic-bezier(0.68, -0.55, 0.265, 1.55)`;
        
        setTimeout(() => {
          element.style.animation = '';
        }, duration);
      }
    };
  }

  animateOnLoad() {
    // Animate elements on page load
    const elements = document.querySelectorAll('.animate-on-load');
    elements.forEach((element, index) => {
      setTimeout(() => {
        this.animations.fadeIn(element, 400);
      }, index * 100);
    });
  }

  animateOnScroll() {
    // Animate elements when they come into view
    const elements = document.querySelectorAll('.animate-on-scroll');
    elements.forEach(element => {
      const rect = element.getBoundingClientRect();
      const isVisible = rect.top < window.innerHeight && rect.bottom > 0;
      
      if (isVisible && !element.classList.contains('animated')) {
        element.classList.add('animated');
        this.animations.fadeIn(element, 500);
      }
    });
  }

  // 🖼️ Advanced Image Handling
  setupLazyLoading() {
    const images = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const img = entry.target;
          img.src = img.dataset.src;
          img.classList.remove('lazy');
          imageObserver.unobserve(img);
          
          // Add fade-in effect
          img.style.opacity = '0';
          img.onload = () => {
            img.style.transition = 'opacity 300ms ease';
            img.style.opacity = '1';
          };
        }
      });
    }, {
      rootMargin: '50px'
    });

    images.forEach(img => imageObserver.observe(img));
  }

  lazyLoadImages() {
    // Additional lazy loading logic
    const lazyImages = document.querySelectorAll('.lazy-image');
    lazyImages.forEach(img => {
      if (this.isInViewport(img)) {
        this.loadImage(img);
      }
    });
  }

  loadImage(img) {
    if (img.dataset.src) {
      img.src = img.dataset.src;
      img.classList.remove('lazy-image');
      img.classList.add('loaded');
    }
  }

  // 🎯 Scroll Effects
  setupScrollEffects() {
    this.setupParallax();
    this.setupScrollProgress();
    this.setupStickyElements();
  }

  setupParallax() {
    const parallaxElements = document.querySelectorAll('.parallax');
    
    window.addEventListener('scroll', () => {
      const scrolled = window.pageYOffset;
      
      parallaxElements.forEach(element => {
        const speed = element.dataset.speed || 0.5;
        const yPos = -(scrolled * speed);
        element.style.transform = `translateY(${yPos}px)`;
      });
    });
  }

  setupScrollProgress() {
    const progressBar = document.createElement('div');
    progressBar.className = 'scroll-progress';
    progressBar.style.cssText = `
      position: fixed;
      top: 0;
      left: 0;
      height: 3px;
      background: linear-gradient(90deg, #667eea, #764ba2);
      z-index: 9999;
      transition: width 0.1s ease;
    `;
    document.body.appendChild(progressBar);

    window.addEventListener('scroll', () => {
      const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
      const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
      const scrolled = (winScroll / height) * 100;
      progressBar.style.width = scrolled + '%';
    });
  }

  setupStickyElements() {
    const stickyElements = document.querySelectorAll('.sticky');
    
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('stuck');
        } else {
          entry.target.classList.remove('stuck');
        }
      });
    }, { threshold: [0, 1] });

    stickyElements.forEach(element => observer.observe(element));
  }

  // 🛒 Advanced Cart System
  setupCart() {
    this.cart = {
      items: JSON.parse(localStorage.getItem('cart')) || [],
      
      add: (product, quantity = 1) => {
        const existingItem = this.cart.items.find(item => item.id === product.id);
        
        if (existingItem) {
          existingItem.quantity += quantity;
        } else {
          this.cart.items.push({
            ...product,
            quantity,
            addedAt: new Date().toISOString()
          });
        }
        
        this.saveCart();
        this.updateCartUI();
        this.showNotification('Product added to cart!', 'success');
        this.animateCartIcon();
      },

      remove: (productId) => {
        this.cart.items = this.cart.items.filter(item => item.id !== productId);
        this.saveCart();
        this.updateCartUI();
        this.showNotification('Product removed from cart', 'info');
      },

      update: (productId, quantity) => {
        const item = this.cart.items.find(item => item.id === productId);
        if (item) {
          item.quantity = Math.max(1, quantity);
          this.saveCart();
          this.updateCartUI();
        }
      },

      getTotal: () => {
        return this.cart.items.reduce((total, item) => {
          return total + (item.price * item.quantity);
        }, 0);
      },

      getCount: () => {
        return this.cart.items.reduce((count, item) => count + item.quantity, 0);
      },

      save: () => {
        localStorage.setItem('cart', JSON.stringify(this.cart.items));
      },

      clear: () => {
        this.cart.items = [];
        this.saveCart();
        this.updateCartUI();
        this.showNotification('Cart cleared', 'info');
      }
    };
  }

  updateCartUI() {
    const cartCount = document.querySelector('.cart-count');
    const cartTotal = document.querySelector('.cart-total');
    
    if (cartCount) {
      cartCount.textContent = this.cart.getCount();
      cartCount.style.display = this.cart.getCount() > 0 ? 'block' : 'none';
    }
    
    if (cartTotal) {
      cartTotal.textContent = this.formatCurrency(this.cart.getTotal());
    }
  }

  animateCartIcon() {
    const cartIcon = document.querySelector('.cart-icon');
    if (cartIcon) {
      cartIcon.classList.add('bounce');
      setTimeout(() => {
        cartIcon.classList.remove('bounce');
      }, 600);
    }
  }

  // 🔍 Advanced Search
  setupSearch() {
    this.search = {
      input: document.querySelector('.search-input'),
      results: document.querySelector('.search-results'),
      
      init: () => {
        if (this.search.input) {
          this.search.input.addEventListener('input', this.debounce(this.handleSearch, 300));
          this.search.input.addEventListener('focus', this.showSearchResults);
          document.addEventListener('click', this.hideSearchResults);
        }
      },

      handleSearch: (e) => {
        const query = e.target.value.trim();
        
        if (query.length < 2) {
          this.hideSearchResults();
          return;
        }

        this.performSearch(query);
      },

      performSearch: async (query) => {
        try {
          const results = await this.fetchSearchResults(query);
          this.displaySearchResults(results);
        } catch (error) {
          console.error('Search error:', error);
          this.showNotification('Search failed. Please try again.', 'error');
        }
      },

      fetchSearchResults: async (query) => {
        // Simulate API call
        return new Promise(resolve => {
          setTimeout(() => {
            resolve([
              { id: 1, name: 'Product 1', price: 99.99, image: 'product1.jpg' },
              { id: 2, name: 'Product 2', price: 149.99, image: 'product2.jpg' }
            ]);
          }, 300);
        });
      },

      displaySearchResults: (results) => {
        if (!this.search.results) return;

        this.search.results.innerHTML = results.map(product => `
          <div class="search-result-item" data-product-id="${product.id}">
            <img src="${product.image}" alt="${product.name}">
            <div class="search-result-info">
              <h4>${product.name}</h4>
              <p>${this.formatCurrency(product.price)}</p>
            </div>
          </div>
        `).join('');

        this.search.results.style.display = 'block';
      },

      showSearchResults: () => {
        if (this.search.results && this.search.input.value.length >= 2) {
          this.search.results.style.display = 'block';
        }
      },

      hideSearchResults: (e) => {
        if (e && e.target.closest('.search-container')) return;
        if (this.search.results) {
          this.search.results.style.display = 'none';
        }
      }
    };

    this.search.init();
  }

  // 🎨 Theme System
  setupTheme() {
    this.theme = {
      current: localStorage.getItem('theme') || 'light',
      
      toggle: () => {
        this.theme.current = this.theme.current === 'light' ? 'dark' : 'light';
        this.applyTheme();
        localStorage.setItem('theme', this.theme.current);
      },

      apply: () => {
        document.documentElement.setAttribute('data-theme', this.theme.current);
        this.updateThemeToggle();
      },

      updateToggle: () => {
        const toggle = document.querySelector('.theme-toggle');
        if (toggle) {
          toggle.innerHTML = this.theme.current === 'light' ? '🌙' : '☀️';
        }
      }
    };

    this.theme.apply();
  }

  // 🔔 Notification System
  setupNotifications() {
    this.notifications = {
      container: document.querySelector('.notifications-container') || this.createNotificationsContainer(),
      
      show: (message, type = 'info', duration = 3000) => {
        const notification = this.createNotification(message, type);
        this.notifications.container.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
          notification.classList.add('show');
        }, 100);

        // Auto remove
        setTimeout(() => {
          this.removeNotification(notification);
        }, duration);
      },

      create: (message, type) => {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
          <div class="notification-content">
            <span class="notification-message">${message}</span>
            <button class="notification-close">&times;</button>
          </div>
        `;

        notification.querySelector('.notification-close').addEventListener('click', () => {
          this.removeNotification(notification);
        });

        return notification;
      },

      remove: (notification) => {
        notification.classList.add('hide');
        setTimeout(() => {
          notification.remove();
        }, 300);
      },

      createContainer: () => {
        const container = document.createElement('div');
        container.className = 'notifications-container';
        container.style.cssText = `
          position: fixed;
          top: 20px;
          right: 20px;
          z-index: 9999;
        `;
        document.body.appendChild(container);
        return container;
      }
    };
  }

  showNotification(message, type = 'info', duration = 3000) {
    this.notifications.show(message, type, duration);
  }

  // 🎯 Form Validation
  setupFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
      form.addEventListener('submit', (e) => this.handleFormSubmit(e));
      
      // Real-time validation
      const inputs = form.querySelectorAll('input, textarea, select');
      inputs.forEach(input => {
        input.addEventListener('blur', () => this.validateField(input));
        input.addEventListener('input', () => this.clearFieldError(input));
      });
    });
  }

  handleFormSubmit(e) {
    const form = e.target;
    const isValid = this.validateForm(form);
    
    if (!isValid) {
      e.preventDefault();
      this.showNotification('Please fix the errors in the form', 'error');
    }
  }

  validateForm(form) {
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    let isValid = true;
    
    inputs.forEach(input => {
      if (!this.validateField(input)) {
        isValid = false;
      }
    });
    
    return isValid;
  }

  validateField(field) {
    const value = field.value.trim();
    const isValid = field.checkValidity();
    
    if (!isValid) {
      this.showFieldError(field);
    } else {
      this.clearFieldError(field);
    }
    
    return isValid;
  }

  showFieldError(field) {
    field.classList.add('error');
    
    let error = field.nextElementSibling;
    if (!error || !error.classList.contains('error-message')) {
      error = document.createElement('div');
      error.className = 'error-message';
      field.parentNode.insertBefore(error, field.nextSibling);
    }
    
    error.textContent = field.validationMessage;
  }

  clearFieldError(field) {
    field.classList.remove('error');
    
    const error = field.nextElementSibling;
    if (error && error.classList.contains('error-message')) {
      error.remove();
    }
  }

  // 🎮 Touch Gestures
  setupTouchGestures() {
    let touchStartX = 0;
    let touchStartY = 0;
    let touchEndX = 0;
    let touchEndY = 0;

    document.addEventListener('touchstart', (e) => {
      touchStartX = e.changedTouches[0].screenX;
      touchStartY = e.changedTouches[0].screenY;
    });

    document.addEventListener('touchend', (e) => {
      touchEndX = e.changedTouches[0].screenX;
      touchEndY = e.changedTouches[0].screenY;
      this.handleSwipe();
    });

    this.handleSwipe = () => {
      const deltaX = touchEndX - touchStartX;
      const deltaY = touchEndY - touchStartY;
      
      if (Math.abs(deltaX) > Math.abs(deltaY)) {
        if (deltaX > 50) {
          this.onSwipeRight();
        } else if (deltaX < -50) {
          this.onSwipeLeft();
        }
      } else {
        if (deltaY > 50) {
          this.onSwipeDown();
        } else if (deltaY < -50) {
          this.onSwipeUp();
        }
      }
    };
  }

  onSwipeRight() {
    // Handle right swipe
    console.log('Swipe right');
  }

  onSwipeLeft() {
    // Handle left swipe
    console.log('Swipe left');
  }

  onSwipeUp() {
    // Handle up swipe
    console.log('Swipe up');
  }

  onSwipeDown() {
    // Handle down swipe
    console.log('Swipe down');
  }

  // ⚡ Performance Optimization
  setupIntersectionObserver() {
    const observerOptions = {
      root: null,
      rootMargin: '0px',
      threshold: 0.1
    };

    this.intersectionObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          this.intersectionObserver.unobserve(entry.target);
        }
      });
    }, observerOptions);

    // Observe elements
    const elements = document.querySelectorAll('.observe');
    elements.forEach(element => {
      this.intersectionObserver.observe(element);
    });
  }

  // 🎯 Utility Functions
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
  }

  throttle(func, limit) {
    let inThrottle;
    return function(...args) {
      if (!inThrottle) {
        func.apply(this, args);
        inThrottle = true;
        setTimeout(() => inThrottle = false, limit);
      }
    };
  }

  formatCurrency(amount, currency = 'USD') {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: currency
    }).format(amount);
  }

  isInViewport(element) {
    const rect = element.getBoundingClientRect();
    return (
      rect.top >= 0 &&
      rect.left >= 0 &&
      rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
      rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
  }

  updateNavbar() {
    const navbar = document.querySelector('.navbar');
    if (navbar) {
      if (window.scrollY > 100) {
        navbar.classList.add('scrolled');
      } else {
        navbar.classList.remove('scrolled');
      }
    }
  }

  setupSmoothScrolling() {
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

  setupTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    tooltipElements.forEach(element => {
      element.addEventListener('mouseenter', (e) => {
        this.showTooltip(e.target);
      });
      
      element.addEventListener('mouseleave', (e) => {
        this.hideTooltip(e.target);
      });
    });
  }

  showTooltip(element) {
    const text = element.dataset.tooltip;
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = text;
    
    document.body.appendChild(tooltip);
    
    const rect = element.getBoundingClientRect();
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
    
    setTimeout(() => tooltip.classList.add('show'), 10);
  }

  hideTooltip(element) {
    const tooltip = document.querySelector('.tooltip');
    if (tooltip) {
      tooltip.remove();
    }
  }

  setupModals() {
    const modalTriggers = document.querySelectorAll('[data-modal]');
    modalTriggers.forEach(trigger => {
      trigger.addEventListener('click', (e) => {
        e.preventDefault();
        const modalId = trigger.dataset.modal;
        this.openModal(modalId);
      });
    });

    // Close modals
    document.addEventListener('click', (e) => {
      if (e.target.classList.contains('modal') || e.target.classList.contains('modal-close')) {
        this.closeModal(e.target.closest('.modal'));
      }
    });
  }

  openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.add('show');
      document.body.style.overflow = 'hidden';
    }
  }

  closeModal(modal) {
    if (modal) {
      modal.classList.remove('show');
      document.body.style.overflow = '';
    }
  }

  handleKeyboard(e) {
    // Keyboard shortcuts
    if (e.ctrlKey || e.metaKey) {
      switch(e.key) {
        case 'k':
          e.preventDefault();
          this.focusSearch();
          break;
        case '/':
          e.preventDefault();
          this.focusSearch();
          break;
      }
    }
    
    // Escape key
    if (e.key === 'Escape') {
      this.closeModal(document.querySelector('.modal.show'));
    }
  }

  focusSearch() {
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
      searchInput.focus();
    }
  }

  hideLoadingScreen() {
    const loadingScreen = document.querySelector('.loading-screen');
    if (loadingScreen) {
      loadingScreen.style.opacity = '0';
      setTimeout(() => {
        loadingScreen.style.display = 'none';
      }, 300);
    }
  }

  trackPageLoad() {
    const loadTime = performance.now() - this.performance.startTime;
    console.log(`Page loaded in ${loadTime.toFixed(2)}ms`);
  }
}

// 🚀 Initialize Modern E-Commerce
document.addEventListener('DOMContentLoaded', () => {
  window.modernEcommerce = new ModernEcommerce();
});

// 🎨 Modern Component Classes
class ModernNavbar {
  constructor() {
    this.init();
  }

  init() {
    this.setupMobileMenu();
    this.setupDropdowns();
    this.setupSearch();
  }

  setupMobileMenu() {
    const toggle = document.querySelector('.mobile-menu-toggle');
    const menu = document.querySelector('.mobile-menu');
    
    if (toggle && menu) {
      toggle.addEventListener('click', () => {
        menu.classList.toggle('open');
        toggle.classList.toggle('active');
      });
    }
  }

  setupDropdowns() {
    const dropdowns = document.querySelectorAll('.dropdown');
    
    dropdowns.forEach(dropdown => {
      const trigger = dropdown.querySelector('.dropdown-trigger');
      
      if (trigger) {
        trigger.addEventListener('click', (e) => {
          e.preventDefault();
          dropdown.classList.toggle('open');
        });
      }
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', (e) => {
      if (!e.target.closest('.dropdown')) {
        dropdowns.forEach(dropdown => {
          dropdown.classList.remove('open');
        });
      }
    });
  }

  setupSearch() {
    const searchToggle = document.querySelector('.search-toggle');
    const searchOverlay = document.querySelector('.search-overlay');
    
    if (searchToggle && searchOverlay) {
      searchToggle.addEventListener('click', () => {
        searchOverlay.classList.add('open');
      });

      searchOverlay.addEventListener('click', (e) => {
        if (e.target === searchOverlay) {
          searchOverlay.classList.remove('open');
        }
      });
    }
  }
}

class ModernProductCards {
  constructor() {
    this.init();
  }

  init() {
    this.setupQuickView();
    this.setupWishlist();
    this.setupCompare();
  }

  setupQuickView() {
    const quickViewButtons = document.querySelectorAll('.quick-view');
    
    quickViewButtons.forEach(button => {
      button.addEventListener('click', (e) => {
        e.preventDefault();
        const productId = button.dataset.productId;
        this.openQuickView(productId);
      });
    });
  }

  openQuickView(productId) {
    // Implement quick view modal
    console.log('Quick view for product:', productId);
  }

  setupWishlist() {
    const wishlistButtons = document.querySelectorAll('.wishlist-toggle');
    
    wishlistButtons.forEach(button => {
      button.addEventListener('click', (e) => {
        e.preventDefault();
        this.toggleWishlist(button);
      });
    });
  }

  toggleWishlist(button) {
    const productId = button.dataset.productId;
    const isInWishlist = button.classList.contains('active');
    
    if (isInWishlist) {
      button.classList.remove('active');
      window.modernEcommerce.showNotification('Removed from wishlist', 'info');
    } else {
      button.classList.add('active');
      window.modernEcommerce.showNotification('Added to wishlist', 'success');
    }
  }

  setupCompare() {
    const compareButtons = document.querySelectorAll('.compare-toggle');
    
    compareButtons.forEach(button => {
      button.addEventListener('click', (e) => {
        e.preventDefault();
        this.addToCompare(button);
      });
    });
  }

  addToCompare(button) {
    const productId = button.dataset.productId;
    window.modernEcommerce.showNotification('Added to compare', 'success');
  }
}

class ModernHero {
  constructor() {
    this.init();
  }

  init() {
    this.setupSlideshow();
    this.setupAnimations();
  }

  setupSlideshow() {
    const slides = document.querySelectorAll('.hero-slide');
    const indicators = document.querySelectorAll('.hero-indicator');
    let currentSlide = 0;

    const showSlide = (index) => {
      slides.forEach((slide, i) => {
        slide.classList.toggle('active', i === index);
      });
      
      indicators.forEach((indicator, i) => {
        indicator.classList.toggle('active', i === index);
      });
    };

    const nextSlide = () => {
      currentSlide = (currentSlide + 1) % slides.length;
      showSlide(currentSlide);
    };

    // Auto-advance slides
    setInterval(nextSlide, 5000);

    // Manual navigation
    indicators.forEach((indicator, index) => {
      indicator.addEventListener('click', () => {
        currentSlide = index;
        showSlide(currentSlide);
      });
    });
  }

  setupAnimations() {
    const heroElements = document.querySelectorAll('.hero-animate');
    
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('animated');
        }
      });
    }, { threshold: 0.1 });

    heroElements.forEach(element => {
      observer.observe(element);
    });
  }
}

class ModernGallery {
  constructor() {
    this.init();
  }

  init() {
    this.setupLightbox();
    this.setupZoom();
    this.setupThumbnails();
  }

  setupLightbox() {
    const galleryItems = document.querySelectorAll('.gallery-item');
    
    galleryItems.forEach(item => {
      item.addEventListener('click', (e) => {
        e.preventDefault();
        this.openLightbox(item);
      });
    });
  }

  openLightbox(item) {
    // Implement lightbox functionality
    console.log('Open lightbox for:', item);
  }

  setupZoom() {
    const zoomImages = document.querySelectorAll('.zoom-image');
    
    zoomImages.forEach(image => {
      image.addEventListener('click', (e) => {
        e.preventDefault();
        this.toggleZoom(image);
      });
    });
  }

  toggleZoom(image) {
    image.classList.toggle('zoomed');
  }

  setupThumbnails() {
    const thumbnails = document.querySelectorAll('.gallery-thumbnail');
    
    thumbnails.forEach(thumbnail => {
      thumbnail.addEventListener('click', (e) => {
        e.preventDefault();
        this.switchImage(thumbnail);
      });
    });
  }

  switchImage(thumbnail) {
    const mainImage = document.querySelector('.gallery-main-image');
    const newSrc = thumbnail.dataset.src;
    
    if (mainImage && newSrc) {
      mainImage.src = newSrc;
      
      // Update active thumbnail
      document.querySelectorAll('.gallery-thumbnail').forEach(thumb => {
        thumb.classList.remove('active');
      });
      thumbnail.classList.add('active');
    }
  }
}

class ModernForms {
  constructor() {
    this.init();
  }

  init() {
    this.setupFloatingLabels();
    this.setupCharacterCount();
    this.setupPasswordStrength();
  }

  setupFloatingLabels() {
    const inputs = document.querySelectorAll('.floating-label input');
    
    inputs.forEach(input => {
      const label = input.nextElementSibling;
      
      input.addEventListener('focus', () => {
        label.classList.add('focused');
      });
      
      input.addEventListener('blur', () => {
        if (!input.value) {
          label.classList.remove('focused');
        }
      });
      
      // Check initial state
      if (input.value) {
        label.classList.add('focused');
      }
    });
  }

  setupCharacterCount() {
    const textareas = document.querySelectorAll('[data-maxlength]');
    
    textareas.forEach(textarea => {
      const maxLength = parseInt(textarea.dataset.maxlength);
      const counter = document.createElement('div');
      counter.className = 'character-count';
      counter.textContent = `0 / ${maxLength}`;
      
      textarea.parentNode.appendChild(counter);
      
      textarea.addEventListener('input', () => {
        const length = textarea.value.length;
        counter.textContent = `${length} / ${maxLength}`;
        
        if (length > maxLength) {
          counter.classList.add('error');
        } else {
          counter.classList.remove('error');
        }
      });
    });
  }

  setupPasswordStrength() {
    const passwordInputs = document.querySelectorAll('.password-strength');
    
    passwordInputs.forEach(input => {
      const strengthIndicator = document.createElement('div');
      strengthIndicator.className = 'password-strength-indicator';
      input.parentNode.appendChild(strengthIndicator);
      
      input.addEventListener('input', () => {
        this.updatePasswordStrength(input, strengthIndicator);
      });
    });
  }

  updatePasswordStrength(input, indicator) {
    const password = input.value;
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]/)) strength++;
    if (password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[^a-zA-Z0-9]/)) strength++;
    
    indicator.className = `password-strength-indicator strength-${strength}`;
    
    const strengthText = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'][strength];
    indicator.textContent = strengthText;
  }
}

class ModernCart {
  constructor() {
    this.init();
  }

  init() {
    this.setupQuantityControls();
    this.setupCartDropdown();
    this.setupCheckout();
  }

  setupQuantityControls() {
    const controls = document.querySelectorAll('.quantity-control');
    
    controls.forEach(control => {
      const decrease = control.querySelector('.decrease');
      const increase = control.querySelector('.increase');
      const input = control.querySelector('input');
      
      decrease.addEventListener('click', () => {
        const current = parseInt(input.value);
        if (current > 1) {
          input.value = current - 1;
          this.updateQuantity(input);
        }
      });
      
      increase.addEventListener('click', () => {
        const current = parseInt(input.value);
        const max = parseInt(input.max) || 99;
        if (current < max) {
          input.value = current + 1;
          this.updateQuantity(input);
        }
      });
      
      input.addEventListener('change', () => {
        this.updateQuantity(input);
      });
    });
  }

  updateQuantity(input) {
    const productId = input.dataset.productId;
    const quantity = parseInt(input.value);
    
    // Update cart
    window.modernEcommerce.cart.update(productId, quantity);
  }

  setupCartDropdown() {
    const cartToggle = document.querySelector('.cart-toggle');
    const cartDropdown = document.querySelector('.cart-dropdown');
    
    if (cartToggle && cartDropdown) {
      cartToggle.addEventListener('click', (e) => {
        e.preventDefault();
        cartDropdown.classList.toggle('open');
      });
      
      // Close when clicking outside
      document.addEventListener('click', (e) => {
        if (!e.target.closest('.cart-container')) {
          cartDropdown.classList.remove('open');
        }
      });
    }
  }

  setupCheckout() {
    const checkoutForm = document.querySelector('.checkout-form');
    
    if (checkoutForm) {
      checkoutForm.addEventListener('submit', (e) => {
        e.preventDefault();
        this.processCheckout();
      });
    }
  }

  processCheckout() {
    // Implement checkout process
    window.modernEcommerce.showNotification('Processing checkout...', 'info');
    
    setTimeout(() => {
      window.modernEcommerce.showNotification('Order placed successfully!', 'success');
    }, 2000);
  }
}

class ModernSearch {
  constructor() {
    this.init();
  }

  init() {
    this.setupInstantSearch();
    this.setupFilters();
    this.setupSuggestions();
  }

  setupInstantSearch() {
    const searchInput = document.querySelector('.search-input');
    
    if (searchInput) {
      searchInput.addEventListener('input', window.modernEcommerce.debounce((e) => {
        this.performSearch(e.target.value);
      }, 300));
    }
  }

  performSearch(query) {
    if (query.length < 2) return;
    
    // Show loading state
    this.showSearchLoading();
    
    // Simulate API call
    setTimeout(() => {
      this.displaySearchResults([]);
    }, 500);
  }

  showSearchLoading() {
    const resultsContainer = document.querySelector('.search-results');
    if (resultsContainer) {
      resultsContainer.innerHTML = '<div class="search-loading">Searching...</div>';
    }
  }

  displaySearchResults(results) {
    const resultsContainer = document.querySelector('.search-results');
    if (resultsContainer) {
      if (results.length === 0) {
        resultsContainer.innerHTML = '<div class="search-no-results">No results found</div>';
      } else {
        // Render results
        resultsContainer.innerHTML = results.map(result => `
          <div class="search-result">
            <img src="${result.image}" alt="${result.name}">
            <div class="search-result-info">
              <h4>${result.name}</h4>
              <p>${result.price}</p>
            </div>
          </div>
        `).join('');
      }
    }
  }

  setupFilters() {
    const filters = document.querySelectorAll('.search-filter');
    
    filters.forEach(filter => {
      filter.addEventListener('change', () => {
        this.applyFilters();
      });
    });
  }

  applyFilters() {
    // Apply search filters
    console.log('Applying filters');
  }

  setupSuggestions() {
    const searchInput = document.querySelector('.search-input');
    
    if (searchInput) {
      searchInput.addEventListener('focus', () => {
        this.showSuggestions();
      });
    }
  }

  showSuggestions() {
    // Show search suggestions
    console.log('Showing suggestions');
  }
}

class ModernTheme {
  constructor() {
    this.init();
  }

  init() {
    this.setupThemeToggle();
    this.setupColorScheme();
  }

  setupThemeToggle() {
    const toggle = document.querySelector('.theme-toggle');
    
    if (toggle) {
      toggle.addEventListener('click', () => {
        window.modernEcommerce.theme.toggle();
      });
    }
  }

  setupColorScheme() {
    // Detect system preference
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)');
    
    prefersDark.addEventListener('change', (e) => {
      if (!localStorage.getItem('theme')) {
        window.modernEcommerce.theme.current = e.matches ? 'dark' : 'light';
        window.modernEcommerce.theme.apply();
      }
    });
  }
}

// Export for global access
window.ModernEcommerce = ModernEcommerce;
