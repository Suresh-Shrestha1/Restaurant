<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    die('<div class="error">Access denied. Please login.</div>');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('<div class="error">Invalid order ID</div>');
}

$orderId = (int)$_GET['id'];

try {
    // Get order details
    $stmt = $pdo->prepare("
        SELECT o.*, u.email as user_email 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    
    if (!$order) {
        die('<div class="error">Order not found</div>');
    }
    
    // Get order items (make sure your order_items table exists)
    $stmt = $pdo->prepare("
        SELECT oi.*, p.name as product_name 
        FROM order_items oi 
        LEFT JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$orderId]);
    $orderItems = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die('<div class="error">Database error: ' . $e->getMessage() . '</div>');
}
?>

<div class="order-details">
    <div class="order-header">
        <h4>Order #<?php echo h($order['order_number']); ?></h4>
        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $order['status'])); ?>">
            <?php echo h($order['status']); ?>
        </span>
    </div>
    
    <div class="order-info-grid">
        <div class="info-section">
            <h5>Customer Information</h5>
            <div class="info-item">
                <label>Name:</label>
                <span><?php echo h($order['name']); ?></span>
            </div>
            <div class="info-item">
                <label>Phone:</label>
                <span><?php echo h($order['phone']); ?></span>
            </div>
            <?php if ($order['email']): ?>
            <div class="info-item">
                <label>Email:</label>
                <span><?php echo h($order['email']); ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="info-section">
            <h5>Delivery Information</h5>
            <div class="info-item">
                <label>Address:</label>
                <span><?php echo nl2br(h($order['address'])); ?></span>
            </div>
            <?php if ($order['delivery_instructions']): ?>
            <div class="info-item">
                <label>Instructions:</label>
                <span><?php echo h($order['delivery_instructions']); ?></span>
            </div>
            <?php endif; ?>
            <div class="info-item">
                <label>Payment Method:</label>
                <span><?php echo h($order['payment_method']); ?></span>
            </div>
        </div>
        
        <div class="info-section">
            <h5>Order Timeline</h5>
            <div class="info-item">
                <label>Order Placed:</label>
                <span><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></span>
            </div>
            <?php if ($order['delivered_at']): ?>
            <div class="info-item">
                <label>Delivered At:</label>
                <span><?php echo date('M j, Y g:i A', strtotime($order['delivered_at'])); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="order-items">
        <h5>Order Items</h5>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($orderItems)): ?>
                    <?php foreach ($orderItems as $item): ?>
                        <tr>
                            <td><?php echo h($item['product_name']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td><?php echo formatCurrency($item['unit_price']); ?></td>
                            <td><?php echo formatCurrency($item['quantity'] * $item['unit_price']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">No items found for this order</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="order-summary">
        <div class="summary-item">
            <label>Subtotal:</label>
            <span><?php echo formatCurrency($order['subtotal']); ?></span>
        </div>
        <div class="summary-item">
            <label>Delivery Fee:</label>
            <span><?php echo formatCurrency($order['delivery_fee']); ?></span>
        </div>
        <div class="summary-item">
            <label>Tax:</label>
            <span><?php echo formatCurrency($order['tax_amount']); ?></span>
        </div>
        <?php if ($order['discount_amount'] > 0): ?>
        <div class="summary-item">
            <label>Discount:</label>
            <span>-<?php echo formatCurrency($order['discount_amount']); ?></span>
        </div>
        <?php endif; ?>
        <div class="summary-item total">
            <label>Total:</label>
            <span><?php echo formatCurrency($order['total']); ?></span>
        </div>
    </div>
</div>

<style>
/* ==============================
   Order Details Page Styling
   ============================== */

.order-details {
    background: #ffffff;
    border-radius: 10px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    max-width: 1100px;
    margin: 2rem auto;
    font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
    color: #1e293b;
}

/* ------------------------------
   Header
------------------------------ */
.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.order-header h4 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
}

/* ------------------------------
   Status Badge
------------------------------ */
.status-badge {
    padding: 0.4rem 0.9rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-confirmed { background: #cce5ff; color: #004085; }
.status-preparing { background: #ffe5cc; color: #8b4000; }
.status-out-for-delivery { background: #e5ccff; color: #4b0080; }
.status-delivered { background: #d4edda; color: #155724; }
.status-cancelled { background: #f8d7da; color: #721c24; }

/* ------------------------------
   Info Grid
------------------------------ */
.order-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2.5rem;
}

.info-section {
    background: #f8fafc;
    border-radius: 8px;
    padding: 1.25rem 1.5rem;
    border: 1px solid #e2e8f0;
}

.info-section h5 {
    font-size: 1.1rem;
    font-weight: 600;
    color: #334155;
    margin-bottom: 1rem;
}

.info-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.75rem;
    font-size: 0.95rem;
}

.info-item label {
    color: #64748b;
    font-weight: 500;
}

.info-item span {
    text-align: right;
    color: #0f172a;
    max-width: 60%;
    word-break: break-word;
}

/* ------------------------------
   Order Items Table
------------------------------ */
.order-items {
    margin-bottom: 2.5rem;
}

.order-items h5 {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: #334155;
}

.items-table {
    width: 100%;
    border-collapse: collapse;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
}

.items-table th,
.items-table td {
    padding: 0.9rem 1rem;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
    font-size: 0.95rem;
}

.items-table th {
    background: #f1f5f9;
    font-weight: 600;
    color: #475569;
    text-transform: uppercase;
    font-size: 0.85rem;
}

.items-table tr:last-child td {
    border-bottom: none;
}

.items-table td {
    color: #1e293b;
}

.items-table td:first-child {
    font-weight: 500;
}

/* ------------------------------
   Order Summary
------------------------------ */
.order-summary {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    border-top: 1px solid #e2e8f0;
    padding-top: 1.5rem;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    gap: 2rem;
    width: 280px;
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
}

.summary-item label {
    color: #64748b;
    font-weight: 500;
}

.summary-item span {
    font-weight: 600;
    color: #0f172a;
}

.summary-item.total {
    border-top: 2px solid #e2e8f0;
    padding-top: 0.75rem;
    margin-top: 0.75rem;
    font-size: 1.05rem;
}

.summary-item.total span {
    color: #16a34a;
    font-weight: 700;
}

/* ------------------------------
   Utility Styles
------------------------------ */
.error {
    background: #fee2e2;
    border: 1px solid #fca5a5;
    color: #991b1b;
    padding: 1rem;
    border-radius: 8px;
    margin: 1rem auto;
    max-width: 600px;
    text-align: center;
    font-weight: 600;
}

/* ------------------------------
   Responsive Adjustments
------------------------------ */
@media (max-width: 768px) {
    .order-details {
        padding: 1.25rem;
    }

    .order-summary {
        align-items: stretch;
    }

    .summary-item {
        width: 100%;
    }
}

</style>