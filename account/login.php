<?php
require_once '../config.php';
$pageTitle = __('login', 'Login');

// Handle language switching
if (isset($_GET['lang']) && !empty($_GET['lang'])) {
    $lang->changeLanguage($_GET['lang']);
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// Handle currency switching
if (isset($_GET['currency']) && !empty($_GET['currency'])) {
    $currency->changeCurrency($_GET['currency']);
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// Redirect if already logged in
if ($user) {
    header('Location: ' . BASE_URL . 'account/dashboard.php');
    exit;
}

// Get redirect URL
$redirect_url = isset($_GET['redirect']) ? $_GET['redirect'] : BASE_URL . 'account/dashboard.php';

// Process login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    // Honeypot check
    $honeypot = $_POST['username_hp'] ?? '';
    
    $errors = [];
    
    if (!empty($honeypot)) {
        // Simple bot detection
        sleep(2); // Slow down the bot
        $errors[] = __('bot_detected', 'Bot detected.');
    }
    
    // CSRF Check
    if (!verifyCSRFToken($csrfToken)) {
        $errors[] = __('csrf_error', 'Invalid security token. Please try again.');
    }
    
    if (empty($email)) {
        $errors[] = __('email_required', 'Email is required');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = __('email_invalid', 'Invalid email format');
    }
    
    if (empty($password)) {
        $errors[] = __('password_required', 'Password is required');
    }
    
    if (empty($errors)) {
        try {
            // Find user by email
            $user_data = $db->fetchOne("
                SELECT * FROM users 
                WHERE email = :email AND is_active = 1
            ", ['email' => $email]);
            
            if ($user_data) {
                // Check if account is locked
                if ($user_data['lockout_until'] && strtotime($user_data['lockout_until']) > time()) {
                    $lockout_time = ceil((strtotime($user_data['lockout_until']) - time()) / 60);
                    $errors[] = sprintf(__('account_locked', 'Security Alert: Too many failed attempts. Account is temporarily locked. Please try again after %s minutes.'), $lockout_time);
                } else {
                    if (password_verify($password, $user_data['password'])) {
                        // Reset failed attempts on success
                        $db->update('users', [
                            'failed_attempts' => 0,
                            'lockout_until' => null,
                            'last_login' => date('Y-m-d H:i:s'),
                            'last_login_ip' => $_SERVER['REMOTE_ADDR']
                        ], 'id = :id', ['id' => $user_data['id']]);

                        // Login successful
                        $_SESSION['user_id'] = $user_data['id'];
                        $_SESSION['user_email'] = $user_data['email'];
                        $_SESSION['user_role'] = $user_data['role'];
                        
                        // If user is admin, log them into admin panel as well
                        if ($user_data['role'] === 'admin') {
                            $_SESSION['admin_id'] = $user_data['id'];
                            $_SESSION['admin_role'] = $user_data['role'];
                            $_SESSION['admin_name'] = $user_data['first_name'] . ' ' . $user_data['last_name'];
                        }
                        
                        // Merge session cart with database cart
                        if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
                            $cart = new Cart($db);
                            $dbCart = $cart->getItems();
                            
                            // Merge session cart with database cart
                            foreach ($_SESSION['cart'] as $key => $sessionItem) {
                                $found = false;
                                foreach ($dbCart as $dbItem) {
                                    if ($dbItem['product_id'] == $sessionItem['product_id']) {
                                        // Update quantity if product exists in both carts
                                        $cart->update($key, $dbItem['quantity'] + $sessionItem['quantity']);
                                        $found = true;
                                        break;
                                    }
                                }
                                
                                if (!$found) {
                                    // Add session item to cart if not in database cart
                                    $cart->add($sessionItem['product_id'], $sessionItem['quantity']);
                                }
                            }
                        }
                        
                        // Remember me functionality
                        if ($remember) {
                            $token = bin2hex(random_bytes(32));
                            $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                            
                            // Save remember token
                            $db->insert('remember_tokens', [
                                'user_id' => $user_data['id'],
                                'token' => $token,
                                'expires_at' => $expires,
                                'created_at' => date('Y-m-d H:i:s')
                            ]);
                            
                            setcookie('remember_token', $token, strtotime('+30 days'), '/', '', false, true);
                        }
                        
                        $_SESSION['flash_message'] = __('login_success', 'Login successful! Welcome back.');
                        $_SESSION['flash_type'] = 'success';
                        
                        header('Location: ' . $redirect_url);
                        exit;
                    } else {
                        // Password incorrect - Increment failed attempts
                        $failed_attempts = $user_data['failed_attempts'] + 1;
                        $lockout_until = null;
                        
                        if ($failed_attempts >= 10) {
                            $lockout_until = date('Y-m-d H:i:s', strtotime('+2 hours'));
                            $errors[] = __('account_locked_severe', 'Extreme security risk detected. Account locked for 2 hours.');
                        } elseif ($failed_attempts >= 5) {
                            $lockout_until = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                            $errors[] = __('account_locked_now', 'Too many failed attempts. Account locked for 15 minutes for your protection.');
                        } else {
                            $errors[] = sprintf(__('login_failed_attempts', 'Invalid email or password. %d attempts remaining before temporary lockout.'), 5 - $failed_attempts);
                        }
                        
                        $db->update('users', [
                            'failed_attempts' => $failed_attempts,
                            'lockout_until' => $lockout_until,
                            'last_failed_login' => date('Y-m-d H:i:s'),
                            'last_failed_ip' => $_SERVER['REMOTE_ADDR']
                        ], 'id = :id', ['id' => $user_data['id']]);
                    }
                }
            } else {
                $errors[] = __('login_failed', 'Invalid email or password');
            }
            
        } catch (Exception $e) {
            $errors[] = __('login_error', 'Login failed. Please try again.');
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
            <li class="breadcrumb-item active"><?php echo __('login', 'Login'); ?></li>
        </ol>
    </div>
</nav>

<!-- Login Section -->
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <h2><?php echo __('login', 'Login'); ?></h2>
                            <p class="text-muted"><?php echo __('login_description', 'Welcome back! Please login to your account.'); ?></p>
                        </div>
                        
                        <form method="POST" action="<?php echo BASE_URL; ?>account/login.php?redirect=<?php echo urlencode($redirect_url); ?>">
                            <?php echo csrfField(); ?>
                            <!-- Honeypot -->
                            <div style="display:none;">
                                <input type="text" name="username_hp" value="">
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
                                <label for="password" class="form-label"><?php echo __('password', 'Password'); ?> *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required
                                           placeholder="<?php echo __('enter_password', 'Enter your password'); ?>">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                                        <i class="fas fa-eye" id="passwordToggle"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">
                                    <?php echo __('remember_me', 'Remember me'); ?>
                                </label>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <?php echo __('login', 'Login'); ?>
                                </button>
                            </div>
                        </form>
                        
                        <div class="mt-4 text-center">
                            <a href="<?php echo BASE_URL; ?>account/forgot-password.php" class="text-decoration-none">
                                <?php echo __('forgot_password', 'Forgot your password?'); ?>
                            </a>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <p class="mb-0"><?php echo __('no_account', "Don't have an account?"); ?></p>
                            <a href="<?php echo BASE_URL; ?>account/register.php" class="btn btn-outline-primary">
                                <?php echo __('register_now', 'Register Now'); ?>
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
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const passwordToggle = document.getElementById('passwordToggle');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        passwordToggle.classList.remove('fa-eye');
        passwordToggle.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        passwordToggle.classList.remove('fa-eye-slash');
        passwordToggle.classList.add('fa-eye');
    }
}



// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    
    if (!email || !password) {
        e.preventDefault();
        alert('<?php echo __('fill_all_fields', 'Please fill in all required fields.'); ?>');
    }
});

// Auto-focus on email field
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('email').focus();
});
</script>
