<?php 
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if required files exist before including
if (!file_exists('../config/database.php')) {
    die('Database configuration file not found');
}
require_once '../config/database.php';

if (!file_exists('../includes/functions.php')) {
    die('Functions file not found');
}
require_once '../includes/functions.php';

// Check if required functions exist
if (!function_exists('isAdminLoggedIn')) {
    die('isAdminLoggedIn function not defined');
}

if (!function_exists('requireAdminLogin')) {
    die('requireAdminLogin function not defined');
}

if (!function_exists('h')) {
    die('h function not defined (for HTML escaping)');
}

// Require admin login
requireAdminLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? h($pageTitle) . ' - ' : ''; ?>Admin Panel - Maharaja Restaurant</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="admin-body">
    <div class="admin-container">
        <nav class="admin-sidebar">
            <div class="sidebar-header">
                <h3><img src="../uploads/Maharaja_Restaurant.png" alt="Maharaja Restaurant Logo" style="height: 80px; width: auto;">
                 Maharaja Restaurant</h3>
                <p>Admin Panel</p>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-dashboard"></i> Dashboard
                </a></li>
                <li><a href="categories.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i> Categories
                </a></li>
                <li><a href="products.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">
                    <i class="fas fa-utensils"></i> Products
                </a></li>
                <li><a href="orders.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-cart"></i> Orders
                </a></li>
                <li><a href="../index.php" target="_blank">
                    <i class="fas fa-external-link-alt"></i> View Website
                </a></li>
                <li><a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a></li>
            </ul>
        </nav>
        
        <main class="admin-main">
            <header class="admin-header">
                <h1><?php echo isset($pageTitle) ? h($pageTitle) : 'Admin Panel'; ?></h1>
                <div class="admin-user">
                    Welcome, <?php echo h($_SESSION['admin_username']); ?>
                </div>
            </header>
            
            <div class="admin-content">