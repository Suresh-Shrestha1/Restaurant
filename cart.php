<?php
// cart.php - Main cart file
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Handle cart updates BEFORE any output to avoid header issues
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        redirect('cart.php', 'Invalid request!', 'error');
        exit;
    }
    
    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    switch ($_POST['action']) {
        case 'add':
            $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
            $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
            
            if ($productId && $quantity && $quantity > 0) {
                // Verify product exists and is available
                $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND is_available = 1");
                $stmt->execute([$productId]);
                
                if ($stmt->fetch()) {
                    $quantity = min(99, max(1, $quantity)); // Limit quantity
                    
                    if (isset($_SESSION['cart'][$productId])) {
                        $_SESSION['cart'][$productId] = min(99, $_SESSION['cart'][$productId] + $quantity);
                    } else {
                        $_SESSION['cart'][$productId] = $quantity;
                    }
                    
                    redirect('menu.php', 'Item added to cart!', 'success');
                } else {
                    redirect('index.php', 'Product not available!', 'error');
                }
            } else {
                redirect('index.php', 'Invalid product or quantity!', 'error');
            }
            break;
            
        case 'update':
            $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
            $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
            
            if ($productId && $quantity !== false) {
                if ($quantity > 0) {
                    $quantity = min(99, max(1, $quantity)); // Limit quantity
                    $_SESSION['cart'][$productId] = $quantity;
                    redirect('cart.php', 'Cart updated!', 'success');
                } else {
                    unset($_SESSION['cart'][$productId]);
                    redirect('cart.php', 'Item removed from cart!', 'success');
                }
            } else {
                redirect('cart.php', 'Invalid update request!', 'error');
            }
            break;
            
        case 'remove':
            $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
            
            if ($productId && isset($_SESSION['cart'][$productId])) {
                unset($_SESSION['cart'][$productId]);
                redirect('cart.php', 'Item removed from cart!', 'success');
            } else {
                redirect('cart.php', 'Item not found in cart!', 'error');
            }
            break;
            
        case 'clear':
            $_SESSION['cart'] = [];
            redirect('cart.php', 'Cart cleared!', 'success');
            break;
            
        default:
            redirect('cart.php', 'Invalid action!', 'error');
            break;
    }
    exit;
}

// Set page title and include header
$pageTitle = "Shopping Cart";
require_once 'includes/header.php';

// Get cart items
$cartItems = [];
$total = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $productIds = array_keys($_SESSION['cart']);
    
    if (!empty($productIds)) {
        $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders) AND is_available = 1");
            $stmt->execute($productIds);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($products as $product) {
                $quantity = $_SESSION['cart'][$product['id']];
                $subtotal = $product['price'] * $quantity;
                $total += $subtotal;
                
                $cartItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal
                ];
            }
            
            // Remove unavailable products from cart
            $availableIds = array_column($products, 'id');
            foreach ($productIds as $id) {
                if (!in_array($id, $availableIds)) {
                    unset($_SESSION['cart'][$id]);
                }
            }
            
        } catch (PDOException $e) {
            error_log("Error fetching cart items: " . $e->getMessage());
            $cartItems = [];
        }
    }
}

// Get delivery fee for display
$deliveryFee = 50.00;

try {
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key = 'delivery_fee'");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $deliveryFee = floatval($settings['delivery_fee'] ?? 50.00);
} catch (PDOException $e) {
    error_log("Error fetching settings: " . $e->getMessage());
}

$grandTotal = $total + $deliveryFee;

// Generate CSRF token for forms
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!-- Display flash messages if any -->
<?php if ($flashMessage = getFlashMessage()): ?>
    <?php 
    $alertType = $flashMessage['type'] === 'error' ? 'danger' : $flashMessage['type'];
    ?>
    <div class="flash-message">
        <div class="alert alert-<?php echo htmlspecialchars($alertType, ENT_QUOTES, 'UTF-8'); ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($flashMessage['text'], ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close alert-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
<?php endif; ?>


<div class="page-header">
    <div class="container">
        <h1>Shopping Cart</h1>
        <?php if (!empty($cartItems)): ?>
            <p><?php echo count($cartItems); ?> item<?php echo count($cartItems) !== 1 ? 's' : ''; ?> in your cart</p>
        <?php endif; ?>
    </div>
</div>

<section class="cart-section">
    <div class="container">
        <?php if (empty($cartItems)): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h3>Your cart is empty</h3>
                <p>Add some delicious items from our menu!</p>
                <a href="menu.php" class="btn btn-primary">Browse Menu</a>
            </div>
        <?php else: ?>
            <div class="cart-content">
                <div class="cart-items">
                    <div class="cart-header">
                        <h3>Items in your cart</h3>
                    </div>
                    
                    <?php foreach ($cartItems as $item): ?>
                        <div class="cart-item">
                            <div class="item-image">
                                <?php if ($item['product']['image']): ?>
                                    <img src="uploads/<?php echo htmlspecialchars($item['product']['image'], ENT_QUOTES, 'UTF-8'); ?>" 
                                         alt="<?php echo htmlspecialchars($item['product']['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php else: ?>
                                    <div class="no-image">
                                        <i class="fas fa-utensils"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="item-details">
                                <h4><?php echo htmlspecialchars($item['product']['name'], ENT_QUOTES, 'UTF-8'); ?></h4>
                                <?php if ($item['product']['description']): ?>
                                    <p class="item-description"><?php echo htmlspecialchars($item['product']['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <?php endif; ?>
                                <span class="unit-price"><?php echo formatCurrency($item['product']['price']); ?> each</span>
                            </div>
                            
                            <div class="item-quantity">
                                <form method="POST" class="quantity-form" style="display: none;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="product_id" value="<?php echo $item['product']['id']; ?>">
                                    <input type="hidden" name="quantity" value="<?php echo $item['quantity']; ?>" class="hidden-qty-input">
                                </form>
                                
                                <div class="quantity-controls">
                                    <button type="button" class="qty-btn" onclick="changeQuantity(this, -1, <?php echo $item['product']['id']; ?>)" <?php echo $item['quantity'] <= 1 ? 'disabled' : ''; ?>>-</button>
                                    <span class="qty-display"><?php echo $item['quantity']; ?></span>
                                    <button type="button" class="qty-btn" onclick="changeQuantity(this, 1, <?php echo $item['product']['id']; ?>)" <?php echo $item['quantity'] >= 99 ? 'disabled' : ''; ?>>+</button>
                                </div>
                                
                                <div class="quantity-loading" style="display: none;">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </div>
                            </div>
                            
                            <div class="item-total">
                                <strong><?php echo formatCurrency($item['subtotal']); ?></strong>
                            </div>
                            
                            <div class="item-remove">
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="product_id" value="<?php echo $item['product']['id']; ?>">
                                    <button type="submit" class="btn-remove" onclick="return confirm('Remove this item from cart?')" title="Remove item">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="cart-summary">
                    <div class="summary-card">
                        <h3>Order Summary</h3>
                        
                        <div class="summary-details">
                            <div class="summary-row">
                                <span>Subtotal (<?php echo count($cartItems); ?> items)</span>
                                <span><?php echo formatCurrency($total); ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Delivery Fee</span>
                                <span><?php echo formatCurrency($deliveryFee); ?></span>
                            </div>
                            <div class="summary-row total-row">
                                <span><strong>Total</strong></span>
                                <strong><?php echo formatCurrency($grandTotal); ?></strong>
                            </div>
                        </div>
                        
                        <div class="cart-actions">
                            <form method="POST" class="clear-cart-form">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="action" value="clear">
                                <button type="submit" class="btn btn-outline" onclick="return confirm('Clear entire cart?')">
                                    <i class="fas fa-trash"></i> Clear Cart
                                </button>
                            </form>
                            
                            <div class="checkout-actions">
                                <a href="menu.php" class="btn btn-outline">
                                    <i class="fas fa-arrow-left"></i> Continue Shopping
                                </a>
                                <a href="checkout.php" class="btn btn-primary btn-large">
                                    <i class="fas fa-credit-card"></i> Proceed to Checkout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
// Automatic quantity update function
function changeQuantity(button, change, productId) {
    const itemElement = button.closest('.cart-item');
    const qtyDisplay = itemElement.querySelector('.qty-display');
    const hiddenForm = itemElement.querySelector('.quantity-form');
    const hiddenQtyInput = hiddenForm.querySelector('.hidden-qty-input');
    const loadingElement = itemElement.querySelector('.quantity-loading');
    const quantityControls = itemElement.querySelector('.quantity-controls');
    
    const currentValue = parseInt(qtyDisplay.textContent) || 1;
    const newValue = Math.max(1, Math.min(99, currentValue + change));
    
    if (newValue !== currentValue) {
        // Show loading state
        quantityControls.style.display = 'none';
        loadingElement.style.display = 'flex';
        
        // Update hidden form values
        hiddenQtyInput.value = newValue;
        
        // Submit the form
        hiddenForm.submit();
    }
}

// Handle direct quantity input changes (if you want to add this feature back)
function handleQuantityInput(input) {
    const itemElement = input.closest('.cart-item');
    const form = itemElement.querySelector('.quantity-form');
    const hiddenQtyInput = form.querySelector('.hidden-qty-input');
    
    let value = parseInt(input.value) || 1;
    value = Math.max(1, Math.min(99, value));
    input.value = value;
    hiddenQtyInput.value = value;
    
    // Auto-submit after a brief delay
    clearTimeout(window.quantityTimer);
    window.quantityTimer = setTimeout(() => {
        form.submit();
    }, 1000);
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Update button states based on current quantities
    document.querySelectorAll('.cart-item').forEach(item => {
        const qtyDisplay = item.querySelector('.qty-display');
        const minusBtn = item.querySelector('.qty-btn:first-child');
        const plusBtn = item.querySelector('.qty-btn:last-child');
        
        if (qtyDisplay) {
            const currentQty = parseInt(qtyDisplay.textContent) || 1;
            minusBtn.disabled = currentQty <= 1;
            plusBtn.disabled = currentQty >= 99;
        }
    });
});

// Optional: Add keyboard support for quantity controls
document.addEventListener('keydown', function(e) {
    if (e.target.classList.contains('qty-display') && e.target.contentEditable === 'true') {
        if (e.key === 'Enter') {
            e.preventDefault();
            e.target.blur();
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>