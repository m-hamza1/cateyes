<?php
require_once '../config.php';
$pageTitle = __('profile', 'Profile');

// Redirect if not logged in
if (!$user) {
    $_SESSION['flash_message'] = __('login_required', 'Please login to access your profile');
    $_SESSION['flash_type'] = 'warning';
    header('Location: ' . BASE_URL . 'account/login.php?redirect=' . urlencode(BASE_URL . 'account/profile.php'));
    exit;
}

// Process profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $newsletter = isset($_POST['newsletter']);
    
    $errors = [];
    
    // Validation
    if (empty($first_name)) {
        $errors[] = __('first_name_required', 'First name is required');
    }
    
    if (empty($last_name)) {
        $errors[] = __('last_name_required', 'Last name is required');
    }
    
    // Password change validation
    if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
        if (empty($current_password)) {
            $errors[] = __('current_password_required', 'Current password is required');
        }
        
        if (empty($new_password)) {
            $errors[] = __('new_password_required', 'New password is required');
        }
        
        if (strlen($new_password) < 8) {
            $errors[] = __('password_min_length', 'Password must be at least 8 characters');
        }
        
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $new_password)) {
            $errors[] = __('password_strength', 'Password must contain at least one uppercase letter, one lowercase letter, and one number');
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = __('password_mismatch', 'Passwords do not match');
        }
        
        // Verify current password
        if (empty($errors) && !password_verify($current_password, $user['password'])) {
            $errors[] = __('current_password_incorrect', 'Current password is incorrect');
        }
    }
    
    if (empty($errors)) {
        try {
            // Update user data
            $update_data = [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'phone' => $phone,
                'company' => $company,
                'newsletter_subscribed' => $newsletter ? 1 : 0,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Update password if provided
            if (!empty($new_password)) {
                $update_data['password'] = password_hash($new_password, PASSWORD_DEFAULT);
            }
            
            $db->update('users', $update_data, 'id = :id', ['id' => $user['id']]);
            
            // Update newsletter subscription
            if ($newsletter) {
                $db->insert('newsletter_subscribers', [
                    'email' => $user['email'],
                    'status' => 'subscribed',
                    'subscribed_at' => date('Y-m-d H:i:s')
                ], true); // Ignore duplicate
            } else {
                $db->update('newsletter_subscribers', [
                    'status' => 'unsubscribed',
                    'unsubscribed_at' => date('Y-m-d H:i:s')
                ], 'email = :email', ['email' => $user['email']]);
            }
            
            $_SESSION['flash_message'] = __('profile_updated', 'Profile updated successfully!');
            $_SESSION['flash_type'] = 'success';
            
            // Refresh user data
            $user = $db->fetchOne("SELECT * FROM users WHERE id = :id", ['id' => $user['id']]);
            
            header('Location: ' . BASE_URL . 'account/profile.php');
            exit;
            
        } catch (Exception $e) {
            $errors[] = __('update_error', 'Update failed. Please try again.');
        }
    }
}

include '../includes/header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="bg-light py-3">
    <div class="container">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>"><?php echo __('home', 'Home'); ?></a></li>
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>account/dashboard.php"><?php echo __('dashboard', 'Dashboard'); ?></a></li>
            <li class="breadcrumb-item active"><?php echo __('profile', 'Profile'); ?></li>
        </ol>
    </div>
</nav>

<!-- Main content -->
<section class="content py-5">
    <div class="container">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="card shadow-sm mb-4">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-user-circle fa-5x text-primary"></i>
                        </div>
                        <h5 class="card-title"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                        <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                        <hr>
                        <div class="text-start">
                            <small class="text-muted d-block mb-1">
                                <i class="fas fa-calendar me-2"></i>
                                <?php echo __('member_since', 'Member since'); ?>: <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                            </small>
                            <?php if ($user['last_login']): ?>
                            <small class="text-muted d-block">
                                <i class="fas fa-clock me-2"></i>
                                <?php echo __('last_login', 'Last login'); ?>: <?php echo date('M d, Y H:i', strtotime($user['last_login'])); ?>
                            </small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title mb-3"><?php echo __('quick_links', 'Quick Links'); ?></h6>
                        <div class="list-group list-group-flush">
                            <a href="<?php echo BASE_URL; ?>account/dashboard.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-tachometer-alt me-2"></i> <?php echo __('dashboard', 'Dashboard'); ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>account/profile.php" class="list-group-item list-group-item-action active">
                                <i class="fas fa-user me-2"></i> <?php echo __('profile', 'Profile'); ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>account/mybookings.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-calendar-check me-2"></i> <?php echo __('my_bookings', 'My Bookings'); ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>account/orders.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-shopping-bag me-2"></i> <?php echo __('orders', 'Orders'); ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>account/wishlist.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-heart me-2"></i> <?php echo __('wishlist', 'Wishlist'); ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>account/addresses.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-map-marker-alt me-2"></i> <?php echo __('addresses', 'Addresses'); ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>account/logout.php" class="list-group-item list-group-item-action text-danger">
                                <i class="fas fa-sign-out-alt me-2"></i> <?php echo __('logout', 'Logout'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-user-edit me-2"></i>
                            <?php echo __('edit_profile', 'Edit Profile'); ?>
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="<?php echo BASE_URL; ?>account/profile.php">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label"><?php echo __('first_name', 'First Name'); ?> *</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required
                                           value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : htmlspecialchars($user['first_name']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label"><?php echo __('last_name', 'Last Name'); ?> *</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required
                                           value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : htmlspecialchars($user['last_name']); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label"><?php echo __('email', 'Email'); ?></label>
                                    <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                                    <small class="text-muted"><?php echo __('email_readonly', 'Email cannot be changed here'); ?></small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label"><?php echo __('phone', 'Phone'); ?></label>
                                    <input type="tel" class="form-control" id="phone" name="phone"
                                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : htmlspecialchars($user['phone'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="company" class="form-label"><?php echo __('company', 'Company'); ?></label>
                                <input type="text" class="form-control" id="company" name="company"
                                       value="<?php echo isset($_POST['company']) ? htmlspecialchars($_POST['company']) : htmlspecialchars($user['company'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="newsletter" name="newsletter" 
                                           <?php echo ($user['newsletter_subscribed'] || (isset($_POST['newsletter']) && $_POST['newsletter'])) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="newsletter">
                                        <?php echo __('newsletter_subscribe', 'Subscribe to newsletter for updates and offers'); ?>
                                    </label>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <h5 class="mb-3"><?php echo __('change_password', 'Change Password'); ?></h5>
                            <p class="text-muted mb-3"><?php echo __('password_change_note', 'Leave blank if you don\'t want to change your password'); ?></p>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="current_password" class="form-label"><?php echo __('current_password', 'Current Password'); ?></label>
                                    <input type="password" class="form-control" id="current_password" name="current_password">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="new_password" class="form-label"><?php echo __('new_password', 'New Password'); ?></label>
                                    <input type="password" class="form-control" id="new_password" name="new_password">
                                    <div class="progress mt-2" style="height: 4px;">
                                        <div class="progress-bar" id="password-strength" role="progressbar" style="width: 0%;"></div>
                                    </div>
                                    <small class="text-muted" id="password-feedback"></small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="confirm_password" class="form-label"><?php echo __('confirm_password', 'Confirm New Password'); ?></label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                    <div class="mt-2">
                                        <small class="text-muted" id="match-feedback"></small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end">
                                <a href="<?php echo BASE_URL; ?>account/dashboard.php" class="btn btn-secondary me-2">
                                    <?php echo __('cancel', 'Cancel'); ?>
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>
                                    <?php echo __('save_changes', 'Save Changes'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    const strengthBar = document.getElementById('password-strength');
    const feedback = document.getElementById('password-feedback');
    const matchFeedback = document.getElementById('match-feedback');
    
    // Password strength checker
    newPassword.addEventListener('input', function() {
        const password = this.value;
        let strength = 0;
        let message = '';
        
        if (password.length >= 8) strength++;
        if (password.match(/[a-z]/)) strength++;
        if (password.match(/[A-Z]/)) strength++;
        if (password.match(/[0-9]/)) strength++;
        if (password.match(/[^a-zA-Z0-9]/)) strength++;
        
        const strengthPercent = (strength / 5) * 100;
        strengthBar.style.width = strengthPercent + '%';
        
        if (strength <= 2) {
            strengthBar.className = 'progress-bar bg-danger';
            message = '<?php echo __('password_weak', 'Weak password'); ?>';
        } else if (strength <= 3) {
            strengthBar.className = 'progress-bar bg-warning';
            message = '<?php echo __('password_medium', 'Medium password'); ?>';
        } else {
            strengthBar.className = 'progress-bar bg-success';
            message = '<?php echo __('password_strong', 'Strong password'); ?>';
        }
        
        feedback.textContent = message;
        
        // Check match
        checkPasswordMatch();
    });
    
    // Password match checker
    confirmPassword.addEventListener('input', checkPasswordMatch);
    
    function checkPasswordMatch() {
        const match = newPassword.value === confirmPassword.value;
        
        if (confirmPassword.value === '') {
            matchFeedback.textContent = '';
            matchFeedback.className = 'text-muted';
        } else if (match) {
            matchFeedback.textContent = '<?php echo __('passwords_match', 'Passwords match'); ?>';
            matchFeedback.className = 'text-success';
        } else {
            matchFeedback.textContent = '<?php echo __('passwords_not_match', 'Passwords do not match'); ?>';
            matchFeedback.className = 'text-danger';
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?>
