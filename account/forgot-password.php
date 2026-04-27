<?php
require_once '../config.php';
require_once INCLUDES_PATH . 'Email.php';

$pageTitle = __('forgot_password', 'Forgot Password');

if ($user) {
    header('Location: ' . BASE_URL . 'account/dashboard.php');
    exit;
}

$emailSent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $csrfToken = $_POST['csrf_token'] ?? '';

    $errors = [];

    if (!verifyCSRFToken($csrfToken)) {
        $errors[] = __('csrf_error', 'Invalid security token. Please try again.');
    }

    if (empty($email)) {
        $errors[] = __('email_required', 'Email is required');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = __('email_invalid', 'Invalid email format');
    }

    if (empty($errors)) {
        $userData = $db->fetchOne("SELECT * FROM users WHERE email = :email AND is_active = 1", ['email' => $email]);

        if ($userData) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $db->update('users', [
                'reset_token' => $token,
                'reset_token_expire' => $expires
            ], 'id = :id', ['id' => $userData['id']]);

            $mailer = new Email($db);
            $mailer->forgotPassword($userData, $token);
        }

        // Always show success to prevent email enumeration
        $emailSent = true;
    }

    if (!empty($errors)) {
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
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>account/login.php"><?php echo __('login', 'Login'); ?></a></li>
            <li class="breadcrumb-item active"><?php echo __('forgot_password', 'Forgot Password'); ?></li>
        </ol>
    </div>
</nav>

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-body p-5">
                        <?php if ($emailSent): ?>
                            <div class="text-center">
                                <div class="mb-4">
                                    <i class="fas fa-envelope-open-text fa-4x text-success"></i>
                                </div>
                                <h3><?php echo __('check_your_email', 'Check Your Email'); ?></h3>
                                <p class="text-muted">
                                    <?php echo __('reset_email_sent', 'If an account exists with that email address, we have sent a password reset link. Please check your inbox and spam folder.'); ?>
                                </p>
                                <a href="<?php echo BASE_URL; ?>account/login.php" class="btn btn-primary mt-3">
                                    <i class="fas fa-arrow-left me-2"></i><?php echo __('back_to_login', 'Back to Login'); ?>
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="text-center mb-4">
                                <div class="mb-3">
                                    <i class="fas fa-lock fa-3x text-primary"></i>
                                </div>
                                <h2><?php echo __('forgot_password', 'Forgot Password'); ?></h2>
                                <p class="text-muted"><?php echo __('forgot_password_description', 'Enter your email address and we\'ll send you a link to reset your password.'); ?></p>
                            </div>

                            <form method="POST">
                                <?php echo csrfField(); ?>
                                <div class="mb-4">
                                    <label for="email" class="form-label"><?php echo __('email', 'Email Address'); ?> *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        <input type="email" class="form-control" id="email" name="email" required
                                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                               placeholder="<?php echo __('enter_email', 'Enter your email'); ?>"
                                               autofocus>
                                    </div>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-paper-plane me-2"></i><?php echo __('send_reset_link', 'Send Reset Link'); ?>
                                    </button>
                                </div>
                            </form>

                            <div class="text-center mt-4">
                                <a href="<?php echo BASE_URL; ?>account/login.php" class="text-decoration-none">
                                    <i class="fas fa-arrow-left me-1"></i><?php echo __('back_to_login', 'Back to Login'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
