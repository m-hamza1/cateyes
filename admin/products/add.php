<?php
/**
 * Add New Product
 */

require_once '../includes/auth.php';
requirePermission('products');
$pageTitle = __('add_new_product', 'Add New Product');

// Initialize classes
$product = new Product($db);

// Get categories and brands
$categories = $db->fetchAll("
    SELECT c.id, ct.name
    FROM categories c
    LEFT JOIN category_translations ct ON c.id = ct.category_id AND ct.language_id = 1
    WHERE c.is_active = 1
    ORDER BY c.sort_order, ct.name
");
$attributes = $db->fetchAll("SELECT * FROM attributes WHERE is_active = 1 ORDER BY name ASC");
$attribute_values = $db->fetchAll("SELECT * FROM attribute_values ORDER BY attribute_id, sort_order ASC");
$attr_values_json = [];
foreach ($attribute_values as $val) {
    $attr_values_json[$val['attribute_id']][] = $val;
}
$brands = $db->fetchAll("SELECT * FROM brands WHERE is_active = 1 ORDER BY name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Check
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        $_SESSION['error'] = 'Invalid security token. Please try again.';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    // Sanitize and validate input
    $data = [
        'sku' => sanitize($_POST['sku'] ?? ''),
        'slug' => sanitize($_POST['slug'] ?? ''),
        'product_type' => sanitize($_POST['product_type'] ?? 'simple'),
        'brand_id' => !empty($_POST['brand_id']) ? (int)$_POST['brand_id'] : null,
        'price' => (float)($_POST['price'] ?? 0),
        'compare_price' => !empty($_POST['compare_price']) ? (float)$_POST['compare_price'] : null,
        'cost_price' => !empty($_POST['cost_price']) ? (float)$_POST['cost_price'] : null,
        'weight' => !empty($_POST['weight']) ? (float)$_POST['weight'] : null,
        'stock_quantity' => 0, // Always start with 0 - managed by stock movements
        'low_stock_threshold' => (int)($_POST['low_stock_threshold'] ?? 10),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
        'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
        'is_bestseller' => isset($_POST['is_bestseller']) ? 1 : 0,
        'is_new' => isset($_POST['is_new']) ? 1 : 0,
        'track_inventory' => isset($_POST['track_inventory']) ? 1 : 0,
        'meta_title' => sanitize($_POST['meta_title'] ?? ''),
        'meta_description' => sanitize($_POST['meta_description'] ?? ''),
        'meta_keywords' => sanitize($_POST['meta_keywords'] ?? ''),
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // Add translations
    $translations = [
        [
            'language_id' => 1,
            'name' => sanitize($_POST['name_en'] ?? ''),
            'description' => sanitize($_POST['description_en'] ?? ''),
            'short_description' => sanitize($_POST['short_description_en'] ?? '')
        ],
        [
            'language_id' => 2,
            'name' => sanitize($_POST['name_ar'] ?? ''),
            'description' => sanitize($_POST['description_ar'] ?? ''),
            'short_description' => sanitize($_POST['short_description_ar'] ?? '')
        ]
    ];
    
    // Add categories
    $categories = $_POST['categories'] ?? [];
    
    // Validation
    $errors = [];
    
    if (empty($data['sku'])) {
        $errors[] = 'SKU is required';
    }
    
    if (empty($data['slug'])) {
        $errors[] = 'Slug is required';
    }
    
    if (empty($data['price']) || $data['price'] <= 0) {
        $errors[] = 'Price is required and must be greater than 0';
    }
    
    // Stock quantity validation removed - always starts with 0
    
    if (empty($translations[0]['name']) || empty($translations[1]['name'])) {
        $errors[] = 'Product name is required for both languages';
    }
    
    // Check unique SKU
    if (!empty($data['sku']) && $db->fetchOne("SELECT id FROM products WHERE sku = :sku", ['sku' => $data['sku']])) {
        $errors[] = 'SKU already exists';
    }
    
    // Check unique slug
    if (!empty($data['slug']) && $db->fetchOne("SELECT id FROM products WHERE slug = :slug", ['slug' => $data['slug']])) {
        $errors[] = 'Slug already exists';
    }
    
    if (empty($errors)) {
        $db->beginTransaction();
        try {
            // Insert product
            $productId = $db->insert('products', $data);
            
            // Insert translations
            foreach ($translations as $translation) {
                $translation['product_id'] = $productId;
                $db->insert('product_translations', $translation);
            }
            
            // Insert categories
            foreach ($categories as $categoryId) {
                $db->insert('product_categories', [
                    'product_id' => $productId,
                    'category_id' => (int)$categoryId
                ]);
            }
            
            // Handle main image upload
            $main_image_filename = null;
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $ext = strtolower(pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowed)) {
                    $products_dir = BASE_PATH . 'assets/uploads/products/';
                    if (!file_exists($products_dir)) {
                        mkdir($products_dir, 0755, true);
                    }
                    
                    $main_image_filename = uniqid('product_') . '.' . $ext;
                    if (move_uploaded_file($_FILES['main_image']['tmp_name'], $products_dir . $main_image_filename)) {
                        $db->update('products', ['main_image' => $main_image_filename], 'id = :id', ['id' => $productId]);
                        
                        // Also add to product_images table as main image (sort_order = 0)
                        $db->insert('product_images', [
                            'product_id' => $productId,
                            'image_path' => $main_image_filename,
                            'sort_order' => 0
                        ]);
                    }
                }
            }
            
            // Handle additional images
            if (isset($_FILES['additional_images']) && !empty($_FILES['additional_images']['name'][0])) {
                foreach ($_FILES['additional_images']['name'] as $key => $name) {
                    if ($_FILES['additional_images']['error'][$key] === UPLOAD_ERR_OK) {
                        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                        if (in_array($ext, $allowed)) {
                            $filename = uniqid('product_img_') . '.' . $ext;
                            if (move_uploaded_file($_FILES['additional_images']['tmp_name'][$key], $products_dir . $filename)) {
                                // Add to product_images table with appropriate sort order
                                $sort_order = $main_image_filename ? ($key + 1) : $key; // If main image exists, start from 1, else from 0
                                $db->insert('product_images', [
                                    'product_id' => $productId,
                                    'image_path' => $filename,
                                    'sort_order' => ($key + 1)
                                ]);
                            }
                        }
                    }
                }
            }

            // Handle Variants
            if (($data['product_type'] === 'variable' || $data['product_type'] === 'both') && isset($_POST['variant_sku'])) {
                $vIndex = 1;
                foreach ($_POST['variant_sku'] as $rowId => $vSku) {
                    $vPrice = (float)($_POST['variant_price'][$rowId] ?? 0);
                    $vStock = (int)($_POST['variant_stock'][$rowId] ?? 0);
                    
                    if (empty($vSku)) {
                        $vSku = $data['sku'] . '-v' . $vIndex;
                    }
                    
                    // Check if this SKU already exists in this product set to avoid internal collision
                    // (Global collision is handled by DB)
                    
                    // Insert variant
                    $variantId = $db->insert('product_variants', [
                        'product_id' => $productId,
                        'sku' => $vSku,
                        'price' => $vPrice,
                        'stock_quantity' => $vStock,
                        'is_active' => 1
                    ]);
                    
                    // Handle variant attributes
                    if ($variantId && isset($_POST['variant_attr'][$rowId])) {
                        foreach ($_POST['variant_attr'][$rowId] as $attrId => $valueName) {
                            if (empty($valueName)) continue;
                            
                            // Find or create attribute value
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
            }
            
            $db->commit();
            $_SESSION['success'] = 'Product created successfully';
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $db->rollback();
            $_SESSION['error'] = 'Failed to create product: ' . $e->getMessage();
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-plus me-2"></i>
                    Add New Product
                </h1>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Products
                </a>
            </div>

            <!-- Form -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-plus me-2"></i> Product Information
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <?php echo csrfField(); ?>
                        <div class="row">
                            <!-- Basic Information -->
                            <div class="col-md-6">
                                <h6 class="mb-3">Basic Information</h6>
                                
                                <div class="mb-3">
                                    <label class="form-label">Product Type</label>
                                    <select name="product_type" id="product_type" class="form-select">
                                        <option value="simple" <?php echo (isset($_POST['product_type']) && $_POST['product_type'] === 'simple') ? 'selected' : ''; ?>>Simple Product</option>
                                        <option value="variable" <?php echo (isset($_POST['product_type']) && $_POST['product_type'] === 'variable') ? 'selected' : ''; ?>>Variable Product</option>
                                        <option value="digital" <?php echo (isset($_POST['product_type']) && $_POST['product_type'] === 'digital') ? 'selected' : ''; ?>>Digital Product</option>
                                        <option value="both" <?php echo (isset($_POST['product_type']) && $_POST['product_type'] === 'both') ? 'selected' : ''; ?>>Both (Physical + Digital)</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">SKU *</label>
                                    <input type="text" name="sku" class="form-control" 
                                           value="<?php echo htmlspecialchars($_POST['sku'] ?? ''); ?>"
                                           required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Slug *</label>
                                    <input type="text" name="slug" class="form-control" 
                                           value="<?php echo htmlspecialchars($_POST['slug'] ?? ''); ?>"
                                           required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Brand</label>
                                    <select name="brand_id" class="form-select">
                                        <option value="">Select Brand</option>
                                        <?php foreach ($brands as $brand): ?>
                                            <option value="<?php echo $brand['id']; ?>" 
                                                    <?php echo (isset($_POST['brand_id']) && $_POST['brand_id'] == $brand['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($brand['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Pricing -->
                            <div class="col-md-6">
                                <h6 class="mb-3">Pricing</h6>
                                
                                <div class="mb-3">
                                    <label class="form-label">Price *</label>
                                    <input type="number" name="price" class="form-control" 
                                           step="0.01" min="0" 
                                           value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>"
                                           required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Compare Price</label>
                                    <input type="number" name="compare_price" class="form-control" 
                                           step="0.01" min="0" 
                                           value="<?php echo htmlspecialchars($_POST['compare_price'] ?? ''); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Cost Price</label>
                                    <input type="number" name="cost_price" class="form-control" 
                                           step="0.01" min="0" 
                                           value="<?php echo htmlspecialchars($_POST['cost_price'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <!-- Inventory -->
                            <div class="col-md-6">
                                <h6 class="mb-3">Inventory</h6>
                                
                                <div class="mb-3">
                                    <label class="form-label">Stock Quantity</label>
                                    <input type="number" name="stock_quantity" class="form-control" 
                                           value="0" readonly
                                           title="Stock quantity is automatically managed through purchase orders, sales, and stock movements.">
                                    <small class="text-muted">Automatically calculated from stock movements (purchase orders, sales, returns, etc.)</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Low Stock Threshold</label>
                                    <input type="number" name="low_stock_threshold" class="form-control" 
                                           min="0" 
                                           value="<?php echo htmlspecialchars($_POST['low_stock_threshold'] ?? 10); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Weight (kg)</label>
                                    <input type="number" name="weight" class="form-control" 
                                           step="0.01" min="0" 
                                           value="<?php echo htmlspecialchars($_POST['weight'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <!-- Options -->
                            <div class="col-md-6">
                                <h6 class="mb-3">Options</h6>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" name="is_featured" id="is_featured" 
                                               <?php echo (isset($_POST['is_featured'])) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_featured">Featured</label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" name="is_bestseller" id="is_bestseller" 
                                               <?php echo (isset($_POST['is_bestseller'])) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_bestseller">Bestseller</label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" name="is_new" id="is_new" 
                                               <?php echo (isset($_POST['is_new'])) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_new">New Product</label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" name="track_inventory" id="track_inventory" 
                                               <?php echo (isset($_POST['track_inventory'])) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="track_inventory">Track Inventory</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Product Variants (Full Width - Conditional) -->
                        <div id="variants_section" class="row mb-4" style="display: <?php echo (isset($_POST['product_type']) && ($_POST['product_type'] === 'variable' || $_POST['product_type'] === 'both')) ? 'flex' : 'none'; ?>;">
                            <div class="col-12">
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
                                                    <!-- Dynamic rows here -->
                                                </tbody>
                                            </table>
                                        </div>
                                        <p class="text-muted small mt-2 mb-0">
                                            <i class="fas fa-info-circle me-1"></i> Make sure to define attributes in the Catalog > Attributes section first.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Categories -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <h6 class="mb-3">Categories</h6>
                                <div class="row">
                                    <?php foreach ($categories as $category): ?>
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input type="checkbox" name="categories[]" 
                                                       value="<?php echo $category['id']; ?>"
                                                       <?php echo (isset($_POST['categories']) && in_array($category['id'], $_POST['categories'])) ? 'checked' : ''; ?>>
                                                <label class="form-check-label">
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>


                        
                        <!-- English Content -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <h6 class="mb-3">English Content</h6>
                                
                                <div class="mb-3">
                                    <label class="form-label">Product Name *</label>
                                    <div class="input-group">
                                        <input type="text" name="name_en" id="name_en" class="form-control" 
                                               value="<?php echo htmlspecialchars($_POST['name_en'] ?? ''); ?>"
                                               required>
                                        <button class="btn btn-outline-primary" type="button" id="btn_append_arabic" title="Translate and append Arabic to English name">
                                            <i class="fas fa-language"></i> Translate & Append
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea name="description_en" id="description_en" class="form-control" rows="4"><?php echo htmlspecialchars($_POST['description_en'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Short Description</label>
                                    <textarea name="short_description_en" id="short_description_en" class="form-control" rows="2"><?php echo htmlspecialchars($_POST['short_description_en'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="btn_translate_arabic" title="Translate English content into Arabic fields">
                                <i class="fas fa-language me-1"></i> Translate into Arabic
                            </button>
                            <span id="translate_status" class="ms-2 small text-muted"></span>
                        </div>
                        
                        <!-- Arabic Content -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <h6 class="mb-3">Arabic Content</h6>
                                
                                <div class="mb-3">
                                    <label class="form-label">اسم المنتج (بالعربية)</label>
                                    <input type="text" name="name_ar" id="name_ar" class="form-control" dir="rtl"
                                           value="<?php echo htmlspecialchars($_POST['name_ar'] ?? ''); ?>"
                                           required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">الوصف (بالعربية)</label>
                                    <textarea name="description_ar" id="description_ar" class="form-control" rows="4" dir="rtl"><?php echo htmlspecialchars($_POST['description_ar'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">الوصف قصير (بالعربية)</label>
                                    <textarea name="short_description_ar" id="short_description_ar" class="form-control" rows="2" dir="rtl"><?php echo htmlspecialchars($_POST['short_description_ar'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- SEO -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <h6 class="mb-3">SEO</h6>
                                
                                <div class="mb-3">
                                    <label class="form-label">Meta Title</label>
                                    <input type="text" name="meta_title" class="form-control" 
                                           value="<?php echo htmlspecialchars($_POST['meta_title'] ?? ''); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Meta Description</label>
                                    <textarea name="meta_description" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['meta_description'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Meta Keywords</label>
                                    <input type="text" name="meta_keywords" class="form-control" 
                                           value="<?php echo htmlspecialchars($_POST['meta_keywords'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Product Images -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <h6 class="mb-3">Product Images</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Main Image</label>
                                            <input type="file" name="main_image" class="form-control" accept="image/*">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Additional Images</label>
                                            <input type="file" name="additional_images" class="form-control" accept="image/*" multiple>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="imagePreview" class="mb-3 text-center">
                                    <div class="preview-container" style="width: 200px; height: 200px; border: 2px dashed #ccc; display: inline-block; vertical-align: top;">
                                        <i class="fas fa-image fa-3x text-muted"></i>
                                        <p class="text-muted">Image preview will appear here</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Active Status -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <h6 class="mb-3">Publication Status</h6>
                                <div class="form-check">
                                    <input type="checkbox" name="is_active" id="is_active" 
                                           <?php echo (isset($_POST['is_active'])) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">Publish Product (Active)</label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Submit -->
                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Create Product
                                </button>
                                <a href="index.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
// Product type change handling
const productTypeSelect = document.querySelector('select[name="product_type"]');
const variantsSection = document.getElementById('variants_section');

function updateVisibility() {
    if (!productTypeSelect) return;
    console.log("Product type changed to:", productTypeSelect.value);
    if (productTypeSelect.value === 'variable' || productTypeSelect.value === 'both') {
        variantsSection.style.display = 'flex';
    } else {
        variantsSection.style.display = 'none';
    }
}

productTypeSelect.addEventListener('change', updateVisibility);
updateVisibility();

// Variants management
const attributesData = <?php echo json_encode($attributes); ?>;
const allAttributeValues = <?php echo json_encode($attr_values_json); ?>;
const variantsBody = document.getElementById('variants_body');
let variantCounter = 0;

function addVariantRow() {
    console.log("Adding variant row...");
    variantCounter++;
    const rowId = 'new_' + variantCounter + '_' + Date.now();
    console.log("New rowId:", rowId);
    let attributesHtml = '';
    
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
    const TRANSLATE_API = '<?php echo defined("SITE_URL") ? SITE_URL : (defined("BASE_URL") ? BASE_URL : "/"); ?>api/translate.php';
    const btn = document.getElementById('btn_translate_arabic');
    const statusEl = document.getElementById('translate_status');
    
    if (btn) {
        async function translateText(text) {
            if (!text || !text.trim()) return '';
            const formData = new FormData();
            formData.append('text', text);
            const res = await fetch(TRANSLATE_API, { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) return data.translatedText;
            throw new Error(data.error || 'Translation failed');
        }
        
        btn.addEventListener('click', async function() {
            const nameEn = (document.getElementById('name_en') || {}).value?.trim() || '';
            const shortDescEn = (document.getElementById('short_description_en') || {}).value?.trim() || '';
            const descEn = (document.getElementById('description_en') || {}).value?.trim() || '';
            
            if (!nameEn && !shortDescEn && !descEn) {
                alert('Please enter some English content first (Product Name, Short Description, or Description).');
                return;
            }
            
            btn.disabled = true;
            if (statusEl) { statusEl.textContent = 'Translating...'; statusEl.className = 'ms-2 small text-muted'; }
            
            try {
                if (nameEn) { const el = document.getElementById('name_ar'); if (el) el.value = await translateText(nameEn); }
                if (shortDescEn) { const el = document.getElementById('short_description_ar'); if (el) el.value = await translateText(shortDescEn); }
                if (descEn) { const el = document.getElementById('description_ar'); if (el) el.value = await translateText(descEn); }
                if (statusEl) { statusEl.textContent = 'Translation complete!'; statusEl.className = 'ms-2 small text-success'; }
            } catch (err) {
                if (statusEl) { statusEl.textContent = 'Error: ' + err.message; statusEl.className = 'ms-2 small text-danger'; }
            } finally {
                btn.disabled = false;
            }
        });
        const btnAppend = document.getElementById('btn_append_arabic');
        if (btnAppend) {
            btnAppend.addEventListener('click', async function() {
                const nameEn = (document.getElementById('name_en') || {}).value?.trim() || '';
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

// Image preview functionality
const mainImageInput = document.querySelector('input[name="main_image"]');
if (mainImageInput) mainImageInput.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imagePreview').innerHTML = 
                '<img src="' + e.target.result + '" style="max-width: 100%; max-height: 200px; object-fit: contain;">';
        };
        reader.readAsDataURL(file);
    }
});

// Auto-generate slug from English name
document.querySelector('input[name="name_en"]').addEventListener('input', function() {
    const name = this.value;
    const slug = name.toLowerCase()
                   .replace(/ /g, '-')
                   .replace(/[^\w-]+/g, '')
                   .replace(/-+/g, '-')
                   .replace(/^-+/, '')
                   .replace(/-+$/, '');
    
    document.querySelector('input[name="slug"]').value = slug;
});

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const requiredFields = document.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('is-invalid');
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    if (!isValid) {
        e.preventDefault();
        alert('Please fill in all required fields.');
    }
});
</script>

<style>
.preview-container {
    border: 2px dashed #ccc;
    padding: 10px;
    margin: 10px auto;
    text-align: center;
    background: #f8f9fa;
}

.form-check {
    margin-bottom: 10px;
}

.form-check-label {
    margin-left: 5px;
}

.is-invalid {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}
</style>

<?php include '../includes/footer.php'; ?>
