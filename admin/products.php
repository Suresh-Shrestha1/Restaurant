<?php
$pageTitle = "Products";
require 'includes/header.php';

// Handle delete action
if (isset($_GET['delete'])) {
    $productId = (int)$_GET['delete'];
    
    try {
        // Get product image to delete
        $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        
        // Delete product
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        
        // Delete image file
        if ($product && $product['image'] && file_exists('../uploads/' . $product['image'])) {
            unlink('../uploads/' . $product['image']);
        }
        
        header('Location: products.php?success=Product deleted successfully');
        exit;
    } catch (Exception $e) {
        $error = "Cannot delete product. It may be part of existing orders.";
    }
}

// Get all products with categories
$stmt = $pdo->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    ORDER BY p.name
");
$products = $stmt->fetchAll();
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
</div>

<div class="admin-card">
    <h3>All Products</h3>
    
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
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <div class="product-image-small">
                                    <?php if ($product['image']): ?>
                                        <img src="../uploads/<?php echo h($product['image']); ?>" alt="<?php echo h($product['name']); ?>">
                                    <?php else: ?>
                                        <div class="no-image">
                                            <i class="fas fa-utensils"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <strong><?php echo h($product['name']); ?></strong>
                                <br>
                                <small><?php echo h(substr($product['description'], 0, 50)); ?>...</small>
                            </td>
                            <td><?php echo h($product['category_name']); ?></td>
                            <td><?php echo formatCurrency($product['price']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($product['created_at'])); ?></td>
                            <td class="actions">
                                <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="products.php?delete=<?php echo $product['id']; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this product?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>