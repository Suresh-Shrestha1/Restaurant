<?php
// Start session and include necessary files BEFORE any output
require_once 'config/database.php';
require_once 'includes/functions.php';

// Define delivery and tax constants (easy to modify)
define('DELIVERY_FEE', 50.00);
define('TAX_RATE', 13.00); // percentage

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

// Get user data from database
$userData = null;
try {
    $stmt = $pdo->prepare("SELECT name, phone, email FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userData = $stmt->fetch();
} catch (Exception $e) {
    error_log("Error fetching user data: " . $e->getMessage());
}

// Pre-fill address with last delivery address
$formData = ['address' => ''];

try {
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
    error_log("Error pre-filling address: " . $e->getMessage());
}

// Handle checkout form submission
if ($_POST && isset($_POST['place_order'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }
    
    // Get and sanitize form data
    $formData['address'] = trim($_POST['address'] ?? '');
    
    // Validation - only address
    if (empty($formData['address'])) {
        $errors['address'] = "Delivery address is required";
    } else if (strlen($formData['address']) < 2) {
        $errors['address'] = "Address must be at least 2 characters long";
    } else if (strlen($formData['address']) > 255) {
        $errors['address'] = "Address must be less than 255 characters";
    }
    
    // Only process order if no validation errors
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Calculate totals
            $subtotal = getCartTotal($pdo);
            $deliveryFee = DELIVERY_FEE;
            $taxRate = TAX_RATE;
            $taxAmount = ($subtotal * $taxRate) / 100;
            $total = $subtotal + $deliveryFee + $taxAmount;
            
            // Create order - use user data from database
            $stmt = $pdo->prepare("
                INSERT INTO orders (
                    user_id, name, phone, email, address, 
                    subtotal, delivery_fee, tax_amount, total
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $_SESSION['user_id'],
                $userData['name'] ?? 'Customer', 
                $userData['phone'] ?? '', 
                $userData['email'] ?? null, 
                $formData['address'],
                $subtotal, 
                $deliveryFee, 
                $taxAmount, 
                $total
            ]);
            
            $orderId = $pdo->lastInsertId();
            
            // Add order items
            foreach ($_SESSION['cart'] as $productId => $quantity) {
                $stmt = $pdo->prepare("SELECT price, name FROM products WHERE id = ?");
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
                INSERT INTO order_status_history (order_id, status) 
                VALUES (?, 'Pending')
            ");
            $stmt->execute([$orderId]);
            
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

// Calculate display totals
$subtotal = getCartTotal($pdo);
$deliveryFee = DELIVERY_FEE;
$taxRate = TAX_RATE;
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
                            <label for="address">Delivery Address *</label>
                            <textarea id="address" name="address" required rows="3"
                                      class="<?php echo isset($errors['address']) ? 'error' : ''; ?>"
                                      minlength="2" maxlength="255"><?php echo h($formData['address']); ?></textarea>
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
                        if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
                            $productIds = array_keys($_SESSION['cart']);
                            $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
                            $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
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
// Client-side validation for address only
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('checkoutForm');
    const addressField = document.getElementById('address');
    
    addressField.addEventListener('blur', function() {
        validateAddress(this);
    });
    
    addressField.addEventListener('focus', function() {
        clearError(this);
    });
    
    form.addEventListener('submit', function(e) {
        if (!validateAddress(addressField)) {
            e.preventDefault();
            alert('Please enter a valid delivery address.');
        }
    });
    
    function validateAddress(field) {
        clearError(field);
        
        if (!field.value.trim()) {
            showError(field, 'Delivery address is required');
            return false;
        }
        
        if (field.value.length < 2) {
            showError(field, 'Address must be at least 2 characters');
            return false;
        }
        
        if (field.value.length > 255) {
            showError(field, 'Address must be less than 255 characters');
            return false;
        }
        
        return true;
    }
    
    function showError(field, message) {
        field.classList.add('error');
        const errorElement = document.createElement('span');
        errorElement.className = 'error-message';
        errorElement.textContent = message;
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
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
}

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

.user-info {
    margin-bottom: 1.5rem;
    padding: 1rem;
    background-color: #e3f2fd;
    border-left: 4px solid #2196f3;
    border-radius: 4px;
    color: #000;
}

.user-info strong {
    color: #1976d2;
}
</style>

<?php require_once 'includes/footer.php'; ?>