<?php
require_once '../includes/auth.php';
requirePermission('services');

$pageTitle = __('edit_category', 'Edit Category');

// Get category
$id = (int)$_GET['id'];
$category = $db->fetchOne("SELECT * FROM service_categories WHERE id = :id", ['id' => $id]);

if (!$category) {
    $_SESSION['error'] = __('category_not_found', 'Category not found');
    header('Location: index.php');
    exit;
}

// Get existing translations
$translations_map = [];
$translations = $db->fetchAll("
    SELECT sct.*, l.code as language_code 
    FROM service_category_translations sct
    LEFT JOIN languages l ON sct.language_id = l.id
    WHERE sct.category_id = :id
", ['id' => $id]);
foreach ($translations as $translation) {
    if (!empty($translation['language_code'])) {
        $translations_map[$translation['language_code']] = [
            'name' => $translation['name'],
            'description' => $translation['description'],
            'meta_title' => $translation['meta_title'],
            'meta_description' => $translation['meta_description']
        ];
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $slug = trim($_POST['slug'] ?? '');
    $image = $category['image']; // Keep existing image by default
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Handle image upload
    if (isset($_FILES['image_upload']) && $_FILES['image_upload']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (in_array($_FILES['image_upload']['type'], $allowed_types) && $_FILES['image_upload']['size'] <= $max_size) {
            $upload_dir = dirname(dirname(dirname(__DIR__))) . '/assets/uploads/services/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $filename = time() . '_' . basename($_FILES['image_upload']['name']);
            $filename = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $filename);
            
            if (move_uploaded_file($_FILES['image_upload']['tmp_name'], $upload_dir . $filename)) {
                // Delete old image if exists
                if (!empty($category['image']) && file_exists($upload_dir . $category['image'])) {
                    unlink($upload_dir . $category['image']);
                }
                $image = $filename;
            } else {
                $errors[] = __('upload_failed', 'Image upload failed');
            }
        } else {
            $errors[] = __('invalid_image', 'Invalid image file. Only JPG, PNG, GIF, WebP allowed (max 5MB)');
        }
    } elseif (!empty($_POST['existing_image'])) {
        $image = trim($_POST['existing_image']);
    }
    
    // Translations
    $translations = [
        'en' => [
            'name' => trim($_POST['name_en'] ?? ''),
            'description' => trim($_POST['description_en'] ?? ''),
            'meta_title' => trim($_POST['meta_title_en'] ?? ''),
            'meta_description' => trim($_POST['meta_description_en'] ?? '')
        ],
        'ar' => [
            'name' => trim($_POST['name_ar'] ?? ''),
            'description' => trim($_POST['description_ar'] ?? ''),
            'meta_title' => trim($_POST['meta_title_ar'] ?? ''),
            'meta_description' => trim($_POST['meta_description_ar'] ?? '')
        ]
    ];
    
    $errors = [];
    
    // Validation
    if (empty($slug)) $errors[] = __('slug_required', 'Slug is required');
    if (empty($translations['en']['name'])) $errors[] = __('name_en_required', 'English name is required');
    if (empty($translations['ar']['name'])) $errors[] = __('name_ar_required', 'Arabic name is required');
    
    // Check if slug exists (excluding current)
    if (!empty($slug)) {
        $existing = $db->fetchOne("SELECT id FROM service_categories WHERE slug = :slug AND id != :id", ['slug' => $slug, 'id' => $id]);
        if ($existing) {
            $errors[] = __('slug_exists', 'Slug already exists');
        }
    }
    
    if (empty($errors)) {
        try {
            // Update category
            $db->update('service_categories', [
                'slug' => $slug,
                'sort_order' => $sort_order,
                'is_active' => $is_active
            ], 'id = :id', ['id' => $id]);
            
            // Get language IDs
            $langs = $db->fetchAll("SELECT id, code FROM languages WHERE code IN ('en', 'ar')");
            $lang_map = [];
            foreach ($langs as $language_row) {
                $lang_map[$language_row['code']] = $language_row['id'];
            }
            
            // Update translations
            foreach ($translations as $lang_code => $translation) {
                $existing_trans = $translations_map[$lang_code] ?? null;
                
                if ($existing_trans) {
                    // Update existing translation
                    $db->update('service_category_translations', [
                        'name' => $translation['name'],
                        'description' => $translation['description'],
                        'meta_title' => $translation['meta_title'],
                        'meta_description' => $translation['meta_description']
                    ], 'category_id = :category_id AND language_id = :language_id', [
                        'category_id' => $id,
                        'language_id' => $lang_map[$lang_code]
                    ]);
                } else {
                    // Insert new translation
                    $db->insert('service_category_translations', [
                        'category_id' => $id,
                        'language_id' => $lang_map[$lang_code],
                        'name' => $translation['name'],
                        'description' => $translation['description'],
                        'meta_title' => $translation['meta_title'],
                        'meta_description' => $translation['meta_description']
                    ]);
                }
            }
            
            $_SESSION['success'] = __('category_updated', 'Category updated successfully');
            header('Location: index.php');
            exit;
            
        } catch (Exception $e) {
            $errors[] = __('save_error', 'Error saving category');
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><?php echo __('edit_category', 'Edit Category'); ?></h1>
    <a href="index.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i><?php echo __('back_to_categories', 'Back to Categories'); ?>
    </a>
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

<div class="card">
    <div class="card-body">
        <form method="POST">
            <!-- Basic Information -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <h5><?php echo __('basic_info', 'Basic Information'); ?></h5>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="slug" class="form-label"><?php echo __('slug', 'Slug'); ?> *</label>
                    <input type="text" class="form-control" id="slug" name="slug" 
                           value="<?php echo isset($_POST['slug']) ? htmlspecialchars($_POST['slug']) : htmlspecialchars($category['slug']); ?>"
                           placeholder="beauty-services" required>
                    <div class="form-text"><?php echo __('slug_help', 'URL-friendly version of the name'); ?></div>
                </div>
                <div class="col-md-3">
                    <label for="sort_order" class="form-label"><?php echo __('sort_order', 'Sort Order'); ?></label>
                    <input type="number" class="form-control" id="sort_order" name="sort_order" 
                           value="<?php echo isset($_POST['sort_order']) ? htmlspecialchars($_POST['sort_order']) : htmlspecialchars($category['sort_order']); ?>"
                           min="0">
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                               <?php echo (isset($_POST['is_active']) ? $_POST['is_active'] : $category['is_active']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_active">
                            <?php echo __('active', 'Active'); ?>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- English Translation -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <h5><?php echo __('english_translation', 'English Translation'); ?></h5>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="name_en" class="form-label"><?php echo __('name', 'Name'); ?> *</label>
                    <input type="text" class="form-control" id="name_en" name="name_en" 
                           value="<?php echo isset($_POST['name_en']) ? htmlspecialchars($_POST['name_en']) : htmlspecialchars($translations_map['en']['name'] ?? ''); ?>"
                           placeholder="Beauty Services" required>
                </div>
                <div class="col-md-6">
                    <label for="meta_title_en" class="form-label"><?php echo __('meta_title', 'Meta Title'); ?></label>
                    <input type="text" class="form-control" id="meta_title_en" name="meta_title_en" 
                           value="<?php echo isset($_POST['meta_title_en']) ? htmlspecialchars($_POST['meta_title_en']) : htmlspecialchars($translations_map['en']['meta_title'] ?? ''); ?>"
                           placeholder="Beauty Services">
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="description_en" class="form-label"><?php echo __('description', 'Description'); ?></label>
                    <textarea class="form-control" id="description_en" name="description_en" rows="3"
                              placeholder="Professional beauty and wellness services"><?php echo isset($_POST['description_en']) ? htmlspecialchars($_POST['description_en']) : htmlspecialchars($translations_map['en']['description'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-12">
                    <label for="meta_description_en" class="form-label"><?php echo __('meta_description', 'Meta Description'); ?></label>
                    <textarea class="form-control" id="meta_description_en" name="meta_description_en" rows="2"
                              placeholder="Professional beauty and wellness services"><?php echo isset($_POST['meta_description_en']) ? htmlspecialchars($_POST['meta_description_en']) : htmlspecialchars($translations_map['en']['meta_description'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <!-- Arabic Translation -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <h5><?php echo __('arabic_translation', 'Arabic Translation'); ?></h5>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="name_ar" class="form-label"><?php echo __('name', 'Name'); ?> *</label>
                    <input type="text" class="form-control" id="name_ar" name="name_ar" 
                           value="<?php echo isset($_POST['name_ar']) ? htmlspecialchars($_POST['name_ar']) : htmlspecialchars($translations_map['ar']['name'] ?? ''); ?>"
                           placeholder="خدمات التجميل" required dir="rtl">
                </div>
                <div class="col-md-6">
                    <label for="meta_title_ar" class="form-label"><?php echo __('meta_title', 'Meta Title'); ?></label>
                    <input type="text" class="form-control" id="meta_title_ar" name="meta_title_ar" 
                           value="<?php echo isset($_POST['meta_title_ar']) ? htmlspecialchars($_POST['meta_title_ar']) : htmlspecialchars($translations_map['ar']['meta_title'] ?? ''); ?>"
                           placeholder="خدمات التجميل" dir="rtl">
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="description_ar" class="form-label"><?php echo __('description', 'Description'); ?></label>
                    <textarea class="form-control" id="description_ar" name="description_ar" rows="3"
                              placeholder="خدمات احترافية للتجميل والعناية بالبشرة" dir="rtl"><?php echo isset($_POST['description_ar']) ? htmlspecialchars($_POST['description_ar']) : htmlspecialchars($translations_map['ar']['description'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-12">
                    <label for="meta_description_ar" class="form-label"><?php echo __('meta_description', 'Meta Description'); ?></label>
                    <textarea class="form-control" id="meta_description_ar" name="meta_description_ar" rows="2"
                              placeholder="خدمات احترافية للتجميل والعناية بالبشرة" dir="rtl"><?php echo isset($_POST['meta_description_ar']) ? htmlspecialchars($_POST['meta_description_ar']) : htmlspecialchars($translations_map['ar']['meta_description'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div class="row">
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i><?php echo __('update_category', 'Update Category'); ?>
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <?php echo __('cancel', 'Cancel'); ?>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
