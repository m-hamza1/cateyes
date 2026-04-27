<?php
require_once '../includes/auth.php';
requirePermission('products');

$id = (int)($_GET['id'] ?? 0);
$product = $db->fetchOne("SELECT * FROM products WHERE id = :id", ['id' => $id]);

if (!$product) {
    $_SESSION['error'] = __('product_not_found', 'Product not found');
    header('Location: index.php');
    exit;
}

$trans_en = $db->fetchOne("SELECT * FROM product_translations WHERE product_id = :id AND language_id = 1", ['id' => $id]);
$trans_ar = $db->fetchOne("SELECT * FROM product_translations WHERE product_id = :id AND language_id = 2", ['id' => $id]);
// Get categories
$product_categories = $db->fetchAll("SELECT category_id FROM product_categories WHERE product_id = :id", ['id' => $id]);
$selected_cats = array_column($product_categories, 'category_id');

// Get existing variants
$existing_variants = $db->fetchAll("
    SELECT pv.*
    FROM product_variants pv
    WHERE pv.product_id = :id
", ['id' => $id]);

$variant_details = [];
foreach ($existing_variants as $v) {
    $vals = $db->fetchAll("
        SELECT av.attribute_id, av.value 
        FROM product_variant_attributes pva
        JOIN attribute_values av ON pva.attribute_value_id = av.id
        WHERE pva.variant_id = :variant_id
    ", ['variant_id' => $v['id']]);
    
    foreach ($vals as $val) {
        $variant_details[$v['id']][$val['attribute_id']] = $val['value'];
    }
}

$pageTitle = __('edit_product', 'Edit Product');

$attributes = $db->fetchAll("SELECT * FROM attributes WHERE is_active = 1 ORDER BY name ASC");
$attribute_values = $db->fetchAll("SELECT * FROM attribute_values ORDER BY attribute_id, sort_order ASC");
$attr_values_json = [];
foreach ($attribute_values as $val) {
    $attr_values_json[$val['attribute_id']][] = $val;
}
$brands = $db->fetchAll("SELECT * FROM brands WHERE is_active = 1 ORDER BY name ASC");
$categories = $db->fetchAll("
    SELECT c.id, ct.name
    FROM categories c
    LEFT JOIN category_translations ct ON c.id = ct.category_id AND ct.language_id = 1
    WHERE c.is_active = 1
    ORDER BY c.sort_order, ct.name
");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Check
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        $_SESSION['error'] = 'Invalid security token. Please try again.';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    // Debug: Log file upload info
    error_log("=== PRODUCT EDIT DEBUG ===");
    error_log("POST data: " . print_r($_POST, true));
    error_log("FILES data structure: " . print_r(array_keys($_FILES), true));
    if (isset($_FILES['main_image'])) {
        error_log("Main image info: " . print_r($_FILES['main_image'], true));
    }
    if (isset($_FILES['additional_images'])) {
        error_log("Additional images info: " . print_r($_FILES['additional_images'], true));
    }
    
    // Same validation as add.php
    $sku = sanitize($_POST['sku']);
    $slug = sanitize($_POST['slug']);
    $brand_id = $_POST['brand_id'] ? (int)$_POST['brand_id'] : NULL;
    $price = (float)$_POST['price'];
    
    $compare_price = $_POST['compare_price'] ? (float)$_POST['compare_price'] : NULL;
    $cost_price = $_POST['cost_price'] ? (float)$_POST['cost_price'] : NULL;
    $weight = $_POST['weight'] ?? null ? (float)$_POST['weight'] : NULL;
    $weight_unit = sanitize($_POST['weight_unit'] ?? 'kg');
    $stock_quantity = $product['stock_quantity']; // Keep existing - not editable
    $low_stock_threshold = (int)($_POST['low_stock_threshold'] ?? 5);
    $stock_status = sanitize($_POST['stock_status'] ?? 'in_stock');
    $track_inventory = isset($_POST['track_inventory']) ? 1 : 0;
    $product_type = sanitize($_POST['product_type'] ?? 'physical');
    $meta_title = sanitize($_POST['meta_title'] ?? '');
    $meta_description = sanitize($_POST['meta_description'] ?? '');
    $meta_keywords = sanitize($_POST['meta_keywords'] ?? '');
    
    $name_en = sanitize($_POST['name_en']);
    $name_ar = sanitize($_POST['name_ar']);
    $desc_en = sanitize($_POST['description_en']);
    $desc_ar = sanitize($_POST['description_ar']);
    $short_desc_en = sanitize($_POST['short_description_en']);
    $short_desc_ar = sanitize($_POST['short_description_ar']);
    
    $categories = $_POST['categories'] ?? [];
    
    // Boolean fields
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_bestseller = isset($_POST['is_bestseller']) ? 1 : 0;
    $is_new = isset($_POST['is_new']) ? 1 : 0;
    
    $errors = [];
    
    if (empty($name_en)) {
        $errors[] = __('name_required', 'Name is required');
    }
    
    // Check unique (excluding current)
    $sku_check = $db->fetchOne("SELECT id FROM products WHERE sku = :sku AND id != :id", 
        ['sku' => $sku, 'id' => $id]);
    if ($sku_check) {
        $errors[] = __('sku_exists', 'SKU already exists');
    }
    
    $products_dir = BASE_PATH . 'assets/uploads/products/';
    if (!file_exists($products_dir)) {
        mkdir($products_dir, 0755, true);
    }
    
    if (empty($errors)) {
        $db->beginTransaction();
        try {
            // Initialize main_image with current value
            $main_image = $product['main_image'];
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            // Handle main image upload or change
            error_log("Checking main image upload...");
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
                error_log("Main image upload detected: " . $_FILES['main_image']['name']);
                $ext = strtolower(pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION));
                error_log("File extension: $ext");
                
                if (in_array($ext, $allowed)) {
                    error_log("Extension allowed, processing...");
                    // Remove old main image file
                    if ($main_image && file_exists($products_dir . $main_image)) {
                        unlink($products_dir . $main_image);
                        error_log("Removed old main image: $main_image");
                    }
                    
                    $main_image = uniqid('product_') . '.' . $ext;
                    error_log("Generated new filename: $main_image");
                    
                    if (move_uploaded_file($_FILES['main_image']['tmp_name'], $products_dir . $main_image)) {
                        error_log("File uploaded successfully");
                        // Update or insert in product_images table as main image (sort_order = 0)
                        $existing_main = $db->fetchOne("SELECT id FROM product_images WHERE product_id = :id AND sort_order = 0", ['id' => $id]);
                        if ($existing_main) {
                            $result = $db->update('product_images', ['image_path' => $main_image], 'id = :id', ['id' => $existing_main['id']]);
                            error_log("Updated existing main image in product_images: " . ($result ? 'SUCCESS' : 'FAILED'));
                        } else {
                            $insert_id = $db->insert('product_images', [
                                'product_id' => $id,
                                'image_path' => $main_image,
                                'sort_order' => 0
                            ]);
                            error_log("Inserted new main image in product_images with ID: $insert_id");
                        }
                    } else {
                        error_log("Failed to move uploaded file");
                        throw new Exception('Failed to upload main image');
                    }
                } else {
                    error_log("Extension not allowed: $ext");
                    throw new Exception('Invalid file type for main image');
                }
            } else {
                error_log("No main image upload or error: " . ($_FILES['main_image']['error'] ?? 'No file'));
            }
            
            // Handle additional images
            error_log("Checking additional images...");
            if (isset($_FILES['additional_images'])) {
                // Handle both single file and multiple files
                $additional_files = $_FILES['additional_images'];
                
                // If it's a single file, convert to array format
                if (!is_array($additional_files['name'])) {
                    $additional_files = [
                        'name' => [$additional_files['name']],
                        'full_path' => [$additional_files['full_path'] ?? $additional_files['name'] ?? ''],
                        'type' => [$additional_files['type']],
                        'tmp_name' => [$additional_files['tmp_name']],
                        'error' => [$additional_files['error']],
                        'size' => [$additional_files['size']]
                    ];
                }
                
                if (!empty($additional_files['name'][0])) {
                    error_log("Additional images detected: " . count($additional_files['name']));
                    foreach ($additional_files['name'] as $key => $name) {
                        error_log("Processing additional image $key: $name");
                        if ($additional_files['error'][$key] === UPLOAD_ERR_OK) {
                            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                            error_log("Extension: $ext");
                            if (in_array($ext, $allowed)) {
                                $filename = uniqid('product_img_') . '.' . $ext;
                                error_log("Generated filename: $filename");
                                if (move_uploaded_file($additional_files['tmp_name'][$key], $products_dir . $filename)) {
                                    error_log("File uploaded successfully");
                                    // Get next sort order
                                    $max_sort = $db->fetchOne("SELECT MAX(sort_order) as max_sort FROM product_images WHERE product_id = :id", ['id' => $id]);
                                    $sort_order = ($max_sort['max_sort'] ?? 0) + 1;
                                    error_log("Sort order: $sort_order");
                                    
                                    // Insert into product_images table
                                    $insert_id = $db->insert('product_images', [
                                        'product_id' => $id,
                                        'image_path' => $filename,
                                        'sort_order' => $sort_order
                                    ]);
                                    error_log("Inserted additional image with ID: $insert_id");
                                } else {
                                    error_log("Failed to move uploaded file: $filename");
                                }
                            } else {
                                error_log("Extension not allowed: $ext");
                            }
                        } else {
                            error_log("Upload error for image $key: " . $additional_files['error'][$key]);
                        }
                    }
                } else {
                    error_log("No additional images uploaded (empty array)");
                }
            } else {
                error_log("No additional images field found");
            }
            $result = $db->update('products', [
                'sku' => $sku,
                'slug' => $slug,
                'product_type' => $product_type,
                'brand_id' => $brand_id,
                'price' => $price,
                'compare_price' => $compare_price,
                'cost_price' => $cost_price,
                'weight' => $weight,
                'weight_unit' => $weight_unit,
                'stock_quantity' => $stock_quantity,
                'low_stock_threshold' => $low_stock_threshold,
                'stock_status' => $stock_status,
                'track_inventory' => $track_inventory,
                'main_image' => $main_image,
                'is_featured' => $is_featured,
                'is_active' => $is_active,
                'is_new' => $is_new,
                'is_bestseller' => $is_bestseller,
                'meta_title' => $meta_title,
                'meta_description' => $meta_description,
                'meta_keywords' => $meta_keywords
            ], 'id = :id', ['id' => $id]);
            
            if ($result === false) {
                throw new Exception("Database update failed. Check logs for details.");
            }
            
            // Update translations
            $db->update('product_translations', [
                'name' => $name_en,
                'description' => $desc_en,
                'short_description' => $short_desc_en
            ], 'product_id = :id AND language_id = 1', ['id' => $id]);
            
            $db->update('product_translations', [
                'name' => $name_ar,
                'description' => $desc_ar,
                'short_description' => $short_desc_ar
            ], 'product_id = :id AND language_id = 2', ['id' => $id]);
            
            // Update categories
            $db->delete('product_categories', 'product_id = :id', ['id' => $id]);
            foreach ($categories as $cat_id) {
                $db->insert('product_categories', [
                    'product_id' => $id,
                    'category_id' => (int)$cat_id
                ]);
            }

            // Handle Variants Update
            if (($product_type === 'variable' || $product_type === 'both') && isset($_POST['variant_sku'])) {
                $submitted_vIds = [];
                foreach (array_keys($_POST['variant_sku']) as $vId) {
                    if (is_numeric($vId)) $submitted_vIds[] = (int)$vId;
                }

                // Delete variants that were removed from the form
                $oldVariants = $db->fetchAll("SELECT id FROM product_variants WHERE product_id = :id", ['id' => $id]);
                foreach ($oldVariants as $ov) {
                    if (!in_array((int)$ov['id'], $submitted_vIds)) {
                        $db->delete('product_variant_attributes', 'variant_id = :id', ['id' => $ov['id']]);
                        $db->delete('product_variants', 'id = :id', ['id' => $ov['id']]);
                        // Log removal?
                    }
                }

                $vIndex = 1;
                foreach ($_POST['variant_sku'] as $vId => $vSku) {
                    $vPrice = (float)($_POST['variant_price'][$vId] ?? 0);
                    $vStock = (int)($_POST['variant_stock'][$vId] ?? 0);
                    
                    if (empty($vSku)) {
                        $vSku = $sku . '-v' . $vIndex;
                    }

                    if (is_numeric($vId)) {
                        // Update existing variant
                        $db->update('product_variants', [
                            'sku' => $vSku,
                            'price' => $vPrice,
                            'stock_quantity' => $vStock
                        ], 'id = :id', ['id' => $vId]);
                        $variantId = $vId;
                        
                        // Clear attributes to recreate them
                        $db->delete('product_variant_attributes', 'variant_id = :id', ['id' => $vId]);
                    } else {
                        // Insert new variant
                        $variantId = $db->insert('product_variants', [
                            'product_id' => $id,
                            'sku' => $vSku,
                            'price' => $vPrice,
                            'stock_quantity' => $vStock,
                            'is_active' => 1
                        ]);
                    }
                    
                    // Handle variant attributes
                    if ($variantId && isset($_POST['variant_attr'][$vId])) {
                        foreach ($_POST['variant_attr'][$vId] as $attrId => $valueName) {
                            if (empty($valueName)) continue;
                            
                            $attrValue = $db->fetchOne(
                                "SELECT id FROM attribute_values WHERE attribute_id = :attr_id AND value = :value",
                                ['attr_id' => $attrId, 'value' => $valueName]
                            );
                            
                            if (!$attrValue) {
                                $valId = $db->insert('attribute_values', [
                                    'attribute_id' => $attrId,
                                    'value' => $valueName
                                ]);
                            } else {
                                $valId = $attrValue['id'];
                            }
                            
                            if ($valId) {
                                $db->insert('product_variant_attributes', [
                                    'variant_id' => $variantId,
                                    'attribute_value_id' => $valId
                                ]);
                            }
                        }
                    }
                    $vIndex++;
                }
            } else {
                // Not a variable product anymore, or no variants provided?
                // If it was variable and now simple, we should probably delete variants.
                if ($product_type !== 'variable' && $product_type !== 'both') {
                    $oldVariants = $db->fetchAll("SELECT id FROM product_variants WHERE product_id = :id", ['id' => $id]);
                    foreach ($oldVariants as $ov) {
                        $db->delete('product_variant_attributes', 'variant_id = :id', ['id' => $ov['id']]);
                        $db->delete('product_variants', 'id = :id', ['id' => $ov['id']]);
                    }
                }
            }
            
            $db->commit();
            $_SESSION['success'] = __('product_updated_successfully', 'Product updated successfully!');
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $db->rollback();
            error_log("PRODUCT UPDATE ERROR: " . $e->getMessage());
            error_log("ERROR TRACE: " . $e->getTraceAsString());
            $errors[] = __('error_updating', 'Error updating') . ': ' . $e->getMessage();
        }
    } else {
        $errors[] = __('error_updating', 'Validation errors occurred');
    }
}

$brands = $db->fetchAll("SELECT id, name FROM brands WHERE is_active = 1 ORDER BY name");
$categories = $db->fetchAll("
    SELECT c.id, ct.name 
    FROM categories c 
    LEFT JOIN category_translations ct ON c.id = ct.category_id AND ct.language_id = 1
    WHERE c.is_active = 1
    ORDER BY ct.name
");

// Get product images
$product_images = $db->fetchAll("
    SELECT * FROM product_images 
    WHERE product_id = :id 
    ORDER BY sort_order
", ['id' => $id]);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <a href="index.php" class="text-decoration-none text-muted"><i class="fas fa-arrow-left"></i></a>
        <?php echo __('edit_product', 'Edit Product'); ?>
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
    <?php echo csrfField(); ?>
    <div class="row">
        <div class="col-md-9">
            <div class="card mb-4">
                <div class="card-header"><h5><?php echo __('basic_information', 'Basic Information'); ?></h5></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label><?php echo __('product_type', 'Product Type'); ?></label>
                            <select class="form-select" id="product_type" name="product_type">
                                <option value="physical" <?php echo ($product['product_type'] ?? 'physical') === 'physical' ? 'selected' : ''; ?>>Physical Product</option>
                                <option value="digital" <?php echo ($product['product_type'] ?? '') === 'digital' ? 'selected' : ''; ?>>Digital Product</option>
                                <option value="both" <?php echo ($product['product_type'] ?? '') === 'both' ? 'selected' : ''; ?>>Both</option>
                                <option value="variable" <?php echo ($product['product_type'] ?? '') === 'variable' ? 'selected' : ''; ?>>Variable (with variants)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label><?php echo __('brand', 'Brand'); ?></label>
                            <select name="brand_id" class="form-select">
                                <option value=""><?php echo __('select_brand', 'Select Brand'); ?></option>
                                <?php foreach ($brands as $brand): ?>
                                <option value="<?php echo $brand['id']; ?>" 
                                        <?php echo $product['brand_id'] == $brand['id'] ? 'selected' : ''; ?>>
                                    <?php echo $brand['name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label><?php echo __('product_name', 'Product Name'); ?> (English) *</label>
                            <div class="input-group">
                                <input type="text" name="name_en" id="name_en" class="form-control" required
                                       value="<?php echo htmlspecialchars($_POST['name_en'] ?? $trans_en['name']); ?>">
                                <button class="btn btn-outline-primary" type="button" id="btn_append_arabic" title="Translate and append Arabic to English name">
                                    <i class="fas fa-language"></i> Translate & Append
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label><?php echo __('product_name', 'Product Name'); ?> (العربية)</label>
                            <input type="text" name="name_ar" id="name_ar" class="form-control" dir="rtl"
                                   value="<?php echo htmlspecialchars($_POST['name_ar'] ?? ($trans_ar['name'] ?? '')); ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="btn_translate_arabic" title="Translate English content into Arabic fields">
                            <i class="fas fa-language me-1"></i> Translate into Arabic
                        </button>
                        <span id="translate_status" class="ms-2 small text-muted"></span>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label><?php echo __('sku', 'SKU'); ?> *</label>
                            <input type="text" name="sku" class="form-control" required
                                   value="<?php echo htmlspecialchars($_POST['sku'] ?? $product['sku']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label><?php echo __('slug', 'Slug'); ?></label>
                            <input type="text" name="slug" class="form-control"
                                   value="<?php echo htmlspecialchars($_POST['slug'] ?? $product['slug']); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label><?php echo __('short_description', 'Short Description'); ?> (English)</label>
                        <textarea name="short_description_en" id="short_description_en" class="form-control" rows="2"><?php echo htmlspecialchars($_POST['short_description_en'] ?? $trans_en['short_description']); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label><?php echo __('short_description', 'Short Description'); ?> (العربية)</label>
                        <textarea name="short_description_ar" id="short_description_ar" class="form-control" rows="2" dir="rtl"><?php echo htmlspecialchars($_POST['short_description_ar'] ?? ($trans_ar['short_description'] ?? '')); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label><?php echo __('description', 'Description'); ?> (English)</label>
                        <textarea name="description_en" id="description_en" class="form-control" rows="6"><?php echo htmlspecialchars($_POST['description_en'] ?? $trans_en['description']); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label><?php echo __('description', 'Description'); ?> (العربية)</label>
                        <textarea name="description_ar" id="description_ar" class="form-control" rows="6" dir="rtl"><?php echo htmlspecialchars($_POST['description_ar'] ?? ($trans_ar['description'] ?? '')); ?></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Product Variants - Full Width Row -->
            <div id="variants_section" class="mb-4" style="display: <?php echo ($product['product_type'] === 'variable' || $product['product_type'] === 'both') ? 'block' : 'none'; ?>;">
                <div class="card border-primary border-top border-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 text-primary"><i class="fas fa-th me-2"></i>Product Variants</h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addVariantRow()">
                            <i class="fas fa-plus"></i> Add Variant
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead class="bg-light small">
                                    <tr>
                                        <th style="min-width: 220px;">Attributes</th>
                                        <th style="min-width: 140px;">SKU</th>
                                        <th style="min-width: 120px;">Price</th>
                                        <th style="min-width: 100px;">Stock</th>
                                        <th style="width: 80px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="variants_body">
                                    <?php foreach ($existing_variants as $v): ?>
                                        <?php $rowId = $v['id']; ?>
                                        <tr id="variant_row_<?php echo $rowId; ?>">
                                            <td>
                                                <?php foreach ($attributes as $attr): ?>
                                                    <div class="mb-1">
                                                        <label class="x-small text-muted d-block"><?php echo $attr['name']; ?></label>
                                                        <?php 
                                                        $values = $attr_values_json[$attr['id']] ?? [];
                                                        $currentValue = $variant_details[$rowId][$attr['id']] ?? '';
                                                        if (!empty($values)): ?>
                                                            <select name="variant_attr[<?php echo $rowId; ?>][<?php echo $attr['id']; ?>]" class="form-select form-select-sm">
                                                                <option value="">Select <?php echo $attr['name']; ?></option>
                                                                <?php foreach ($values as $v_val): ?>
                                                                    <option value="<?php echo htmlspecialchars($v_val['value']); ?>" 
                                                                            <?php echo $currentValue == $v_val['value'] ? 'selected' : ''; ?>>
                                                                        <?php echo htmlspecialchars($v_val['value']); ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        <?php else: ?>
                                                            <input type="text" name="variant_attr[<?php echo $rowId; ?>][<?php echo $attr['id']; ?>]" 
                                                                   class="form-control form-control-sm" 
                                                                   value="<?php echo htmlspecialchars($currentValue); ?>">
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </td>
                                            <td><input type="text" name="variant_sku[<?php echo $rowId; ?>]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($v['sku']); ?>"></td>
                                            <td><input type="number" name="variant_price[<?php echo $rowId; ?>]" class="form-control form-control-sm" step="0.01" value="<?php echo $v['price']; ?>"></td>
                                            <td><input type="number" name="variant_stock[<?php echo $rowId; ?>]" class="form-control form-control-sm" value="<?php echo $v['stock_quantity']; ?>"></td>
                                            <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="fas fa-trash"></i></button></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5><?php echo __('pricing', 'Pricing'); ?></h5></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label><?php echo __('selling_price', 'Selling Price'); ?> *</label>
                            <input type="number" name="price" class="form-control" step="0.01" min="0" required
                                   value="<?php echo htmlspecialchars($_POST['price'] ?? $product['price'] ?? ''); ?>"
                                   placeholder="<?php echo __('selling_price', 'Selling Price'); ?>">
                            <small class="text-muted"><?php echo __('regular_price', 'Regular Price'); ?> — price shown to customers</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label><?php echo __('compare_price', 'Compare Price'); ?></label>
                            <input type="number" name="compare_price" class="form-control" step="0.01" min="0"
                                   value="<?php echo htmlspecialchars($_POST['compare_price'] ?? $product['compare_price']); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label><?php echo __('cost_price', 'Cost Price'); ?></label>
                            <input type="number" name="cost_price" class="form-control" step="0.01" min="0"
                                   value="<?php echo htmlspecialchars($_POST['cost_price'] ?? $product['cost_price']); ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label><?php echo __('weight', 'Weight'); ?></label>
                            <input type="number" name="weight" class="form-control" step="0.01" min="0"
                                   value="<?php echo htmlspecialchars($_POST['weight'] ?? $product['weight']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label><?php echo __('weight_unit', 'Weight Unit'); ?></label>
                            <select name="weight_unit" class="form-select">
                                <option value="kg" <?php echo ($product['weight_unit'] ?? 'kg') === 'kg' ? 'selected' : ''; ?>>kg</option>
                                <option value="g" <?php echo ($product['weight_unit'] ?? 'kg') === 'g' ? 'selected' : ''; ?>>g</option>
                                <option value="lb" <?php echo ($product['weight_unit'] ?? 'kg') === 'lb' ? 'selected' : ''; ?>>lb</option>
                                <option value="oz" <?php echo ($product['weight_unit'] ?? 'kg') === 'oz' ? 'selected' : ''; ?>>oz</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header"><h5><?php echo __('inventory', 'Inventory'); ?></h5></div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="track_inventory" 
                               <?php echo $product['track_inventory'] ? 'checked' : ''; ?>>
                        <label><?php echo __('track_inventory', 'Track inventory'); ?></label>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label><?php echo __('stock_quantity', 'Stock Quantity'); ?></label>
                            <input type="number" name="stock_quantity" class="form-control" 
                                   value="<?php echo $product['stock_quantity']; ?>" readonly
                                   title="Stock quantity is automatically managed through purchase orders, sales, and stock movements.">
                            <small class="text-muted">Current: <?php echo $product['stock_quantity']; ?> (managed automatically through stock movements)</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label><?php echo __('low_stock_threshold', 'Low Stock Threshold'); ?></label>
                            <input type="number" name="low_stock_threshold" class="form-control" min="0" 
                                   value="<?php echo $product['low_stock_threshold']; ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label><?php echo __('stock_status', 'Stock Status'); ?></label>
                            <select name="stock_status" class="form-select">
                                <option value="in_stock" <?php echo ($product['stock_status'] ?? 'in_stock') === 'in_stock' ? 'selected' : ''; ?>>In Stock</option>
                                <option value="out_of_stock" <?php echo ($product['stock_status'] ?? 'in_stock') === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                                <option value="on_backorder" <?php echo ($product['stock_status'] ?? 'in_stock') === 'on_backorder' ? 'selected' : ''; ?>>On Backorder</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-header"><h5><?php echo __('product_image', 'Product Image'); ?></h5></div>
                <div class="card-body">
                    <?php if ($product['main_image']): ?>
                    <div class="text-center mb-3">
                        <?php 
                        $main_image_path = UPLOADS_URL . 'products/' . $product['main_image'];
                        ?>
                        <img src="<?php echo $main_image_path; ?>" 
                             class="img-fluid" style="max-height: 250px;"
                             onerror="this.style.display='none';">
                    </div>
                    <?php endif; ?>
                    <input type="file" name="main_image" class="form-control" accept="image/*">
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header"><h5><?php echo __('categories', 'Categories'); ?></h5></div>
                <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                    <?php foreach ($categories as $cat): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="categories[]" 
                               value="<?php echo $cat['id']; ?>" id="cat<?php echo $cat['id']; ?>"
                               <?php echo in_array($cat['id'], $selected_cats) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="cat<?php echo $cat['id']; ?>">
                            <?php echo $cat['name']; ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            

            <div class="card mb-4">
                <div class="card-header"><h5>Additional Images</h5></div>
                <div class="card-body">
                    <?php if (!empty($product_images)): ?>
                        <div class="row mb-3">
                            <?php foreach ($product_images as $img): ?>
                                <div class="col-md-3 mb-2">
                                    <div class="position-relative">
                                        <?php 
                                        // Debug: Check if image path exists
                                        $image_path = UPLOADS_URL . 'products/' . ($img['image_path'] ?? '');
                                        if (empty($img['image_path'])) {
                                            echo '<div class="alert alert-warning">No image filename found</div>';
                                        }
                                        ?>
                                        <img src="<?php echo $image_path; ?>" 
                                             class="img-fluid" style="height: 100px; object-fit: cover;"
                                             onerror="this.style.display='none';">
                                        <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1" 
                                                onclick="removeImage(<?php echo $img['id']; ?>)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Add More Images</label>
                        <input type="file" name="additional_images" class="form-control" accept="image/*" multiple>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header"><h5><?php echo __('seo_settings', 'SEO Settings'); ?></h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label><?php echo __('meta_title', 'Meta Title'); ?></label>
                        <input type="text" name="meta_title" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['meta_title'] ?? $product['meta_title']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label><?php echo __('meta_description', 'Meta Description'); ?></label>
                        <textarea name="meta_description" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['meta_description'] ?? $product['meta_description']); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label><?php echo __('meta_keywords', 'Meta Keywords'); ?></label>
                        <input type="text" name="meta_keywords" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['meta_keywords'] ?? $product['meta_keywords']); ?>">
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header"><h5><?php echo __('settings', 'Settings'); ?></h5></div>
                <div class="card-body">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="is_active" 
                               <?php echo $product['is_active'] ? 'checked' : ''; ?>>
                        <label><?php echo __('active', 'Active'); ?></label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="is_featured" 
                               <?php echo $product['is_featured'] ? 'checked' : ''; ?>>
                        <label><?php echo __('featured', 'Featured'); ?></label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="is_bestseller" 
                               <?php echo $product['is_bestseller'] ? 'checked' : ''; ?>>
                        <label>Bestseller</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="is_new" 
                               <?php echo $product['is_new'] ? 'checked' : ''; ?>>
                        <label>New Product</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="track_inventory" 
                               <?php echo $product['track_inventory'] ? 'checked' : ''; ?>>
                        <label><?php echo __('track_inventory', 'Track inventory'); ?></label>
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
// Product type change handling
const productTypeSelect = document.getElementById('product_type');
const variantsSection = document.getElementById('variants_section');

function updateVisibility() {
    if (!productTypeSelect) return;
    if (productTypeSelect.value === 'variable' || productTypeSelect.value === 'both') {
        variantsSection.style.display = 'block';
    } else {
        variantsSection.style.display = 'none';
    }
}

if (productTypeSelect) {
    productTypeSelect.addEventListener('change', updateVisibility);
    updateVisibility(); // Call on load
}

// Variants management
const attributesData = <?php echo json_encode($attributes); ?>;
const allAttributeValues = <?php echo json_encode($attr_values_json); ?>;
const variantsBody = document.getElementById('variants_body');
let variantCounter = 0;

function addVariantRow() {
    console.log("Adding variant row in edit.php...");
    variantCounter++;
    const rowId = 'new_' + variantCounter + '_' + Date.now();
    console.log("New rowId:", rowId);
    const row = document.createElement('tr');
    row.id = `variant_row_${rowId}`;
    
    let cellsHtml = '<td>';
    attributesData.forEach(attr => {
        const values = allAttributeValues[attr.id] || [];
        cellsHtml += `<div class="mb-1">
            <label class="x-small text-muted d-block">${attr.name}</label>`;
        
        if (values.length > 0) {
            cellsHtml += `<select name="variant_attr[${rowId}][${attr.id}]" class="form-select form-select-sm">
                <option value="">Select ${attr.name}</option>`;
            values.forEach(v => {
                cellsHtml += `<option value="${v.value}">${v.value}</option>`;
            });
            cellsHtml += `</select>`;
        } else {
            cellsHtml += `<input type="text" name="variant_attr[${rowId}][${attr.id}]" class="form-control form-control-sm" placeholder="Value">`;
        }
        
        cellsHtml += `</div>`;
    });
    cellsHtml += '</td>';
    
    cellsHtml += `
        <td><input type="text" name="variant_sku[${rowId}]" class="form-control form-control-sm" placeholder="SKU"></td>
        <td><input type="number" name="variant_price[${rowId}]" class="form-control form-control-sm" step="0.01" placeholder="Price"></td>
        <td><input type="number" name="variant_stock[${rowId}]" class="form-control form-control-sm" placeholder="Stock"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="fas fa-trash"></i></button></td>
    `;
    
    row.innerHTML = cellsHtml;
    variantsBody.appendChild(row);
}

// Translate English to Arabic
(function() {
    const TRANSLATE_API = '<?php echo SITE_URL; ?>api/translate.php';
    const btn = document.getElementById('btn_translate_arabic');
    const statusEl = document.getElementById('translate_status');
    
    if (!btn) return;
    
    async function translateText(text) {
        if (!text || !text.trim()) return '';
        const formData = new FormData();
        formData.append('text', text);
        const res = await fetch(TRANSLATE_API, {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) return data.translatedText;
        throw new Error(data.error || 'Translation failed');
    }
    
    btn.addEventListener('click', async function() {
        const nameEn = document.getElementById('name_en').value.trim();
        const shortDescEn = document.getElementById('short_description_en').value.trim();
        const descEn = document.getElementById('description_en').value.trim();
        
        if (!nameEn && !shortDescEn && !descEn) {
            alert('Please enter some English content first (Product Name, Short Description, or Description).');
            return;
        }
        
        btn.disabled = true;
        statusEl.textContent = 'Translating...';
        statusEl.classList.remove('text-success', 'text-danger');
        statusEl.classList.add('text-muted');
        
        try {
            if (nameEn) {
                document.getElementById('name_ar').value = await translateText(nameEn);
            }
            if (shortDescEn) {
                document.getElementById('short_description_ar').value = await translateText(shortDescEn);
            }
            if (descEn) {
                document.getElementById('description_ar').value = await translateText(descEn);
            }
            statusEl.textContent = 'Translation complete!';
            statusEl.classList.remove('text-muted');
            statusEl.classList.add('text-success');
        } catch (err) {
            statusEl.textContent = 'Error: ' + err.message;
            statusEl.classList.remove('text-muted');
            statusEl.classList.add('text-danger');
        } finally {
            btn.disabled = false;
        }
    });

    const btnAppend = document.getElementById('btn_append_arabic');
        if (btnAppend) {
            btnAppend.addEventListener('click', async function() {
                const nameEn = document.getElementById('name_en').value.trim();
                if (!nameEn) {
                    alert('Please enter an English product name first.');
                    return;
                }

                const parts = nameEn.split(' - ');
                const textToTranslate = parts[0];

                this.disabled = true;
                const originalHtml = this.innerHTML;
                this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

                try {
                    const translated = await translateText(textToTranslate);
                    if (translated) {
                        document.getElementById('name_en').value = `${textToTranslate} - ${translated}`;
                        // Also fill name_ar if empty
                        const nameAr = document.getElementById('name_ar');
                        if (nameAr && !nameAr.value.trim()) nameAr.value = translated;
                    }
                } catch (err) {
                    alert('Error: ' + err.message);
                } finally {
                    this.disabled = false;
                    this.innerHTML = originalHtml;
                }
            });
        }
    }
})();

// Remove product image
function removeImage(imageId) {
    if (confirm('Are you sure you want to remove this image?')) {
        fetch('remove_image.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({image_id: imageId})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to remove image');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while removing the image');
        });
    }
}
</script>

<?php include '../includes/footer.php'; ?>
