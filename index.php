<?php
$pageTitle = "Home";
require_once 'includes/header.php';

// Get featured products
$stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY RAND() LIMIT 6");
$featuredProducts = $stmt->fetchAll();
?>

<section class="hero">
    <div class="hero-content">
        <h1>Welcome to Agan Cafe</h1>
        <p>Experience the finest dining with our carefully crafted menu featuring fresh ingredients and bold flavors.</p>
        <a href="menu.php" class="btn btn-primary">View Our Menu</a>
    </div>
    <div class="hero-image">
        <img src="https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80" alt="Restaurant Interior">
    </div>
</section>

<section class="featured-menu">
    <div class="container">
        <h2 class="section-title">Featured Items</h2>
        <div class="products-grid">
            <?php foreach ($featuredProducts as $product): ?>
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
                            <!-- Replace button with form -->
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
        
        <div class="text-center">
            <a href="menu.php" class="btn btn-outline">View Full Menu</a>
        </div>
    </div>
</section>

<section class="about">
    <div class="container">
        <div class="about-content">
            <div class="about-text">
                <h2>About Agan Cafe</h2>
                <p>Since 2020, Agan Cafe has been serving the community with exceptional food and warm hospitality. Our passion for culinary excellence drives us to create memorable dining experiences for every guest.</p>
                <p>We believe in using only the freshest ingredients, supporting local suppliers, and preparing every dish with care and attention to detail.</p>
                <div class="features">
                    <div class="feature">
                        <i class="fas fa-leaf"></i>
                        <h3>Fresh Ingredients</h3>
                        <p>We source the freshest ingredients from local suppliers daily.</p>
                    </div>
                    <div class="feature">
                        <i class="fas fa-heart"></i>
                        <h3>Made with Love</h3>
                        <p>Every dish is prepared with passion and attention to detail.</p>
                    </div>
                    <div class="feature">
                        <i class="fas fa-clock"></i>
                        <h3>Quick Service</h3>
                        <p>Fast and efficient service without compromising quality.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>