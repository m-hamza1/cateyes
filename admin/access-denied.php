<?php
/**
 * Shown when admin user has no permissions assigned to their role.
 * No requirePermission here - this page is accessible when redirecting from access denial.
 */
require_once 'includes/auth.php';

$pageTitle = 'Access Denied';
include 'includes/header.php';
include 'includes/sidebar.php';
?>
<div class="d-flex align-items-center justify-content-center min-vh-75 py-5">
    <div class="text-center">
        <i class="fas fa-lock fa-5x text-warning mb-4"></i>
        <h2 class="mb-3"><?php echo __('access_denied', 'Access Denied'); ?></h2>
        <p class="text-muted mb-4">
            <?php echo __('no_permissions_assigned', 'You do not have any permissions assigned to your role. Please contact your administrator to get access.'); ?>
        </p>
        <a href="<?php echo ADMIN_URL; ?>logout.php" class="btn btn-primary">
            <i class="fas fa-sign-out-alt me-1"></i> <?php echo __('log_out', 'Log Out'); ?>
        </a>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
