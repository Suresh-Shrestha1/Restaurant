document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit status forms
    const statusSelects = document.querySelectorAll('.status-form select');
    statusSelects.forEach(select => {
        select.addEventListener('change', function() {
            const form = this.closest('form');
            if (confirm('Update order status?')) {
                form.submit();
            } else {
                // Reset to original value if cancelled
                this.selectedIndex = 0;
            }
        });
    });
    
    // Image preview for product forms
    const imageInput = document.getElementById('image');
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Create or update preview
                    let preview = document.getElementById('image-preview');
                    if (!preview) {
                        preview = document.createElement('div');
                        preview.id = 'image-preview';
                        preview.style.marginTop = '1rem';
                        imageInput.parentNode.appendChild(preview);
                    }
                    
                    preview.innerHTML = `
                        <img src="${e.target.result}" alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <p style="margin: 0.5rem 0 0; font-size: 0.85rem; color: #666;">Preview</p>
                    `;
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Form validation
    const forms = document.querySelectorAll('.admin-form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#dc3545';
                    
                    // Remove error styling after user starts typing
                    field.addEventListener('input', function() {
                        this.style.borderColor = '#ddd';
                    });
                } else {
                    field.style.borderColor = '#ddd';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    });
    
    // Confirmation dialogs
    const deleteLinks = document.querySelectorAll('a[href*="delete="]');
    deleteLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
    
    // Auto-refresh dashboard stats every 30 seconds
    if (window.location.pathname.includes('admin/index.php') || window.location.pathname.endsWith('admin/')) {
        setInterval(function() {
            // You could implement AJAX refresh here
            console.log('Dashboard auto-refresh (implement AJAX call)');
        }, 30000);
    }
    
    // Search functionality for tables
    const searchInputs = document.querySelectorAll('.search-input');
    searchInputs.forEach(input => {
        input.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const table = document.querySelector('.admin-table tbody');
            const rows = table.querySelectorAll('tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(filter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
});