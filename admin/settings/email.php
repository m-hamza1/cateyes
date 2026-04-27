<?php
require_once '../includes/auth.php';
requirePermission('email_settings');
require_once INCLUDES_PATH . 'Settings.php';
$settings = new Settings($db);
$pageTitle = __('email_settings', 'Email Settings');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'test_email') {
    $testTo = trim($_POST['test_email_to'] ?? '');
    if (!empty($testTo) && filter_var($testTo, FILTER_VALIDATE_EMAIL)) {
        require_once INCLUDES_PATH . 'Email.php';
        $mailer = new Email($db);
        $testBody = $mailer->send(
            $testTo,
            'Test Email - ' . ($settings->get('site_name') ?: SITE_NAME),
            '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:30px;border:1px solid #e5e7eb;border-radius:12px;text-align:center;">
                <div style="width:64px;height:64px;background-color:#ecfdf5;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;"><span style="font-size:32px;">&#9989;</span></div>
                <h2 style="color:#1f2937;margin:0 0 12px;">Email Configuration Working!</h2>
                <p style="color:#6b7280;font-size:15px;line-height:1.6;">
                    This is a test email from <strong>' . htmlspecialchars($settings->get('site_name') ?: SITE_NAME) . '</strong>.<br>
                    Your SMTP settings are configured correctly and emails are being delivered.
                </p>
                <p style="color:#9ca3af;font-size:13px;margin-top:20px;">Sent on ' . date('F j, Y \a\t g:i A') . '</p>
            </div>'
        );
        if ($testBody) {
            $_SESSION['success'] = 'Test email sent successfully to ' . htmlspecialchars($testTo) . '!';
        } else {
            $_SESSION['error'] = 'Failed to send test email. Please check your SMTP settings.';
        }
    } else {
        $_SESSION['error'] = 'Please provide a valid email address.';
    }
    header('Location: email.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') !== 'test_email') {
    $settings->updateMultiple([
        'smtp_host' => sanitize($_POST['smtp_host']),
        'smtp_port' => sanitize($_POST['smtp_port']),
        'smtp_username' => sanitize($_POST['smtp_username']),
        'smtp_password' => sanitize($_POST['smtp_password']),
        'smtp_encryption' => sanitize($_POST['smtp_encryption']),
        'mail_from_address' => sanitize($_POST['mail_from_address']),
        'mail_from_name' => sanitize($_POST['mail_from_name'])
    ]);
    $_SESSION['success'] = __('settings_updated', 'Settings updated!');
    header('Location: email.php');
    exit;
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-envelope"></i> <?php echo __('email_settings', 'Email Settings'); ?></h1>
</div>

<ul class="nav nav-tabs mb-4">
    <li class="nav-item"><a class="nav-link" href="general.php"><?php echo __('general', 'General'); ?></a></li>
    <li class="nav-item"><a class="nav-link" href="appearance.php"><?php echo __('appearance', 'Appearance'); ?></a></li>
    <li class="nav-item"><a class="nav-link" href="languages.php"><?php echo __('languages', 'Languages'); ?></a></li>
    <li class="nav-item"><a class="nav-link" href="currencies.php"><?php echo __('currencies', 'Currencies'); ?></a></li>
    <li class="nav-item"><a class="nav-link active" href="email.php"><?php echo __('email', 'Email'); ?></a></li>
    <li class="nav-item"><a class="nav-link" href="payment.php"><?php echo __('payment', 'Payment'); ?></a></li>
    <li class="nav-item"><a class="nav-link" href="social.php"><?php echo __('social_media', 'Social'); ?></a></li>
</ul>

<?php if (isset($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST">
    <div class="card mb-4">
        <div class="card-header"><h5><?php echo __('smtp_configuration', 'SMTP Configuration'); ?></h5></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label><?php echo __('smtp_host', 'SMTP Host'); ?></label>
                    <input type="text" name="smtp_host" class="form-control"
                           value="<?php echo htmlspecialchars($settings->get('smtp_host')); ?>"
                           placeholder="smtp.gmail.com">
                </div>
                <div class="col-md-4 mb-3">
                    <label><?php echo __('smtp_port', 'Port'); ?></label>
                    <input type="number" name="smtp_port" class="form-control"
                           value="<?php echo $settings->get('smtp_port'); ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label><?php echo __('smtp_username', 'Username'); ?></label>
                    <input type="text" name="smtp_username" class="form-control"
                           value="<?php echo htmlspecialchars($settings->get('smtp_username')); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label><?php echo __('smtp_password', 'Password'); ?></label>
                    <input type="password" name="smtp_password" class="form-control"
                           value="<?php echo htmlspecialchars($settings->get('smtp_password')); ?>">
                </div>
            </div>
            <div class="mb-3">
                <label><?php echo __('smtp_encryption', 'Encryption'); ?></label>
                <select name="smtp_encryption" class="form-select">
                    <option value="tls" <?php echo $settings->get('smtp_encryption') == 'tls' ? 'selected' : ''; ?>>TLS</option>
                    <option value="ssl" <?php echo $settings->get('smtp_encryption') == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                </select>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header"><h5><?php echo __('mail_from', 'Mail From'); ?></h5></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label><?php echo __('from_address', 'From Address'); ?></label>
                    <input type="email" name="mail_from_address" class="form-control"
                           value="<?php echo htmlspecialchars($settings->get('mail_from_address')); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label><?php echo __('from_name', 'From Name'); ?></label>
                    <input type="text" name="mail_from_name" class="form-control"
                           value="<?php echo htmlspecialchars($settings->get('mail_from_name')); ?>">
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> <?php echo __('save_changes', 'Save Changes'); ?>
            </button>
            <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#testEmailModal">
                <i class="fas fa-paper-plane"></i> <?php echo __('send_test_email', 'Send Test Email'); ?>
            </button>
        </div>
    </div>
</form>

<!-- Test Email Modal -->
<div class="modal fade" id="testEmailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-paper-plane me-2"></i><?php echo __('send_test_email', 'Send Test Email'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="email.php">
                <div class="modal-body">
                    <p class="text-muted">Send a test email to verify your SMTP configuration is working correctly.</p>
                    <div class="mb-3">
                        <label class="form-label">Recipient Email *</label>
                        <input type="email" name="test_email_to" class="form-control" required
                               placeholder="your@email.com"
                               value="<?php echo htmlspecialchars($settings->get('admin_email', $settings->get('mail_from_address', ''))); ?>">
                    </div>
                    <input type="hidden" name="action" value="test_email">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-paper-plane me-1"></i> Send Test
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
