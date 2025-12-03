<?php
declare(strict_types=1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configuration and functions first
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in and redirect if necessary
if (isLoggedIn()) {
    $redirect = $_GET['redirect'] ?? 'index.php';
    header('Location: ' . $redirect);
    exit;
}

if (isAdminLoggedIn()) {
    header('Location: admin/index.php');
    exit;
}

$errors = [];
$loginType = $_POST['login_type'] ?? 'user'; // 'user' or 'admin'

if ($_POST && isset($_POST['login'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }
    
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';
    $loginType = $_POST['login_type'] ?? 'user';
    
    // Validation
    if (empty($identifier)) {
        $errors[] = $loginType === 'admin' ? "Username is required" : "Email is required";
    }
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    if (empty($errors)) {
        if ($loginType === 'admin') {
            // Admin login
            
            $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ? AND is_active = 1");
            $stmt->execute([$identifier]);
            $admin = $stmt->fetch();
            
            if ($admin && (password_verify($password, $admin['password']) || $password === $admin['password'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                // Update last login
                $updateStmt = $pdo->prepare("UPDATE admin_users SET last_login_at = NOW() WHERE id = ?");
                $updateStmt->execute([$admin['id']]);
                
                header('Location: admin/index.php');
                exit;
            } else {
                $errors[] = "Invalid username or password";
            }
        } else {
            // User login
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$identifier]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                
                // Update last login
                $updateStmt = $pdo->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);
                
                $redirect = $_GET['redirect'] ?? 'index.php';
                header('Location: ' . $redirect);
                exit;
            } else {
                $errors[] = "Invalid email or password";
            }
        }
    }
}

// Set page title and include header AFTER all potential redirects
$pageTitle = "Login - Maharaja Restaurant";
require_once 'includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <!-- Login Type Toggle -->
        <div class="login-type-toggle">
            <button type="button" class="toggle-btn <?php echo $loginType === 'user' ? 'active' : ''; ?>" data-type="user">
                <i class="fas fa-user"></i> Customer Login
            </button>
            <button type="button" class="toggle-btn <?php echo $loginType === 'admin' ? 'active' : ''; ?>" data-type="admin">
                <i class="fas fa-lock"></i> Admin Login
            </button>
        </div>
        
        <h2 id="login-title"><?php echo $loginType === 'admin' ? 'Admin Portal' : 'Welcome Back'; ?></h2>
        <p id="login-subtitle"><?php echo $loginType === 'admin' ? 'Access the admin panel' : 'Sign in to your account'; ?></p>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo h($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="auth-form" id="login-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="login_type" id="login_type" value="<?php echo h($loginType); ?>">
            
            <div class="form-group">
                <label for="identifier" id="identifier-label">
                    <?php echo $loginType === 'admin' ? 'Username' : 'Email Address'; ?>
                </label>
                <input type="text" id="identifier" name="identifier" required 
                       value="<?php echo h($_POST['identifier'] ?? ''); ?>"
                       placeholder="<?php echo $loginType === 'admin' ? 'Enter your username' : 'Enter your email'; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-input-container">
                    <input type="password" id="password" name="password" required 
                           placeholder="Enter your password">
                    <button type="button" class="toggle-password" aria-label="Show password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <?php if ($loginType === 'user'): ?>
            <div class="form-group remember-forgot">
                <label class="checkbox-container">
                    <input type="checkbox" name="remember_me" value="1">
                    <span class="checkmark"></span>
                    Remember me
                </label>
                <a href="forgot-password.php" class="forgot-link">Forgot password?</a>
            </div>
            <?php endif; ?>
            
            <button type="submit" name="login" class="btn btn-primary btn-full">
                <i class="fas fa-sign-in-alt"></i>
                <?php echo $loginType === 'admin' ? 'Admin Login' : 'Sign In'; ?>
            </button>
        </form>
        
        <div class="auth-links">
            <?php if ($loginType === 'user'): ?>
                <p>Don't have an account? <a href="register.php">Create one here</a></p>
                <p><a href="index.php">← Back to Homepage</a></p>
            <?php else: ?>
                <p><a href="../index.php">← Back to Main Website</a></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* ============================================
   Auth Container & Card
   ============================================ */
.auth-container {
    min-height: calc(100vh - 200px);
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
    max-width: 440px;
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

/* ============================================
   Login Type Toggle
   ============================================ */
.login-type-toggle {
    display: flex;
    gap: 0;
    margin-bottom: 2rem;
    background: #f1f5f9;
    border-radius: 10px;
    padding: 4px;
    position: relative;
}

.toggle-btn {
    flex: 1;
    padding: 12px 20px;
    border: none;
    background: transparent;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    font-weight: 600;
    font-size: 0.95rem;
    color: #64748b;
    border-radius: 8px;
    position: relative;
    z-index: 1;
}

.toggle-btn i {
    margin-right: 6px;
    font-size: 0.9rem;
}

.toggle-btn.active {
    background: #3b82f6;
    color: white;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.toggle-btn:not(.active):hover {
    color: #3b82f6;
    background: #e0e7ff;
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
}

.auth-card p#login-subtitle {
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
    padding-left: 1.25rem;
    list-style: none;
}

.alert-error li {
    position: relative;
    padding-left: 0;
}

.alert-error li::before {
    content: "⚠";
    margin-right: 8px;
    color: #ef4444;
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
.form-group input[type="email"],
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
   Remember & Forgot
   ============================================ */
.remember-forgot {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.checkbox-container {
    display: flex;
    align-items: center;
    cursor: pointer;
    font-size: 0.95rem;
    color: #475569;
    user-select: none;
}

.checkbox-container input[type="checkbox"] {
    width: 18px;
    height: 18px;
    margin-right: 8px;
    cursor: pointer;
    accent-color: #3b82f6;
}

.forgot-link {
    color: #3b82f6;
    text-decoration: none;
    font-size: 0.95rem;
    font-weight: 500;
    transition: color 0.2s ease;
}

.forgot-link:hover {
    color: #2563eb;
    text-decoration: underline;
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
}

.btn-primary:hover {
    background: #2563eb;
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
    transform: translateY(-2px);
}

.btn-primary:active {
    transform: translateY(0);
}

.btn-full {
    width: 100%;
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
}

.auth-links a:hover {
    color: #2563eb;
    text-decoration: underline;
}

/* ============================================
   Responsive Design
   ============================================ */
@media (max-width: 640px) {
    .auth-container {
        padding: 1rem;
        min-height: calc(100vh - 150px);
    }
    
    .auth-card {
        padding: 2rem 1.5rem;
        border-radius: 12px;
    }
    
    .auth-card h2 {
        font-size: 1.5rem;
    }
    
    .login-type-toggle {
        flex-direction: column;
        gap: 4px;
    }
    
    .toggle-btn {
        padding: 14px 20px;
        width: 100%;
    }
    
    .remember-forgot {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .form-group input[type="text"],
    .form-group input[type="email"],
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
    
    .toggle-btn {
        font-size: 0.9rem;
        padding: 12px 16px;
    }
}

/* ============================================
   Loading State (Optional Enhancement)
   ============================================ */
.btn-primary.loading {
    pointer-events: none;
    opacity: 0.7;
    position: relative;
}

.btn-primary.loading::after {
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
   Focus Visible (Accessibility)
   ============================================ */
*:focus-visible {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

.toggle-btn:focus-visible,
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleButtons = document.querySelectorAll('.toggle-btn');
    const loginTypeInput = document.getElementById('login_type');
    const identifierInput = document.getElementById('identifier');
    const identifierLabel = document.getElementById('identifier-label');
    const loginTitle = document.getElementById('login-title');
    const loginSubtitle = document.getElementById('login-subtitle');
    const form = document.getElementById('login-form');
    const submitButton = form.querySelector('button[type="submit"]');
    
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
    
    // Login type toggle
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const type = this.getAttribute('data-type');
            
            // Update active state
            toggleButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Update form for login type
            updateLoginForm(type);
        });
    });
    
    function updateLoginForm(type) {
        loginTypeInput.value = type;
        
        // Get the auth-links div for updating
        const authLinks = document.querySelector('.auth-links');
        
        if (type === 'admin') {
            identifierLabel.textContent = 'Username';
            identifierInput.setAttribute('placeholder', 'Enter your username');
            identifierInput.setAttribute('type', 'text');
            loginTitle.textContent = 'Admin Portal';
            loginSubtitle.textContent = 'Access the admin panel';
            submitButton.innerHTML = '<i class="fas fa-sign-in-alt"></i> Admin Login';
            
            // Hide remember me/forgot password for admin
            const rememberForgot = document.querySelector('.remember-forgot');
            if (rememberForgot) rememberForgot.style.display = 'none';
            
            // Update auth links for admin - NO REGISTRATION LINK
            if (authLinks) {
                authLinks.innerHTML = '<p><a href="index.php">← Back to Main Website</a></p>';
            }
        } else {
            identifierLabel.textContent = 'Email Address';
            identifierInput.setAttribute('placeholder', 'Enter your email');
            identifierInput.setAttribute('type', 'email');
            loginTitle.textContent = 'Welcome Back';
            loginSubtitle.textContent = 'Sign in to your account';
            submitButton.innerHTML = '<i class="fas fa-sign-in-alt"></i> Sign In';
            
            // Show remember me/forgot password for users
            const rememberForgot = document.querySelector('.remember-forgot');
            if (rememberForgot) rememberForgot.style.display = 'flex';
            
            // Update auth links for regular users - WITH REGISTRATION LINK
            if (authLinks) {
                authLinks.innerHTML = `
                    <p>Don't have an account? <a href="register.php">Create one here</a></p>
                    <p><a href="index.php">← Back to Homepage</a></p>
                `;
            }
        }
        
        // Clear previous values
        identifierInput.value = '';
        passwordInput.value = '';
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>