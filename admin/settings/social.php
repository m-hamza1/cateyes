<?php
require_once '../includes/auth.php';
requirePermission('social_settings');
require_once INCLUDES_PATH . 'Settings.php';
$settings = new Settings($db);
$pageTitle = __('social_media_settings', 'Social Media');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings->updateMultiple([
        'social_facebook' => sanitize($_POST['social_facebook']),
        'social_twitter' => sanitize($_POST['social_twitter']),
        'social_instagram' => sanitize($_POST['social_instagram']),
        'social_youtube' => sanitize($_POST['social_youtube']),
        'social_linkedin' => sanitize($_POST['social_linkedin'])
    ]);
    $_SESSION['success'] = __('settings_updated', 'Settings updated!');
    header('Location: social.php');
    exit;
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-share-alt"></i> <?php echo __('social_media_settings', 'Social Media'); ?></h1>
</div>

<ul class="nav nav-tabs mb-4">
    <li class="nav-item"><a class="nav-link" href="general.php"><?php echo __('general', 'General'); ?></a></li>
    <li class="nav-item"><a class="nav-link" href="appearance.php"><?php echo __('appearance', 'Appearance'); ?></a></li>
    <li class="nav-item"><a class="nav-link" href="languages.php"><?php echo __('languages', 'Languages'); ?></a></li>
    <li class="nav-item"><a class="nav-link" href="currencies.php"><?php echo __('currencies', 'Currencies'); ?></a></li>
    <li class="nav-item"><a class="nav-link" href="email.php"><?php echo __('email', 'Email'); ?></a></li>
    <li class="nav-item"><a class="nav-link" href="payment.php"><?php echo __('payment', 'Payment'); ?></a></li>
    <li class="nav-item"><a class="nav-link active" href="social.php"><?php echo __('social_media', 'Social'); ?></a></li>
</ul>

<?php if (isset($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible fade show">
    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST">
    <div class="card mb-4">
        <div class="card-header"><h5><?php echo __('social_links', 'Social Media Links'); ?></h5></div>
        <div class="card-body">
            <?php
            $socials = [
                'social_facebook' => ['icon' => 'fab fa-facebook', 'label' => 'Facebook', 'placeholder' => 'https://facebook.com/yourpage'],
                'social_twitter' => ['icon' => 'fab fa-snapchat', 'label' => 'Snap Chat', 'placeholder' => 'https://twitter.com/yourhandle'],
                'social_instagram' => ['icon' => 'fab fa-instagram', 'label' => 'Instagram', 'placeholder' => 'https://instagram.com/yourhandle'],
                'social_youtube' => ['icon' => 'fab fa-youtube', 'label' => 'YouTube', 'placeholder' => 'https://youtube.com/yourchannel'],
                'social_linkedin' => ['icon' => 'fab fa-tiktok', 'label' => 'Tiktok', 'placeholder' => 'https://linkedin.com/company/yourcompany']
            ];
            foreach ($socials as $key => $data):
            ?>
            <div class="mb-3">
                <label><i class="<?php echo $data['icon']; ?>"></i> <?php echo $data['label']; ?></label>
                <input type="url" name="<?php echo $key; ?>" class="form-control"
                       value="<?php echo htmlspecialchars($settings->get($key)); ?>"
                       placeholder="<?php echo $data['placeholder']; ?>">
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> <?php echo __('save_changes', 'Save Changes'); ?>
            </button>
        </div>
    </div>
</form>

<?php include '../includes/footer.php'; ?>
