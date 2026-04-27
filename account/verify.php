<?php
require_once '../config.php';

$pageTitle = __('verify_email', 'Verify Email');

$token = $_GET['token'] ?? '';
$verified = false;
$alreadyVerified = false;
$invalidToken = false;

if (!empty($token)) {
    $tokenUser = $db->fetchOne(
        "SELECT * FROM users WHERE verification_token = :token AND is_active = 1",
        ['token' => $token]
    );

    if ($tokenUser) {
        if ($tokenUser['email_verified'] == 1) {
            $alreadyVerified = true;
        } else {
            $db->update('users', [
                'email_verified' => 1,
                'verification_token' => null,
                'email_verified_at' => date('Y-m-d H:i:s')
            ], 'id = :id', ['id' => $tokenUser['id']]);
            $verified = true;
        }
    } else {
        $invalidToken = true;
    }
} else {
    $invalidToken = true;
}

include '../includes/header.php';
?>

<nav aria-label="breadcrumb" class="bg-light py-3">
    <div class="container">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>"><?php echo __('home', 'Home'); ?></a></li>
            <li class="breadcrumb-item active"><?php echo __('verify_email', 'Email Verification'); ?></li>
        </ol>
    </div>
</nav>

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-body p-5 text-center">

                        <?php if ($verified): ?>
                            <div class="mb-4" style="animation: scaleIn 0.5s ease-out;">
                                <i class="fas fa-check-circle fa-5x text-success"></i>
                            </div>
                            <h2 class="mb-3"><?php echo __('email_verified', 'Email Verified!'); ?></h2>
                            <p class="text-muted mb-4">
                                <?php echo __('email_verified_msg', 'Your email has been verified successfully. You can now enjoy all features of your account.'); ?>
                            </p>
                            <div class="d-flex gap-2 justify-content-center">
                                <a href="<?php echo BASE_URL; ?>account/login.php" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sign-in-alt me-2"></i><?php echo __('login_now', 'Login Now'); ?>
                                </a>
                                <a href="<?php echo BASE_URL; ?>" class="btn btn-outline-primary btn-lg">
                                    <i class="fas fa-shopping-bag me-2"></i><?php echo __('start_shopping', 'Start Shopping'); ?>
                                </a>
                            </div>

                        <?php elseif ($alreadyVerified): ?>
                            <div class="mb-4">
                                <i class="fas fa-info-circle fa-5x text-info"></i>
                            </div>
                            <h2 class="mb-3"><?php echo __('already_verified', 'Already Verified'); ?></h2>
                            <p class="text-muted mb-4">
                                <?php echo __('already_verified_msg', 'Your email address has already been verified. You can log in to your account.'); ?>
                            </p>
                            <a href="<?php echo BASE_URL; ?>account/login.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i><?php echo __('go_to_login', 'Go to Login'); ?>
                            </a>

                        <?php else: ?>
                            <div class="mb-4">
                                <i class="fas fa-exclamation-triangle fa-5x text-danger"></i>
                            </div>
                            <h2 class="mb-3"><?php echo __('verification_failed', 'Verification Failed'); ?></h2>
                            <p class="text-muted mb-4">
                                <?php echo __('verification_failed_msg', 'This verification link is invalid or has already been used. Please try registering again or contact support.'); ?>
                            </p>
                            <div class="d-flex gap-2 justify-content-center">
                                <a href="<?php echo BASE_URL; ?>account/register.php" class="btn btn-primary">
                                    <i class="fas fa-user-plus me-2"></i><?php echo __('register_again', 'Register Again'); ?>
                                </a>
                                <a href="<?php echo BASE_URL; ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-home me-2"></i><?php echo __('home', 'Home'); ?>
                                </a>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
@keyframes scaleIn {
    0% { transform: scale(0); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}
</style>

<?php include '../includes/footer.php'; ?>
