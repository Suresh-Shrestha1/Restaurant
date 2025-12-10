<?php
// admin/orders.php - Enhanced orders management
$pageTitle = "Orders Management";
require 'includes/header.php';

// Handle order status updates
if ($_POST && isset($_POST['update_status'])) {
    CSRFProtection::validateRequest();
    
    $orderId = (int)$_POST['order_id'];
    $newStatus = $_POST['status'];
    
    $validStatuses = ['Pending', 'Confirmed', 'Preparing', 'Out for Delivery', 'Delivered', 'Cancelled'];
    
    if (in_array($newStatus, $validStatuses)) {
        try {
            $pdo->beginTransaction();
            
            // Update order status
            $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newStatus, $orderId]);
            
            // Add to status history
            $stmt = $pdo->prepare("
                INSERT INTO order_status_history (order_id, status, changed_by_admin_id) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$orderId, $newStatus, $_SESSION['admin_id']]);
            
            // Set delivery timestamp if delivered
            if ($newStatus === 'Delivered') {
                $stmt = $pdo->prepare("UPDATE orders SET delivered_at = NOW() WHERE id = ?");
                $stmt->execute([$orderId]);
            }
            
            $pdo->commit();
            redirect('orders.php', 'Order status updated successfully');
        } catch (Exception $e) {
            $pdo->rollback();
            logError('Failed to update order status: ' . $e->getMessage(), ['order_id' => $orderId]);
            redirect('orders.php', 'Failed to update order status', 'error');
        }
    }
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? '';
$dateFilter = $_GET['date'] ?? '';
$searchQuery = sanitize($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build query conditions
$conditions = [];
$params = [];

if (!empty($statusFilter)) {
    $conditions[] = "o.status = ?";
    $params[] = $statusFilter;
}

if (!empty($dateFilter)) {
    $conditions[] = "DATE(o.created_at) = ?";
    $params[] = $dateFilter;
}

if (!empty($searchQuery)) {
    $conditions[] = "(o.order_number LIKE ? OR o.name LIKE ? OR o.phone LIKE ? OR o.email LIKE ?)";
    $searchTerm = '%' . $searchQuery . '%';
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

$whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Get total count for pagination
$countQuery = "
    SELECT COUNT(*) as total 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    $whereClause
";
$stmt = $pdo->prepare($countQuery);
$stmt->execute($params);
$totalOrders = $stmt->fetch()['total'];
$totalPages = ceil($totalOrders / $perPage);

// Get orders with pagination
$query = "
    SELECT o.*, u.email as user_email,
           COUNT(oi.id) as item_count,
           GROUP_CONCAT(COALESCE(oi.product_name, p.name, 'Deleted Product') SEPARATOR ', ') as product_names
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.id
    $whereClause
    GROUP BY o.id
    ORDER BY o.created_at DESC 
    LIMIT ? OFFSET ?
";

$stmt = $pdo->prepare($query);
$stmt->execute([...$params, $perPage, $offset]);
$orders = $stmt->fetchAll();

// Get order statistics
$statsQuery = "
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN status = 'Preparing' THEN 1 ELSE 0 END) as preparing_orders,
        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_orders,
        SUM(CASE WHEN status = 'Delivered' THEN total ELSE 0 END) as total_revenue
    FROM orders
";
$stats = $pdo->query($statsQuery)->fetch();
?>

<div class="orders-header">
    <h2>Orders Management</h2>
    
    <!-- Order Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon pending">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($stats['pending_orders']); ?></h3>
                <p>Pending Orders</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon preparing">
                <i class="fas fa-fire"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($stats['preparing_orders']); ?></h3>
                <p>Being Prepared</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon today">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($stats['today_orders']); ?></h3>
                <p>Today's Orders</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon revenue">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo formatCurrency($stats['total_revenue'] ?? 0); ?></h3>
                <p>Total Revenue</p>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="orders-filters">
    <form method="GET" class="filter-form">
        <div class="filter-group">
            <label for="status">Status:</label>
            <select name="status" id="status">
                <option value="">All Statuses</option>
                <option value="Pending" <?php echo $statusFilter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="Confirmed" <?php echo $statusFilter === 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                <option value="Preparing" <?php echo $statusFilter === 'Preparing' ? 'selected' : ''; ?>>Preparing</option>
                <option value="Out for Delivery" <?php echo $statusFilter === 'Out for Delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                <option value="Delivered" <?php echo $statusFilter === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                <option value="Cancelled" <?php echo $statusFilter === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="date">Date:</label>
            <input type="date" name="date" id="date" value="<?php echo h($dateFilter); ?>">
        </div>
        
        <div class="filter-group">
            <label for="search">Search:</label>
            <input type="text" name="search" id="search" placeholder="Order number, name, phone..." value="<?php echo h($searchQuery); ?>">
        </div>
        
        <div class="filter-buttons">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="orders.php" class="btn btn-outline">Clear</a>
        </div>
    </form>
</div>

<!-- Orders Table -->
<div class="admin-card">
    <div class="card-header">
        <h3>Orders (<?php echo number_format($totalOrders); ?> total)</h3>
        <div class="card-actions">
            <button onclick="refreshOrders()" class="btn btn-sm btn-outline" title="Refresh">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    </div>
    
    <?php if (empty($orders)): ?>
        <div class="no-data">
            <i class="fas fa-inbox"></i>
            <h3>No orders found</h3>
            <p>No orders match your current filters.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr class="order-row" data-order-id="<?php echo $order['id']; ?>">
                            <td>
                                <strong><?php echo h($order['order_number']); ?></strong>
                            </td>
                            <td>
                                <div class="customer-info">
                                    <strong><?php echo h($order['name']); ?></strong>
                                    <small><?php echo h($order['phone']); ?></small>
                                    <?php if ($order['user_email']): ?>
                                        <small><?php echo h($order['user_email']); ?></small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="item-count"><?php echo $order['item_count']; ?> items</span>
                                <small class="item-preview" title="<?php echo h($order['product_names']); ?>">
                                    <?php echo h(substr($order['product_names'], 0, 30)); ?>...
                                </small>
                            </td>
                            <td>
                                <strong><?php echo formatCurrency($order['total']); ?></strong>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $order['status'])); ?>">
                                    <?php echo h($order['status']); ?>
                                </span>
                            </td>
                            <td>
                                <time datetime="<?php echo $order['created_at']; ?>">
                                    <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                    <small><?php echo date('g:i A', strtotime($order['created_at'])); ?></small>
                                </time>
                            </td>
                            <td class="actions">
                                <button onclick="viewOrder(<?php echo $order['id']; ?>)" 
                                        class="btn btn-sm btn-outline" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="updateStatus(<?php echo $order['id']; ?>, '<?php echo h($order['status']); ?>')" 
                                        class="btn btn-sm btn-primary" title="Update Status">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                       class="btn btn-outline">Previous</a>
                <?php endif; ?>
                
                <span class="page-info">
                    Page <?php echo $page; ?> of <?php echo $totalPages; ?>
                </span>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                       class="btn btn-outline">Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Order Details Modal -->
<div id="orderModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Order Details</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div id="orderDetailsContent">
            <!-- Content loaded via AJAX -->
        </div>
    </div>
</div>

<!-- Status Update Modal -->
<div id="statusModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Update Order Status</h3>
            <button class="modal-close" onclick="closeStatusModal()">&times;</button>
        </div>
        <form method="POST" id="statusForm">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="update_status" value="1">
            <input type="hidden" name="order_id" id="statusOrderId">
            
            <div class="form-group">
                <label for="statusSelect">New Status:</label>
                <select name="status" id="statusSelect" required>
                    <option value="Pending">Pending</option>
                    <option value="Confirmed">Confirmed</option>
                    <option value="Preparing">Preparing</option>
                    <option value="Out for Delivery">Out for Delivery</option>
                    <option value="Delivered">Delivered</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="button" onclick="closeStatusModal()" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Status</button>
            </div>
        </form>
    </div>
</div>

<style>
.orders-header {
    margin-bottom: 2rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin: 1rem 0;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
}

.stat-icon.pending { background: #ffc107; }
.stat-icon.preparing { background: #fd7e14; }
.stat-icon.today { background: #20c997; }
.stat-icon.revenue { background: #28a745; }

.orders-filters {
    background: #ffffff;
    padding: 1.75rem;
    border-radius: 10px;
    margin-bottom: 2rem;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
    border: 1px solid #f1f3f5;
}

.filter-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1.25rem;
    align-items: end;
}

.filter-group label {
    display: block;
    margin-bottom: 0.4rem;
    font-weight: 600;
    color: #343a40;
    font-size: 0.9rem;
}

.filter-group select,
.filter-group input[type="date"],
.filter-group input[type="text"] {
    width: 100%;
    padding: 0.6rem 0.75rem;
    border: 1px solid #ced4da;
    border-radius: 6px;
    background-color: #fff;
    color: #495057;
    font-size: 0.95rem;
    transition: all 0.2s ease;
    outline: none;
}

.filter-group select:focus,
.filter-group input:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.15);
}

.filter-group select:hover,
.filter-group input:hover {
    border-color: #adb5bd;
}

.filter-form .btn {
    padding: 0.65rem 1.25rem;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid transparent;
}

.filter-form .btn-primary {
    color: white;
    width: 200px;
}

.filter-form .btn-primary:hover {
    background-color: #0069d9;
}

.filter-form .btn-outline {
    background-color: white;
    border: 1px solid #ced4da;
    color: #495057;
}

.filter-form .btn-outline:hover {
    background-color: #f8f9fa;
    border-color: #adb5bd;
}

.filter-form .btn,
.filter-form .btn-outline {
    align-self: center;
    height: fit-content;
}

.filter-form input::placeholder {
    color: #adb5bd;
}

.orders-table {
    width: 100%;
    border-collapse: collapse;
}

.orders-table th,
.orders-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #e9ecef;
}

.orders-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #495057;
}

.customer-info {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}

.customer-info small {
    color: #6c757d;
}

.item-count {
    font-weight: 500;
    color: #495057;
}

.item-preview {
    display: block;
    color: #6c757d;
    font-size: 0.875rem;
}

/* ====== Modal Styles ====== */
.modal {
    display: none;
    position: fixed;
    z-index: 2000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(44, 62, 80, 0.7);
    animation: fadeIn 0.3s ease;
}

.modal-content {
    background-color: #fff;
    margin: 5% auto;
    padding: 0;
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    width: 100%;
    max-width: 1000px;
    animation: slideIn 0.3s ease;
    overflow: hidden;
}

.modal-header {
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
    color: white;
    padding: 1rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.3rem;
    font-weight: 600;
}

.modal-close {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    transition: transform 0.2s ease;
}

.modal-close:hover {
    transform: scale(1.2);
}

/* Form Styling Inside Modal */
#statusForm {
    padding: 1.5rem;
}

#statusForm .form-group {
    margin-bottom: 1.5rem;
}

#statusForm label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #2c3e50;
}

#statusForm select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
}

#statusForm select:focus {
    border-color: #e74c3c;
    outline: none;
}

/* Form Actions (Buttons) */
#statusForm .form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e9ecef;
}

.btn {
    padding: 10px 20px;
    border-radius: 6px;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
}

.btn-primary {
    background-color: #e74c3c;
    color: white;
}

.btn-primary:hover {
    background-color: #c0392b;
}

.btn-outline {
    background-color: transparent;
    color: #333;
    border: 1px solid #ccc;
}

.btn-outline:hover {
    background-color: #f8f9fa;
}

/* Animations */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-40px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
</style>

<script>
function viewOrder(orderId) {
    fetch(`order-details.php?id=${orderId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('orderDetailsContent').innerHTML = html;
            document.getElementById('orderModal').style.display = 'block';
        })
        .catch(error => {
            console.error('Error loading order details:', error);
            alert('Failed to load order details');
        });
}

function updateStatus(orderId, currentStatus) {
    document.getElementById('statusOrderId').value = orderId;
    document.getElementById('statusSelect').value = currentStatus;
    document.getElementById('statusModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('orderModal').style.display = 'none';
}

function closeStatusModal() {
    document.getElementById('statusModal').style.display = 'none';
}

function refreshOrders() {
    location.reload();
}

window.onclick = function(event) {
    const orderModal = document.getElementById('orderModal');
    const statusModal = document.getElementById('statusModal');
    
    if (event.target === orderModal) {
        closeModal();
    }
    if (event.target === statusModal) {
        closeStatusModal();
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>