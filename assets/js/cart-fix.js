// cart-fix.js - Simple, reliable cart solution
// Use this if you want a simpler approach

console.log('Cart Fix Script Loaded');

// Main add to cart function
window.addToCart = function(productId, quantity = 1, button = null) {
    console.log('Adding to cart - Product ID:', productId);
    
    // If no button provided, find it
    if (!button) {
        button = document.querySelector(`[data-product-id="${productId}"]`) ||
                document.querySelector(`[onclick*="${productId}"]`);
    }
    
    // Save original button state
    let originalHTML = '';
    if (button) {
        originalHTML = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Adding...';
        button.disabled = true;
    }
    
    // Create form data for POST request
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    
    // Make POST request
    fetch('../includes/add_to_cart.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        console.log('Cart response:', data);
        
        // Restore button
        if (button && originalHTML) {
            setTimeout(() => {
                button.innerHTML = originalHTML;
                button.disabled = false;
            }, 1000);
        }
        
        if (data.success) {
            // Update cart count display
            updateCartCount(data.cart_count);
            
            // Show success message
            showCartMessage(data.message || 'Added to cart successfully!', 'success');
            
            // Dispatch event for other scripts
            document.dispatchEvent(new CustomEvent('cartUpdated', {
                detail: data
            }));
        } else {
            // Show error message
            showCartMessage(data.message || 'Failed to add to cart', 'error');
        }
    })
    .catch(error => {
        console.error('Cart error:', error);
        
        // Restore button
        if (button && originalHTML) {
            button.innerHTML = originalHTML;
            button.disabled = false;
        }
        
        showCartMessage('Network error. Please try again.', 'error');
    });
    
    // Prevent default if called from onclick
    if (window.event) {
        window.event.preventDefault();
        window.event.stopPropagation();
        return false;
    }
};

// Update cart count in UI
function updateCartCount(count) {
    // Update cart badges
    const cartBadges = document.querySelectorAll('.cart-badge, .cart-count, .cart-count-badge');
    cartBadges.forEach(badge => {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'inline-block' : 'none';
    });
    
    // Also update page title if there's a cart indicator
    const titleCart = document.querySelector('.cart-items-count');
    if (titleCart) {
        titleCart.textContent = count;
    }
    
    // Store in localStorage for persistence
    localStorage.setItem('cart_count', count);
}

// Show cart message
function showCartMessage(message, type) {
    // Remove existing messages
    const existingMessages = document.querySelectorAll('.cart-message');
    existingMessages.forEach(msg => msg.remove());
    
    // Create message element
    const messageDiv = document.createElement('div');
    messageDiv.className = `cart-message alert alert-${type === 'success' ? 'success' : 'danger'}`;
    messageDiv.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        z-index: 9999;
        padding: 12px 16px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: slideInRight 0.3s ease;
        max-width: 350px;
        font-size: 14px;
    `;
    
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    messageDiv.innerHTML = `
        <i class="fas ${icon} me-2"></i>
        ${message}
        <button type="button" class="btn-close btn-close-${type === 'success' ? 'white' : ''} ms-2" 
                style="float: none; font-size: 10px;"></button>
    `;
    
    document.body.appendChild(messageDiv);
    
    // Auto-remove after 3 seconds
    setTimeout(() => {
        messageDiv.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => messageDiv.remove(), 300);
    }, 3000);
    
    // Close button functionality
    const closeBtn = messageDiv.querySelector('.btn-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            messageDiv.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => messageDiv.remove(), 300);
        });
    }
}

// Fix all cart buttons on the page
function fixAllCartButtons() {
    console.log('Fixing cart buttons...');
    
    // Look for all buttons with cart-related attributes
    const cartSelectors = [
        '[data-product-id]',
        '[onclick*="addToCart"]',
        '[onclick*="cart"]',
        '.add-to-cart',
        '.add-to-cart-btn',
        '.cart-btn'
    ];
    
    const cartButtons = document.querySelectorAll(cartSelectors.join(','));
    
    cartButtons.forEach(button => {
        // Skip if already fixed
        if (button.hasAttribute('data-cart-fixed')) return;
        
        // Extract product ID
        let productId = button.dataset.productId ||
                      button.getAttribute('data-id') ||
                      button.getAttribute('data-product-id');
        
        // Try to extract from onclick
        if (!productId && button.getAttribute('onclick')) {
            const onclick = button.getAttribute('onclick');
            const match = onclick.match(/\d+/);
            if (match) productId = match[0];
        }
        
        // If still no product ID, try to find from parent
        if (!productId) {
            const parentWithId = button.closest('[data-product-id]');
            if (parentWithId) {
                productId = parentWithId.dataset.productId;
            }
        }
        
        // Skip if no product ID found
        if (!productId) {
            console.warn('No product ID for button:', button);
            return;
        }
        
        // Remove old onclick handlers
        button.onclick = null;
        
        // Remove problematic href
        if (button.tagName === 'A' && button.href.includes('add_to_cart')) {
            button.href = 'javascript:void(0);';
        }
        
        // Add new click handler
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Cart button clicked, product ID:', productId);
            addToCart(productId, 1, button);
        });
        
        // Mark as fixed
        button.setAttribute('data-cart-fixed', 'true');
    });
    
    console.log('Fixed', cartButtons.length, 'cart buttons');
}

// Initialize cart system
function initCartSystem() {
    // Fix buttons immediately
    fixAllCartButtons();
    
    // Update cart count on load
    fetch('../includes/add_to_cart.php?get_count=1')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCartCount(data.cart_count);
            }
        })
        .catch(error => console.error('Error loading cart count:', error));
    
    // Watch for dynamically added buttons
    const observer = new MutationObserver(function(mutations) {
        let shouldFixButtons = false;
        
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length > 0) {
                shouldFixButtons = true;
            }
        });
        
        if (shouldFixButtons) {
            setTimeout(fixAllCartButtons, 100);
        }
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
}

// Add animation styles
function addCartStyles() {
    if (!document.querySelector('#cart-animations')) {
        const style = document.createElement('style');
        style.id = 'cart-animations';
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
            @keyframes slideOutRight {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
            
            /* Cart badge styles */
            .cart-count-badge {
                font-size: 0.7rem;
                padding: 0.2rem 0.4rem;
                min-width: 18px;
                height: 18px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
        `;
        document.head.appendChild(style);
    }
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing cart system...');
    
    // Add styles
    addCartStyles();
    
    // Initialize cart system
    initCartSystem();
    
    // Add test button in development
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
        const testBtn = document.createElement('button');
        testBtn.textContent = 'Test Cart';
        testBtn.style.cssText = `
            position: fixed;
            bottom: 20px;
            left: 20px;
            padding: 8px 12px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            z-index: 9998;
            font-size: 12px;
        `;
        testBtn.onclick = function() {
            console.log('Testing cart with product ID 10');
            addToCart(10, 1);
        };
        document.body.appendChild(testBtn);
    }
});

// Export for manual use
window.cartFix = {
    addToCart,
    fixAllCartButtons,
    updateCartCount,
    showCartMessage
};

console.log('Cart Fix System Ready');