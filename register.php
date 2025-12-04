<?php
$pageTitle = "Register";
require_once 'includes/header.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$errors = [];
$success = false;
$formData = [
    'name' => '',
    'email' => '',
    'phone' => ''
];

if ($_POST && isset($_POST['register'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }
    
    $formData['name'] = trim($_POST['name'] ?? '');
    $formData['email'] = trim($_POST['email'] ?? '');
    $formData['phone'] = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Name Validation
    if (empty($formData['name'])) {
        $errors['name'] = "Name is required";
    } else {
        if (strlen($formData['name']) < 2) {
            $errors['name'] = "Name must be at least 2 characters long";
        } elseif (strlen($formData['name']) > 50) {
            $errors['name'] = "Name cannot exceed 50 characters";
        } elseif (!preg_match('/^[a-zA-Z\s\.\-]+$/', $formData['name'])) {
            $errors['name'] = "Name can only contain letters, spaces, dots, and hyphens";
        }
    }
    
    // Email Validation
    if (empty($formData['email'])) {
        $errors['email'] = "Email is required";
    } else {
        if (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Invalid email format";
        } elseif (strlen($formData['email']) > 100) {
            $errors['email'] = "Email cannot exceed 100 characters";
        }
    }
    
    // Phone Validation
    if (empty($formData['phone'])) {
        $errors['phone'] = "Phone number is required";
    } else {
        // Remove any non-digit characters
        $cleanPhone = preg_replace('/[^\d]/', '', $formData['phone']);
        
        if (strlen($cleanPhone) !== 10) {
            $errors['phone'] = "Phone number must be exactly 10 digits";
        } elseif (!preg_match('/^\d{10}$/', $cleanPhone)) {
            $errors['phone'] = "Phone number can only contain digits";
        } elseif (!preg_match('/^9\d{9}$/', $cleanPhone)) {
            $errors['phone'] = "Phone number must start with 9";
        } else {
            // Format phone number to store as 10 digits only
            $formData['phone'] = $cleanPhone;
        }
    }
    
    // Password Validation
    if (empty($password)) {
        $errors['password'] = "Password is required";
    } else {
        if (strlen($password) < 8) {
            $errors['password'] = "Password must be at least 8 characters long";
        } elseif (strlen($password) > 50) {
            $errors['password'] = "Password cannot exceed 50 characters";
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errors['password'] = "Password must contain at least one uppercase letter";
        } elseif (!preg_match('/[a-z]/', $password)) {
            $errors['password'] = "Password must contain at least one lowercase letter";
        } elseif (!preg_match('/[0-9]/', $password)) {
            $errors['password'] = "Password must contain at least one number";
        } elseif (!preg_match('/[!@#$%^&*()\-_=+{};:,<.>]/', $password)) {
            $errors['password'] = "Password must contain at least one special character";
        }
    }
    
    // Confirm Password Validation
    if (empty($confirmPassword)) {
        $errors['confirm_password'] = "Please confirm your password";
    } elseif ($password !== $confirmPassword) {
        $errors['confirm_password'] = "Passwords do not match";
    }
    
    // Check if email already exists (only if no errors so far)
    if (empty($errors) && isset($pdo)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$formData['email']]);
            if ($stmt->fetch()) {
                $errors['email'] = "Email already exists. Please use a different email or <a href='login.php'>login here</a>";
            }
        } catch (Exception $e) {
            $errors[] = "Database error. Please try again.";
        }
    }
    
    // Check if phone already exists (only if no errors so far)
    if (empty($errors) && isset($pdo)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
            $stmt->execute([$formData['phone']]);
            if ($stmt->fetch()) {
                $errors['phone'] = "Phone number already registered. Please use a different phone number.";
            }
        } catch (Exception $e) {
            $errors[] = "Database error. Please try again.";
        }
    }
    
    // Create user if no errors
    if (empty($errors)) {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$formData['name'], $formData['email'], $formData['phone'], $hashedPassword]);
            
            $success = true;
            // Clear form data after successful registration
            $formData = ['name' => '', 'email' => '', 'phone' => ''];
        } catch (Exception $e) {
            $errors[] = "Error creating account. Please try again. Error: " . $e->getMessage();
        }
    }
}
?>


<style>
/* ============================================
   Auth Container & Card (Consistent with Login)
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
    max-width: 500px;
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
   Typography
   ============================================ */
.auth-card h2 {
    font-size: 1.875rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 0.5rem;
    text-align: center;
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

.alert-success {
    background: #f0fdf4;
    border-left: 4px solid #22c55e;
    color: #166534;
    font-weight: 500;
}

.alert-success a {
    color: #15803d;
    text-decoration: underline;
    font-weight: 600;
}

.alert-success a:hover {
    color: #166534;
}

.auth-form {
    margin-bottom: 1.5rem;
}

.form-group {
    margin-bottom: 1.25rem;
    position: relative;
}

.form-group label {
    display: block;
    font-weight: 600;
    color: #334155;
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
}

.form-group label .required {
    color: #ef4444;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="tel"],
.form-group input[type="password"] {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: #f8fafc;
    color: #1e293b;
    box-sizing: border-box;
}

.form-group input:focus {
    outline: none;
    border-color: #3b82f6;
    background: white;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
}

.form-group input.error {
    border-color: #ef4444;
    background: #fef2f2;
}

.form-group input.error:focus {
    box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
}

.form-group input.success {
    border-color: #22c55e;
    background: #f0fdf4;
}

.form-group input::placeholder {
    color: #94a3b8;
}

/* ============================================
   Error & Helper Messages
   ============================================ */
.error-message {
    display: block;
    color: #ef4444;
    font-size: 0.875rem;
    margin-top: 0.5rem;
    font-weight: 500;
    animation: shake 0.3s ease-in-out;
}

.error-message a {
    color: #dc2626;
    text-decoration: underline;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

.form-group small {
    display: block;
    color: #64748b;
    font-size: 0.85rem;
    margin-top: 0.5rem;
    line-height: 1.4;
}

/* ============================================
   Password Strength Indicator
   ============================================ */
.password-strength {
    margin-top: 0.75rem;
}

.password-strength-bar {
    height: 4px;
    background: #e2e8f0;
    border-radius: 2px;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.password-strength-fill {
    height: 100%;
    width: 0%;
    transition: all 0.3s ease;
    border-radius: 2px;
}

.password-strength-fill.weak {
    width: 33%;
    background: #ef4444;
}

.password-strength-fill.medium {
    width: 66%;
    background: #f59e0b;
}

.password-strength-fill.strong {
    width: 100%;
    background: #22c55e;
}

.password-strength-text {
    font-size: 0.85rem;
    font-weight: 500;
}

.password-strength-text.weak {
    color: #ef4444;
}

.password-strength-text.medium {
    color: #f59e0b;
}

.password-strength-text.strong {
    color: #22c55e;
}

.password-requirements {
    margin-top: 0.5rem;
    padding: 0.75rem;
    background: #f8fafc;
    border-radius: 6px;
    font-size: 0.85rem;
}

.password-requirements ul {
    margin: 0;
    padding-left: 1.25rem;
    list-style: none;
}

.password-requirements li {
    padding: 0.25rem 0;
    color: #64748b;
    position: relative;
}

.password-requirements li::before {
    content: "○";
    position: absolute;
    left: -1.25rem;
    color: #cbd5e1;
}

.password-requirements li.valid {
    color: #22c55e;
}

.password-requirements li.valid::before {
    content: "✓";
    color: #22c55e;
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
   Input Icons
   ============================================ */
.input-with-icon {
    position: relative;
}

.input-with-icon .input-icon {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    pointer-events: none;
}

.input-with-icon input {
    padding-right: 40px;
}

.input-with-icon .input-icon.success {
    color: #22c55e;
}

.input-with-icon .input-icon.error {
    color: #ef4444;
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
    position: relative;
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
}

.auth-links a:hover {
    color: #2563eb;
    text-decoration: underline;
}

/* ============================================
   Progress Indicator
   ============================================ */
.form-progress {
    display: flex;
    justify-content: space-between;
    margin-bottom: 2rem;
    padding: 0 0.5rem;
}

.progress-step {
    flex: 1;
    text-align: center;
    position: relative;
}

.progress-step::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 2px;
    background: #e2e8f0;
    z-index: -1;
}

.progress-step:first-child::before {
    left: 50%;
}

.progress-step:last-child::before {
    right: 50%;
}

.progress-step-circle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #e2e8f0;
    color: #94a3b8;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
}

.progress-step.active .progress-step-circle {
    background: #3b82f6;
    color: white;
}

.progress-step.completed .progress-step-circle {
    background: #22c55e;
    color: white;
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
    
    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group input[type="tel"],
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
    
    .form-group {
        margin-bottom: 1rem;
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
</style>

<div class="auth-container">
    <div class="auth-card">
        <h2>Create Account</h2>
        <p>Join Maharaja Restaurant today</p>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <p style="color: #000">
                    <i class="fas fa-check-circle"></i>
                    Account created successfully! <a href="login.php">Click here to login</a>
                </p>
            </div>
        <?php else: ?>
            <?php if (!empty($errors) && is_array($errors)): ?>
                <div class="alert alert-error">
                    <?php 
                    // Show general errors (non-field specific)
                    $generalErrors = array_filter($errors, function($key) {
                        return is_numeric($key);
                    }, ARRAY_FILTER_USE_KEY);
                    
                    if (!empty($generalErrors)): ?>
                        <ul>
                            <?php foreach ($generalErrors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="auth-form" id="registerForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label for="name">
                        Full Name <span class="required">*</span>
                    </label>
                    <div class="input-with-icon">
                        <input type="text" id="name" name="name" required 
                               value="<?php echo h($formData['name']); ?>"
                               class="<?php echo isset($errors['name']) ? 'error' : ''; ?>"
                               minlength="2" maxlength="50"
                               placeholder="Enter your full name"
                               autocomplete="name">
                        <span class="input-icon" id="name-icon"></span>
                    </div>
                    <?php if (isset($errors['name'])): ?>
                        <span class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo h($errors['name']); ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="email">
                        Email Address <span class="required">*</span>
                    </label>
                    <div class="input-with-icon">
                        <input type="email" id="email" name="email" required 
                               value="<?php echo h($formData['email']); ?>"
                               class="<?php echo isset($errors['email']) ? 'error' : ''; ?>"
                               maxlength="100"
                               placeholder="your.email@example.com"
                               autocomplete="email">
                        <span class="input-icon" id="email-icon"></span>
                    </div>
                    <?php if (isset($errors['email'])): ?>
                        <span class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo $errors['email']; ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="phone">
                        Phone Number <span class="required">*</span>
                    </label>
                    <div class="input-with-icon">
                        <input type="tel" id="phone" name="phone" required 
                               value="<?php echo h($formData['phone']); ?>"
                               class="<?php echo isset($errors['phone']) ? 'error' : ''; ?>"
                               pattern="9[0-9]{9}"
                               placeholder="98XXXXXXXX"
                               maxlength="10"
                               autocomplete="tel">
                        <span class="input-icon" id="phone-icon"></span>
                    </div>
                    <?php if (isset($errors['phone'])): ?>
                        <span class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo h($errors['phone']); ?>
                        </span>
                    <?php endif; ?>
                    <small><i class="fas fa-info-circle"></i> 10 digits starting with 9</small>
                </div>
                
                <div class="form-group">
                    <label for="password">
                        Password <span class="required">*</span>
                    </label>
                    <div class="password-input-container">
                        <input type="password" id="password" name="password" required
                               class="<?php echo isset($errors['password']) ? 'error' : ''; ?>"
                               minlength="8" maxlength="50"
                               placeholder="Create a strong password"
                               autocomplete="new-password">
                        <button type="button" class="toggle-password" id="togglePassword" aria-label="Show password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <?php if (isset($errors['password'])): ?>
                        <span class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo h($errors['password']); ?>
                        </span>
                    <?php endif; ?>
                    
                    <div class="password-strength" id="passwordStrength" style="display: none;">
                        <div class="password-strength-bar">
                            <div class="password-strength-fill" id="strengthBar"></div>
                        </div>
                        <span class="password-strength-text" id="strengthText"></span>
                    </div>
                    
                    <div class="password-requirements">
                        <ul id="passwordRequirements">
                            <li id="req-length">At least 8 characters</li>
                            <li id="req-uppercase">One uppercase letter</li>
                            <li id="req-lowercase">One lowercase letter</li>
                            <li id="req-number">One number</li>
                            <li id="req-special">One special character (!@#$%^&*)</li>
                        </ul>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">
                        Confirm Password <span class="required">*</span>
                    </label>
                    <div class="password-input-container">
                        <input type="password" id="confirm_password" name="confirm_password" required
                               class="<?php echo isset($errors['confirm_password']) ? 'error' : ''; ?>"
                               placeholder="Re-enter your password"
                               autocomplete="new-password">
                        <button type="button" class="toggle-password" id="toggleConfirmPassword" aria-label="Show password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <?php if (isset($errors['confirm_password'])): ?>
                        <span class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo h($errors['confirm_password']); ?>
                        </span>
                    <?php endif; ?>
                    <span class="error-message" id="confirmPasswordError" style="display: none;">
                        <i class="fas fa-exclamation-circle"></i>
                        Passwords do not match
                    </span>
                </div>
                
                <button type="submit" name="register" class="btn btn-primary btn-full" id="submitBtn">
                    <i class="fas fa-user-plus"></i>
                    Create Account
                </button>
            </form>
        <?php endif; ?>
        
        <div class="auth-links">
            <p>Already have an account? <a href="login.php">Sign in here</a></p>
            <p><a href="index.php"><i class="fas fa-arrow-left"></i> Back to Homepage</a></p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registerForm');
    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const phoneInput = document.getElementById('phone');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const submitBtn = document.getElementById('submitBtn');

    /* ==========================
       Password toggle
    ========================== */
    const toggleButtons = document.querySelectorAll('.toggle-password');
    toggleButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const icon = this.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    /* ==========================
       Name validation
    ========================== */
    if (nameInput) {
        nameInput.addEventListener('input', function() {
            const icon = document.getElementById('name-icon');
            const value = this.value.trim();
            if (value.length >= 2 && value.length <= 50 && /^[a-zA-Z\s.\-]+$/.test(value)) {
                this.classList.remove('error');
                this.classList.add('success');
                icon.innerHTML = '<i class="fas fa-check-circle success"></i>';
            } else if (value.length > 0) {
                this.classList.add('error');
                this.classList.remove('success');
                icon.innerHTML = '<i class="fas fa-times-circle error"></i>';
            } else {
                this.classList.remove('error', 'success');
                icon.innerHTML = '';
            }
        });
    }

    /* ==========================
       Email validation
    ========================== */
    if (emailInput) {
        emailInput.addEventListener('blur', function() {
            const icon = document.getElementById('email-icon');
            const value = this.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (emailRegex.test(value)) {
                this.classList.remove('error');
                this.classList.add('success');
                icon.innerHTML = '<i class="fas fa-check-circle success"></i>';
            } else if (value.length > 0) {
                this.classList.add('error');
                this.classList.remove('success');
                icon.innerHTML = '<i class="fas fa-times-circle error"></i>';
            } else {
                this.classList.remove('error', 'success');
                icon.innerHTML = '';
            }
        });
    }

    /* ==========================
       Phone validation
    ========================== */
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.substring(0, 10);
            e.target.value = value;
            const icon = document.getElementById('phone-icon');
            if (value.length === 10 && /^9\d{9}$/.test(value)) {
                this.classList.remove('error');
                this.classList.add('success');
                icon.innerHTML = '<i class="fas fa-check-circle success"></i>';
            } else if (value.length > 0) {
                this.classList.add('error');
                this.classList.remove('success');
                icon.innerHTML = '<i class="fas fa-times-circle error"></i>';
            } else {
                this.classList.remove('error', 'success');
                icon.innerHTML = '';
            }
        });
    }

    /* ==========================
       Password strength
    ========================== */
    if (passwordInput) {
        const strengthIndicator = document.getElementById('passwordStrength');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        const requirements = {
            length: document.getElementById('req-length'),
            uppercase: document.getElementById('req-uppercase'),
            lowercase: document.getElementById('req-lowercase'),
            number: document.getElementById('req-number'),
            special: document.getElementById('req-special')
        };

        passwordInput.addEventListener('input', function() {
            const value = this.value;
            if (value.length > 0) {
                strengthIndicator.style.display = 'block';
            } else {
                strengthIndicator.style.display = 'none';
                return;
            }

            let strength = 0;
            const checks = {
                length: value.length >= 8,
                uppercase: /[A-Z]/.test(value),
                lowercase: /[a-z]/.test(value),
                number: /[0-9]/.test(value),
                special: /[!@#$%^&*()\-_=+{};:,<.>]/.test(value)
            };

            for (let req in checks) {
                if (checks[req]) {
                    requirements[req].classList.add('valid');
                    strength++;
                } else {
                    requirements[req].classList.remove('valid');
                }
            }

            strengthBar.className = 'password-strength-fill';
            strengthText.className = 'password-strength-text';
            if (strength <= 2) {
                strengthBar.classList.add('weak');
                strengthText.classList.add('weak');
                strengthText.textContent = 'Weak password';
            } else if (strength <= 4) {
                strengthBar.classList.add('medium');
                strengthText.classList.add('medium');
                strengthText.textContent = 'Medium strength';
            } else {
                strengthBar.classList.add('strong');
                strengthText.classList.add('strong');
                strengthText.textContent = 'Strong password';
            }

            if (confirmPasswordInput.value) {
                validateConfirmPassword();
            }
        });
    }

    /* ==========================
       Confirm password
    ========================== */
    if (confirmPasswordInput) {
        const errorMsg = document.getElementById('confirmPasswordError');
        function validateConfirmPassword() {
            if (confirmPasswordInput.value && passwordInput.value !== confirmPasswordInput.value) {
                confirmPasswordInput.classList.add('error');
                confirmPasswordInput.classList.remove('success');
                errorMsg.style.display = 'block';
                return false;
            } else if (confirmPasswordInput.value && passwordInput.value === confirmPasswordInput.value) {
                confirmPasswordInput.classList.remove('error');
                confirmPasswordInput.classList.add('success');
                errorMsg.style.display = 'none';
                return true;
            } else {
                confirmPasswordInput.classList.remove('error', 'success');
                errorMsg.style.display = 'none';
                return false;
            }
        }
        confirmPasswordInput.addEventListener('input', validateConfirmPassword);
        confirmPasswordInput.addEventListener('blur', validateConfirmPassword);
    }

    /* ==========================
       Form submit (FIXED)
    ========================== */
    if (form) {
        form.addEventListener('submit', function(e) {
            // Let the browser handle submission first, then show loading state
            setTimeout(() => {
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
            }, 10);
        });
    }

    /* ==========================
       Autofocus first field
    ========================== */
    if (nameInput) {
        nameInput.focus();
    }
});
</script>


<?php require_once 'includes/footer.php'; ?>