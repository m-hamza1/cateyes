<?php
require_once '../includes/auth.php';
requirePermission('categories');

$id = (int)($_GET['id'] ?? 0);
$category = $db->fetchOne("SELECT * FROM categories WHERE id = :id", ['id' => $id]);

if (!$category) {
    $_SESSION['error'] = __('category_not_found', 'Category not found');
    header('Location: index.php');
    exit;
}

// Get translations
$trans_en = $db->fetchOne("SELECT * FROM category_translations WHERE category_id = :id AND language_id = 1", ['id' => $id]);
$trans_ar = $db->fetchOne("SELECT * FROM category_translations WHERE category_id = :id AND language_id = 2", ['id' => $id]);

$pageTitle = __('edit_category', 'Edit Category');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $slug = sanitize($_POST['slug']);
    $parent_id = $_POST['parent_id'] ? (int)$_POST['parent_id'] : NULL;
    $sort_order = (int)$_POST['sort_order'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $meta_title = sanitize($_POST['meta_title']);
    $meta_description = sanitize($_POST['meta_description']);
    
    $name_en = sanitize($_POST['name_en']);
    $name_ar = sanitize($_POST['name_ar']);
    $desc_en = sanitize($_POST['description_en']);
    $desc_ar = sanitize($_POST['description_ar']);
    
    $errors = [];
    
    if (empty($name_en)) {
        $errors[] = __('name_required', 'Name is required');
    }
    
    // Check slug
    $existing = $db->fetchOne("SELECT id FROM categories WHERE slug = :slug AND id != :id", 
        ['slug' => $slug, 'id' => $id]);
    if ($existing) {
        $errors[] = __('slug_exists', 'Slug already exists');
    }
    
    $categories_dir = UPLOADS_PATH . 'categories/';
    if (!file_exists($categories_dir)) {
        mkdir($categories_dir, 0755, true);
    }
    
    // Handle image
    $image = $category['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            if ($image && file_exists($categories_dir . $image)) {
                unlink($categories_dir . $image);
            }
            $image = uniqid('category_') . '.' . $ext;
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $categories_dir . $image)) {
                $errors[] = __('upload_failed', 'Failed to upload image');
                $image = $category['image'];
            }
        }
    }
    
    if (empty($errors)) {
        $db->beginTransaction();
        try {
            $db->update('categories', [
                'parent_id' => $parent_id,
                'slug' => $slug,
                'image' => $image,
                'is_active' => $is_active,
                'sort_order' => $sort_order,
                'meta_title' => $meta_title,
                'meta_description' => $meta_description
            ], 'id = :id', ['id' => $id]);
            
            // Update translations
            $db->update('category_translations', [
                'name' => $name_en,
                'description' => $desc_en
            ], 'category_id = :id AND language_id = 1', ['id' => $id]);
            
            $db->update('category_translations', [
                'name' => $name_ar,
                'description' => $desc_ar
            ], 'category_id = :id AND language_id = 2', ['id' => $id]);
            
            $db->commit();
            $_SESSION['success'] = __('category_updated_successfully', 'Category updated successfully!');
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $db->rollback();
            $errors[] = __('error_updating', 'Error updating category');
        }
    }
}

$parents = $db->fetchAll("SELECT c.id, ct.name 
    FROM categories c 
    LEFT JOIN category_translations ct ON c.id = ct.category_id 
    WHERE ct.language_id = :lang AND c.parent_id IS NULL AND c.id != :current
    ORDER BY ct.name", ['lang' => $lang->getCurrentId(), 'current' => $id]);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <a href="index.php" class="text-decoration-none text-muted"><i class="fas fa-arrow-left"></i></a>
        <?php echo __('edit_category', 'Edit Category'); ?>
    </h1>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul class="mb-0">
        <?php foreach ($errors as $error): ?>
        <li><?php echo $error; ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5><?php echo __('basic_information', 'Basic Information'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('name', 'Name'); ?> (English) <span class="text-danger">*</span></label>
                        <input type="text" name="name_en" class="form-control" required
                               value="<?php echo htmlspecialchars($_POST['name_en'] ?? $trans_en['name']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('name', 'Name'); ?> (العربية)</label>
                        <input type="text" name="name_ar" class="form-control" dir="rtl"
                               value="<?php echo htmlspecialchars($_POST['name_ar'] ?? $trans_ar['name']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('slug', 'Slug'); ?></label>
                        <input type="text" name="slug" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['slug'] ?? $category['slug']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('description', 'Description'); ?> (English)</label>
                        <textarea name="description_en" class="form-control" rows="4"><?php echo htmlspecialchars($_POST['description_en'] ?? $trans_en['description']); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('description', 'Description'); ?> (العربية)</label>
                        <textarea name="description_ar" class="form-control" rows="4" dir="rtl"><?php echo htmlspecialchars($_POST['description_ar'] ?? $trans_ar['description']); ?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5><?php echo __('seo_information', 'SEO Information'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('meta_title', 'Meta Title'); ?></label>
                        <input type="text" name="meta_title" class="form-control" maxlength="60"
                               value="<?php echo htmlspecialchars($_POST['meta_title'] ?? $category['meta_title']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('meta_description', 'Meta Description'); ?></label>
                        <textarea name="meta_description" class="form-control" rows="3" maxlength="160"><?php echo htmlspecialchars($_POST['meta_description'] ?? $category['meta_description']); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5><?php echo __('category_image', 'Category Image'); ?></h5>
                </div>
                <div class="card-body">
                    <?php if ($category['image']): ?>
                    <div class="text-center mb-3">
                        <img src="<?php echo UPLOADS_URL . 'categories/' . $category['image']; ?>" 
                             class="img-fluid" style="max-height: 200px;" id="currentImage">
                    </div>
                    <?php endif; ?>
                    <input type="file" name="image" class="form-control" accept="image/*" onchange="previewImage(this)">
                    <div id="imagePreview" class="text-center mt-3 d-none">
                        <img src="" alt="Preview" class="img-fluid" style="max-height: 200px;">
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5><?php echo __('parent_category', 'Parent Category'); ?></h5>
                </div>
                <div class="card-body">
                    <select name="parent_id" class="form-select">
                        <option value=""><?php echo __('no_parent', 'No Parent'); ?></option>
                        <?php foreach ($parents as $parent): ?>
                        <option value="<?php echo $parent['id']; ?>" 
                                <?php echo $category['parent_id'] == $parent['id'] ? 'selected' : ''; ?>>
                            <?php echo $parent['name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5><?php echo __('settings', 'Settings'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('sort_order', 'Sort Order'); ?></label>
                        <input type="number" name="sort_order" class="form-control" 
                               value="<?php echo $category['sort_order']; ?>" min="0">
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" 
                               id="is_active" <?php echo $category['is_active'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_active"><?php echo __('active', 'Active'); ?></label>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary w-100 mb-2">
                        <i class="fas fa-save"></i> <?php echo __('update', 'Update'); ?>
                    </button>
                    <a href="index.php" class="btn btn-secondary w-100">
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
    const currentImage = document.getElementById('currentImage');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            img.src = e.target.result;
            preview.classList.remove('d-none');
            if (currentImage) currentImage.style.display = 'none';
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include '../includes/footer.php'; ?>
