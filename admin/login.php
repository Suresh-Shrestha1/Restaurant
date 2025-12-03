<?php
require_once '../config/database.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include functions
require_once '../includes/functions.php';

// Redirect if already logged in
if (isAdminLoggedIn()) {
    header('Location: index.php');
    exit;
}

$errors = [];

if ($_POST && isset($_POST['login'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username)) {
        $errors[] = "Username is required";
    }
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
       if ($admin && (password_verify($password, $admin['password']) || $password === $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            
            // Update last login
            $updateStmt = $pdo->prepare("UPDATE admin_users SET last_login_at = NOW() WHERE id = ?");
            $updateStmt->execute([$admin['id']]);
            
            header('Location: index.php');
            exit;
        } else {
            $errors[] = "Invalid username or password";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Maharaja Restaurant</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<style>
    /* ============================================
       CSS Reset & Base Styles
       ============================================ */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        line-height: 1.6;
        color: #333;
    }
    
    /* ============================================
       Auth Container & Card
       ============================================ */
    .auth-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 1rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        position: relative;
    }
    
    .auth-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" fill="none"/><circle cx="50" cy="50" r="1" fill="white" opacity="0.1"/></svg>');
        opacity: 0.3;
    }
    
    .auth-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        padding: 2.5rem;
        width: 100%;
        max-width: 420px;
        position: relative;
        z-index: 1;
        animation: slideUp 0.5s ease-out;
    }
    
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Admin Portal Badge */
    .auth-card::before {
        content: 'ADMIN';
        position: absolute;
        top: -12px;
        right: 30px;
        background: #ef4444;
        color: white;
        padding: 4px 16px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 1px;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
    }
    
    /* ============================================
       Typography
       ============================================ */
    .auth-card h2 {
        font-size: 1.875rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.5rem;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }
    
    .auth-card h2::before {
        content: '\f023';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        color: #3b82f6;
        font-size: 1.5rem;
    }
    
    .auth-card > p {
        color: #64748b;
        text-align: center;
        margin-bottom: 2rem;
        font-size: 1rem;
    }
    
    /* ============================================
       Alerts
       ============================================ */
    .alert {
        padding: 1rem 1.25rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        animation: slideDown 0.3s ease-out;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .alert-error {
        background: #fef2f2;
        border-left: 4px solid #ef4444;
        color: #991b1b;
    }
    
    .alert-error ul {
        margin: 0;
        padding-left: 0;
        list-style: none;
    }
    
    .alert-error li {
        position: relative;
        padding-left: 24px;
    }
    
    .alert-error li::before {
        content: "⚠";
        position: absolute;
        left: 0;
        color: #ef4444;
    }
    
    .alert-success {
        background: #f0fdf4;
        border-left: 4px solid #22c55e;
        color: #166534;
    }
    
    /* ============================================
       Form Styling
       ============================================ */
    .auth-form {
        margin-bottom: 1.5rem;
    }
    
    .form-group {
        margin-bottom: 1.25rem;
    }
    
    .form-group label {
        display: block;
        font-weight: 600;
        color: #334155;
        margin-bottom: 0.5rem;
        font-size: 0.95rem;
    }
    
    .form-group input[type="text"],
    .form-group input[type="password"] {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background: #f8fafc;
        color: #1e293b;
    }
    
    .form-group input:focus {
        outline: none;
        border-color: #3b82f6;
        background: white;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    }
    
    .form-group input::placeholder {
        color: #94a3b8;
    }
    
    /* Icon input styling */
    .form-group {
        position: relative;
    }
    
    .form-group label::before {
        content: '';
        position: absolute;
        right: 16px;
        top: 42px;
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        color: #94a3b8;
        pointer-events: none;
    }
    
    .form-group:nth-of-type(1) label::before {
        content: '\f007'; /* User icon */
    }
    
    /* ============================================
       Password Toggle
       ============================================ */
    .password-input-container {
        position: relative;
    }
    
    .password-input-container input {
        padding-right: 45px;
    }
    
    .toggle-password {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #64748b;
        cursor: pointer;
        padding: 8px;
        border-radius: 6px;
        transition: all 0.2s ease;
    }
    
    .toggle-password:hover {
        background: #f1f5f9;
        color: #3b82f6;
    }
    
    .toggle-password i {
        font-size: 1.1rem;
    }
    
    /* ============================================
       Buttons
       ============================================ */
    .btn {
        padding: 13px 24px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        text-decoration: none;
    }
    
    .btn-primary {
        background: #3b82f6;
        color: white;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        position: relative;
        overflow: hidden;
    }
    
    .btn-primary::before {
        content: '\f023';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        margin-right: 4px;
    }
    
    .btn-primary:hover:not(:disabled) {
        background: #2563eb;
        box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        transform: translateY(-2px);
    }
    
    .btn-primary:active {
        transform: translateY(0);
    }
    
    .btn-primary:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    .btn-full {
        width: 100%;
    }
    
    /* Loading state */
    .btn.loading {
        color: transparent;
        pointer-events: none;
    }
    
    .btn.loading::after {
        content: '';
        position: absolute;
        width: 16px;
        height: 16px;
        top: 50%;
        left: 50%;
        margin-left: -8px;
        margin-top: -8px;
        border: 2px solid #ffffff;
        border-radius: 50%;
        border-top-color: transparent;
        animation: spin 0.6s linear infinite;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    /* ============================================
       Auth Links
       ============================================ */
    .auth-links {
        text-align: center;
        padding-top: 1.5rem;
        border-top: 1px solid #e2e8f0;
    }
    
    .auth-links p {
        margin: 0.75rem 0;
        color: #64748b;
        font-size: 0.95rem;
    }
    
    .auth-links a {
        color: #3b82f6;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }
    
    .auth-links a:hover {
        color: #2563eb;
        text-decoration: underline;
    }
    
    /* ============================================
       Security Notice
       ============================================ */
    .security-notice {
        background: linear-gradient(135deg, #fef3c7 0%, #fed7aa 100%);
        border-radius: 8px;
        padding: 1rem;
        margin-top: 1.5rem;
        font-size: 0.85rem;
        color: #92400e;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .security-notice::before {
        content: '\f071';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        font-size: 1.25rem;
        color: #f59e0b;
    }
    
    /* ============================================
       Shake Animation for Error
       ============================================ */
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }
    
    .shake {
        animation: shake 0.5s ease-in-out;
    }
    
    /* ============================================
       Responsive Design
       ============================================ */
    @media (max-width: 640px) {
        .auth-container {
            padding: 1rem;
        }
        
        .auth-card {
            padding: 2rem 1.5rem;
            border-radius: 12px;
        }
        
        .auth-card h2 {
            font-size: 1.5rem;
        }
        
        .form-group input[type="text"],
        .form-group input[type="password"] {
            font-size: 16px; /* Prevents zoom on iOS */
        }
    }
    
    @media (max-width: 480px) {
        .auth-card {
            padding: 1.5rem 1.25rem;
        }
        
        .auth-card h2 {
            font-size: 1.375rem;
        }
        
        .auth-card::before {
            top: -10px;
            right: 20px;
            font-size: 0.7rem;
            padding: 3px 12px;
        }
    }
    
    /* ============================================
       Focus Visible (Accessibility)
       ============================================ */
    *:focus-visible {
        outline: 2px solid #3b82f6;
        outline-offset: 2px;
    }
    
    .btn:focus-visible {
        outline-offset: 4px;
    }
    
    /* ============================================
       Print Styles
       ============================================ */
    @media print {
        .auth-container {
            background: white;
        }
        
        .auth-card {
            box-shadow: none;
            border: 1px solid #e2e8f0;
        }
    }
    </style>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h2>Admin Login</h2>
            <p>Access the admin panel</p>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo h($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required value="<?php echo h($_POST['username'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-input-container">
                        <input type="password" id="password" name="password" required>
                        <button type="button" class="toggle-password" aria-label="Show password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" name="login" class="btn btn-primary btn-full">Login</button>
            </form>
            
            <div class="auth-links">
                <p><a href="../index.php">← Back to Website</a></p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle password visibility
            const togglePasswordBtn = document.querySelector('.toggle-password');
            const passwordInput = document.getElementById('password');
            
            if (togglePasswordBtn && passwordInput) {
                togglePasswordBtn.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.querySelector('i').classList.toggle('fa-eye');
                    this.querySelector('i').classList.toggle('fa-eye-slash');
                });
            }
            
            // Form submission handling
            const loginForm = document.getElementById('loginForm');
            const errorMessage = document.getElementById('errorMessage');
            const successMessage = document.getElementById('successMessage');
            
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const username = document.getElementById('username').value;
                    const password = document.getElementById('password').value;
                    
                    // Simple validation
                    
                });
            }
        });
    </script>
</body>
</html>