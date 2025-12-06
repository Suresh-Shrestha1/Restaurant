<?php
// Start session and include necessary files BEFORE any output
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in - redirect to login if not
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = 'my-orders.php';
    header('Location: login.php?message=' . urlencode('Please log in to view your orders'));
    exit;
}

// Set page title and include header AFTER login check
$pageTitle = "My Orders";
require_once 'includes/header.php';

// Get user orders
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();
?>

<div class="page-header">
    <div class="container">
        <h1>My Orders</h1>
        <p>Track your order history and status</p>
    </div>
</div>

<section class="orders-section">
    <div class="container">
        <?php if (empty($orders)): ?>
            <div class="no-orders">
                <i class="fas fa-receipt"></i>
                <h3>No orders yet</h3>
                <p>You haven't placed any orders yet. Start by browsing our menu!</p>
                <a href="menu.php" class="btn btn-primary">Browse Menu</a>
            </div>
        <?php else: ?>
            <div class="orders-list">
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-info">
                                <h3>Order #<?php echo h($order['order_number']); ?></h3>
                                <p class="order-date"><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></p>
                            </div>
                            <div class="order-status">
                                <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $order['status'])); ?>">
                                    <?php echo h($order['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="order-details">
                            <div class="delivery-info">
                                <h4>Delivery Details</h4>
                                <p><strong>Name:</strong> <?php echo h($order['name']); ?></p>
                                <p><strong>Phone:</strong> <?php echo h($order['phone']); ?></p>
                                <?php if ($order['email']): ?>
                                    <p><strong>Email:</strong> <?php echo h($order['email']); ?></p>
                                <?php endif; ?>
                                <p><strong>Address:</strong> <?php echo h($order['address']); ?></p>
                            </div>
                            
                            <div class="order-items">
                                <h4>Items Ordered</h4>
                                <?php
                                // FIXED: Use LEFT JOIN and COALESCE to handle deleted products
                                $stmt = $pdo->prepare("
                                    SELECT 
                                        oi.*, 
                                        COALESCE(oi.product_name, p.name, 'Product Unavailable') as name,
                                        COALESCE(oi.product_image, p.image) as image,
                                        CASE WHEN p.id IS NULL THEN 1 ELSE 0 END as is_deleted
                                    FROM order_items oi 
                                    LEFT JOIN products p ON oi.product_id = p.id 
                                    WHERE oi.order_id = ?
                                ");
                                $stmt->execute([$order['id']]);
                                $items = $stmt->fetchAll();
                                ?>
                                
                                <div class="items-list">
                                    <?php foreach ($items as $item): ?>
                                        <div class="item-row <?php echo $item['is_deleted'] ? 'item-unavailable' : ''; ?>">
                                            <div class="item-image">
                                                <?php if ($item['image'] && file_exists('uploads/' . $item['image'])): ?>
                                                    <img src="uploads/<?php echo h($item['image']); ?>" alt="<?php echo h($item['name']); ?>">
                                                <?php else: ?>
                                                    <div class="no-image">
                                                        <i class="fas fa-utensils"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="item-details">
                                                <span class="item-name">
                                                    <?php echo h($item['name']); ?>
                                                    <?php if ($item['is_deleted']): ?>
                                                        <small class="text-muted">(No longer available)</small>
                                                    <?php endif; ?>
                                                </span>
                                                <span class="item-quantity">x<?php echo $item['quantity']; ?></span>
                                                <span class="item-unit-price"><?php echo formatCurrency($item['unit_price']); ?> each</span>
                                                <span class="item-total-price"><?php echo formatCurrency($item['total_price']); ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="order-summary">
                            <div class="summary-row">
                                <span>Subtotal:</span>
                                <span><?php echo formatCurrency($order['subtotal']); ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Delivery Fee:</span>
                                <span><?php echo formatCurrency($order['delivery_fee']); ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Tax:</span>
                                <span><?php echo formatCurrency($order['tax_amount']); ?></span>
                            </div>
                            <div class="summary-row total" style="font-weight: bold; font-size: 18px; color: #08420dff;">
                                <strong>Total:</strong>
                                <strong><?php echo formatCurrency($order['total']); ?></strong>
                            </div>
                        </div>
                        
                        <div class="order-actions">
                            <?php if ($order['status'] === 'Pending'): ?>
                                <small class="order-note">Your order is being processed. We'll call you soon to confirm.</small>
                            <?php elseif ($order['status'] === 'Confirmed'): ?>
                                <small class="order-note">Order confirmed! We're preparing your food.</small>
                            <?php elseif ($order['status'] === 'Preparing'): ?>
                                <small class="order-note">Your food is being prepared with care.</small>
                            <?php elseif ($order['status'] === 'Out for Delivery'): ?>
                                <small class="order-note">Your order is on the way! Please be available at the delivery address.</small>
                            <?php elseif ($order['status'] === 'Delivered'): ?>
                                <small class="order-note">Order delivered successfully. Thank you for choosing us!</small>
                            <?php elseif ($order['status'] === 'Cancelled'): ?>
                                <small class="order-note">This order was cancelled.</small>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
.item-unavailable {
    opacity: 0.7;
    background-color: #f8f9fa;
    border-left: 3px solid #dc3545;
}

.item-unavailable .item-name small {
    color: #dc3545;
    font-style: italic;
}
</style>

<?php require_once 'includes/footer.php'; ?>