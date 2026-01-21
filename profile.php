<?php
// Include database first (this also starts the session)
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';
$errors = [];

// Generate CSRF token BEFORE any form processing
$csrf_token = generateCSRFToken();

// Fetch current user data
function getUserData($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$user = getUserData($pdo, $user_id);

if (!$user) {
    SessionManager::destroy();
    header('Location: login.php');
    exit;
}

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Security token expired. Please refresh the page and try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        
        if (empty($name)) {
            $errors['name'] = 'Full name is required.';
        } elseif (strlen($name) < 2) {
            $errors['name'] = 'Name must be at least 2 characters.';
        } elseif (strlen($name) > 255) {
            $errors['name'] = 'Name must not exceed 255 characters.';
        }
        
        if (!empty($phone)) {
            $phone_clean = preg_replace('/[\s\-]/', '', $phone);
            if (!preg_match('/^[0-9]{10,15}$/', $phone_clean)) {
                $errors['phone'] = 'Please enter a valid phone number (10-15 digits).';
            }
        }
        
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$name, $phone ?: null, $user_id]);
                
                $_SESSION['user_name'] = $name;
                $success_message = 'Profile updated successfully!';
                $user = getUserData($pdo, $user_id);
                
            } catch (PDOException $e) {
                error_log("Profile update error: " . $e->getMessage());
                $error_message = 'An error occurred while updating your profile. Please try again.';
            }
        }
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Security token expired. Please refresh the page and try again.';
    } else {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password)) {
            $errors['current_password'] = 'Current password is required.';
        } elseif (!password_verify($current_password, $user['password'])) {
            $errors['current_password'] = 'Current password is incorrect.';
        }
        
        if (empty($new_password)) {
            $errors['new_password'] = 'New password is required.';
        } elseif (strlen($new_password) < 8) {
            $errors['new_password'] = 'Password must be at least 8 characters.';
        } elseif (!preg_match('/[A-Z]/', $new_password)) {
            $errors['new_password'] = 'Password must contain at least one uppercase letter.';
        } elseif (!preg_match('/[a-z]/', $new_password)) {
            $errors['new_password'] = 'Password must contain at least one lowercase letter.';
        } elseif (!preg_match('/[0-9]/ ', $new_password)) {
            $errors['new_password'] = 'Password must contain at least one number.';
        } elseif (!preg_match('/[^a-zA-Z0-9]/', $new_password)) {
            $errors['new_password'] = 'Password must contain at least one special character.';
        }
        
        if (empty($confirm_password)) {
            $errors['confirm_password'] = 'Please confirm your new password.';
        } elseif ($new_password !== $confirm_password) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }
        
        if (!empty($current_password) && !empty($new_password) && $current_password === $new_password) {
            $errors['new_password'] = 'New password must be different from current password.';
        }
        
        if (empty($errors) && empty($error_message)) {
            try {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);
                
                $success_message = 'Password changed successfully!';
                $user = getUserData($pdo, $user_id);
                
            } catch (PDOException $e) {
                error_log("Password change error: " . $e->getMessage());
                $error_message = 'An error occurred while changing your password. Please try again.';
            }
        }
    }
}

// Get order statistics
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_orders, COALESCE(SUM(total), 0) as total_spent FROM orders WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $stats = ['total_orders' => 0, 'total_spent' => 0];
}

$page_title = 'My Profile';
include 'includes/header.php';
?>

<div class="profile-page">

    <div class="profile-container">
        <div class="profile-wrapper">
            <!-- Profile Header Card -->
            <div class="profile-header-card">
                <div class="profile-header-content">
                    <div class="profile-avatar-wrapper">
                        <div class="profile-avatar">
                            <span class="avatar-text"><?php echo strtoupper(substr($user['name'], 0, 2)); ?></span>
                        </div>
                        <span class="avatar-badge"><i class="fas fa-check"></i></span>
                    </div>
                    <div class="profile-header-info">
                        <h1><?php echo htmlspecialchars($user['name']); ?></h1>
                        <p class="profile-email">
                            <i class="fas fa-envelope"></i>
                            <?php echo htmlspecialchars($user['email']); ?>
                        </p>
                        <div class="profile-meta">
                            <span class="meta-item">
                                <i class="fas fa-calendar-alt"></i>
                                Member since <?php echo date('F Y', strtotime($user['created_at'])); ?>
                            </span>
                            <?php if (!empty($user['phone'])): ?>
                            <span class="meta-item">
                                <i class="fas fa-phone"></i>
                                <?php echo htmlspecialchars($user['phone']); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alerts -->
            <?php if ($success_message): ?>
                <div class="alert alert-success" id="successAlert">
                    <div class="alert-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="alert-content">
                        <strong>Success!</strong>
                        <p><?php echo htmlspecialchars($success_message); ?></p>
                    </div>
                    <button type="button" class="alert-close" onclick="closeAlert('successAlert')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error" id="errorAlert">
                    <div class="alert-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="alert-content">
                        <strong>Error!</strong>
                        <p><?php echo htmlspecialchars($error_message); ?></p>
                    </div>
                    <button type="button" class="alert-close" onclick="closeAlert('errorAlert')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-icon orders">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-value"><?php echo (int)($stats['total_orders'] ?? 0); ?></span>
                        <span class="stat-label">Total Orders</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon spent">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-value">Rs. <?php echo number_format((float)($stats['total_spent'] ?? 0), 0); ?></span>
                        <span class="stat-label">Total Spent</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon login">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-value">
                            <?php 
                            if (isset($user['last_login_at']) && $user['last_login_at']) {
                                echo date('M d', strtotime($user['last_login_at']));
                            } else {
                                echo 'Today';
                            }
                            ?>
                        </span>
                        <span class="stat-label">Last Login</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon status">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-value status-active">Active</span>
                        <span class="stat-label">Account Status</span>
                    </div>
                </div>
            </div>

            <div class="profile-content">
                
                <!-- Left Column -->
                <div class="profile-main">
                    
                    <!-- Profile Information Form -->
                    <div class="profile-card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-user-edit"></i>
                            </div>
                            <div>
                                <h3>Profile Information</h3>
                                <p>Update your personal details</p>
                            </div>
                        </div>
                        
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="profile-form" id="profileForm">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="name">
                                        <i class="fas fa-user"></i>
                                        Full Name <span class="required">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        id="name" 
                                        name="name" 
                                        value="<?php echo htmlspecialchars($_POST['name'] ?? $user['name']); ?>"
                                        placeholder="Enter your full name"
                                        class="<?php echo isset($errors['name']) ? 'error' : ''; ?>"
                                        required
                                    >
                                    <?php if (isset($errors['name'])): ?>
                                        <span class="error-message">
                                            <i class="fas fa-exclamation-circle"></i>
                                            <?php echo htmlspecialchars($errors['name']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group">
                                    <label for="phone">
                                        <i class="fas fa-phone"></i>
                                        Phone Number
                                    </label>
                                    <input 
                                        type="tel" 
                                        id="phone" 
                                        name="phone" 
                                        value="<?php echo htmlspecialchars($_POST['phone'] ?? $user['phone'] ?? ''); ?>"
                                        placeholder="Enter your phone number"
                                        class="<?php echo isset($errors['phone']) ? 'error' : ''; ?>"
                                    >
                                    <?php if (isset($errors['phone'])): ?>
                                        <span class="error-message">
                                            <i class="fas fa-exclamation-circle"></i>
                                            <?php echo htmlspecialchars($errors['phone']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="email">
                                    <i class="fas fa-envelope"></i>
                                    Email Address
                                    <span class="badge-locked">
                                        <i class="fas fa-lock"></i> Locked
                                    </span>
                                </label>
                                <div class="input-with-icon">
                                    <input 
                                        type="email" 
                                        id="email" 
                                        value="<?php echo htmlspecialchars($user['email']); ?>"
                                        readonly
                                        disabled
                                    >
                                    <span class="input-icon"><i class="fas fa-lock"></i></span>
                                </div>
                                <small class="form-hint">
                                    <i class="fas fa-info-circle"></i>
                                    Email cannot be changed for security reasons
                                </small>
                            </div>

                            <div class="form-actions">
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save"></i>
                                    Save Changes
                                </button>
                                <button type="reset" class="btn btn-outline">
                                    <i class="fas fa-undo"></i>
                                    Reset
                                </button>
                            </div>
                        </form>
                    </div>

                </div>

                <!-- Right Column -->
                <div class="profile-sidebar">

                    <!-- Change Password -->
                    <div class="profile-card">
                        <div class="card-header">
                            <div class="card-icon warning">
                                <i class="fas fa-key"></i>
                            </div>
                            <div>
                                <h3>Change Password</h3>
                                <p>Secure your account</p>
                            </div>
                        </div>
                        
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="profile-form" id="passwordForm">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            
                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <div class="password-wrapper">
                                    <input 
                                        type="password" 
                                        id="current_password" 
                                        name="current_password"
                                        placeholder="••••••••"
                                        class="<?php echo isset($errors['current_password']) ? 'error' : ''; ?>"
                                        autocomplete="current-password"
                                    >
                                    <button type="button" class="password-toggle" onclick="togglePassword('current_password', this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <?php if (isset($errors['current_password'])): ?>
                                    <span class="error-message">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <?php echo htmlspecialchars($errors['current_password']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <div class="password-wrapper">
                                    <input 
                                        type="password" 
                                        id="new_password" 
                                        name="new_password"
                                        placeholder="••••••••"
                                        class="<?php echo isset($errors['new_password']) ? 'error' : ''; ?>"
                                        autocomplete="new-password"
                                    >
                                    <button type="button" class="password-toggle" onclick="togglePassword('new_password', this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <?php if (isset($errors['new_password'])): ?>
                                    <span class="error-message">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <?php echo htmlspecialchars($errors['new_password']); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <!-- Password Strength Indicator -->
                                <div class="password-strength" id="passwordStrength">
                                    <div class="strength-bars">
                                        <span class="bar"></span>
                                        <span class="bar"></span>
                                        <span class="bar"></span>
                                        <span class="bar"></span>
                                    </div>
                                    <span class="strength-text">Password Strength</span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">Confirm Password</label>
                                <div class="password-wrapper">
                                    <input 
                                        type="password" 
                                        id="confirm_password" 
                                        name="confirm_password"
                                        placeholder="••••••••"
                                        class="<?php echo isset($errors['confirm_password']) ? 'error' : ''; ?>"
                                        autocomplete="new-password"
                                    >
                                    <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <?php if (isset($errors['confirm_password'])): ?>
                                    <span class="error-message">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <?php echo htmlspecialchars($errors['confirm_password']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="password-requirements">
                                <p><strong>Password Requirements:</strong></p>
                                <ul>
                                    <li id="req-length"><i class="fas fa-circle"></i> At least 8 characters</li>
                                    <li id="req-upper"><i class="fas fa-circle"></i> One uppercase letter</li>
                                    <li id="req-lower"><i class="fas fa-circle"></i> One lowercase letter</li>
                                    <li id="req-number-special"><i class="fas fa-circle"></i> One number & special charater</li>
                                </ul>
                            </div>

                            <div class="form-actions">
                                <button type="submit" name="change_password" class="btn btn-warning btn-full">
                                    <i class="fas fa-shield-alt"></i>
                                    Update Password
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<style>
.profile-page {
    min-height: 100vh;
    background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ec 100%);
    position: relative;
    overflow-x: hidden;
    padding-top: 20px;
}

/* Profile Container */
.profile-container {
    position: relative;
    z-index: 1;
    padding: 1rem 1.5rem 3rem;
    max-width: 1300px;
    margin: 0 auto;
}

/* Profile Header Card */
.profile-header-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    margin-bottom: 1.5rem;
}

.profile-header-content {
    display: flex;
    align-items: flex-end;
    gap: 1.5rem;
    padding: 2rem 1.5rem;
    position: relative;
    z-index: 1;
}

.profile-avatar-wrapper {
    position: relative;
    flex-shrink: 0;
}

.profile-avatar {
    width: 100px;
    height: 100px;
    border-radius: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    border: 4px solid white;
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.35);
}

.avatar-text {
    font-size: 2rem;
    font-weight: 700;
    color: white;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.avatar-badge {
    position: absolute;
    bottom: -4px;
    right: -4px;
    width: 28px;
    height: 28px;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.7rem;
    border: 3px solid white;
    box-shadow: 0 4px 10px rgba(34, 197, 94, 0.4);
}

.profile-header-info {
    flex: 1;
    padding-bottom: 0.25rem;
}

.profile-header-info h1 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 0.25rem 0;
}

.profile-email {
    color: #64748b;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0 0 0.6rem 0;
}

.profile-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.8rem;
    color: #64748b;
    background: #f1f5f9;
    padding: 0.35rem 0.7rem;
    border-radius: 8px;
}

.meta-item i {
    color: #667eea;
    font-size: 0.75rem;
}

/* Alerts */
.alert {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.25rem;
    border-radius: 14px;
    margin-bottom: 1.5rem;
    animation: slideInDown 0.4s ease-out;
}

.alert-success {
    background: linear-gradient(135deg, #ecfdf5, #d1fae5);
    border: 1px solid #6ee7b7;
}

.alert-error {
    background: linear-gradient(135deg, #fef2f2, #fecaca);
    border: 1px solid #fca5a5;
}

.alert-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.alert-success .alert-icon {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: white;
}

.alert-error .alert-icon {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.alert-content {
    flex: 1;
}

.alert-content strong {
    display: block;
    font-size: 0.9rem;
    margin-bottom: 0.1rem;
}

.alert-success .alert-content strong { color: #166534; }
.alert-error .alert-content strong { color: #991b1b; }

.alert-content p {
    font-size: 0.8rem;
    margin: 0;
}

.alert-success .alert-content p { color: #15803d; }
.alert-error .alert-content p { color: #b91c1c; }

.alert-close {
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 0.5rem;
    color: #94a3b8;
    transition: all 0.3s ease;
    border-radius: 8px;
}

.alert-close:hover {
    background: rgba(0, 0, 0, 0.05);
    color: #64748b;
}

@keyframes slideInDown {
    from { opacity: 0; transform: translateY(-15px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Stats Row */
.stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    background: white;
    border-radius: 14px;
    padding: 1.1rem;
    display: flex;
    align-items: center;
    gap: 0.9rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.stat-icon.orders {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.15), rgba(118, 75, 162, 0.15));
    color: #667eea;
}

.stat-icon.spent {
    background: linear-gradient(135deg, rgba(34, 197, 94, 0.15), rgba(22, 163, 74, 0.15));
    color: #22c55e;
}

.stat-icon.login {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(37, 99, 235, 0.15));
    color: #3b82f6;
}

.stat-icon.status {
    background: linear-gradient(135deg, rgba(168, 85, 247, 0.15), rgba(139, 92, 246, 0.15));
    color: #a855f7;
}

.stat-info { flex: 1; }

.stat-value {
    display: block;
    font-size: 1.2rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 0.1rem;
}

.stat-value.status-active { color: #22c55e; }

.stat-label {
    font-size: 0.75rem;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Profile Content Grid */
.profile-content {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 1.5rem;
}

.profile-main,
.profile-sidebar {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

/* Profile Cards */
.profile-card {
    background: white;
    border-radius: 18px;
    padding: 1.5rem;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.06);
}

.card-header {
    display: flex;
    align-items: center;
    gap: 0.9rem;
    margin-bottom: 1.25rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #f1f5f9;
}

.card-icon {
    width: 44px;
    height: 44px;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    flex-shrink: 0;
}

.card-icon.warning {
    background: linear-gradient(135deg, #f59e0b, #d97706);
}

.card-header h3 {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.card-header p {
    font-size: 0.8rem;
    color: #94a3b8;
    margin: 0;
}

/* Form Styles */
.profile-form {
    display: flex;
    flex-direction: column;
    gap: 1.1rem;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.1rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}

.form-group label {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-weight: 500;
    color: #475569;
    font-size: 0.85rem;
}

.form-group label i {
    color: #667eea;
    font-size: 0.8rem;
}

.form-group label .required {
    color: #ef4444;
}

.badge-locked {
    margin-left: auto;
    font-size: 0.65rem;
    font-weight: 500;
    color: #94a3b8;
    background: #f1f5f9;
    padding: 0.15rem 0.4rem;
    border-radius: 5px;
    display: flex;
    align-items: center;
    gap: 0.2rem;
}

.profile-form input[type="text"],
.profile-form input[type="email"],
.profile-form input[type="tel"],
.profile-form input[type="password"] {
    width: 100%;
    padding: 0.75rem 0.9rem;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 0.9rem;
    font-family: inherit;
    transition: all 0.3s ease;
    background: #f8fafc;
    color: #1e293b;
    box-sizing: border-box;
}

.profile-form input:focus {
    outline: none;
    border-color: #667eea;
    background: white;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.profile-form input.error {
    border-color: #ef4444;
    background: #fef2f2;
}

.profile-form input:disabled {
    background: #f1f5f9;
    color: #64748b;
    cursor: not-allowed;
}

.input-with-icon {
    position: relative;
}

.input-with-icon .input-icon {
    position: absolute;
    right: 0.9rem;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 0.85rem;
}

.form-hint {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.75rem;
    color: #94a3b8;
}

.error-message {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    color: #ef4444;
    font-size: 0.75rem;
    font-weight: 500;
}

/* Password Wrapper */
.password-wrapper {
    position: relative;
}

.password-wrapper input {
    padding-right: 2.75rem;
}

.password-toggle {
    position: absolute;
    right: 0.6rem;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    padding: 0.4rem;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.password-toggle:hover {
    background: #f1f5f9;
    color: #667eea;
}

/* Password Strength */
.password-strength {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    margin-top: 0.4rem;
}

.strength-bars {
    display: flex;
    gap: 3px;
    flex: 1;
}

.strength-bars .bar {
    height: 4px;
    flex: 1;
    background: #e2e8f0;
    border-radius: 2px;
    transition: all 0.3s ease;
}

.strength-bars.weak .bar:nth-child(1) { background: #ef4444; }
.strength-bars.medium .bar:nth-child(1),
.strength-bars.medium .bar:nth-child(2) { background: #f59e0b; }
.strength-bars.strong .bar:nth-child(1),
.strength-bars.strong .bar:nth-child(2),
.strength-bars.strong .bar:nth-child(3) { background: #22c55e; }
.strength-bars.very-strong .bar { background: #22c55e; }

.strength-text {
    font-size: 0.7rem;
    color: #94a3b8;
    white-space: nowrap;
}

/* Password Requirements */
.password-requirements {
    background: #f8fafc;
    border-radius: 10px;
    padding: 0.9rem;
    margin-top: 0.4rem;
}

.password-requirements p {
    font-size: 0.75rem;
    font-weight: 600;
    color: #475569;
    margin: 0 0 0.4rem 0;
}

.password-requirements ul {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.35rem;
}

.password-requirements li {
    font-size: 0.7rem;
    color: #94a3b8;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.password-requirements li i { font-size: 0.35rem; }
.password-requirements li.valid { color: #22c55e; }
.password-requirements li.valid i { font-size: 0.7rem; }

/* Form Actions */
.form-actions {
    display: flex;
    gap: 0.9rem;
    margin-top: 0.5rem;
    padding-top: 0.9rem;
    border-top: 1px solid #f1f5f9;
}

/* Buttons */
.btn {
    padding: 0.75rem 1.25rem;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.9rem;
    font-family: inherit;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    transition: all 0.3s ease;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.btn-warning {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
    box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
}

.btn-warning:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
}

.btn-outline {
    background: transparent;
    color: #64748b;
    border: 2px solid #e2e8f0;
}

.btn-outline:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
}

.btn-full { width: 100%; }

/* Responsive Design */
@media (max-width: 1100px) {
    .profile-content {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .profile-container {
        padding: 1rem;
    }
    
    .stats-row {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .profile-header-content {
        flex-direction: column;
        align-items: center;
        text-align: center;
        padding: 1.5rem 1.25rem;
    }
    
    .profile-header-info {
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .profile-meta {
        justify-content: center;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .password-requirements ul {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .stats-row {
        grid-template-columns: 1fr;
    }
    
    .profile-avatar {
        width: 85px;
        height: 85px;
    }
    
    .avatar-text {
        font-size: 1.75rem;
    }
    
    .profile-header-info h1 {
        font-size: 1.3rem;
    }
    
    .stat-card {
        padding: 0.9rem;
    }
    
    .profile-card {
        padding: 1.1rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
    }
}
</style>

<script>
// Toggle Password Visibility
function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Close Alert
function closeAlert(alertId) {
    const alert = document.getElementById(alertId);
    if (alert) {
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-15px)';
        setTimeout(() => alert.remove(), 300);
    }
}

// Password Strength Checker
document.addEventListener('DOMContentLoaded', function() {
    const newPasswordInput = document.getElementById('new_password');
    const strengthBars = document.querySelector('.strength-bars');
    const strengthText = document.querySelector('.strength-text');
    
    const reqLength = document.getElementById('req-length');
    const reqUpper = document.getElementById('req-upper');
    const reqLower = document.getElementById('req-lower');
    const reqNumber = document.getElementById('req-number-special');
    
    if (newPasswordInput && strengthBars && strengthText) {
        newPasswordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            const hasLength = password.length >= 8;
            const hasUpper = /[A-Z]/.test(password);
            const hasLower = /[a-z]/.test(password);
            const hasNumber = /[0-9]/.test(password) && /[!@#$%^&*(),.?":{}|<>]/.test(password);;
            
            updateRequirement(reqLength, hasLength);
            updateRequirement(reqUpper, hasUpper);
            updateRequirement(reqLower, hasLower);
            updateRequirement(reqNumber, hasNumber);
            
            if (hasLength) strength++;
            if (hasUpper) strength++;
            if (hasLower) strength++;
            if (hasNumber) strength++;
            
            strengthBars.className = 'strength-bars';
            
            if (password.length === 0) {
                strengthText.textContent = 'Password Strength';
            } else if (strength === 1) {
                strengthBars.classList.add('weak');
                strengthText.textContent = 'Weak';
            } else if (strength === 2) {
                strengthBars.classList.add('medium');
                strengthText.textContent = 'Medium';
            } else if (strength === 3) {
                strengthBars.classList.add('strong');
                strengthText.textContent = 'Strong';
            } else if (strength === 4) {
                strengthBars.classList.add('very-strong');
                strengthText.textContent = 'Very Strong';
            }
        });
    }
    
    function updateRequirement(element, isValid) {
        if (element) {
            const icon = element.querySelector('i');
            if (isValid) {
                element.classList.add('valid');
                if (icon) icon.className = 'fas fa-check-circle';
            } else {
                element.classList.remove('valid');
                if (icon) icon.className = 'fas fa-circle';
            }
        }
    }
    
    // Auto-hide success alerts after 5 seconds
    const successAlerts = document.querySelectorAll('.alert-success');
    successAlerts.forEach(function(alert) {
        setTimeout(function() {
            if (alert) {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-15px)';
                setTimeout(function() {
                    if (alert.parentNode) {
                        alert.remove();
                    }
                }, 300);
            }
        }, 5000);
    });
});
</script>

<?php include 'includes/footer.php'; ?>