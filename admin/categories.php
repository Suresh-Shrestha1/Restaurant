<?php
$pageTitle = "Categories";
require 'includes/header.php';

// Handle delete action
if (isset($_GET['delete'])) {
    $categoryId = (int)$_GET['delete'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$categoryId]);
        header('Location: categories.php?success=Category deleted successfully');
        exit;
    } catch (Exception $e) {
        $error = "Cannot delete category. It may have products associated with it.";
    }
}

// Get all categories
$stmt = $pdo->query("
    SELECT c.*, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id 
    GROUP BY c.id 
    ORDER BY c.name
");
$categories = $stmt->fetchAll();
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
    <a href="add-category.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add New Category
    </a>
</div>

<div class="admin-card">
    <h3>All Categories</h3>
    
    <?php if (empty($categories)): ?>
        <p>No categories found. <a href="add-category.php">Add your first category</a>.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Products</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?php echo $category['id']; ?></td>
                            <td><strong><?php echo h($category['name']); ?></strong></td>
                            <td><?php echo h($category['description']); ?></td>
                            <td><?php echo number_format($category['product_count']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($category['created_at'])); ?></td>
                            <td class="actions">
                                <a href="edit-category.php?id=<?php echo $category['id']; ?>" class="btn btn-sm btn-outline" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($category['product_count'] == 0): ?>
                                    <a href="categories.php?delete=<?php echo $category['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       title="Delete"
                                       onclick="return confirm('Are you sure you want to delete this category?')">
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

<?php require_once 'includes/footer.php'; ?>