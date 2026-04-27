<?php
require_once '../includes/auth.php';
requirePermission('brands');
$pageTitle = __('add_new_brand', 'Add New Brand');

// Ensure brands directory exists
$brands_dir = UPLOADS_PATH . 'brands/';
if (!file_exists($brands_dir)) {
    mkdir($brands_dir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $slug = sanitize($_POST['slug']);
    $description = sanitize($_POST['description']);
    $website = sanitize($_POST['website']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $sort_order = (int)$_POST['sort_order'];
    $meta_title = sanitize($_POST['meta_title']);
    $meta_description = sanitize($_POST['meta_description']);
    
    $errors = [];
    
    if (empty($name)) {
        $errors[] = __('name_required', 'Brand name is required');
    }
    if (empty($slug)) {
        $slug = strtolower(str_replace(' ', '-', $name));
    }
    
    if ($db->fetchOne("SELECT id FROM brands WHERE slug = :slug", ['slug' => $slug])) {
        $errors[] = __('slug_exists', 'Slug already exists');
    }
    
    // Handle logo upload
    $logo = '';
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $logo = uniqid('brand_') . '.' . $ext;
            $target_path = $brands_dir . $logo;
            
            if (!move_uploaded_file($_FILES['logo']['tmp_name'], $target_path)) {
                $errors[] = __('upload_failed', 'Failed to upload logo');
                $logo = '';
            }
        } else {
            $errors[] = __('invalid_image_format', 'Invalid image format');
        }
    }
    
    if (empty($errors)) {
        try {
            $db->insert('brands', [
                'name' => $name,
                'slug' => $slug,
                'logo' => $logo,
                'description' => $description,
                'meta_title' => $meta_title,
                'meta_description' => $meta_description,
                'is_active' => $is_active,
                'sort_order' => $sort_order
            ]);
            
            $_SESSION['success'] = __('brand_added_successfully', 'Brand added successfully!');
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            if ($logo && file_exists($brands_dir . $logo)) {
                unlink($brands_dir . $logo);
            }
            $errors[] = __('error_adding', 'Error adding brand');
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <a href="index.php" class="text-decoration-none text-muted"><i class="fas fa-arrow-left"></i></a>
        <?php echo __('add_new_brand', 'Add New Brand'); ?>
    </h1>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <strong><i class="fas fa-exclamation-triangle"></i> <?php echo __('error', 'Error'); ?>:</strong>
    <ul class="mb-0 mt-2">
        <?php foreach ($errors as $error): ?>
        <li><?php echo $error; ?></li>
        <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> <?php echo __('basic_information', 'Basic Information'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('brand_name', 'Brand Name'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                               onkeyup="generateSlug(this.value)"
                               placeholder="e.g., Nike">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('slug', 'Slug'); ?></label>
                        <input type="text" name="slug" id="slug" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['slug'] ?? ''); ?>"
                               placeholder="nike">
                        <small class="text-muted"><?php echo __('slug_help', 'URL-friendly version. Auto-generated from name.'); ?></small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('description', 'Description'); ?></label>
                        <textarea name="description" class="form-control" rows="4"
                                  placeholder="Brief description of the brand..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('website', 'Website'); ?></label>
                        <input type="url" name="website" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['website'] ?? ''); ?>"
                               placeholder="https://example.com">
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-search"></i> <?php echo __('seo_information', 'SEO Information'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('meta_title', 'Meta Title'); ?></label>
                        <input type="text" name="meta_title" class="form-control" maxlength="60"
                               value="<?php echo htmlspecialchars($_POST['meta_title'] ?? ''); ?>">
                        <small class="text-muted"><?php echo __('recommended_60_chars', 'Recommended: 60 characters or less'); ?></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('meta_description', 'Meta Description'); ?></label>
                        <textarea name="meta_description" class="form-control" rows="3" maxlength="160"><?php echo htmlspecialchars($_POST['meta_description'] ?? ''); ?></textarea>
                        <small class="text-muted"><?php echo __('recommended_160_chars', 'Recommended: 160 characters or less'); ?></small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-image"></i> <?php echo __('brand_logo', 'Brand Logo'); ?></h5>
                </div>
                <div class="card-body">
                    <input type="file" name="logo" class="form-control" accept="image/*" onchange="previewImage(this)">
                    <small class="text-muted d-block mt-1">
                        <i class="fas fa-info-circle"></i> <?php echo __('allowed_formats', 'Allowed: JPG, PNG, GIF, WEBP'); ?>
                    </small>
                    <div id="imagePreview" class="text-center mt-3 d-none">
                        <img src="" alt="Preview" class="img-fluid rounded">
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-cog"></i> <?php echo __('settings', 'Settings'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('sort_order', 'Sort Order'); ?></label>
                        <input type="number" name="sort_order" class="form-control" value="<?php echo htmlspecialchars($_POST['sort_order'] ?? '0'); ?>" min="0">
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?php echo (!isset($_POST['is_active']) || $_POST['is_active']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_active">
                            <?php echo __('active', 'Active'); ?>
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary w-100 mb-2">
                        <i class="fas fa-save"></i> <?php echo __('save', 'Save Brand'); ?>
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-times"></i> <?php echo __('cancel', 'Cancel'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
function generateSlug(str) {
    document.getElementById('slug').value = str.toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-').replace(/^-+|-+$/g, '');
}
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    const img = preview.querySelector('img');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            img.src = e.target.result;
            preview.classList.remove('d-none');
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include '../includes/footer.php'; ?>
