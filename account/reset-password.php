<?php
require_once '../config.php';

$pageTitle = __('reset_password', 'Reset Password');

if ($user) {
    header('Location: ' . BASE_URL . 'account/dashboard.php');
    exit;
}

$token = $_GET['token'] ?? '';
$validToken = false;
$resetSuccess = false;
$tokenUser = null;

if (!empty($token)) {
    $tokenUser = $db->fetchOne(
        "SELECT * FROM users WHERE reset_token = :token AND reset_token_expire > NOW() AND is_active = 1",
        ['token' => $token]
    );
    $validToken = !empty($tokenUser);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    $errors = [];

    if (!verifyCSRFToken($csrfToken)) {
        $errors[] = __('csrf_error', 'Invalid security token. Please try again.');
    }

    if (empty($password)) {
        $errors[] = __('password_required', 'Password is required');
    } elseif (strlen($password) < 8) {
        $errors[] = __('password_min_length', 'Password must be at least 8 characters');
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
        $errors[] = __('password_strength', 'Password must contain uppercase, lowercase, and number');
    }

    if ($password !== $confirmPassword) {
        $errors[] = __('password_mismatch', 'Passwords do not match');
    }

    if (empty($errors)) {
        $db->update('users', [
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'reset_token' => null,
            'reset_token_expire' => null,
            'failed_attempts' => 0,
            'lockout_until' => null
        ], 'id = :id', ['id' => $tokenUser['id']]);

        $resetSuccess = true;
    } else {
        $_SESSION['flash_message'] = implode('<br>', $errors);
        $_SESSION['flash_type'] = 'danger';
    }
}

include '../includes/header.php';
?>

<nav aria-label="breadcrumb" class="bg-light py-3">
    <div class="container">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>"><?php echo __('home', 'Home'); ?></a></li>
            <li class="breadcrumb-item active"><?php echo __('reset_password', 'Reset Password'); ?></li>
        </ol>
    </div>
</nav>

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-body p-5">

                        <?php if ($resetSuccess): ?>
                            <div class="text-center">
                                <div class="mb-4">
                                    <i class="fas fa-check-circle fa-4x text-success"></i>
                                </div>
                                <h3><?php echo __('password_reset_success', 'Password Reset Successfully!'); ?></h3>
                                <p class="text-muted">
                                    <?php echo __('password_reset_success_msg', 'Your password has been changed. You can now log in with your new password.'); ?>
                                </p>
                                <a href="<?php echo BASE_URL; ?>account/login.php" class="btn btn-primary btn-lg mt-3">
                                    <i class="fas fa-sign-in-alt me-2"></i><?php echo __('login_now', 'Login Now'); ?>
                                </a>
                            </div>

                        <?php elseif (!$validToken): ?>
                            <div class="text-center">
                                <div class="mb-4">
                                    <i class="fas fa-exclamation-triangle fa-4x text-danger"></i>
                                </div>
                                <h3><?php echo __('invalid_token', 'Invalid or Expired Link'); ?></h3>
                                <p class="text-muted">
                                    <?php echo __('invalid_token_msg', 'This password reset link is invalid or has expired. Please request a new one.'); ?>
                                </p>
                                <a href="<?php echo BASE_URL; ?>account/forgot-password.php" class="btn btn-primary mt-3">
                                    <i class="fas fa-redo me-2"></i><?php echo __('request_new_link', 'Request New Link'); ?>
                                </a>
                            </div>

                        <?php else: ?>
                            <div class="text-center mb-4">
                                <div class="mb-3">
                                    <i class="fas fa-key fa-3x text-primary"></i>
                                </div>
                                <h2><?php echo __('set_new_password', 'Set New Password'); ?></h2>
                                <p class="text-muted"><?php echo __('set_new_password_description', 'Enter your new password below.'); ?></p>
                            </div>

                            <form method="POST">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                                <div class="mb-3">
                                    <label for="password" class="form-label"><?php echo __('new_password', 'New Password'); ?> *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="password" name="password" required
                                               placeholder="<?php echo __('enter_new_password', 'Enter new password'); ?>"
                                               minlength="8">
                                        <button class="btn btn-outline-secondary" type="button" onclick="toggleVis('password')">
                                            <i class="fas fa-eye" id="passwordIcon"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted"><?php echo __('password_requirements', 'Min 8 characters, with uppercase, lowercase, and number'); ?></small>
                                </div>

                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label"><?php echo __('confirm_password', 'Confirm Password'); ?> *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required
                                               placeholder="<?php echo __('confirm_new_password', 'Confirm new password'); ?>">
                                        <button class="btn btn-outline-secondary" type="button" onclick="toggleVis('confirm_password')">
                                            <i class="fas fa-eye" id="confirm_passwordIcon"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save me-2"></i><?php echo __('reset_password_btn', 'Reset Password'); ?>
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function toggleVis(fieldId) {
    const input = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + 'Icon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>

<?php include '../includes/footer.php'; ?>
