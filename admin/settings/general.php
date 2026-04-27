<?php
require_once '../includes/auth.php';
requirePermission('settings');
require_once INCLUDES_PATH . 'Settings.php';

$settings = new Settings($db);
$pageTitle = __('general_settings', 'General Settings');

$uploads_dir = UPLOADS_PATH . 'settings/';
if (!file_exists($uploads_dir)) {
    mkdir($uploads_dir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updates = [
        'site_name' => sanitize($_POST['site_name']),
        'site_tagline' => sanitize($_POST['site_tagline']),
        'site_description' => sanitize($_POST['site_description']),
        'contact_email' => sanitize($_POST['contact_email']),
        'contact_phone' => sanitize($_POST['contact_phone']),
        'contact_address' => sanitize($_POST['contact_address']),
        'timezone' => sanitize($_POST['timezone']),
        'date_format' => sanitize($_POST['date_format']),
        'time_format' => sanitize($_POST['time_format'])
    ];
    
    // Handle logo
    if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['site_logo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'svg'])) {
            $old_logo = $settings->get('site_logo');
            if ($old_logo && file_exists($uploads_dir . $old_logo)) {
                unlink($uploads_dir . $old_logo);
            }
            $logo = 'logo_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['site_logo']['tmp_name'], $uploads_dir . $logo);
            $updates['site_logo'] = $logo;
        }
    }
    
    // Handle favicon
    if (isset($_FILES['site_favicon']) && $_FILES['site_favicon']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['site_favicon']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['ico', 'png', 'jpg', 'jpeg'])) {
            $old_favicon = $settings->get('site_favicon');
            if ($old_favicon && file_exists($uploads_dir . $old_favicon)) {
                unlink($uploads_dir . $old_favicon);
            }
            $favicon = 'favicon_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['site_favicon']['tmp_name'], $uploads_dir . $favicon);
            $updates['site_favicon'] = $favicon;
        }
    }
    
    $settings->updateMultiple($updates);
    $_SESSION['success'] = __('settings_updated', 'Settings updated successfully!');
    header('Location: general.php');
    exit;
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-cog"></i> <?php echo __('general_settings', 'General Settings'); ?></h1>
</div>

<!-- Settings Navigation -->
<ul class="nav nav-tabs mb-4">
    <li class="nav-item"><a class="nav-link active" href="general.php"><?php echo __('general', 'General'); ?></a></li>
    <li class="nav-item"><a class="nav-link" href="appearance.php"><?php echo __('appearance', 'Appearance'); ?></a></li>
    <li class="nav-item"><a class="nav-link" href="languages.php"><?php echo __('languages', 'Languages'); ?></a></li>
    <li class="nav-item"><a class="nav-link" href="currencies.php"><?php echo __('currencies', 'Currencies'); ?></a></li>
    <li class="nav-item"><a class="nav-link" href="email.php"><?php echo __('email', 'Email'); ?></a></li>
    <li class="nav-item"><a class="nav-link" href="payment.php"><?php echo __('payment', 'Payment'); ?></a></li>
    <li class="nav-item"><a class="nav-link" href="social.php"><?php echo __('social_media', 'Social'); ?></a></li>
</ul>
<?php if (isset($_SESSION['success'])): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header"><h5><?php echo __('site_information', 'Site Information'); ?></h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label><?php echo __('site_name', 'Site Name'); ?> *</label>
                        <input type="text" name="site_name" class="form-control" required
                               value="<?php echo htmlspecialchars($settings->get('site_name')); ?>">
                    </div>
                    <div class="mb-3">
                        <label><?php echo __('site_tagline', 'Site Tagline'); ?></label>
                        <input type="text" name="site_tagline" class="form-control"
                               value="<?php echo htmlspecialchars($settings->get('site_tagline')); ?>">
                    </div>
                    <div class="mb-3">
                        <label><?php echo __('site_description', 'Site Description'); ?></label>
                        <textarea name="site_description" class="form-control" rows="4"><?php echo htmlspecialchars($settings->get('site_description')); ?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header"><h5><?php echo __('contact_information', 'Contact Information'); ?></h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label><?php echo __('contact_email', 'Contact Email'); ?></label>
                        <input type="email" name="contact_email" class="form-control"
                               value="<?php echo htmlspecialchars($settings->get('contact_email')); ?>">
                    </div>
                    <div class="mb-3">
                        <label><?php echo __('contact_phone', 'Contact Phone'); ?></label>
                        <input type="text" name="contact_phone" class="form-control"
                               value="<?php echo htmlspecialchars($settings->get('contact_phone')); ?>">
                    </div>
                    <div class="mb-3">
                        <label><?php echo __('contact_address', 'Contact Address'); ?></label>
                        <textarea name="contact_address" class="form-control" rows="3"><?php echo htmlspecialchars($settings->get('contact_address')); ?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header"><h5><?php echo __('regional_settings', 'Regional Settings'); ?></h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label><?php echo __('timezone', 'Timezone'); ?></label>
                        <select name="timezone" class="form-select">
                            <?php
                            $timezones = ['UTC', 'America/New_York', 'Europe/London', 'Asia/Dubai', 'Asia/Beirut'];
                            foreach ($timezones as $tz):
                            ?>
                            <option value="<?php echo $tz; ?>" <?php echo $settings->get('timezone') == $tz ? 'selected' : ''; ?>>
                                <?php echo $tz; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label><?php echo __('date_format', 'Date Format'); ?></label>
                            <select name="date_format" class="form-select">
                                <option value="Y-m-d" <?php echo $settings->get('date_format') == 'Y-m-d' ? 'selected' : ''; ?>>YYYY-MM-DD</option>
                                <option value="m/d/Y" <?php echo $settings->get('date_format') == 'm/d/Y' ? 'selected' : ''; ?>>MM/DD/YYYY</option>
                                <option value="d/m/Y" <?php echo $settings->get('date_format') == 'd/m/Y' ? 'selected' : ''; ?>>DD/MM/YYYY</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label><?php echo __('time_format', 'Time Format'); ?></label>
                            <select name="time_format" class="form-select">
                                <option value="H:i:s" <?php echo $settings->get('time_format') == 'H:i:s' ? 'selected' : ''; ?>>24 Hour</option>
                                <option value="h:i A" <?php echo $settings->get('time_format') == 'h:i A' ? 'selected' : ''; ?>>12 Hour</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><h5><?php echo __('site_logo', 'Site Logo'); ?></h5></div>
                <div class="card-body">
                    <?php if ($settings->get('site_logo')): ?>
                    <div class="text-center mb-3">
                        <img src="<?php echo UPLOADS_URL . 'settings/' . $settings->get('site_logo'); ?>" 
                             class="img-fluid" style="max-height: 100px;">
                    </div>
                    <?php endif; ?>
                    <input type="file" name="site_logo" class="form-control" accept="image/*">
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header"><h5><?php echo __('site_favicon', 'Site Favicon'); ?></h5></div>
                <div class="card-body">
                    <?php if ($settings->get('site_favicon')): ?>
                    <div class="text-center mb-3">
                        <img src="<?php echo UPLOADS_URL . 'settings/' . $settings->get('site_favicon'); ?>" 
                             class="img-fluid" style="max-height: 32px;">
                    </div>
                    <?php endif; ?>
                    <input type="file" name="site_favicon" class="form-control" accept=".ico,.png,.jpg">
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-save"></i> <?php echo __('save_changes', 'Save Changes'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<?php include '../includes/footer.php'; ?>
