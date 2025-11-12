<?php
$pageTitle = "Add Category";
require_once 'includes/header.php';

$errors = [];
$success = false;

if ($_POST && isset($_POST['add_category'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }
    
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    // Validation
    if (empty($name)) {
        $errors[] = "Category name is required";
    }
    
    // Check if category name already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetch()) {
            $errors[] = "Category name already exists";
        }
    }
    
    // Create category
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->execute([$name, $description]);
            
            header('Location: categories.php?success=Category added successfully');
            exit;
        } catch (Exception $e) {
            $errors[] = "Error creating category. Please try again.";
        }
    }
}
?>

<div class="page-actions">
    <a href="categories.php" class="btn btn-outline">
        <i class="fas fa-arrow-left"></i> Back to Categories
    </a>
</div>

<div class="admin-card">
    <h3>Add New Category</h3>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo h($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form method="POST" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
        <div class="form-group">
            <label for="name">Category Name *</label>
            <input type="text" id="name" name="name" required maxlength="255" value="<?php echo h($_POST['name'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3"><?php echo h($_POST['description'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-actions">
            <a href="categories.php" class="btn btn-outline">Cancel</a>
            <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>