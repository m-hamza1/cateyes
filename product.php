<?php
require_once 'config.php';

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

// Handle language switching - SIMPLIFIED VERSION
if (isset($_GET['lang']) && !empty($_GET['lang'])) {
    // Simple language change without complex URL manipulation
    $lang->changeLanguage($_GET['lang']);
    
    // Simple redirect back to same page without lang parameter
    $current_url = $_SERVER['PHP_SELF'];
    $query_params = $_GET;
    unset($query_params['lang']); // Remove lang from parameters
    
    $redirect_url = $current_url . '?' . http_build_query($query_params);
    
    header('Location: ' . $redirect_url);
    exit;
}

// Get product details with translations
if ($product_id > 0) {
    // Debug: Log the product query attempt
    error_log("PRODUCT QUERY: product_id=$product_id, lang=" . $lang->getCurrentId() . "");
    
    $product = $db->fetchOne("
        SELECT p.*, 
               COALESCE(pt.name, p.slug) as title, 
               COALESCE(pt.short_description, p.meta_description) as description, 
               COALESCE(pt.description, p.meta_description) as full_description
        FROM products p
        LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = " . $lang->getCurrentId() . "
        WHERE p.id = $product_id AND p.is_active = 1
    ");
    
    // Debug: Log the result
    error_log("PRODUCT RESULT: " . ($product ? "FOUND - {$product['title']}" : "NOT FOUND") . "");
} elseif (!empty($slug)) {
    // Debug: Log the slug query attempt
    error_log("SLUG QUERY: slug=$slug, lang=" . $lang->getCurrentId() . "");
    
    $product = $db->fetchOne("
        SELECT p.*, 
               COALESCE(pt.name, p.slug) as title, 
               COALESCE(pt.short_description, p.meta_description) as description, 
               COALESCE(pt.description, p.meta_description) as full_description
        FROM products p
        LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = " . $lang->getCurrentId() . "
        WHERE p.slug = '$slug' AND p.is_active = 1
    ");
    
    // Debug: Log the result
    error_log("SLUG RESULT: " . ($product ? "FOUND - {$product['title']}" : "NOT FOUND") . "");
} else {
    // Debug: Log no product specified
    error_log("NO PRODUCT SPECIFIED");
    header('Location: ' . BASE_URL . 'shop.php');
    exit;
}

// If product not found, redirect to shop
if (!$product || !isset($product['id']) || empty($product['id'])) {
    // Debug: Log product not found
    error_log("PRODUCT NOT FOUND: product_id=" . ($product_id ?? 'none') . ", lang=" . $lang->getCurrentId() . ", result=" . var_export($product, true));
    header('Location: ' . BASE_URL . 'shop.php');
    exit;
}

// Increment view count (only if product is valid)
if (isset($product['id']) && !empty($product['id'])) {
    $db->query("UPDATE products SET views_count = views_count + 1 WHERE id = {$product['id']}");
}

// Get product images
$product_images = $db->fetchAll("
    SELECT * FROM product_images
    WHERE product_id = {$product['id']}
    ORDER BY sort_order
");

// Get product variations with their attribute values
$variations_raw = $db->fetchAll("
    SELECT pv.*, GROUP_CONCAT(CONCAT(a.name, ': ', av.value) SEPARATOR ', ') as variant_name
    FROM product_variants pv
    LEFT JOIN product_variant_attributes pva ON pv.id = pva.variant_id
    LEFT JOIN attribute_values av ON pva.attribute_value_id = av.id
    LEFT JOIN attributes a ON av.attribute_id = a.id
    WHERE pv.product_id = {$product['id']} AND pv.is_active = 1
    GROUP BY pv.id
    ORDER BY pv.price ASC
");
$variations = $variations_raw;

// Get product attributes/specifications
$attributes = $db->fetchAll("
    SELECT attribute_name, attribute_value
    FROM product_attributes
    WHERE product_id = {$product['id']}
    ORDER BY attribute_name
");

// Get related products (same category, excluding current product)
$related_products = $db->fetchAll("
    SELECT DISTINCT p.*, 
           COALESCE(pt.name, p.slug) as title, 
           COALESCE(pt.short_description, p.meta_description) as description
    FROM products p
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = " . $lang->getCurrentId() . "
    LEFT JOIN product_categories pc ON p.id = pc.product_id
    WHERE p.is_active = 1 
    AND p.id != {$product['id']}
    AND pc.category_id IN (
        SELECT category_id FROM product_categories WHERE product_id = {$product['id']}
    )
    ORDER BY p.is_featured DESC, RAND()
    LIMIT 4
");

// Get product reviews
$reviews = $db->fetchAll("
    SELECT pr.*, u.first_name, u.last_name
    FROM product_reviews pr
    LEFT JOIN users u ON pr.user_id = u.id
    WHERE pr.product_id = {$product['id']} AND pr.is_approved = 1
    ORDER BY pr.created_at DESC
    LIMIT 5
");

// Calculate average rating
$avg_rating = 0;
if (!empty($reviews)) {
    $total_rating = array_sum(array_column($reviews, 'rating'));
    $avg_rating = $total_rating / count($reviews);
}

// Check if user has already reviewed this product
$user_has_reviewed = false;
if (isset($_SESSION['user_id'])) {
    $existing_review = $db->fetchOne("
        SELECT id FROM product_reviews 
        WHERE product_id = {$product['id']} AND user_id = {$_SESSION['user_id']}
    ");
    $user_has_reviewed = !empty($existing_review);
}

$pageTitle = $product['title'] ?: 'Product Details';
include 'includes/header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="bg-light py-3">
    <div class="container">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>"><?php echo __('home', 'Home'); ?></a></li>
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>shop.php"><?php echo __('shop', 'Shop'); ?></a></li>
            <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['title']); ?></li>
        </ol>
    </div>
</nav>

<!-- Product Details Section -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <!-- Product Images -->
            <div class="col-lg-6 mb-4">
                <div class="product-gallery">
                    <!-- Main Image -->
                    <div class="main-image mb-3">
                        <?php
                        $mainImagePath = $product['main_image'] ?: (!empty($product_images[0]['image_path']) ? $product_images[0]['image_path'] : '');
                        ?>
                        <?php if ($mainImagePath): ?>
                            <img src="<?php echo htmlspecialchars(UPLOADS_URL . 'products/' . $mainImagePath, ENT_QUOTES, 'UTF-8'); ?>" 
                                 class="img-fluid rounded" 
                                 id="mainProductImage"
                                 alt="<?php echo htmlspecialchars($product['title'] ?? $product['slug'] ?? 'Product'); ?>"
                                 onerror="this.src='<?php echo ASSETS_URL; ?>images/placeholder.jpg'; this.onerror=null;">
                        <?php else: ?>
                            <div class="img-fluid rounded bg-light d-flex align-items-center justify-content-center" 
                                 style="height: 400px;">
                                <i class="fas fa-box fa-4x text-muted"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Thumbnail Images -->
                    <?php if (!empty($product_images)): ?>
                    <div class="thumbnail-images d-flex gap-2 flex-wrap mb-3">
                        <div class="text-muted small mb-2">Click to change main image:</div>
                        <?php foreach ($product_images as $index => $image): ?>
                        <?php if (empty($image['image_path'])) { continue; } ?>
                        <?php $imageUrl = UPLOADS_URL . 'products/' . $image['image_path']; ?>
                        <div class="thumbnail-item">
                            <img src="<?php echo htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8'); ?>" 
                                 class="img-thumbnail <?php echo ($mainImagePath && $image['image_path'] === $mainImagePath) ? 'active' : ''; ?>" 
                                 style="width: 80px; height: 80px; object-fit: cover; cursor: pointer;" 
                                 onclick="changeMainImage('<?php echo htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8'); ?>', this)"
                                 alt="<?php echo htmlspecialchars($product['title']); ?>">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Product Information -->
            <div class="col-lg-6">
                <div class="product-info">
                    <!-- Product Title -->
                    <h1 class="mb-3"><?php echo htmlspecialchars($product['title']); ?></h1>
                    
                    <!-- Rating -->
                    <div class="rating mb-3">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star <?php echo $i <= round($avg_rating) ? 'text-warning' : 'text-muted'; ?>"></i>
                        <?php endfor; ?>
                        <span class="ms-2 text-muted">
                            <?php echo is_array($reviews) ? count($reviews) : 0; ?> <?php echo __('reviews', 'reviews'); ?>
                        </span>
                    </div>
                    
                    <!-- Price -->
                    <div class="price-section mb-4">
                        <h2 class="text-primary mb-2">
                            <?php echo $currency->format($product['price']); ?>
                        </h2>
                        <?php if ($product['compare_price'] && $product['compare_price'] > $product['price']): ?>
                        <span class="text-decoration-line-through text-muted">
                            <?php echo $currency->format($product['compare_price']); ?>
                        </span>
                        <span class="badge bg-danger ms-2">
                            <?php echo __('save', 'Save'); ?> <?php echo $currency->format($product['compare_price'] - $product['price']); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Stock Status -->
                    <div class="stock-status mb-4">
                        <?php if ($product['stock_quantity'] > 0): ?>
                            <span class="badge bg-success">
                                <i class="fas fa-check-circle"></i> <?php echo __('in_stock', 'In Stock'); ?> (<?php echo $product['stock_quantity']; ?>)
                            </span>
                        <?php else: ?>
                            <span class="badge bg-danger">
                                <i class="fas fa-times-circle"></i> <?php echo __('out_of_stock', 'Out of Stock'); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Short Description -->
                    <div class="short-description mb-4">
                        <p><?php echo htmlspecialchars($product['description']); ?></p>
                    </div>
                    
                    <!-- Product Variations -->
                    <?php if (!empty($variations)): ?>
                    <div class="variations mb-4">
                        <h5><?php echo __('options', 'Options'); ?>:</h5>
                        <div class="variation-options">
                            <?php foreach ($variations as $variation): ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="variation" 
                                       id="variation_<?php echo $variation['id']; ?>" 
                                       value="<?php echo $variation['id']; ?>"
                                       data-price="<?php echo $variation['price']; ?>"
                                       data-stock="<?php echo $variation['stock_quantity']; ?>"
                                       <?php echo $variation['stock_quantity'] > 0 ? '' : 'disabled'; ?>>
                                <label class="form-check-label" for="variation_<?php echo $variation['id']; ?>">
                                    <strong><?php echo htmlspecialchars($variation['variant_name'] ?: 'Default'); ?></strong>
                                    <span class="ms-2 text-primary"><?php echo $currency->format($variation['price']); ?></span>
                                    <?php if ($variation['stock_quantity'] <= 0): ?>
                                    <span class="text-danger small">(<?php echo __('out_of_stock', 'Out of Stock'); ?>)</span>
                                    <?php endif; ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Quantity and Add to Cart -->
                    <div class="purchase-section mb-4">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <div class="input-group" style="width: 150px;">
                                    <button class="btn btn-outline-secondary" type="button" onclick="decreaseQuantity()">-</button>
                                    <input type="number" class="form-control text-center" id="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>">
                                    <button class="btn btn-outline-secondary" type="button" onclick="increaseQuantity()">+</button>
                                </div>
                            </div>
                            <div class="col">
                                <button type="submit" class="btn btn-warning btn-sm" onclick="addToCart(<?php echo $product_id; ?>)" <?php echo $product['stock_quantity'] <= 0 ? 'disabled' : ''; ?>>
                                    <i class="fas fa-shopping-cart"></i> <?php echo __('add_to_cart', 'Add to Cart'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Product Meta -->
                    <div class="product-meta">
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">
                                    <i class="fas fa-eye"></i> <?php echo $product['views_count']; ?> <?php echo __('views', 'views'); ?>
                                </small>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">
                                    <i class="fas fa-box"></i> SKU: <?php echo htmlspecialchars($product['sku']); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Product Tabs -->
        <div class="row mt-5">
            <div class="col-lg-12">
                <ul class="nav nav-tabs" id="productTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="description-tab" data-bs-toggle="tab" data-bs-target="#description" type="button" role="tab">
                            <?php echo __('description', 'Description'); ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="specifications-tab" data-bs-toggle="tab" data-bs-target="#specifications" type="button" role="tab">
                            <?php echo __('specifications', 'Specifications'); ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews" type="button" role="tab">
                            <?php echo __('reviews', 'Reviews'); ?> (<?php echo is_array($reviews) ? count($reviews) : 0; ?>)
                        </button>
                    </li>
                </ul>
                <div class="tab-content" id="productTabsContent">
                    <!-- Description Tab -->
                    <div class="tab-pane fade show active" id="description" role="tabpanel">
                        <div class="pt-4">
                            <?php echo nl2br(htmlspecialchars($product['full_description'])); ?>
                        </div>
                    </div>
                    
                    <!-- Specifications Tab -->
                    <div class="tab-pane fade" id="specifications" role="tabpanel">
                        <div class="pt-4">
                            <?php if (!empty($attributes)): ?>
                            <div class="row">
                                <?php foreach ($attributes as $attribute): ?>
                                <div class="col-md-6 mb-3">
                                    <strong><?php echo htmlspecialchars($attribute['attribute_name']); ?>:</strong>
                                    <span><?php echo htmlspecialchars($attribute['attribute_value']); ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <p><?php echo __('no_specifications', 'No specifications available.'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Reviews Tab -->
                    <div class="tab-pane fade" id="reviews" role="tabpanel">
                        <div class="pt-4">
                            <!-- Review Form for Logged-in Users -->
                            <?php if (isset($_SESSION['user_id']) && !$user_has_reviewed): ?>
                            <div class="review-form mb-5">
                                <h4><?php echo __('write_review', 'Write a Review'); ?></h4>
                                <p class="text-muted"><?php echo __('share_experience', 'Share your experience with this product'); ?></p>
                                
                                <form id="reviewForm" class="needs-validation" novalidate>
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="reviewRating" class="form-label"><?php echo __('rating', 'Rating'); ?> *</label>
                                            <div class="rating-input">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
                                                    <label for="star<?php echo $i; ?>" class="star-label">
                                                        <i class="fas fa-star"></i>
                                                    </label>
                                                <?php endfor; ?>
                                            </div>
                                            <div class="invalid-feedback">
                                                <?php echo __('please_select_rating', 'Please select a rating'); ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="reviewTitle" class="form-label"><?php echo __('review_title', 'Review Title'); ?></label>
                                            <input type="text" class="form-control" id="reviewTitle" name="title" 
                                                   placeholder="<?php echo __('review_title_placeholder', 'Summarize your experience'); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="reviewText" class="form-label"><?php echo __('review_text', 'Your Review'); ?> *</label>
                                        <textarea class="form-control" id="reviewText" name="review" rows="4" 
                                                  placeholder="<?php echo __('review_placeholder', 'Tell us about your experience with this product...'); ?>" required></textarea>
                                        <div class="invalid-feedback">
                                            <?php echo __('please_write_review', 'Please write your review'); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="form-check mb-3">
                                        <input type="checkbox" class="form-check-input" id="verifiedPurchase" name="verified_purchase" checked>
                                        <label class="form-check-label" for="verifiedPurchase">
                                            <?php echo __('verified_purchase', 'I confirm this is a verified purchase'); ?>
                                        </label>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i> <?php echo __('submit_review', 'Submit Review'); ?>
                                    </button>
                                </form>
                            </div>
                            <?php endif; ?>
                            
                            <!-- User Already Reviewed Message -->
                            <?php if (isset($_SESSION['user_id']) && $user_has_reviewed): ?>
                            <div class="alert alert-info mb-4">
                                <i class="fas fa-info-circle me-2"></i>
                                <?php echo __('already_reviewed', 'You have already reviewed this product. Thank you for your feedback!'); ?>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Login to Review Message -->
                            <?php if (!isset($_SESSION['user_id'])): ?>
                            <div class="alert alert-info mb-4">
                                <i class="fas fa-info-circle me-2"></i>
                                <?php echo __('login_to_review', 'Please'); ?> <a href="<?php echo BASE_URL; ?>login.php"><?php echo __('login', 'login'); ?></a> <?php echo __('to_write_review', 'to write a review'); ?>.
                            </div>
                            <?php endif; ?>
                            
                            <!-- Existing Reviews -->
                            <?php if (!empty($reviews)): ?>
                                <h4><?php echo __('customer_reviews', 'Customer Reviews'); ?></h4>
                                <?php foreach ($reviews as $review): ?>
                                <div class="review-item mb-4 pb-3 border-bottom">
                                    <div class="d-flex justify-content-between mb-2">
                                        <h6><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></h6>
                                        <small class="text-muted"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></small>
                                    </div>
                                    <div class="rating mb-2">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                        <?php endfor; ?>
                                        <?php if ($review['is_verified_purchase']): ?>
                                            <span class="badge bg-success ms-2"><?php echo __('verified_purchase', 'Verified Purchase'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($review['title'])): ?>
                                        <h6 class="mb-2"><?php echo htmlspecialchars($review['title']); ?></h6>
                                    <?php endif; ?>
                                    <p><?php echo nl2br(htmlspecialchars($review['review'])); ?></p>
                                    <div class="review-actions">
                                        <button class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-thumbs-up"></i> Helpful (<?php echo $review['helpful_count']; ?>)
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-star fa-3x text-muted mb-3"></i>
                                    <h5><?php echo __('no_reviews_yet', 'No reviews yet'); ?></h5>
                                    <p class="text-muted"><?php echo __('be_first_review', 'Be the first to review this product!'); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Related Products -->
<?php if (!empty($related_products)): ?>
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5"><?php echo __('related_products', 'Related Products'); ?></h2>
        <div class="row">
            <?php foreach ($related_products as $related): ?>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card h-100 border-0 shadow-sm hover-shadow">
                    <?php if ($related['main_image']): ?>
                    <img src="<?php echo UPLOADS_URL . 'products/' . $related['main_image']; ?>" 
                         class="card-img-top" 
                         alt="<?php echo htmlspecialchars($related['title']); ?>"
                         style="height: 200px; object-fit: cover;">
                    <?php else: ?>
                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center" 
                         style="height: 200px;">
                        <i class="fas fa-box fa-3x text-muted"></i>
                    </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h6 class="card-title"><?php echo htmlspecialchars($related['title']); ?></h6>
                        <p class="card-text text-muted small"><?php echo htmlspecialchars(substr($related['description'], 0, 80)); ?>...</p>
                        <h5 class="text-primary"><?php echo $currency->format($related['price']); ?></h5>
                        <a href="<?php echo BASE_URL; ?>product.php?id=<?php echo $related['id']; ?>" 
                           class="btn btn-outline-primary btn-sm">
                            <?php echo __('view_details', 'View Details'); ?>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- JavaScript -->
<style>
.thumbnail-item img.active {
    border: 2px solid #007bff !important;
    opacity: 1 !important;
}
.thumbnail-item img {
    border: 2px solid #dee2e6;
    opacity: 0.7;
    transition: all 0.3s ease;
}
.thumbnail-item img:hover {
    opacity: 1;
    border-color: #6c757d;
}
</style>

<script>
function changeMainImage(imageSrc, thumbnail) {
    console.log('=== Change Main Image Debug ===');
    console.log('New image src:', imageSrc);
    console.log('Thumbnail element:', thumbnail);
    
    const mainImage = document.getElementById('mainProductImage');
    if (mainImage) {
        console.log('Main image found:', mainImage);
        console.log('Current src:', mainImage.src);
        mainImage.src = imageSrc;
        console.log('Updated src to:', mainImage.src);
        
        // Update active thumbnail
        document.querySelectorAll('.thumbnail-item img').forEach(img => {
            img.classList.remove('active');
        });
        
        thumbnail.classList.add('active');
        
        console.log('=== Image Change Complete ===');
    } else {
        console.error('Main image element not found!');
    }
}

// Update price and stock on variation change
document.querySelectorAll('input[name="variation"]').forEach(input => {
    input.addEventListener('change', function() {
        const price = this.dataset.price;
        const stock = this.dataset.stock;
        
        // Update price display
        const priceDisplay = document.querySelector('.price-section h2');
        if (priceDisplay && price) {
            // Using a simple format for now, or we could pass the formatted currency via data-attr
            priceDisplay.textContent = '<?php echo $currency->getSymbol(); ?>' + parseFloat(price).toFixed(2);
        }
        
        // Update stock display and quantity input max
        const quantityInput = document.getElementById('quantity');
        if (quantityInput) {
            quantityInput.max = stock;
            if (parseInt(quantityInput.value) > parseInt(stock)) {
                quantityInput.value = stock;
            }
        }
        
        const stockDisplay = document.querySelector('.stock-status');
        if (stockDisplay) {
            if (parseInt(stock) > 0) {
                stockDisplay.innerHTML = `<span class="badge bg-success"><i class="fas fa-check-circle"></i> <?php echo __('in_stock', 'In Stock'); ?> (${stock})</span>`;
            } else {
                stockDisplay.innerHTML = `<span class="badge bg-danger"><i class="fas fa-times-circle"></i> <?php echo __('out_of_stock', 'Out of Stock'); ?></span>`;
            }
        }
    });
});

function increaseQuantity() {
    const quantityInput = document.getElementById('quantity');
    const maxValue = parseInt(quantityInput.max);
    const currentValue = parseInt(quantityInput.value);
    
    if (currentValue < maxValue) {
        quantityInput.value = currentValue + 1;
    }
}

function decreaseQuantity() {
    const quantityInput = document.getElementById('quantity');
    const currentValue = parseInt(quantityInput.value);
    
    if (currentValue > 1) {
        quantityInput.value = currentValue - 1;
    }
}

// addToCart is now handled by product-integration.js

// Test function - call this directly from console
function testAddToCart() {
    console.log('TEST: Direct function call');
    
    // Check if quantity element exists
    const quantityElement = document.getElementById('quantity');
    console.log('Quantity element:', quantityElement);
    
    if (quantityElement) {
        console.log('Quantity value:', quantityElement.value);
        // Set quantity to 3 for testing
        quantityElement.value = 3;
        console.log('Set quantity to 3, about to call addToCart...');
        
        try {
            const result = addToCart(<?php echo $product_id; ?>);
            console.log('addToCart returned:', result);
        } catch (error) {
            console.error('addToCart threw error:', error);
        }
        
        console.log('Finished addToCart call');
    } else {
        console.error('Quantity element not found!');
        // Try to find any input with 'quantity' in name/id
        const allInputs = document.querySelectorAll('input');
        console.log('All inputs:', allInputs);
        allInputs.forEach((input, index) => {
            console.log(`Input ${index}:`, input.id, input.name, input.type);
        });
    }
}

// Test function on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Page loaded, checking for main image...');
    const mainImage = document.getElementById('mainProductImage');
    if (mainImage) {
        console.log('Main image element found on page load');
        console.log('Initial src:', mainImage.src);
    } else {
        console.error('Main image element NOT found on page load');
    }
});

// Review Form Submission
document.addEventListener('DOMContentLoaded', function() {
    const reviewForm = document.getElementById('reviewForm');
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(reviewForm);
            const submitBtn = reviewForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php echo __('submitting', 'Submitting...'); ?>';
            
            // Submit review via AJAX
            fetch('<?php echo BASE_URL; ?>product-ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(formData) + '&action=submit_review'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message || '<?php echo __('review_submitted', 'Review submitted successfully!'); ?>', 'success');
                    
                    // Reload page after 2 seconds to show the new review
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    showNotification(data.message || '<?php echo __('review_error', 'Error submitting review'); ?>', 'error');
                }
            })
            .catch(error => {
                console.error('Review submission error:', error);
                showNotification('<?php echo __('error_occurred', 'An error occurred'); ?>', 'error');
            })
            .finally(() => {
                // Restore button state
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    }
});

// Rating Stars Interaction
document.addEventListener('DOMContentLoaded', function() {
    const ratingInputs = document.querySelectorAll('input[name="rating"]');
    const starLabels = document.querySelectorAll('.star-label');
    
    if (ratingInputs.length > 0) {
        // Handle star clicks
        starLabels.forEach((label, index) => {
            label.addEventListener('click', function() {
                const rating = parseInt(this.getAttribute('for').replace('star', ''));
                
                // Update visual state
                starLabels.forEach((starLabel, starIndex) => {
                    if (starIndex < rating) {
                        starLabel.querySelector('i').classList.remove('text-muted');
                        starLabel.querySelector('i').classList.add('text-warning');
                    } else {
                        starLabel.querySelector('i').classList.remove('text-warning');
                        starLabel.querySelector('i').classList.add('text-muted');
                    }
                });
            });
        });
        
        // Handle hover effects
        starLabels.forEach((label, index) => {
            label.addEventListener('mouseenter', function() {
                const rating = parseInt(this.getAttribute('for').replace('star', ''));
                
                starLabels.forEach((starLabel, starIndex) => {
                    if (starIndex < rating) {
                        starLabel.querySelector('i').classList.remove('text-muted');
                        starLabel.querySelector('i').classList.add('text-warning');
                    } else {
                        starLabel.querySelector('i').classList.remove('text-warning');
                        starLabel.querySelector('i').classList.add('text-muted');
                    }
                });
            });
        });
        
        // Reset on mouse leave if no rating selected
        document.querySelector('.rating-input').addEventListener('mouseleave', function() {
            const selectedRating = document.querySelector('input[name="rating"]:checked');
            const rating = selectedRating ? parseInt(selectedRating.value) : 0;
            
            starLabels.forEach((starLabel, starIndex) => {
                if (starIndex < rating) {
                    starLabel.querySelector('i').classList.remove('text-muted');
                    starLabel.querySelector('i').classList.add('text-warning');
                } else {
                    starLabel.querySelector('i').classList.remove('text-warning');
                    starLabel.querySelector('i').classList.add('text-muted');
                }
            });
        });
    }
});

// showNotification is now handled by product-integration.js
</script>

<!-- Review Form Styles -->
<style>
.rating-input {
    display: flex;
    gap: 5px;
}

.rating-input input[type="radio"] {
    display: none;
}

.star-label {
    cursor: pointer;
    font-size: 1.5rem;
    color: #ddd;
    transition: color 0.2s;
}

.star-label i {
    color: #ddd;
}

.star-label:hover i,
.rating-input input[type="radio"]:checked + .star-label i {
    color: #ffc107 !important;
}

.star-label i.text-warning {
    color: #ffc107 !important;
}

.star-label i.text-muted {
    color: #ddd !important;
}

.review-form {
    background: #f8f9fa;
    padding: 30px;
    border-radius: 10px;
    border: 1px solid #e9ecef;
}

.review-item {
    padding: 20px;
    background: #fff;
    border-radius: 8px;
    margin-bottom: 20px;
    border: 1px solid #e9ecef;
}

.review-actions {
    margin-top: 15px;
}

.alert {
    border-radius: 8px;
}
</style>

<?php include 'includes/footer.php'; ?>
