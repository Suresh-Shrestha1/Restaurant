<?php
// Start session and include necessary files BEFORE any output
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in - redirect to login if not
if (!isLoggedIn()) {
    // Store the intended destination (checkout) in session for redirect after login
    $_SESSION['redirect_after_login'] = 'checkout.php';
    header('Location: login.php?message=' . urlencode('Please log in to continue with checkout'));
    exit;
}

// Set page title and include header AFTER login check
$pageTitle = "Checkout";
require_once 'includes/header.php';

// Check if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

$errors = [];
$success = false;

// Pre-fill form data with user information if available
$formData = [
    'name' => '',
    'phone' => '',
    'email' => '',
    'address' => ''
];

// Pre-fill with user data from database if available
try {
    $stmt = $pdo->prepare("SELECT name, phone, email FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userData = $stmt->fetch();
    
    if ($userData) {
        $formData['name'] = $userData['name'] ?? '';
        $formData['phone'] = $userData['phone'] ?? '';
        $formData['email'] = $userData['email'] ?? '';
    }
    
    // Get user's last delivery address from previous orders
    $stmt = $pdo->prepare("
        SELECT address FROM orders 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $lastOrder = $stmt->fetch();
    
    if ($lastOrder) {
        $formData['address'] = $lastOrder['address'];
    }
} catch (Exception $e) {
    // Continue with empty form data if there's an error
    error_log("Error pre-filling form data: " . $e->getMessage());
}

// Handle checkout form submission
if ($_POST && isset($_POST['place_order'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }
    
    // Get and sanitize form data
    $formData['name'] = trim($_POST['name'] ?? '');
    $formData['phone'] = trim($_POST['phone'] ?? '');
    $formData['email'] = trim($_POST['email'] ?? '');
    $formData['address'] = trim($_POST['address'] ?? '');
    
    // Validation
    if (empty($formData['name'])) {
        $errors['name'] = "Name is required";
    } else if (strlen($formData['name']) < 2) {
        $errors['name'] = "Name must be at least 2 characters long";
    } else if (strlen($formData['name']) > 100) {
        $errors['name'] = "Name must be less than 100 characters";
    } else if (!preg_match("/^[a-zA-Z\s\.\-']+$/", $formData['name'])) {
        $errors['name'] = "Name can only contain letters, spaces, hyphens, and apostrophes";
    }
    
    if (empty($formData['phone'])) {
        $errors['phone'] = "Phone number is required";
    } else {
        // Remove any non-digit characters
        $cleanPhone = preg_replace('/[^\d]/', '', $formData['phone']);
        
        // Phone validation - exactly 10 digits
        if (strlen($cleanPhone) !== 10) {
            $errors['phone'] = "Phone number must be exactly 10 digits";
        } else if (!preg_match('/^\d{10}$/', $cleanPhone)) {
            $errors['phone'] = "Phone number can only contain digits";
        } else {
            // Format phone number to store as 10 digits only
            $formData['phone'] = $cleanPhone;
        }
    }
    
    if (!empty($formData['email'])) {
        if (strlen($formData['email']) > 100) {
            $errors['email'] = "Email must be less than 100 characters";
        } else if (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Please enter a valid email address";
        } else if (strpos($formData['email'], '@') === false) {
            $errors['email'] = "Email must contain @ symbol";
        }
    }
    
    if (empty($formData['address'])) {
        $errors['address'] = "Delivery address is required";
    } else if (strlen($formData['address']) < 10) {
        $errors['address'] = "Address must be at least 10 characters long";
    } else if (strlen($formData['address']) > 255) {
        $errors['address'] = "Address must be less than 255 characters";
    }
    
    // Only process order if no validation errors
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Calculate totals
            $subtotal = getCartTotal($pdo);
            
            // Get settings from database
            $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('delivery_fee', 'tax_rate')");
            $stmt->execute();
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            $deliveryFee = floatval($settings['delivery_fee'] ?? 50.00);
            $taxRate = floatval($settings['tax_rate'] ?? 13.00);
            $taxAmount = ($subtotal * $taxRate) / 100;
            $total = $subtotal + $deliveryFee + $taxAmount;
            
            // Create order - order_number will be auto-generated by trigger
            $stmt = $pdo->prepare("
                INSERT INTO orders (
                    user_id, name, phone, email, address, 
                    subtotal, delivery_fee, tax_amount, total
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $_SESSION['user_id'], // Now we know user is logged in
                $formData['name'], 
                $formData['phone'], 
                $formData['email'] ?: null, 
                $formData['address'],
                $subtotal, 
                $deliveryFee, 
                $taxAmount, 
                $total
            ]);
            
            $orderId = $pdo->lastInsertId();
            
            // Add order items
            foreach ($_SESSION['cart'] as $productId => $quantity) {
                $stmt = $pdo->prepare("SELECT price, name FROM products WHERE id = ? AND is_available = 1");
                $stmt->execute([$productId]);
                $product = $stmt->fetch();
                
                if ($product) {
                    $unitPrice = floatval($product['price']);
                    $totalPrice = $unitPrice * $quantity;
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$orderId, $productId, $quantity, $unitPrice, $totalPrice]);
                } else {
                    throw new Exception("Product with ID $productId is no longer available");
                }
            }
            
            // Add initial status to order history
            $stmt = $pdo->prepare("
                INSERT INTO order_status_history (order_id, status, notes) 
                VALUES (?, 'Pending', 'Order placed successfully')
            ");
            $stmt->execute([$orderId]);
            
            // Update user profile with latest information (optional)
            try {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET name = ?, phone = ?, email = COALESCE(?, email)
                    WHERE id = ?
                ");
                $stmt->execute([
                    $formData['name'],
                    $formData['phone'],
                    !empty($formData['email']) ? $formData['email'] : null,
                    $_SESSION['user_id']
                ]);
            } catch (Exception $e) {
                // Don't fail the order if user update fails
                error_log("User profile update failed: " . $e->getMessage());
            }
            
            $pdo->commit();
            
            // Clear cart
            unset($_SESSION['cart']);
            $success = true;
            
        } catch (Exception $e) {
            $pdo->rollback();
            $errors['general'] = "Failed to process order. Please try again.";
            error_log("Order creation failed: " . $e->getMessage());
        }
    }
}

// Calculate display totals (consistent with order creation)
$subtotal = getCartTotal($pdo);

// Get settings from database for display
$stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('delivery_fee', 'tax_rate')");
$stmt->execute();
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$deliveryFee = floatval($settings['delivery_fee'] ?? 50.00);
$taxRate = floatval($settings['tax_rate'] ?? 13.00);
$taxAmount = ($subtotal * $taxRate) / 100;
$total = $subtotal + $deliveryFee + $taxAmount;
?>

<div class="page-header">
    <div class="container">
        <h1>Checkout</h1>
    </div>
</div>

<section class="checkout-section">
    <div class="container">
        <?php if ($success): ?>
            <div class="order-success">
                <i class="fas fa-check-circle"></i>
                <h2>Order Placed Successfully!</h2>
                <p>Thank you for your order. We'll contact you soon to confirm the details.</p>
                <div class="success-actions">
                    <a href="menu.php" class="btn btn-primary">Continue Shopping</a>
                    <a href="my-orders.php" class="btn btn-outline">View My Orders</a>
                </div>
            </div>
              <script>
    // Update cart count immediately
    document.addEventListener('DOMContentLoaded', function() {
        // Hide all cart badges
        document.querySelectorAll('.cart-count, .cart-badge').forEach(el => {
            el.textContent = '0';
            el.style.display = 'none';
        });
    });
    </script>
        <?php else: ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <h3>Please fix the following errors:</h3>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo h($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="checkout-content">
                <div class="checkout-form">
                    <h2>Delivery Information</h2>
                    <p class="user-info">Ordering as: <strong><?php echo h($_SESSION['username'] ?? 'User'); ?></strong></p>
                    
                    <form method="POST" id="checkoutForm" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="form-group">
                            <label for="name">Full Name *</label>
                            <input type="text" id="name" name="name" required 
                                   value="<?php echo h($formData['name']); ?>"
                                   class="<?php echo isset($errors['name']) ? 'error' : ''; ?>"
                                   minlength="2" maxlength="100">
                            <?php if (isset($errors['name'])): ?>
                                <span class="error-message"><?php echo h($errors['name']); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number *</label>
                            <input type="tel" id="phone" name="phone" required 
                                   value="<?php echo h($formData['phone']); ?>"
                                   class="<?php echo isset($errors['phone']) ? 'error' : ''; ?>"
                                   pattern="[0-9]{10}" 
                                   title="Please enter exactly 10 digits">
                            <?php if (isset($errors['phone'])): ?>
                                <span class="error-message"><?php echo h($errors['phone']); ?></span>
                            <?php endif; ?>
                            <small>Format: Exactly 10 digits (no spaces or special characters)</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email (Optional)</label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo h($formData['email']); ?>"
                                   class="<?php echo isset($errors['email']) ? 'error' : ''; ?>"
                                   maxlength="100">
                            <?php if (isset($errors['email'])): ?>
                                <span class="error-message"><?php echo h($errors['email']); ?></span>
                            <?php endif; ?>
                            <small>Must be a valid email address containing @ symbol</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Delivery Address *</label>
                            <textarea id="address" name="address" required rows="3"
                                      class="<?php echo isset($errors['address']) ? 'error' : ''; ?>"
                                      minlength="10" maxlength="255"><?php echo h($formData['address']); ?></textarea>
                            <?php if (isset($errors['address'])): ?>
                                <span class="error-message"><?php echo h($errors['address']); ?></span>
                            <?php endif; ?>
                            <small>Please provide complete address including street, city, and any relevant landmarks</small>
                        </div>
                        
                        <div class="form-actions">
                            <a href="cart.php" class="btn btn-outline">Back to Cart</a>
                            <button type="submit" name="place_order" class="btn btn-primary">Place Order</button>
                        </div>
                    </form>
                </div>
                
                <div class="order-summary">
                    <h3>Order Summary</h3>
                    
                    <div class="summary-items">
                        <?php
                        $cartItems = [];
                        if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
                            $productIds = array_keys($_SESSION['cart']);
                            $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
                            $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders) AND is_available = 1");
                            $stmt->execute($productIds);
                            $products = $stmt->fetchAll();
                            
                            foreach ($products as $product):
                                $quantity = $_SESSION['cart'][$product['id']];
                                $itemTotal = $product['price'] * $quantity;
                        ?>
                            <div class="summary-item">
                                <span class="item-name"><?php echo h($product['name']); ?></span>
                                <span class="item-quantity">x<?php echo $quantity; ?></span>
                                <span class="item-price"><?php echo formatCurrency($itemTotal); ?></span>
                            </div>
                        <?php 
                            endforeach;
                        }
                        ?>
                    </div>
                    
                    <div class="summary-total">
                        <div class="total-row">
                            <span>Subtotal</span>
                            <span><?php echo formatCurrency($subtotal); ?></span>
                        </div>
                        <div class="total-row">
                            <span>Delivery Fee</span>
                            <span><?php echo formatCurrency($deliveryFee); ?></span>
                        </div>
                        <div class="total-row">
                            <span>Tax (<?php echo number_format($taxRate, 1); ?>%)</span>
                            <span><?php echo formatCurrency($taxAmount); ?></span>
                        </div>
                        <div class="total-row final-total">
                            <span><strong>Total</strong></span>
                            <strong><?php echo formatCurrency($total); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
// Client-side validation enhancement
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('checkoutForm');
    const inputs = form.querySelectorAll('input, textarea');
    
    // Phone number formatting - allow only digits
    const phoneInput = document.getElementById('phone');
    phoneInput.addEventListener('input', function(e) {
        // Remove any non-digit characters
        this.value = this.value.replace(/[^\d]/g, '');
        
        // Limit to 10 digits
        if (this.value.length > 10) {
            this.value = this.value.slice(0, 10);
        }
    });
    
    inputs.forEach(input => {
        // Real-time validation on input
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        // Clear error on focus
        input.addEventListener('focus', function() {
            clearError(this);
        });
    });
    
    // Form submission validation
    form.addEventListener('submit', function(e) {
        let isValid = true;
        inputs.forEach(input => {
            if (!validateField(input)) {
                isValid = false;
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fix the errors before submitting the form.');
        }
    });
    
    function validateField(field) {
        clearError(field);
        
        // Required field validation
        if (field.hasAttribute('required') && !field.value.trim()) {
            showError(field, 'This field is required');
            return false;
        }
        
        // Email validation
        if (field.type === 'email' && field.value.trim()) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(field.value)) {
                showError(field, 'Please enter a valid email address containing @ symbol');
                return false;
            }
            
            // Additional check for @ symbol
            if (field.value.indexOf('@') === -1) {
                showError(field, 'Email must contain @ symbol');
                return false;
            }
        }
        
        // Phone validation - exactly 10 digits
        if (field.name === 'phone' && field.value.trim()) {
            const cleanPhone = field.value.replace(/[^\d]/g, '');
            if (cleanPhone.length !== 10) {
                showError(field, 'Phone number must be exactly 10 digits');
                return false;
            }
            
            // Basic format check (all digits)
            if (!/^\d+$/.test(cleanPhone)) {
                showError(field, 'Phone number can only contain digits');
                return false;
            }
        }
        
        // Length validation
        if (field.hasAttribute('minlength')) {
            const minLength = parseInt(field.getAttribute('minlength'));
            if (field.value.length < minLength) {
                showError(field, `Must be at least ${minLength} characters`);
                return false;
            }
        }
        
        if (field.hasAttribute('maxlength')) {
            const maxLength = parseInt(field.getAttribute('maxlength'));
            if (field.value.length > maxLength) {
                showError(field, `Must be less than ${maxLength} characters`);
                return false;
            }
        }
        
        return true;
    }
    
    function showError(field, message) {
        field.classList.add('error');
        const errorElement = document.createElement('span');
        errorElement.className = 'error-message';
        errorElement.textContent = message;
        errorElement.style.color = '#dc3545';
        errorElement.style.fontSize = '0.875rem';
        errorElement.style.marginTop = '0.25rem';
        errorElement.style.display = 'block';
        field.parentNode.appendChild(errorElement);
    }
    
    function clearError(field) {
        field.classList.remove('error');
        const existingError = field.parentNode.querySelector('.error-message');
        if (existingError) {
            existingError.remove();
        }
    }
});
</script>

<style>
.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: bold;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
}

.form-group input.error,
.form-group textarea.error {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

.error-message {
    color: #dc3545;
    font-size: 0.875rem;
    margin-top: 0.25rem;
    display: block;
}

small {
    color: #6c757d;
    font-size: 0.875rem;
    margin-top: 0.25rem;
    display: block;
}

.alert-error {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 2rem;
}

.alert-error h3 {
    margin-top: 0;
    margin-bottom: 0.5rem;
}

.alert-error ul {
    margin-bottom: 0;
}

.user-info {
    margin-bottom: 1.5rem;
    padding: 1rem;
    background-color: #e3f2fd;
    border-left: 4px solid #2196f3;
    border-radius: 4px;
}

.user-info strong {
    color: #1976d2;
}
</style>

<?php require_once 'includes/footer.php'; ?>