<?php
require_once '../includes/auth.php';
requirePermission('brands');

$id = (int)($_GET['id'] ?? 0);
$brand = $db->fetchOne("SELECT * FROM brands WHERE id = :id", ['id' => $id]);

if (!$brand) {
    $_SESSION['error'] = __('brand_not_found', 'Brand not found');
    header('Location: index.php');
    exit;
}

$pageTitle = __('edit_brand', 'Edit Brand');

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
        $errors[] = __('name_required', 'Name is required');
    }
    
    $slug_check = $db->fetchOne("SELECT id FROM brands WHERE slug = :slug AND id != :id", 
        ['slug' => $slug, 'id' => $id]);
    if ($slug_check) {
        $errors[] = __('slug_exists', 'Slug already exists');
    }
    
    $logo = $brand['logo'];
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            if ($logo && file_exists($brands_dir . $logo)) {
                unlink($brands_dir . $logo);
            }
            $logo = uniqid('brand_') . '.' . $ext;
            if (!move_uploaded_file($_FILES['logo']['tmp_name'], $brands_dir . $logo)) {
                $errors[] = __('upload_failed', 'Failed to upload logo');
                $logo = $brand['logo'];
            }
        } else {
            $errors[] = __('invalid_image_format', 'Invalid image format');
        }
    }
    
    if (empty($errors)) {
        try {
            $db->update('brands', [
                'name' => $name,
                'slug' => $slug,
                'logo' => $logo,
                'description' => $description,
                'meta_title' => $meta_title,
                'meta_description' => $meta_description,
                'is_active' => $is_active,
                'sort_order' => $sort_order
            ], 'id = :id', ['id' => $id]);
            
            $_SESSION['success'] = __('brand_updated_successfully', 'Brand updated successfully!');
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $errors[] = __('error_updating', 'Error updating brand');
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <a href="index.php" class="text-decoration-none text-muted"><i class="fas fa-arrow-left"></i></a>
        <?php echo __('edit_brand', 'Edit Brand'); ?>
    </h1>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo $error; ?></li><?php endforeach; ?></ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0"><?php echo __('basic_information', 'Basic Information'); ?></h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('brand_name', 'Brand Name'); ?> *</label>
                        <input type="text" name="name" class="form-control" required
                               value="<?php echo htmlspecialchars($_POST['name'] ?? $brand['name']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('slug', 'Slug'); ?></label>
                        <input type="text" name="slug" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['slug'] ?? $brand['slug']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('description', 'Description'); ?></label>
                        <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($_POST['description'] ?? $brand['description']); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('website', 'Website'); ?></label>
                        <input type="url" name="website" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['website'] ?? $brand['website']); ?>">
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0"><?php echo __('seo_information', 'SEO Information'); ?></h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="meta_title"><?php echo __('meta_title', 'Meta Title'); ?></label>
                        <input type="text" name="meta_title" class="form-control" maxlength="60"
                               value="<?php echo htmlspecialchars($_POST['meta_title'] ?? $brand['meta_title']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="meta_description"><?php echo __('meta_description', 'Meta Description'); ?></label>
                        <textarea name="meta_description" class="form-control" rows="3" maxlength="160"><?php echo htmlspecialchars($_POST['meta_description'] ?? $brand['meta_description']); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0"><?php echo __('brand_logo', 'Brand Logo'); ?></h5></div>
                <div class="card-body">
                    <?php if ($brand['logo']): ?>
                    <div class="text-center mb-3">
                        <img src="<?php echo UPLOADS_URL . 'brands/' . $brand['logo']; ?>" class="img-fluid rounded" style="max-height: 150px;">
                    </div>
                    <?php endif; ?>
                    <input type="file" name="logo" class="form-control" accept="image/*" onchange="previewImage(this)">
                    <small class="text-muted"><?php echo __('leave_empty_keep', 'Leave empty to keep current'); ?></small>
                    <div id="imagePreview" class="text-center mt-3 d-none">
                        <img src="" alt="Preview" class="img-fluid rounded">
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0"><?php echo __('settings', 'Settings'); ?></h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="sort_order"><?php echo __('sort_order', 'Sort Order'); ?></label>
                        <input type="number" name="sort_order" class="form-control" 
                               value="<?php echo $brand['sort_order']; ?>" min="0">
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" 
                               <?php echo $brand['is_active'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_active"><?php echo __('active', 'Active'); ?></label>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary w-100 mb-2">
                        <i class="fas fa-save"></i> <?php echo __('update', 'Update'); ?>
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
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    const img = preview.querySelector('img');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) { img.src = e.target.result; preview.classList.remove('d-none'); }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include '../includes/footer.php'; ?>
