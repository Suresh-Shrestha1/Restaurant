<?php
$pageTitle = "Add Product";
require_once 'includes/header.php';

// Get categories
$categories = getCategories($pdo);

$errors = [];

if ($_POST && isset($_POST['add_product'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }
    
    $name = trim($_POST['name'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    
    // Validation
    if (empty($name)) {
        $errors[] = "Product name is required";
    }
    if ($category_id <= 0) {
        $errors[] = "Please select a category";
    }
    if ($price <= 0) {
        $errors[] = "Price must be greater than 0";
    }
    
    // Handle image upload
    $imageName = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imageName = uploadImage($_FILES['image'], '../uploads/');
        if ($imageName === false) {
            $errors[] = "Invalid image file. Please upload a valid image (JPG, PNG, GIF) under 5MB.";
        }
    }
    
    // Create product
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO products (category_id, name, description, price, image) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$category_id, $name, $description, $price, $imageName]);
            
            header('Location: products.php?success=Product added successfully');
            exit;
        } catch (Exception $e) {
            $errors[] = "Error creating product. Please try again.";
            
            // Delete uploaded image if database insert failed
            if ($imageName && file_exists('../uploads/' . $imageName)) {
                unlink('../uploads/' . $imageName);
            }
        }
    }
}
?>

<div class="page-actions">
    <a href="products.php" class="btn btn-outline">
        <i class="fas fa-arrow-left"></i> Back to Products
    </a>
</div>

<div class="admin-card">
    <h3>Add New Product</h3>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo h($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
        <div class="form-row">
            <div class="form-group">
                <label for="name">Product Name *</label>
                <input type="text" id="name" name="name" required maxlength="255" value="<?php echo h($_POST['name'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="category_id">Category *</label>
                <select id="category_id" name="category_id" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo h($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4"><?php echo h($_POST['description'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="price">Price *</label>
                <input type="number" id="price" name="price" required step="0.01" min="0" value="<?php echo h($_POST['price'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="image">Product Image</label>
                <input type="file" id="image" name="image" accept="image/*">
                <small>Upload JPG, PNG, or GIF. Max 5MB.</small>
            </div>
        </div>
        
        <div class="form-actions">
            <a href="products.php" class="btn btn-outline">Cancel</a>
            <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>