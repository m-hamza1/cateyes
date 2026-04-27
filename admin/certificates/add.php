<?php
require_once '../includes/auth.php';
$pageTitle = __('add_certificate', 'Add Certificate');

$certs_dir = UPLOADS_PATH . 'certificates/';
if (!file_exists($certs_dir)) {
    mkdir($certs_dir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $errors = [];
    if (empty($title)) {
        $errors[] = __('title_required', 'Title is required');
    }
    
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $image = 'cert_' . uniqid() . '.' . $ext;
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $certs_dir . $image)) {
                $errors[] = __('upload_failed', 'Upload failed');
                $image = '';
            }
        } else {
            $errors[] = __('invalid_image_format', 'Invalid image format (JPG, PNG, GIF, WEBP only)');
        }
    } else {
        $errors[] = __('image_required', 'Certificate image is required');
    }
    
    if (empty($errors)) {
        $db->insert('certificates', [
            'title' => $title,
            'title_ar' => trim($_POST['title_ar'] ?? ''),
            'description' => $description,
            'description_ar' => trim($_POST['description_ar'] ?? ''),
            'image' => $image,
            'sort_order' => $sort_order,
            'is_active' => $is_active
        ]);
        $_SESSION['success'] = __('certificate_added', 'Certificate added successfully.');
        header('Location: index.php');
        exit;
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <a href="index.php" class="text-decoration-none text-muted"><i class="fas fa-arrow-left"></i></a>
        <?php echo __('add_certificate', 'Add Certificate'); ?>
    </h1>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?php echo $e; ?></li><?php endforeach; ?></ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0"><?php echo __('certificate_details', 'Certificate Details'); ?></h5></div>
                <div class="card-body">
                    <!-- English Content -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="mb-3">English Content</h6>
                            <div class="mb-3">
                                <label class="form-label"><?php echo __('title', 'Title'); ?> *</label>
                                <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><?php echo __('description', 'Description'); ?></label>
                                <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Arabic Content -->
                    <div class="row">
                        <div class="col-12">
                            <h6 class="mb-3">Arabic Content</h6>
                            <div class="mb-3">
                                <label class="form-label">العنوان (بالعربية)</label>
                                <input type="text" name="title_ar" class="form-control" dir="rtl" value="<?php echo htmlspecialchars($_POST['title_ar'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">الوصف (بالعربية)</label>
                                <textarea name="description_ar" class="form-control" rows="3" dir="rtl"><?php echo htmlspecialchars($_POST['description_ar'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0"><?php echo __('certificate_image', 'Certificate Image'); ?></h5></div>
                <div class="card-body">
                    <input type="file" name="image" class="form-control" accept="image/*" required onchange="previewImage(this)">
                    <small class="text-muted">JPG, PNG, GIF, WEBP</small>
                    <div id="imagePreview" class="mt-3 text-center d-none">
                        <img src="" alt="Preview" class="img-fluid rounded">
                    </div>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0"><?php echo __('settings', 'Settings'); ?></h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('sort_order', 'Sort Order'); ?></label>
                        <input type="number" name="sort_order" class="form-control" value="0" min="0">
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                        <label class="form-check-label" for="is_active"><?php echo __('active', 'Active'); ?></label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save"></i> <?php echo __('save', 'Save'); ?></button>
        </div>
    </div>
</form>

<script>
function previewImage(input) {
    var p = document.getElementById('imagePreview');
    var img = p.querySelector('img');
    if (input.files && input.files[0]) {
        var r = new FileReader();
        r.onload = function(e) { img.src = e.target.result; p.classList.remove('d-none'); };
        r.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include '../includes/footer.php'; ?>
