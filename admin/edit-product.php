<?php
$pageTitle = "Edit Product";
require_once 'includes/header.php';

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get product data
$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ?
");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: products.php');
    exit;
}

// Get categories
$categories = getCategories($pdo);

$errors = [];

if ($_POST && isset($_POST['edit_product'])) {
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
    $imageName = $product['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $newImageName = uploadImage($_FILES['image'], '../uploads/');
        if ($newImageName === false) {
            $errors[] = "Invalid image file. Please upload a valid image (JPG, PNG, GIF) under 5MB.";
        } else {
            // Delete old image if it exists
            if ($imageName && file_exists('../uploads/' . $imageName)) {
                unlink('../uploads/' . $imageName);
            }
            $imageName = $newImageName;
        }
    }
    
    // Update product
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE products 
                SET category_id = ?, name = ?, description = ?, price = ?, image = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$category_id, $name, $description, $price, $imageName, $productId]);
            
            header('Location: products.php?success=Product updated successfully');
            exit;
        } catch (Exception $e) {
            $errors[] = "Error updating product. Please try again.";
            
            // Delete uploaded image if database update failed
            if (isset($newImageName) && $newImageName && file_exists('../uploads/' . $newImageName)) {
                unlink('../uploads/' . $newImageName);
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
    <h3>Edit Product: <?php echo h($product['name']); ?></h3>
    
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
                <input type="text" id="name" name="name" required maxlength="255" 
                       value="<?php echo h($_POST['name'] ?? $product['name']); ?>">
            </div>
            
            <div class="form-group">
                <label for="category_id">Category *</label>
                <select id="category_id" name="category_id" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" 
                            <?php echo ($category['id'] == ($_POST['category_id'] ?? $product['category_id'])) ? 'selected' : ''; ?>>
                            <?php echo h($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4"><?php echo h($_POST['description'] ?? $product['description']); ?></textarea>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="price">Price *</label>
                <input type="number" id="price" name="price" required step="0.01" min="0" 
                       value="<?php echo h($_POST['price'] ?? $product['price']); ?>">
            </div>
            
            <div class="form-group">
                <label for="image">Product Image</label>
                
                <?php if ($product['image']): ?>
                    <div class="current-image-preview">
                        <img src="../uploads/<?php echo h($product['image']); ?>" 
                             alt="Current product image" 
                             style="max-width: 100px; height: auto; display: block; margin-bottom: 10px;">
                        <small>Current image</small>
                    </div>
                <?php endif; ?>
                
                <input type="file" id="image" name="image" accept="image/*">
                <small>Upload JPG, PNG, or GIF. Max 5MB. Leave empty to keep current image.</small>
            </div>
        </div>
        
        <div class="form-actions">
            <a href="products.php" class="btn btn-outline">Cancel</a>
            <button type="submit" name="edit_product" class="btn btn-primary">Update Product</button>
        </div>
    </form>
</div>

<style>
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.current-image-preview {
    margin-bottom: 10px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
    border: 1px solid #e9ecef;
}

.current-image-preview img {
    border-radius: 4px;
}

.current-image-preview small {
    color: #6c757d;
    font-size: 0.875rem;
}

.form-help {
    color: #6c757d;
    font-size: 0.875rem;
    margin-top: 0.25rem;
    display: block;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>