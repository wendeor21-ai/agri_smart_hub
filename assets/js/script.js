// Agri Smart Hub - Main JavaScript (FIXED)
// Single solution for all cart functionality

// Global variables
let csrfToken = null;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Get CSRF token from meta tag
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : 'default-token';
    
    // Initialize cart system
    initCartSystem();
    
    console.log('Agri Smart Hub JavaScript initialized');
});

// ========== CART SYSTEM ==========
function initCartSystem() {
    // Update cart count display on page load
    updateCartCountDisplay();
    
    // Setup cart event listeners
    setupCartEventListeners();
    
    // Check session for existing cart
    checkCartSession();
}

function updateCartCountDisplay() {
    // Get current cart count from session
    fetch('../includes/add_to_cart.php?get_count=1')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const cartCount = data.cart_count;
                
                // Update all cart badges
                const cartBadges = document.querySelectorAll('.cart-badge, .cart-count, .cart-count-badge');
                cartBadges.forEach(badge => {
                    badge.textContent = cartCount;
                    badge.style.display = cartCount > 0 ? 'inline-block' : 'none';
                });
                
                // Also update in localStorage for quick access
                localStorage.setItem('cart_count', cartCount);
            }
        })
        .catch(error => console.error('Error updating cart count:', error));
}

function checkCartSession() {
    // Quick check if cart session exists
    fetch('../includes/add_to_cart.php?check_session=1')
        .then(response => response.json())
        .then(data => {
            console.log('Cart session check:', data);
        })
        .catch(error => console.error('Session check error:', error));
}

// Setup event listeners for cart buttons
function setupCartEventListeners() {
    console.log('Setting up cart event listeners...');
    
    // Event delegation for better performance
    document.addEventListener('click', function(e) {
        const target = e.target;
        
        // Check if this is or contains an add-to-cart button
        const cartButton = target.closest('[data-add-to-cart], .add-to-cart-btn, [data-product-id]');
        
        if (cartButton) {
            e.preventDefault();
            e.stopPropagation();
            
            // Extract product ID
            const productId = cartButton.dataset.productId || 
                            cartButton.getAttribute('data-id') ||
                            cartButton.closest('[data-product-id]')?.dataset.productId;
            
            if (!productId) {
                console.error('No product ID found for cart button');
                showNotification('Product ID not found', 'danger');
                return;
            }
            
            // Call add to cart function
            addToCart(productId, 1);
        }
    });
    
    // Also directly attach to buttons for safety
    setTimeout(() => {
        const cartButtons = document.querySelectorAll('[data-add-to-cart], .add-to-cart-btn');
        
        cartButtons.forEach(button => {
            if (!button.hasAttribute('data-listener-attached')) {
                button.setAttribute('data-listener-attached', 'true');
                
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const productId = button.dataset.productId || 
                                    button.getAttribute('data-id');
                    
                    if (productId) {
                        addToCart(productId, 1);
                    }
                });
            }
        });
        
        console.log('Attached listeners to', cartButtons.length, 'cart buttons');
    }, 500);
}

// Main add to cart function
window.addToCart = async function(productId, quantity = 1) {
    console.log('Adding to cart:', productId, quantity);
    
    try {
        // Validate inputs
        if (!productId || isNaN(productId)) {
            throw new Error('Invalid product ID');
        }
        
        productId = parseInt(productId);
        quantity = parseInt(quantity) || 1;
        
        // Get the button that was clicked
        const button = document.querySelector(`[data-product-id="${productId}"]`);
        let originalHTML = '';
        
        if (button) {
            originalHTML = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Adding...';
            button.disabled = true;
        }
        
        // Create FormData for POST request
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('quantity', quantity);
        
        // Send POST request to add_to_cart.php
        const response = await fetch('../includes/add_to_cart.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        // Restore button state
        if (button && originalHTML) {
            setTimeout(() => {
                button.innerHTML = originalHTML;
                button.disabled = false;
            }, 1000);
        }
        
        if (data.success) {
            // Success - update UI
            updateCartCountDisplay();
            
            // Show success notification
            showNotification(
                data.message || 'Product added to cart!',
                'success',
                3000
            );
            
            // Emit event for other scripts
            document.dispatchEvent(new CustomEvent('cart:updated', {
                detail: data
            }));
            
            return data;
        } else {
            // Error from server
            throw new Error(data.message || 'Failed to add to cart');
        }
        
    } catch (error) {
        console.error('Add to cart error:', error);
        
        // Show error notification
        showNotification(
            error.message || 'Error adding to cart. Please try again.',
            'danger',
            4000
        );
        
        return { success: false, message: error.message };
    }
};

// ========== NOTIFICATION SYSTEM ==========
function initNotifications() {
    // Create notification container if it doesn't exist
    if (!document.getElementById('notification-container')) {
        const container = document.createElement('div');
        container.id = 'notification-container';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 350px;
        `;
        document.body.appendChild(container);
    }
}

window.showNotification = function(message, type = 'info', duration = 3000) {
    // Initialize notifications if needed
    if (!document.getElementById('notification-container')) {
        initNotifications();
    }
    
    const container = document.getElementById('notification-container');
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show`;
    notification.style.cssText = `
        margin-bottom: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: slideInRight 0.3s ease;
    `;
    
    // Set icon based on type
    const icons = {
        success: 'check-circle',
        danger: 'exclamation-circle',
        warning: 'exclamation-triangle',
        info: 'info-circle'
    };
    
    const icon = icons[type] || 'info-circle';
    
    notification.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas fa-${icon} me-2"></i>
            <div class="flex-grow-1">${message}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    container.appendChild(notification);
    
    // Auto-remove after duration
    setTimeout(() => {
        if (notification.parentElement) {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }
    }, duration);
    
    // Close on button click
    notification.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-close') || e.target.closest('.btn-close')) {
            this.classList.remove('show');
            setTimeout(() => this.remove(), 300);
        }
    });
    
    return notification;
};

// Add animation styles
if (!document.querySelector('#notification-styles')) {
    const style = document.createElement('style');
    style.id = 'notification-styles';
    style.textContent = `
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
    `;
    document.head.appendChild(style);
}

// ========== DEBUG FUNCTIONS ==========
// Add these only in development
if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    document.addEventListener('DOMContentLoaded', function() {
        // Add debug button
        const debugBtn = document.createElement('button');
        debugBtn.id = 'debug-cart-btn';
        debugBtn.textContent = '🛒 Debug Cart';
        debugBtn.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 8px 12px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            z-index: 9998;
            font-size: 12px;
            opacity: 0.8;
        `;
        debugBtn.onclick = function() {
            console.log('=== CART DEBUG ===');
            console.log('Testing cart with product ID 10...');
            addToCart(10, 1);
        };
        document.body.appendChild(debugBtn);
    });
}

// Export for global use
window.AgriSmartHub = {
    addToCart,
    showNotification,
    updateCartCountDisplay
};

console.log('Agri Smart Hub JavaScript loaded successfully');