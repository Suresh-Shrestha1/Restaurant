<?php
// includes/header.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

// CSRF validation for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRFProtection::validateRequest();
}

$cartCount = 0;
if (isset($_SESSION['cart'])) {
    $cartCount = array_sum($_SESSION['cart']);
}

$flashMessage = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($pageTitle ?? 'Agan Cafe'); ?> - Agan Cafe</title>
    <meta name="description" content="Delicious food delivered fresh to your door. Order online from Agan Cafe.">
    
    <!-- Preload critical resources -->
    <link rel="preload" href="css/styles.css" as="style">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" as="style">
    
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    
    <!-- Open Graph tags -->
    <meta property="og:title" content="<?php echo h($pageTitle ?? 'Agan Cafe'); ?>">
    <meta property="og:description" content="Delicious food delivered fresh to your door">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo h($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="nav-brand">
                <img src="uploads/logo.png" alt="Agan Cafe Logo" style="height: 60px; width: auto;">
                Agan Cafe
            </a>
            
            <div class="nav-menu" id="nav-menu">
                <a href="index.php" class="nav-link">
                    <i class="fas fa-home"></i> Home
                </a>
                <a href="menu.php" class="nav-link">
                    <i class="fas fa-book"></i> Menu
                </a>
                <a href="cart.php" class="nav-link cart-link">
                    <i class="fas fa-shopping-cart"></i> 
                    Cart 
                    <?php if ($cartCount > 0): ?>
                        <span class="cart-badge"><?php echo $cartCount; ?></span>
                    <?php endif; ?>
                </a>
                
                <?php if (isLoggedIn()): ?>
                    <a href="my-orders.php" class="nav-link">
                        <i class="fas fa-receipt"></i> My Orders
                    </a>
                    <div class="nav-dropdown">
                        <a href="#" class="nav-link dropdown-toggle">
                            <i class="fas fa-user"></i> 
                            <?php echo h($_SESSION['user_name']); ?>
                            <i class="fas fa-chevron-down"></i>
                        </a>
                        <div class="dropdown-menu">
                            <a href="profile.php" class="dropdown-link">
                                <i class="fas fa-user-edit"></i> Profile
                            </a>
                            <a href="logout.php" class="dropdown-link">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="nav-link">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                    <a href="register.php" class="nav-link">
                        <i class="fas fa-user-plus"></i> Register
                    </a>
                <?php endif; ?>
            </div>
            
            <div class="nav-toggle" id="nav-toggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>
    
    <div class="nav-overlay" id="nav-overlay"></div>


    <?php if ($flashMessage): ?>
        <div class="alert alert-<?php echo h($flashMessage['type']); ?> flash-message">
            <?php echo h($flashMessage['text']); ?>
            <button class="alert-close" onclick="this.parentElement.style.display='none'">
                <i class="fas fa-times"></i>
            </button>
        </div>
    <?php endif; ?>
    <script src="js/script.js"></script>
    
    <!-- Add to cart functionality -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
    // Mobile navigation toggle with overlay
const navToggle = document.getElementById('nav-toggle');
const navMenu = document.getElementById('nav-menu');
const navOverlay = document.getElementById('nav-overlay');

if (navToggle && navMenu && navOverlay) {
    navToggle.addEventListener('click', function() {
        navMenu.classList.toggle('active');
        navOverlay.classList.toggle('active');
    });

    // Close nav when overlay clicked
    navOverlay.addEventListener('click', function() {
        navMenu.classList.remove('active');
        navOverlay.classList.remove('active');
    });
}

// Dropdown toggle in mobile view
document.querySelectorAll('.nav-dropdown .dropdown-toggle').forEach(toggle => {
    toggle.addEventListener('click', function(e) {
        e.preventDefault();
        const parent = this.closest('.nav-dropdown');
        parent.classList.toggle('active');
    });
});

    
    // Toggle mobile menu
    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            navMenu.classList.toggle('active');
            navOverlay.classList.toggle('active');
            document.body.style.overflow = navMenu.classList.contains('active') ? 'hidden' : '';
        });
    }
    
    // Close menu when overlay is clicked
    if (navOverlay) {
        navOverlay.addEventListener('click', function() {
            navMenu.classList.remove('active');
            navOverlay.classList.remove('active');
            document.body.style.overflow = '';
        });
    }
    
    // Handle dropdown on mobile
    if (navDropdown && window.innerWidth <= 768) {
        const dropdownToggle = navDropdown.querySelector('.dropdown-toggle');
        
        dropdownToggle.addEventListener('click', function(e) {
            e.preventDefault();
            navDropdown.classList.toggle('active');
        });
    }
    
    // Close mobile menu on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && navMenu.classList.contains('active')) {
            navMenu.classList.remove('active');
            navOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
    
    // Handle resize events
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth > 768) {
                navMenu.classList.remove('active');
                navOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        }, 250);
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
                // Close mobile menu if open
                if (navMenu.classList.contains('active')) {
                    navMenu.classList.remove('active');
                    navOverlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            }
        });
    });
    
    // Add to cart buttons (existing code)
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.getAttribute('data-product-id');
            addToCart(productId);
        });
    });
    
    // Cart quantity controls (existing code)
    const quantityControls = document.querySelectorAll('.qty-btn');
    quantityControls.forEach(button => {
        button.addEventListener('click', function() {
            const input = this.parentNode.querySelector('input[type="number"]');
            const change = parseInt(this.textContent);
            const currentValue = parseInt(input.value);
            const newValue = Math.max(1, currentValue + change);
            input.value = newValue;
        });
    });
    
    // Auto-hide flash messages (existing code)
    const flashMessage = document.querySelector('.flash-message');
    if (flashMessage) {
        setTimeout(() => {
            flashMessage.style.opacity = '0';
            setTimeout(() => {
                flashMessage.remove();
            }, 300);
        }, 5000);
    }
});

// Keep your existing addToCart and updateQuantity functions as they are
    
    // Add to cart function
    async function addToCart(productId, quantity = 1) {
        try {
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('product_id', productId);
            formData.append('quantity', quantity);
            formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');
            
            const response = await fetch('cart.php', {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                // Update cart count in UI
                location.reload(); // Simple approach, can be improved with AJAX
            } else {
                alert('Failed to add item to cart');
            }
        } catch (error) {
            console.error('Error adding to cart:', error);
            alert('Failed to add item to cart');
        }
    }
    
    // Quantity update function
    function updateQuantity(button, change) {
        const input = button.parentNode.querySelector('input[type="number"]');
        const currentValue = parseInt(input.value);
        const newValue = Math.max(1, Math.min(99, currentValue + change));
        input.value = newValue;
    }
    </script>
</body>
</html>