<?php
require_once '../includes/auth.php';
requirePermission('services');

$pageTitle = __('add_category', 'Add Category');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $slug = trim($_POST['slug'] ?? '');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
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
    
    // Check if slug exists
    if (!empty($slug)) {
        $existing = $db->fetchOne("SELECT id FROM service_categories WHERE slug = :slug", ['slug' => $slug]);
        if ($existing) {
            $errors[] = __('slug_exists', 'Slug already exists');
        }
    }
    
    if (empty($errors)) {
        try {
            // Insert category
            $category_id = $db->insert('service_categories', [
                'slug' => $slug,
                'sort_order' => $sort_order,
                'is_active' => $is_active
            ]);
            
            // Get language IDs
            $langs = $db->fetchAll("SELECT id, code FROM languages WHERE code IN ('en', 'ar')");
            $lang_map = [];
            foreach ($langs as $language) {
                $lang_map[$language['code']] = $language['id'];
            }
            
            // Insert translations
            foreach ($translations as $lang_code => $translation) {
                $db->insert('service_category_translations', [
                    'category_id' => $category_id,
                    'language_id' => $lang_map[$lang_code],
                    'name' => $translation['name'],
                    'description' => $translation['description'],
                    'meta_title' => $translation['meta_title'],
                    'meta_description' => $translation['meta_description']
                ]);
            }
            
            $_SESSION['success'] = __('category_added', 'Category added successfully');
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
    <h1><?php echo __('add_category', 'Add Category'); ?></h1>
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
                           value="<?php echo isset($_POST['slug']) ? htmlspecialchars($_POST['slug']) : ''; ?>"
                           placeholder="beauty-services" required>
                    <div class="form-text"><?php echo __('slug_help', 'URL-friendly version of the name'); ?></div>
                </div>
                <div class="col-md-3">
                    <label for="sort_order" class="form-label"><?php echo __('sort_order', 'Sort Order'); ?></label>
                    <input type="number" class="form-control" id="sort_order" name="sort_order" 
                           value="<?php echo isset($_POST['sort_order']) ? htmlspecialchars($_POST['sort_order']) : '0'; ?>"
                           min="0">
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                               <?php echo (isset($_POST['is_active']) || !isset($_POST)) ? 'checked' : ''; ?>>
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
                           value="<?php echo isset($_POST['name_en']) ? htmlspecialchars($_POST['name_en']) : ''; ?>"
                           placeholder="Beauty Services" required>
                </div>
                <div class="col-md-6">
                    <label for="meta_title_en" class="form-label"><?php echo __('meta_title', 'Meta Title'); ?></label>
                    <input type="text" class="form-control" id="meta_title_en" name="meta_title_en" 
                           value="<?php echo isset($_POST['meta_title_en']) ? htmlspecialchars($_POST['meta_title_en']) : ''; ?>"
                           placeholder="Beauty Services">
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="description_en" class="form-label"><?php echo __('description', 'Description'); ?></label>
                    <textarea class="form-control" id="description_en" name="description_en" rows="3"
                              placeholder="Professional beauty and wellness services"><?php echo isset($_POST['description_en']) ? htmlspecialchars($_POST['description_en']) : ''; ?></textarea>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-12">
                    <label for="meta_description_en" class="form-label"><?php echo __('meta_description', 'Meta Description'); ?></label>
                    <textarea class="form-control" id="meta_description_en" name="meta_description_en" rows="2"
                              placeholder="Professional beauty and wellness services"><?php echo isset($_POST['meta_description_en']) ? htmlspecialchars($_POST['meta_description_en']) : ''; ?></textarea>
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
                           value="<?php echo isset($_POST['name_ar']) ? htmlspecialchars($_POST['name_ar']) : ''; ?>"
                           placeholder="خدمات التجميل" required dir="rtl">
                </div>
                <div class="col-md-6">
                    <label for="meta_title_ar" class="form-label"><?php echo __('meta_title', 'Meta Title'); ?></label>
                    <input type="text" class="form-control" id="meta_title_ar" name="meta_title_ar" 
                           value="<?php echo isset($_POST['meta_title_ar']) ? htmlspecialchars($_POST['meta_title_ar']) : ''; ?>"
                           placeholder="خدمات التجميل" dir="rtl">
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="description_ar" class="form-label"><?php echo __('description', 'Description'); ?></label>
                    <textarea class="form-control" id="description_ar" name="description_ar" rows="3"
                              placeholder="خدمات احترافية للتجميل والعناية بالبشرة" dir="rtl"><?php echo isset($_POST['description_ar']) ? htmlspecialchars($_POST['description_ar']) : ''; ?></textarea>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-12">
                    <label for="meta_description_ar" class="form-label"><?php echo __('meta_description', 'Meta Description'); ?></label>
                    <textarea class="form-control" id="meta_description_ar" name="meta_description_ar" rows="2"
                              placeholder="خدمات احترافية للتجميل والعناية بالبشرة" dir="rtl"><?php echo isset($_POST['meta_description_ar']) ? htmlspecialchars($_POST['meta_description_ar']) : ''; ?></textarea>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div class="row">
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i><?php echo __('save_category', 'Save Category'); ?>
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
