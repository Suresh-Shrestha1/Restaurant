<?php
// includes/functions.php - Enhanced helper functions

/**
 * HTML output escaping to prevent XSS
 */
function h(?string $string): string {
    return $string !== null ? htmlspecialchars($string, ENT_QUOTES, 'UTF-8') : '';
}

/**
 * Sanitize input data
 */
function sanitize(string $input): string {
    return trim(strip_tags($input));
}

/**
 * Validate email format
 */
function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Format currency display
 */
function formatCurrency(float $amount): string {
    return 'Rs. ' . number_format($amount, 2);
}

/**
 * Authentication helpers
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdminLoggedIn(): bool {
    return isset($_SESSION['admin_id']) && 
           !empty($_SESSION['admin_id']) &&
           isset($_SESSION['admin_role']) &&
           in_array($_SESSION['admin_role'], ['Super Admin', 'Manager', 'Staff']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function requireAdminLogin(): void {
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Password validation
 */
function validatePassword(string $password): array {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    return $errors;
}

/**
 * File upload handler with enhanced security
 */
function uploadImage(array $file, string $uploadDir): string|false {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    // Validate file
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return false;
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        return false;
    }
    
    // Verify MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return false;
    }
    
    // Verify image dimensions (additional security)
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return false;
    }
    
    // Generate unique filename
    $extension = match($mimeType) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        default => 'jpg'
    };
    
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $destination = $uploadDir . $filename;
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return $filename;
    }
    
    return false;
}

/**
 * Database query helpers
 */
function getCategories(PDO $pdo): array {
    try {
        $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching categories: " . $e->getMessage());
        return [];
    }
}

function getProductsByCategory(PDO $pdo, ?int $categoryId = null): array {
    try {
        if ($categoryId) {
            $stmt = $pdo->prepare("
                SELECT p.*, c.name as category_name 
                FROM products p 
                JOIN categories c ON p.category_id = c.id 
                WHERE p.category_id = ? 
                ORDER BY p.name ASC
            ");
            $stmt->execute([$categoryId]);
        } else {
            $stmt = $pdo->query("
                SELECT p.*, c.name as category_name 
                FROM products p 
                JOIN categories c ON p.category_id = c.id 
                ORDER BY p.name ASC
            ");
        }
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching products: " . $e->getMessage());
        return [];
    }
}

function searchProducts(PDO $pdo, string $query): array {
    try {
        $searchTerm = '%' . $query . '%';
        $stmt = $pdo->prepare("
            SELECT p.*, c.name as category_name 
            FROM products p 
            JOIN categories c ON p.category_id = c.id 
            WHERE p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ?
            ORDER BY p.name ASC
        ");
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error searching products: " . $e->getMessage());
        return [];
    }
}

function getCartTotal(PDO $pdo): float {
    $total = 0;
    
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        try {
            $productIds = array_keys($_SESSION['cart']);
            $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
            $stmt = $pdo->prepare("SELECT id, price FROM products WHERE id IN ($placeholders)");
            $stmt->execute($productIds);
            $products = $stmt->fetchAll();
            
            foreach ($products as $product) {
                $quantity = $_SESSION['cart'][$product['id']] ?? 0;
                $total += $product['price'] * $quantity;
            }
        } catch (PDOException $e) {
            error_log("Error calculating cart total: " . $e->getMessage());
        }
    }
    
    return $total;
}

/**
 * Input validation helpers
 */
function validateRequired(array $fields, array $data): array {
    $errors = [];
    foreach ($fields as $field => $label) {
        if (empty($data[$field] ?? '')) {
            $errors[] = "$label is required";
        }
    }
    return $errors;
}

function validateLength(string $value, int $min, int $max, string $fieldName): ?string {
    $length = strlen($value);
    if ($length < $min) {
        return "$fieldName must be at least $min characters long";
    }
    if ($length > $max) {
        return "$fieldName must be no more than $max characters long";
    }
    return null;
}

/**
 * Error logging and handling
 */
function logError(string $message, array $context = []): void {
    $logMessage = $message;
    if (!empty($context)) {
        $logMessage .= ' | Context: ' . json_encode($context);
    }
    error_log($logMessage);
}

/**
 * Rate limiting for login attempts
 */
function checkRateLimit(string $identifier, int $maxAttempts = 5, int $timeWindow = 3600): bool {
    $key = "rate_limit_" . md5($identifier);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
    }
    
    $data = $_SESSION[$key];
    
    // Reset if time window has passed
    if (time() - $data['first_attempt'] > $timeWindow) {
        $_SESSION[$key] = ['count' => 1, 'first_attempt' => time()];
        return true;
    }
    
    // Check if limit exceeded
    if ($data['count'] >= $maxAttempts) {
        return false;
    }
    
    $_SESSION[$key]['count']++;
    return true;
}
/**
 * Redirect with message - handles headers already sent scenario
 */
function redirect(string $location, ?string $message = null, string $type = 'success'): void {
    if ($message) {
        $_SESSION['flash_message'] = ['text' => $message, 'type' => $type];
    }
    
    // Check if headers have been sent
    if (!headers_sent()) {
        header("Location: $location");
        exit;
    } else {
        // Fallback using JavaScript if headers already sent
        echo "<script>window.location.href = '$location';</script>";
        exit;
    }
}

/**
 * Display flash message
 */
function getFlashMessage(): ?array {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Generate secure random string
 */
function generateSecureToken(int $length = 32): string {
    return bin2hex(random_bytes($length));
}

