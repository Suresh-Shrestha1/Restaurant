<?php
// Start session and check authentication FIRST
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Include configuration and database connection
require_once '../config/database.php';
require_once '../includes/functions.php';

$pageTitle = "Dashboard";

// Get statistics using your conditional logic
$stats = [];
$recent_orders = [];
$top_products = [];

try {
    // Use your single query with CASE statements for all order statistics
    // Only include delivered orders in total sales
    $statsQuery = "
        SELECT 
            COUNT(*) as total_orders,
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_orders,
            SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as todays_orders,
            SUM(CASE WHEN status = 'Delivered' THEN total ELSE 0 END) as total_sales
        FROM orders
    ";
    $orderStats = $pdo->query($statsQuery)->fetch();
    
    // Assign the values to stats array
    $stats['total_orders'] = $orderStats['total_orders'] ?? 0;
    $stats['pending_orders'] = $orderStats['pending_orders'] ?? 0;
    $stats['todays_orders'] = $orderStats['todays_orders'] ?? 0;
    $stats['total_sales'] = $orderStats['total_sales'] ?? 0;

    // Total products (separate query since it's from different table)
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $stats['total_products'] = $stmt->fetch()['count'];

    // Recent orders
    $stmt = $pdo->query("
        SELECT id, order_number, name, total, status, created_at 
        FROM orders 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recent_orders = $stmt->fetchAll();

    // Top selling products
    $stmt = $pdo->query("
        SELECT p.name, SUM(oi.quantity) as total_sold, 
               SUM(oi.quantity * oi.unit_price) as total_revenue
        FROM products p 
        JOIN order_items oi ON p.id = oi.product_id 
        GROUP BY p.id, p.name
        ORDER BY total_sold DESC 
        LIMIT 5
    ");
    $top_products = $stmt->fetchAll();

} catch (PDOException $e) {
    // Handle database errors gracefully
    error_log("Database error: " . $e->getMessage());
    $stats = [
        'total_orders' => 0, 
        'total_sales' => 0, 
        'pending_orders' => 0, 
        'todays_orders' => 0,
        'total_products' => 0
    ];
    $recent_orders = [];
    $top_products = [];
}

// Include header after all processing
require_once 'includes/header.php';
?>


<div class="dashboard-stats">
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-shopping-cart"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo number_format($stats['total_orders']); ?></h3>
            <p>Total Orders</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-dollar-sign"></i>
        </div>
          <div class="stat-info">
                <h3><?php echo formatCurrency($stats['total_sales'] ?? 0); ?></h3>
                <p>Total Sales</p>
            </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo number_format($stats['pending_orders']); ?></h3>
            <p>Pending Orders</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-utensils"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo number_format($stats['total_products']); ?></h3>
            <p>Total Products</p>
        </div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="dashboard-card">
        <h3>Recent Orders</h3>
        
        <?php if (empty($recent_orders)): ?>
            <p>No orders yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_orders as $order): ?>
                            <tr>
                                <td><?php echo h($order['order_number']); ?></td>
                                <td><?php echo h($order['name']); ?></td>
                                <td><?php echo formatCurrency($order['total']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                        <?php echo h($order['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="card-footer">
                <a href="orders.php" class="btn btn-sm btn-outline">View All Orders</a>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="dashboard-card">
        <h3>Top Selling Products</h3>
        
        <?php if (empty($top_products)): ?>
            <p>No sales data available.</p>
        <?php else: ?>
            <div class="top-products-list">
                <?php foreach ($top_products as $product): ?>
                    <div class="product-item">
                        <div class="product-info">
                            <h4><?php echo h($product['name']); ?></h4>
                            <p>Sold: <?php echo number_format($product['total_sold']); ?> items</p>
                        </div>
                        <div class="product-revenue">
                            <?php echo formatCurrency($product['total_revenue']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>