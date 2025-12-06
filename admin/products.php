<?php
$pageTitle = "Products";
require 'includes/header.php';

// Handle delete action (soft delete)
if (isset($_GET['delete'])) {
    $productId = (int)$_GET['delete'];
    
    try {
        $stmt = $pdo->prepare("SELECT id, name FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        
        if ($product) {
            $stmt = $pdo->prepare("UPDATE products SET is_active = 0 WHERE id = ?");
            $stmt->execute([$productId]);
            
            header('Location: products.php?success=Product "' . urlencode($product['name']) . '" has been deactivated');
            exit;
        } else {
            $error = "Product not found.";
        }
    } catch (Exception $e) {
        $error = "Error deactivating product: " . $e->getMessage();
    }
}

// Handle restore action
if (isset($_GET['restore'])) {
    $productId = (int)$_GET['restore'];
    
    try {
        $stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        
        $stmt = $pdo->prepare("UPDATE products SET is_active = 1 WHERE id = ?");
        $stmt->execute([$productId]);
        
        header('Location: products.php?show_inactive=1&success=Product "' . urlencode($product['name']) . '" restored successfully');
        exit;
    } catch (Exception $e) {
        $error = "Error restoring product: " . $e->getMessage();
    }
}

// Handle permanent delete action - NOW WORKS WITH ORDER HISTORY
if (isset($_GET['permanent_delete'])) {
    $productId = (int)$_GET['permanent_delete'];
    
    try {
        // Get product details before deleting
        $stmt = $pdo->prepare("SELECT name, image FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        
        if (!$product) {
            $error = "Product not found.";
        } else {
            // Save product info to order_items before deleting
            // This preserves order history!
            $stmt = $pdo->prepare("
                UPDATE order_items 
                SET product_name = ?,
                    product_image = ?
                WHERE product_id = ?
            ");
            $stmt->execute([$product['name'], $product['image'], $productId]);
            
            // Now delete the product (foreign key will SET NULL)
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            
            // Delete image file from server
            if ($product['image'] && file_exists('../uploads/' . $product['image'])) {
                unlink('../uploads/' . $product['image']);
            }
            
            header('Location: products.php?show_inactive=1&success=Product "' . urlencode($product['name']) . '" permanently deleted. Order history preserved.');
            exit;
        }
    } catch (Exception $e) {
        $error = "Error deleting product: " . $e->getMessage();
    }
}

// Get filter
$showInactive = isset($_GET['show_inactive']) && $_GET['show_inactive'] == '1';

// Get all products with categories
if ($showInactive) {
    $stmt = $pdo->query("
        SELECT p.*, c.name as category_name 
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        ORDER BY p.is_active DESC, p.name
    ");
} else {
    $stmt = $pdo->query("
        SELECT p.*, c.name as category_name 
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        WHERE p.is_active = 1
        ORDER BY p.name
    ");
}
$products = $stmt->fetchAll();

// Get counts
$stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1");
$activeCount = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 0");
$inactiveCount = $stmt->fetchColumn();
?>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <?php echo h($_GET['success']); ?>
    </div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-error">
        <?php echo h($error); ?>
    </div>
<?php endif; ?>

<div class="page-actions">
    <a href="add-product.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add New Product
    </a>
    
    <div class="filter-buttons">
        <a href="products.php" class="btn <?php echo !$showInactive ? 'btn-primary' : 'btn-outline'; ?>">
            <i class="fas fa-check-circle"></i> Active (<?php echo $activeCount; ?>)
        </a>
        <a href="products.php?show_inactive=1" class="btn <?php echo $showInactive ? 'btn-primary' : 'btn-outline'; ?>">
            <i class="fas fa-list"></i> All Products (<?php echo $activeCount + $inactiveCount; ?>)
        </a>
    </div>
</div>

<div class="admin-card">
    <h3><?php echo $showInactive ? 'All Products' : 'Active Products'; ?></h3>
    
    <?php if (empty($products)): ?>
        <p>No products found. <a href="add-product.php">Add your first product</a>.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr class="<?php echo !$product['is_active'] ? 'inactive-row' : ''; ?>">
                            <td>
                                <div class="product-image-small">
                                    <?php if ($product['image']): ?>
                                        <img src="../uploads/<?php echo h($product['image']); ?>" 
                                             alt="<?php echo h($product['name']); ?>"
                                             class="<?php echo !$product['is_active'] ? 'img-inactive' : ''; ?>">
                                    <?php else: ?>
                                        <div class="no-image">
                                            <i class="fas fa-utensils"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <strong><?php echo h($product['name']); ?></strong>
                                <?php if (!$product['is_active']): ?>
                                    <span class="badge-inactive">Inactive</span>
                                <?php endif; ?>
                                <br>
                                <small class="text-muted"><?php echo h(substr($product['description'], 0, 50)); ?>...</small>
                            </td>
                            <td><?php echo h($product['category_name']); ?></td>
                            <td><?php echo formatCurrency($product['price']); ?></td>
                            <td>
                                <?php if ($product['is_active']): ?>
                                    <span class="status-badge status-active">
                                        <i class="fas fa-check"></i> Active
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge status-inactive">
                                        <i class="fas fa-times"></i> Inactive
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($product['created_at'])); ?></td>
                            <td class="actions">
                                <a href="edit-product.php?id=<?php echo $product['id']; ?>" 
                                   class="btn btn-sm btn-outline" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                
                                <?php if ($product['is_active']): ?>
                                    <a href="products.php?delete=<?php echo $product['id']; ?>" 
                                       class="btn btn-sm btn-warning" 
                                       title="Deactivate Product"
                                       onclick="return confirm('Deactivate this product?\n\nIt will be hidden from the menu.')">
                                        <i class="fas fa-eye-slash"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="products.php?restore=<?php echo $product['id']; ?>" 
                                       class="btn btn-sm btn-success" 
                                       title="Restore Product">
                                        <i class="fas fa-undo"></i>
                                    </a>
                                    <a href="products.php?permanent_delete=<?php echo $product['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       title="Delete Permanently"
                                       onclick="return confirm('⚠️ PERMANENTLY DELETE this product?\n\nThe product will be removed but order history will be preserved.\n\nThis action cannot be undone!')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<style>
.page-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 10px;
}

.filter-buttons {
    display: flex;
    gap: 10px;
}

.inactive-row {
    background-color: #f8f9fa !important;
}

.inactive-row td {
    color: #6c757d;
}

.img-inactive {
    opacity: 0.5;
    filter: grayscale(50%);
}

.badge-inactive {
    display: inline-block;
    background-color: #dc3545;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 10px;
    margin-left: 8px;
    vertical-align: middle;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.status-active {
    background-color: #d4edda;
    color: #155724;
}

.status-inactive {
    background-color: #f8d7da;
    color: #721c24;
}

.btn-warning {
    background-color: #ffc107;
    color: #212529;
    border: none;
}

.btn-warning:hover {
    background-color: #e0a800;
}

.btn-success {
    background-color: #28a745;
    color: white;
    border: none;
}

.btn-success:hover {
    background-color: #218838;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
    border: none;
}

.btn-danger:hover {
    background-color: #c82333;
}

.text-muted {
    color: #6c757d;
}

.actions {
    white-space: nowrap;
}

.actions .btn {
    margin: 2px;
}
</style>

<?php require_once 'includes/footer.php'; ?>