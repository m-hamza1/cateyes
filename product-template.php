<?php
require_once 'config.php';

// Get product ID from URL
$productId = $_GET['id'] ?? 0;

// Get product details
$product = $db->fetchOne("
    SELECT p.*, pt.name, pt.description, pt.meta_title, pt.meta_description
    FROM products p
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang_id
    WHERE p.id = :product_id
", [
    'product_id' => $productId,
    'lang_id' => $lang->getCurrentId()
]);

if (!$product) {
    header('HTTP/1.0 404 Not Found');
    include 'includes/404.php';
    exit;
}

// Check if product is in wishlist (for logged-in users)
$isInWishlist = false;
if (isset($_SESSION['user_id'])) {
    $wishlist = new Wishlist($db);
    $isInWishlist = $wishlist->isInWishlist($_SESSION['user_id'], $productId);
}

$pageTitle = $product['name'] ?? 'Product';
include 'includes/header.php';
?>

<!-- Product Integration CSS -->
<style>
.product-actions {
    margin: 20px 0;
}

.quantity-selector {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 15px 0;
}

.quantity-selector input {
    width: 80px;
    text-align: center;
}

.btn-add-cart {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
    padding: 12px 30px;
    font-weight: bold;
    border-radius: 25px;
    transition: all 0.3s ease;
}

.btn-add-cart:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.btn-wishlist {
    border-radius: 25px;
    padding: 12px 20px;
    transition: all 0.3s ease;
}

.btn-wishlist:hover {
    transform: translateY(-2px);
}

.product-price {
    font-size: 2rem;
    font-weight: bold;
    color: #28a745;
    margin: 20px 0;
}

.product-image {
    max-width: 100%;
    height: auto;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.notification-toast {
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
</style>

<!-- Include the integration JavaScript -->
<script src="product-integration.js"></script>

<div class="container py-5">
    <div class="row">
        <!-- Product Images -->
        <div class="col-md-6">
            <div class="product-image-container">
                <?php 
                $productImage = $product['main_image'] ?? '';
                $hasImage = !empty($productImage) && file_exists('assets/uploads/products/' . $productImage);
                ?>
                <?php if ($hasImage): ?>
                    <img src="<?php echo UPLOADS_URL . 'products/' . htmlspecialchars($productImage); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                         class="product-image"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <?php endif; ?>
                <div class="product-image d-flex align-items-center justify-content-center bg-light text-muted" 
                     style="<?php echo $hasImage ? 'display: none;' : ''; ?>">
                    <div class="text-center">
                        <i class="fas fa-image fa-4x mb-2"></i>
                        <div>No Image Available</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Product Details -->
        <div class="col-md-6">
            <h1 class="mb-3"><?php echo htmlspecialchars($product['name']); ?></h1>
            
            <div class="product-price">
                <?php echo $currency->format($product['price']); ?>
            </div>
            
            <div class="product-description">
                <?php echo nl2br(htmlspecialchars($product['description'] ?? '')); ?>
            </div>
            
            <!-- Product Actions -->
            <div class="product-actions">
                <div class="quantity-selector">
                    <label for="quantity"><?php echo __('quantity'); ?>:</label>
                    <input type="number" 
                           id="quantity" 
                           data-quantity="<?php echo $productId; ?>"
                           value="1" 
                           min="1" 
                           max="99" 
                           class="form-control">
                </div>
                
                <div class="action-buttons d-flex gap-3">
                    <button type="submit" class="btn btn-warning btn-sm" onclick="addToCart(<?php echo $product_id; ?>)" <?php echo $product['stock_quantity'] <= 0 ? 'disabled' : ''; ?>>
                        <i class="fas fa-shopping-cart me-2"></i>
                        <?php echo __('add_to_cart'); ?>
                    </button>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <button class="btn <?php echo $isInWishlist ? 'btn-danger' : 'btn-outline-danger'; ?> btn-wishlist" 
                                data-wishlist-btn="<?php echo $productId; ?>">
                            <i class="<?php echo $isInWishlist ? 'fas' : 'far'; ?> fa-heart me-2"></i>
                            <?php echo $isInWishlist ? __('in_wishlist') : __('add_to_wishlist'); ?>
                        </button>
                    <?php else: ?>
                        <a href="account/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" 
                           class="btn btn-outline-danger btn-wishlist">
                            <i class="far fa-heart me-2"></i>
                            <?php echo __('add_to_wishlist'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Product Information -->
            <div class="product-info mt-4">
                <h5><?php echo __('product_details_heading'); ?></h5>
                <ul class="list-unstyled">
                    <li><strong><?php echo __('sku'); ?>:</strong> <?php echo htmlspecialchars($product['sku'] ?? __('na')); ?></li>
                    <li><strong><?php echo __('weight'); ?>:</strong> <?php echo htmlspecialchars($product['weight'] ?? '1.0'); ?> kg</li>
                    <li><strong><?php echo __('availability'); ?>:</strong> 
                        <span class="badge bg-success"><?php echo __('in_stock_badge'); ?></span>
                    </li>
                </ul>
            </div>
            
            <!-- Shipping Information -->
            <div class="shipping-info mt-4">
                <h5><?php echo __('shipping_information'); ?></h5>
                <div class="alert alert-info">
                    <i class="fas fa-truck me-2"></i>
                    <?php echo __('free_shipping_on_orders'); ?>
                </div>
                <ul class="list-unstyled">
                    <li><i class="fas fa-check text-success me-2"></i> <?php echo __('standard_shipping'); ?></li>
                    <li><i class="fas fa-check text-success me-2"></i> <?php echo __('express_shipping'); ?></li>
                    <li><i class="fas fa-check text-success me-2"></i> <?php echo __('international_shipping'); ?></li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Related Products (Optional) -->
    <div class="row mt-5">
        <div class="col-12">
            <h3><?php echo __('related_products'); ?></h3>
            <div class="row">
                <!-- Add related products here -->
                <div class="col-md-3">
                    <div class="card">
                        <img src="https://via.placeholder.com/200x150" class="card-img-top" alt="Related Product">
                        <div class="card-body">
                            <h6>Related Product 1</h6>
                            <p class="card-text">$29.99</p>
                            <button class="btn btn-sm btn-primary" data-add-to-cart="2"><?php echo __('add_to_cart'); ?></button>
                        </div>
                    </div>
                </div>
                <!-- Add more related products as needed -->
            </div>
        </div>
    </div>
</div>

<!-- Quick View Modal (Optional) -->
<div class="modal fade" id="quickViewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Quick View</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Quick view content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
// Additional product-specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Image zoom functionality
    const productImage = document.querySelector('.product-image');
    if (productImage) {
        productImage.addEventListener('click', function() {
            // Open image in modal or lightbox
            const modal = new bootstrap.Modal(document.getElementById('imageModal'));
            modal.show();
        });
    }
    
    // Quantity validation
    const quantityInput = document.getElementById('quantity');
    if (quantityInput) {
        quantityInput.addEventListener('input', function() {
            const value = parseInt(this.value);
            if (value < 1) this.value = 1;
            if (value > 99) this.value = 99;
        });
    }
    
    // Add to cart animation
    const addToCartBtn = document.querySelector('[data-add-to-cart]');
    if (addToCartBtn) {
        addToCartBtn.addEventListener('click', function() {
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Adding...';
            this.disabled = true;
            
            // Reset after a short delay (in real implementation, this would be reset after AJAX completes)
            setTimeout(() => {
                this.innerHTML = '<i class="fas fa-shopping-cart me-2"></i> <?php echo __('add_to_cart'); ?>';
                this.disabled = false;
            }, 1000);
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
