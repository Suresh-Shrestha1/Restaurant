<?php
$pageTitle = "Menu";
require_once 'includes/header.php';

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get categories
$categories = getCategories($pdo);

// Get selected category
$selectedCategory = isset($_GET['category']) ? (int)$_GET['category'] : null;

// Get search query
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get products
if ($searchQuery) {
    $products = searchProducts($pdo, $searchQuery);
} else {
    $products = getProductsByCategory($pdo, $selectedCategory);
}
?>

<div class="page-header">
    <div class="container">
        <h1>Our Menu</h1>
        <p>Discover our delicious selection of freshly prepared dishes</p>
    </div>
</div>

<section class="menu-section">
    <div class="container">
        <div class="menu-filters">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Search menu items..." value="<?php echo h($searchQuery); ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
            
            <div class="category-filters">
                <a href="menu.php" class="filter-btn <?php echo !$selectedCategory ? 'active' : ''; ?>">All</a>
                <?php foreach ($categories as $category): ?>
                    <a href="menu.php?category=<?php echo $category['id']; ?>" 
                       class="filter-btn <?php echo $selectedCategory == $category['id'] ? 'active' : ''; ?>">
                        <?php echo h($category['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (empty($products)): ?>
            <div class="no-products">
                <i class="fas fa-search"></i>
                <h3>No items found</h3>
                <p>Try adjusting your search or browse all categories.</p>
                <a href="menu.php" class="btn btn-primary">View All Items</a>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <?php if ($product['image']): ?>
                                <img src="uploads/<?php echo h($product['image']); ?>" alt="<?php echo h($product['name']); ?>">
                            <?php else: ?>
                                <div class="no-image">
                                    <i class="fas fa-utensils"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <h3><?php echo h($product['name']); ?></h3>
                            <p class="product-category"><?php echo h($product['category_name']); ?></p>
                            <p class="product-description"><?php echo h($product['description']); ?></p>
                            <div class="product-footer">
                                <span class="price"><?php echo formatCurrency($product['price']); ?></span>
                                <!-- Use a form instead of just a button -->
                                <form method="POST" action="cart.php" class="add-to-cart-form" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="btn btn-sm add-to-cart">
                                        <i class="fas fa-plus"></i> Add to Cart
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>