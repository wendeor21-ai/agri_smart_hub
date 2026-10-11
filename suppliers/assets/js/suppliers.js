// Suppliers Dashboard JavaScript

$(document).ready(function() {
    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
    
    // Initialize popovers
    $('[data-bs-toggle="popover"]').popover();
    
    // Notification bell click
    $('.notification-bell').click(function(e) {
        e.preventDefault();
        // TODO: Show notifications dropdown
        alert('Notifications feature coming soon!');
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
    
    // Confirm before deleting
    $('.delete-btn').click(function(e) {
        if (!confirm('Are you sure you want to delete this item?')) {
            e.preventDefault();
        }
    });
    
    // Form validation for numeric inputs
    $('input[type="number"]').on('input', function() {
        if (parseFloat($(this).val()) < 0) {
            $(this).val(0);
        }
    });
    
    // Stock level indicators
    function updateStockIndicators() {
        $('.stock-indicator').each(function() {
            const stock = parseInt($(this).data('stock'));
            const $badge = $(this).find('.badge');
            
            $badge.removeClass('bg-success bg-warning bg-danger');
            
            if (stock > 10) {
                $badge.addClass('bg-success');
            } else if (stock > 0) {
                $badge.addClass('bg-warning');
            } else {
                $badge.addClass('bg-danger');
            }
        });
    }
    
    // Initialize stock indicators
    updateStockIndicators();
    
    // Search functionality for products table
    $('#searchProducts').on('keyup', function() {
        const value = $(this).val().toLowerCase();
        $('#productsTable tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });
    
    // Category filter for products
    $('#categoryFilter').on('change', function() {
        const category = $(this).val();
        if (category === 'all') {
            $('#productsTable tr').show();
        } else {
            $('#productsTable tr').each(function() {
                const rowCategory = $(this).find('td:eq(2)').text().toLowerCase();
                $(this).toggle(rowCategory.indexOf(category.toLowerCase()) > -1);
            });
        }
    });
    
    // Status filter for products
    $('#statusFilter').on('change', function() {
        const status = $(this).val();
        if (status === 'all') {
            $('#productsTable tr').show();
        } else {
            $('#productsTable tr').each(function() {
                const rowStatus = $(this).find('.status-badge').text().toLowerCase();
                $(this).toggle(rowStatus === status);
            });
        }
    });
    
    // Product image preview
    $('#productImage').on('change', function(e) {
        const input = this;
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#imagePreview').html('<img src="' + e.target.result + '" alt="Preview">');
            };
            reader.readAsDataURL(input.files[0]);
        }
    });
    
    // Toggle product status
    $('.toggle-status').click(function(e) {
        e.preventDefault();
        const $btn = $(this);
        const productId = $btn.data('id');
        const currentStatus = $btn.data('status');
        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
        
        if (confirm(`Are you sure you want to ${newStatus === 'active' ? 'activate' : 'deactivate'} this product?`)) {
            $.ajax({
                url: 'ajax/toggle_product_status.php',
                method: 'POST',
                data: {
                    id: productId,
                    status: newStatus
                },
                success: function(response) {
                    if (response.success) {
                        $btn.data('status', newStatus);
                        $btn.find('.status-text').text(newStatus);
                        $btn.toggleClass('btn-success btn-secondary');
                        $btn.find('i').toggleClass('fa-toggle-on fa-toggle-off');
                        
                        // Update status badge in table
                        const $row = $btn.closest('tr');
                        const $badge = $row.find('.status-badge');
                        $badge.text(newStatus);
                        $badge.removeClass('active inactive');
                        $badge.addClass(newStatus);
                        
                        // Show success message
                        showNotification('Product status updated successfully!', 'success');
                    }
                }
            });
        }
    });
    
    // Update stock quantity
    $('.update-stock').click(function(e) {
        e.preventDefault();
        const $btn = $(this);
        const productId = $btn.data('id');
        const currentStock = parseInt($btn.data('stock'));
        
        const newStock = prompt('Enter new stock quantity:', currentStock);
        if (newStock !== null && !isNaN(newStock) && parseInt(newStock) >= 0) {
            $.ajax({
                url: 'ajax/update_stock.php',
                method: 'POST',
                data: {
                    id: productId,
                    stock: newStock
                },
                success: function(response) {
                    if (response.success) {
                        $btn.data('stock', newStock);
                        $btn.find('.stock-text').text(newStock);
                        
                        // Update stock badge in table
                        const $row = $btn.closest('tr');
                        const $badge = $row.find('.stock-badge');
                        $badge.text(newStock);
                        
                        // Update badge color
                        $badge.removeClass('bg-success bg-warning bg-danger');
                        if (newStock > 10) {
                            $badge.addClass('bg-success');
                        } else if (newStock > 0) {
                            $badge.addClass('bg-warning');
                        } else {
                            $badge.addClass('bg-danger');
                        }
                        
                        // Show success message
                        showNotification('Stock quantity updated successfully!', 'success');
                    }
                }
            });
        }
    });
    
    // Export products to CSV
    $('#exportProducts').click(function() {
        // TODO: Implement CSV export
        alert('Export feature coming soon!');
    });
    
    // Print order details
    $('.print-order').click(function() {
        window.print();
    });
    
    // Show notification function
    function showNotification(message, type = 'info') {
        const $notification = $('<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
            message +
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
            '</div>');
        
        $('.content-wrapper').prepend($notification);
        
        setTimeout(function() {
            $notification.alert('close');
        }, 5000);
    }
    
    // Dashboard charts (using Chart.js if available)
    if (typeof Chart !== 'undefined') {
        // Sales chart
        const salesCtx = document.getElementById('salesChart');
        if (salesCtx) {
            new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Sales (ETB)',
                        data: [12000, 19000, 15000, 25000, 22000, 30000],
                        borderColor: '#2ecc71',
                        backgroundColor: 'rgba(46, 204, 113, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
        
        // Products by category chart
        const categoryCtx = document.getElementById('categoryChart');
        if (categoryCtx) {
            new Chart(categoryCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Seeds', 'Fertilizers', 'Pesticides', 'Equipment'],
                    datasets: [{
                        data: [12, 8, 15, 10],
                        backgroundColor: [
                            '#2ecc71',
                            '#3498db',
                            '#f39c12',
                            '#9b59b6'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
    }
    
    // Mobile menu toggle
    $('#mobileMenuToggle').click(function() {
        $('.sidebar').toggleClass('active');
    });
    
    // Close sidebar when clicking outside on mobile
    $(document).click(function(e) {
        if ($(window).width() <= 768) {
            if (!$(e.target).closest('.sidebar').length && !$(e.target).closest('#mobileMenuToggle').length) {
                $('.sidebar').removeClass('active');
            }
        }
    });
});