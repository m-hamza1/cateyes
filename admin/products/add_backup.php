<?php
require_once '../includes/auth.php';
$pageTitle = __('add_new_product', 'Add New Product');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sku = sanitize($_POST['sku']);
    $slug = sanitize($_POST['slug']);
    $brand_id = $_POST['brand_id'] ? (int)$_POST['brand_id'] : NULL;
    $price = (float)$_POST['price'];
    $compare_price = $_POST['compare_price'] ? (float)$_POST['compare_price'] : NULL;
    $cost_price = $_POST['cost_price'] ? (float)$_POST['cost_price'] : NULL;
    $weight = $_POST['weight'] ? (float)$_POST['weight'] : NULL;
    $stock_quantity = (int)$_POST['stock_quantity'];
    $low_stock_threshold = (int)$_POST['low_stock_threshold'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $track_inventory = isset($_POST['track_inventory']) ? 1 : 0;
    $meta_title = sanitize($_POST['meta_title']);
    $meta_description = sanitize($_POST['meta_description']);
    $meta_keywords = sanitize($_POST['meta_keywords']);
    
    // Translations
    $name_en = sanitize($_POST['name_en']);
    $name_ar = sanitize($_POST['name_ar']);
    $desc_en = sanitize($_POST['description_en']);
    $desc_ar = sanitize($_POST['description_ar']);
    $short_desc_en = sanitize($_POST['short_description_en']);
    $short_desc_ar = sanitize($_POST['short_description_ar']);
    
    // Categories
    $categories = $_POST['categories'] ?? [];
    
    $errors = [];
    
    if (empty($name_en)) {
        $errors[] = __('name_required', 'Product name is required');
    }
    if (empty($sku)) {
        $errors[] = __('sku_required', 'SKU is required');
    }
    if (empty($slug)) {
        $slug = strtolower(str_replace(' ', '-', $name_en));
    }
    
    // Check unique
    if ($db->fetchOne("SELECT id FROM products WHERE sku = :sku", ['sku' => $sku])) {
        $errors[] = __('sku_exists', 'SKU already exists');
    }
    if ($db->fetchOne("SELECT id FROM products WHERE slug = :slug", ['slug' => $slug])) {
        $errors[] = __('slug_exists', 'Slug already exists');
    }
    
    $products_dir = UPLOADS_PATH . 'products/';
    if (!file_exists($products_dir)) {
        mkdir($products_dir, 0755, true);
    }
    
    // Handle image
    $main_image = '';
    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $main_image = uniqid('product_') . '.' . $ext;
            if (!move_uploaded_file($_FILES['main_image']['tmp_name'], $products_dir . $main_image)) {
                $errors[] = __('upload_failed', 'Failed to upload image');
                $main_image = '';
            }
        } else {
            $errors[] = __('invalid_image_format', 'Invalid image format');
        }
    }
    
    // Determine stock status
    $stock_status = $stock_quantity > 0 ? 'in_stock' : 'out_of_stock';
    
    if (empty($errors)) {
        $db->beginTransaction();
        try {
            // Insert product
            $productId = $db->insert('products', [
                'sku' => $sku,
                'slug' => $slug,
                'brand_id' => $brand_id,
                'price' => $price,
                'compare_price' => $compare_price,
                'cost_price' => $cost_price,
                'weight' => $weight,
                'stock_quantity' => $stock_quantity,
                'low_stock_threshold' => $low_stock_threshold,
                'stock_status' => $stock_status,
                'track_inventory' => $track_inventory,
                'main_image' => $main_image,
                'is_featured' => $is_featured,
                'is_active' => $is_active,
                'meta_title' => $meta_title,
                'meta_description' => $meta_description,
                'meta_keywords' => $meta_keywords
            ]);
            
            // Insert translations
            $db->insert('product_translations', [
                'product_id' => $productId,
                'language_id' => 1,
                'name' => $name_en,
                'description' => $desc_en,
                'short_description' => $short_desc_en
            ]);
            
            $db->insert('product_translations', [
                'product_id' => $productId,
                'language_id' => 2,
                'name' => $name_ar,
                'description' => $desc_ar,
                'short_description' => $short_desc_ar
            ]);
            
            // Insert categories
            foreach ($categories as $cat_id) {
                $db->insert('product_categories', [
                    'product_id' => $productId,
                    'category_id' => (int)$cat_id
                ]);
            }
            
            $db->commit();
            $_SESSION['success'] = __('product_added_successfully', 'Product added successfully!');
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $db->rollback();
            $errors[] = __('error_adding', 'Error adding product: ') . $e->getMessage();
        }
    }
}

// Get data for dropdowns
$brands = $db->fetchAll("SELECT id, name FROM brands WHERE is_active = 1 ORDER BY name");
$categories = $db->fetchAll("
    SELECT c.id, ct.name 
    FROM categories c 
    LEFT JOIN category_translations ct ON c.id = ct.category_id 
    WHERE ct.language_id = :lang AND c.is_active = 1
    ORDER BY ct.name
", ['lang' => $lang->getCurrentId()]);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <a href="index.php" class="text-decoration-none text-muted"><i class="fas fa-arrow-left"></i></a>
        <?php echo __('add_new_product', 'Add New Product'); ?>
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
            <!-- Basic Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><?php echo __('basic_information', 'Basic Information'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('product_name', 'Product Name'); ?> (English) <span class="text-danger">*</span></label>
                            <input type="text" name="name_en" class="form-control" required
                                   value="<?php echo htmlspecialchars($_POST['name_en'] ?? ''); ?>"
                                   onkeyup="generateSlug(this.value)">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('product_name', 'Product Name'); ?> (العربية)</label>
                            <input type="text" name="name_ar" class="form-control" dir="rtl"
                                   value="<?php echo htmlspecialchars($_POST['name_ar'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('sku', 'SKU'); ?> <span class="text-danger">*</span></label>
                            <input type="text" name="sku" class="form-control" required
                                   value="<?php echo htmlspecialchars($_POST['sku'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('slug', 'Slug'); ?></label>
                            <input type="text" name="slug" id="slug" class="form-control"
                                   value="<?php echo htmlspecialchars($_POST['slug'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('short_description', 'Short Description'); ?> (English)</label>
                        <textarea name="short_description_en" class="form-control" rows="2"><?php echo htmlspecialchars($_POST['short_description_en'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('short_description', 'Short Description'); ?> (العربية)</label>
                        <textarea name="short_description_ar" class="form-control" rows="2" dir="rtl"><?php echo htmlspecialchars($_POST['short_description_ar'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('description', 'Description'); ?> (English)</label>
                        <textarea name="description_en" class="form-control" rows="6"><?php echo htmlspecialchars($_POST['description_en'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('description', 'Description'); ?> (العربية)</label>
                        <textarea name="description_ar" class="form-control" rows="6" dir="rtl"><?php echo htmlspecialchars($_POST['description_ar'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Pricing -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><?php echo __('pricing', 'Pricing'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?php echo __('regular_price', 'Regular Price'); ?> <span class="text-danger">*</span></label>
                            <input type="number" name="price" class="form-control" step="0.01" min="0" required
                                   value="<?php echo $_POST['price'] ?? '0.00'; ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?php echo __('compare_price', 'Compare Price'); ?></label>
                            <input type="number" name="compare_price" class="form-control" step="0.01" min="0"
                                   value="<?php echo $_POST['compare_price'] ?? ''; ?>">
                            <small class="text-muted"><?php echo __('original_price', 'Original price for comparison'); ?></small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?php echo __('cost_price', 'Cost Price'); ?></label>
                            <input type="number" name="cost_price" class="form-control" step="0.01" min="0"
                                   value="<?php echo $_POST['cost_price'] ?? ''; ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Inventory -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><?php echo __('inventory', 'Inventory'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="track_inventory" id="track_inventory" checked>
                        <label class="form-check-label" for="track_inventory">
                            <?php echo __('track_inventory', 'Track inventory for this product'); ?>
                        </label>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('stock_quantity', 'Stock Quantity'); ?></label>
                            <input type="number" name="stock_quantity" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('low_stock_threshold', 'Low Stock Threshold'); ?></label>
                            <input type="number" name="low_stock_threshold" class="form-control" min="0" value="5">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('weight', 'Weight (kg)'); ?></label>
                        <input type="number" name="weight" class="form-control" step="0.01" min="0"
                               value="<?php echo $_POST['weight'] ?? ''; ?>">
                    </div>
                </div>
            </div>
            
            <!-- SEO -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><?php echo __('seo_information', 'SEO Information'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('meta_title', 'Meta Title'); ?></label>
                        <input type="text" name="meta_title" class="form-control" maxlength="60"
                               value="<?php echo htmlspecialchars($_POST['meta_title'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('meta_description', 'Meta Description'); ?></label>
                        <textarea name="meta_description" class="form-control" rows="3" maxlength="160"><?php echo htmlspecialchars($_POST['meta_description'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('meta_keywords', 'Meta Keywords'); ?></label>
                        <input type="text" name="meta_keywords" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['meta_keywords'] ?? ''); ?>">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Product Image -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><?php echo __('product_image', 'Product Image'); ?></h5>
                </div>
                <div class="card-body">
                    <input type="file" name="main_image" class="form-control" accept="image/*" onchange="previewImage(this)">
                    <div id="imagePreview" class="text-center mt-3 d-none">
                        <img src="" alt="Preview" class="img-fluid" style="max-height: 250px;">
                    </div>
                </div>
            </div>
            
            <!-- Categories -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><?php echo __('categories', 'Categories'); ?></h5>
                </div>
                <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                    <?php foreach ($categories as $cat): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="categories[]" 
                               value="<?php echo $cat['id']; ?>" id="cat<?php echo $cat['id']; ?>">
                        <label class="form-check-label" for="cat<?php echo $cat['id']; ?>">
                            <?php echo $cat['name']; ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Brand -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><?php echo __('brand', 'Brand'); ?></h5>
                </div>
                <div class="card-body">
                    <select name="brand_id" class="form-select">
                        <option value=""><?php echo __('select_brand', 'Select Brand'); ?></option>
                        <?php foreach ($brands as $brand): ?>
                        <option value="<?php echo $brand['id']; ?>"><?php echo $brand['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Settings -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><?php echo __('settings', 'Settings'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                        <label class="form-check-label" for="is_active"><?php echo __('active', 'Active'); ?></label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured">
                        <label class="form-check-label" for="is_featured"><?php echo __('featured', 'Featured'); ?></label>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary w-100 mb-2">
                        <i class="fas fa-save"></i> <?php echo __('save', 'Save Product'); ?>
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
function generateSlug(str) {
    str = str.toLowerCase().replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-');
    document.getElementById('slug').value = str;
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
