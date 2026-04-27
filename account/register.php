<?php
require_once '../config.php';
$pageTitle = __('register', 'Register');

// Redirect if already logged in
if ($user) {
    header('Location: ' . BASE_URL . 'account/dashboard.php');
    exit;
}

// Process registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $newsletter = isset($_POST['newsletter']);
    $terms = isset($_POST['terms']);
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    $errors = [];
    
    // CSRF Check
    if (!verifyCSRFToken($csrfToken)) {
        $errors[] = __('csrf_error', 'Invalid security token. Please try again.');
    }
    
    // Validation
    if (empty($first_name)) {
        $errors[] = __('first_name_required', 'First name is required');
    }
    
    if (empty($last_name)) {
        $errors[] = __('last_name_required', 'Last name is required');
    }
    
    if (empty($email)) {
        $errors[] = __('email_required', 'Email is required');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = __('email_invalid', 'Invalid email format');
    } else {
        // Check if email already exists
        $existing_user = $db->fetchOne("SELECT id FROM users WHERE email = :email", ['email' => $email]);
        if ($existing_user) {
            $errors[] = __('email_exists', 'Email already registered');
        }
    }
    
    if (empty($password)) {
        $errors[] = __('password_required', 'Password is required');
    } elseif (strlen($password) < 8) {
        $errors[] = __('password_min_length', 'Password must be at least 8 characters');
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
        $errors[] = __('password_strength', 'Password must contain at least one uppercase letter, one lowercase letter, and one number');
    }
    
    if ($password !== $confirm_password) {
        $errors[] = __('password_mismatch', 'Passwords do not match');
    }
    
    if (!$terms) {
        $errors[] = __('terms_required', 'You must agree to the terms and conditions');
    }
    
    if (empty($errors)) {
        try {
            // Create user
            $user_data = [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'phone' => $phone,
                'role' => 'customer',
                'newsletter_subscribed' => $newsletter ? 1 : 0,
                'is_active' => 1,
                'email_verified' => 0,
                'verification_token' => bin2hex(random_bytes(32)),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $user_id = $db->insert('users', $user_data);
            
            // Send professional emails via Email class
            require_once INCLUDES_PATH . 'Email.php';
            $mailer = new Email($db);
            $mailer->emailVerification($user_id, $user_data['verification_token']);
            $mailer->welcome($user_id);
            
            // Add to newsletter if requested
            if ($newsletter) {
                $db->insert('newsletter_subscribers', [
                    'email' => $email,
                    'status' => 'subscribed',
                    'subscribed_at' => date('Y-m-d H:i:s')
                ]);
            }
            
            $_SESSION['flash_message'] = __('registration_success', 'Registration successful! Please check your email to verify your account.');
            $_SESSION['flash_type'] = 'success';
            
            header('Location: ' . BASE_URL . 'account/login.php');
            exit;
            
        } catch (Exception $e) {
            $errors[] = __('registration_error', 'Registration failed. Please try again.');
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['flash_message'] = implode('<br>', $errors);
        $_SESSION['flash_type'] = 'danger';
    }
}

include '../includes/header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="bg-light py-3">
    <div class="container">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>"><?php echo __('home', 'Home'); ?></a></li>
            <li class="breadcrumb-item active"><?php echo __('register', 'Register'); ?></li>
        </ol>
    </div>
</nav>

<!-- Register Section -->
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <h2><?php echo __('register', 'Create Account'); ?></h2>
                            <p class="text-muted"><?php echo __('register_description', 'Join us today and enjoy exclusive benefits and offers.'); ?></p>
                        </div>
                        
                        <form method="POST" action="<?php echo BASE_URL; ?>account/register.php">
                            <?php echo csrfField(); ?>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label"><?php echo __('first_name', 'First Name'); ?> *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" id="first_name" name="first_name" required
                                               value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>"
                                               placeholder="<?php echo __('enter_first_name', 'Enter your first name'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label"><?php echo __('last_name', 'Last Name'); ?> *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" id="last_name" name="last_name" required
                                               value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>"
                                               placeholder="<?php echo __('enter_last_name', 'Enter your last name'); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label"><?php echo __('email', 'Email'); ?> *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" required
                                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                           placeholder="<?php echo __('enter_email', 'Enter your email'); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label"><?php echo __('phone', 'Phone'); ?></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    <input type="tel" class="form-control" id="phone" name="phone"
                                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                                           placeholder="<?php echo __('enter_phone', 'Enter your phone number'); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label"><?php echo __('password', 'Password'); ?> *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="password" name="password" required
                                               placeholder="<?php echo __('enter_password', 'Enter your password'); ?>"
                                               onkeyup="checkPasswordStrength()">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                                            <i class="fas fa-eye" id="passwordToggle"></i>
                                        </button>
                                    </div>
                                    <div id="passwordStrength" class="mt-2"></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label"><?php echo __('confirm_password', 'Confirm Password'); ?> *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required
                                               placeholder="<?php echo __('confirm_password', 'Confirm your password'); ?>"
                                               onkeyup="checkPasswordMatch()">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                            <i class="fas fa-eye" id="confirmPasswordToggle"></i>
                                        </button>
                                    </div>
                                    <div id="passwordMatch" class="mt-2"></div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="newsletter" name="newsletter">
                                    <label class="form-check-label" for="newsletter">
                                        <?php echo __('subscribe_newsletter', 'Subscribe to our newsletter for exclusive offers and updates'); ?>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                                    <label class="form-check-label" for="terms">
                                        <?php echo __('agree_terms', 'I agree to the'); ?> <a href="<?php echo BASE_URL; ?>terms.php" target="_blank"><?php echo __('terms_conditions', 'Terms and Conditions'); ?></a> <?php echo __('and', 'and'); ?> <a href="<?php echo BASE_URL; ?>privacy.php" target="_blank"><?php echo __('privacy_policy', 'Privacy Policy'); ?></a>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <?php echo __('create_account', 'Create Account'); ?>
                                </button>
                            </div>
                        </form>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <p class="mb-0"><?php echo __('already_have_account', 'Already have an account?'); ?></p>
                            <a href="<?php echo BASE_URL; ?>account/login.php" class="btn btn-outline-primary">
                                <?php echo __('login', 'Login'); ?>
                            </a>
                        </div>
                        

                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>

<script>
// Toggle password visibility
function togglePassword(fieldId) {
    const passwordInput = document.getElementById(fieldId);
    const toggleIcon = document.getElementById(fieldId + 'Toggle');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

// Check password strength
function checkPasswordStrength() {
    const password = document.getElementById('password').value;
    const strengthDiv = document.getElementById('passwordStrength');
    
    if (password.length === 0) {
        strengthDiv.innerHTML = '';
        return;
    }
    
    let strength = 0;
    let feedback = [];
    
    if (password.length >= 8) strength++;
    else feedback.push('<?php echo __('password_length', 'At least 8 characters'); ?>');
    
    if (/[a-z]/.test(password)) strength++;
    else feedback.push('<?php echo __('password_lowercase', 'One lowercase letter'); ?>');
    
    if (/[A-Z]/.test(password)) strength++;
    else feedback.push('<?php echo __('password_uppercase', 'One uppercase letter'); ?>');
    
    if (/\d/.test(password)) strength++;
    else feedback.push('<?php echo __('password_number', 'One number'); ?>');
    
    if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength++;
    
    const strengthText = ['<?php echo __('very_weak', 'Very Weak'); ?>', '<?php echo __('weak', 'Weak'); ?>', '<?php echo __('fair', 'Fair'); ?>', '<?php echo __('good', 'Good'); ?>', '<?php echo __('strong', 'Strong'); ?>'][strength];
    const strengthColor = ['danger', 'warning', 'info', 'success', 'success'][strength];
    
    strengthDiv.innerHTML = `
        <div class="progress" style="height: 5px;">
            <div class="progress-bar bg-${strengthColor}" style="width: ${(strength + 1) * 20}%"></div>
        </div>
        <small class="text-${strengthColor}">${strengthText}</small>
        ${feedback.length > 0 ? '<small class="text-muted d-block">' + feedback.join(', ') + '</small>' : ''}
    `;
}

// Check password match
function checkPasswordMatch() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const matchDiv = document.getElementById('passwordMatch');
    
    if (confirmPassword.length === 0) {
        matchDiv.innerHTML = '';
        return;
    }
    
    if (password === confirmPassword) {
        matchDiv.innerHTML = '<small class="text-success"><i class="fas fa-check"></i> <?php echo __('passwords_match', 'Passwords match'); ?></small>';
    } else {
        matchDiv.innerHTML = '<small class="text-danger"><i class="fas fa-times"></i> <?php echo __('passwords_not_match', 'Passwords do not match'); ?></small>';
    }
}



// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('<?php echo __('password_mismatch_alert', 'Passwords do not match!'); ?>');
    }
});
</script>
