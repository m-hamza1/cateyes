<?php
require_once '../config.php';
$pageTitle = __('wishlist', 'Wishlist');

// Redirect if not logged in
if (!$user) {
    $_SESSION['flash_message'] = __('login_required', 'Please login to access your wishlist');
    $_SESSION['flash_type'] = 'warning';
    header('Location: ' . BASE_URL . 'account/login.php?redirect=' . urlencode(BASE_URL . 'account/wishlist.php'));
    exit;
}

// Get wishlist items with product details
$wishlist_items = $db->fetchAll("
    SELECT w.*, p.main_image, p.slug, p.price, p.stock_quantity, p.is_active,
           pt.name, pt.description
    FROM wishlist w
    LEFT JOIN products p ON w.product_id = p.id
    LEFT JOIN product_translations pt ON p.id = pt.product_id AND pt.language_id = :lang
    WHERE w.user_id = :user_id
    ORDER BY w.created_at DESC
", [
    'user_id' => $user['id'],
    'lang' => $lang->getCurrentId()
]);

// Ensure wishlist_items is an array
if (!is_array($wishlist_items)) {
    $wishlist_items = [];
}

// Process wishlist actions
$action = $_GET['action'] ?? '';
$product_id = $_GET['product_id'] ?? 0;

if ($action === 'remove' && $product_id > 0) {
    try {
        $db->delete('wishlist', 'user_id = :user_id AND product_id = :product_id', [
            'user_id' => $user['id'],
            'product_id' => $product_id
        ]);
        $_SESSION['flash_message'] = __('removed_from_wishlist', 'Item removed from wishlist');
        $_SESSION['flash_type'] = 'success';
    } catch (Exception $e) {
        $_SESSION['flash_message'] = __('remove_error', 'Error removing item from wishlist');
        $_SESSION['flash_type'] = 'danger';
    }
    
    header('Location: ' . BASE_URL . 'account/wishlist.php');
    exit;
}

if ($action === 'add_to_cart' && $product_id > 0) {
    try {
        // Check if product exists and is active
        $product = $db->fetchOne("SELECT * FROM products WHERE id = :id AND is_active = 1", ['id' => $product_id]);
        
        if ($product) {
            // Add to cart logic here (simplified for now)
            $_SESSION['flash_message'] = __('added_to_cart', 'Item added to cart');
            $_SESSION['flash_type'] = 'success';
            
            // Optionally remove from wishlist after adding to cart
            $remove_from_wishlist = $_GET['remove_from_wishlist'] ?? false;
            if ($remove_from_wishlist) {
                $db->delete('wishlist', 'user_id = :user_id AND product_id = :product_id', [
                    'user_id' => $user['id'],
                    'product_id' => $product_id
                ]);
            }
        } else {
            $_SESSION['flash_message'] = __('product_not_available', 'Product is not available');
            $_SESSION['flash_type'] = 'warning';
        }
    } catch (Exception $e) {
        $_SESSION['flash_message'] = __('cart_error', 'Error adding item to cart');
        $_SESSION['flash_type'] = 'danger';
    }
    
    header('Location: ' . BASE_URL . 'account/wishlist.php');
    exit;
}

include '../includes/header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="bg-light py-3">
    <div class="container">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>"><?php echo __('home', 'Home'); ?></a></li>
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>account/dashboard.php"><?php echo __('dashboard', 'Dashboard'); ?></a></li>
            <li class="breadcrumb-item active"><?php echo __('wishlist', 'Wishlist'); ?></li>
        </ol>
    </div>
</nav>

<!-- Main content -->
<section class="content py-5">
    <div class="container">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="card shadow-sm mb-4">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-user-circle fa-5x text-primary"></i>
                        </div>
                        <h5 class="card-title"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                        <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title mb-3"><?php echo __('quick_links', 'Quick Links'); ?></h6>
                        <div class="list-group list-group-flush">
                            <a href="<?php echo BASE_URL; ?>account/dashboard.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-tachometer-alt me-2"></i> <?php echo __('dashboard', 'Dashboard'); ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>account/profile.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-user me-2"></i> <?php echo __('profile', 'Profile'); ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>account/mybookings.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-calendar-check me-2"></i> <?php echo __('my_bookings', 'My Bookings'); ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>account/orders.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-shopping-bag me-2"></i> <?php echo __('orders', 'Orders'); ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>account/wishlist.php" class="list-group-item list-group-item-action active">
                                <i class="fas fa-heart me-2"></i> <?php echo __('wishlist', 'Wishlist'); ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>account/addresses.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-map-marker-alt me-2"></i> <?php echo __('addresses', 'Addresses'); ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>account/logout.php" class="list-group-item list-group-item-action text-danger">
                                <i class="fas fa-sign-out-alt me-2"></i> <?php echo __('logout', 'Logout'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-heart me-2"></i>
                            <?php echo __('my_wishlist', 'My Wishlist'); ?>
                            <span class="badge bg-light text-primary ms-2"><?php echo count($wishlist_items); ?></span>
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($wishlist_items)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-heart-broken fa-4x text-muted mb-3"></i>
                                <h5><?php echo __('empty_wishlist', 'Your wishlist is empty'); ?></h5>
                                <p class="text-muted"><?php echo __('wishlist_empty_message', 'Start adding items you love to your wishlist'); ?></p>
                                <a href="<?php echo BASE_URL; ?>products.php" class="btn btn-primary">
                                    <i class="fas fa-shopping-bag me-2"></i>
                                    <?php echo __('start_shopping', 'Start Shopping'); ?>
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($wishlist_items as $item): ?>
                                    <?php 
                                    $is_available = $item['is_active'] && $item['stock_quantity'] > 0;
                                    $product_url = BASE_URL . 'product/' . ($item['slug'] ?: 'product-' . $item['product_id']);
                                    ?>
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card h-100">
                                            <div class="position-relative">
                                                <?php if ($item['main_image']): ?>
                                                    <img src="<?php echo BASE_URL; ?>assets/uploads/products/<?php echo htmlspecialchars($item['main_image']); ?>" 
                                                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                                         class="card-img-top" style="height: 200px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="card-img-top d-flex align-items-center justify-content-center bg-light" style="height: 200px;">
                                                        <i class="fas fa-image fa-3x text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if (!$is_available): ?>
                                                    <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 d-flex align-items-center justify-content-center">
                                                        <span class="badge bg-danger fs-6"><?php echo __('out_of_stock', 'Out of Stock'); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div class="position-absolute top-0 end-0 p-2">
                                                    <button class="btn btn-sm btn-danger rounded-circle" 
                                                            onclick="removeFromWishlist(<?php echo $item['product_id']; ?>)"
                                                            title="<?php echo __('remove_from_wishlist', 'Remove from wishlist'); ?>">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            <div class="card-body d-flex flex-column">
                                                <h6 class="card-title">
                                                    <a href="<?php echo $product_url; ?>" class="text-decoration-none">
                                                        <?php echo htmlspecialchars($item['name'] ?: 'Product #' . $item['product_id']); ?>
                                                    </a>
                                                </h6>
                                                <p class="card-text text-muted small">
                                                    <?php echo htmlspecialchars(substr($item['description'] ?: '', 0, 100)); ?>...
                                                </p>
                                                <div class="mt-auto">
                                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                                        <span class="h5 text-primary mb-0">
                                                            <?php echo $currency->format($item['price'] ?? 0); ?>
                                                        </span>
                                                        <?php if ($item['stock_quantity'] > 0): ?>
                                                            <small class="text-success">
                                                                <i class="fas fa-check-circle me-1"></i>
                                                                <?php echo __('in_stock', 'In Stock'); ?> (<?php echo $item['stock_quantity']; ?>)
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <div class="btn-group w-100" role="group">
                                                        <?php if ($is_available): ?>
                                                            <a href="<?php echo $product_url; ?>" class="btn btn-outline-primary btn-sm">
                                                                <i class="fas fa-eye me-1"></i>
                                                                <?php echo __('view_details', 'View Details'); ?>
                                                            </a>
                                                            <button class="btn btn-primary btn-sm" 
                                                                    onclick="addToCart(<?php echo $item['product_id']; ?>)">
                                                                <i class="fas fa-shopping-cart me-1"></i>
                                                                <?php echo __('add_to_cart', 'Add to Cart'); ?>
                                                            </button>
                                                        <?php else: ?>
                                                            <a href="<?php echo $product_url; ?>" class="btn btn-outline-secondary btn-sm w-100">
                                                                <i class="fas fa-eye me-1"></i>
                                                                <?php echo __('view_details', 'View Details'); ?>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="card-footer text-muted small">
                                                <i class="fas fa-clock me-1"></i>
                                                <?php echo __('added_on', 'Added on'); ?> <?php echo date('M d, Y', strtotime($item['created_at'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Wishlist Actions -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <div class="row align-items-center">
                                                <div class="col-md-6">
                                                    <h6 class="mb-0">
                                                        <i class="fas fa-info-circle me-2"></i>
                                                        <?php echo __('wishlist_info', 'Wishlist Information'); ?>
                                                    </h6>
                                                    <small class="text-muted">
                                                        <?php echo __('wishlist_items_count', 'You have {count} items in your wishlist', ['count' => count($wishlist_items)]); ?>
                                                    </small>
                                                </div>
                                                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                                                    <button class="btn btn-outline-danger me-2" onclick="clearWishlist()">
                                                        <i class="fas fa-trash me-1"></i>
                                                        <?php echo __('clear_wishlist', 'Clear Wishlist'); ?>
                                                    </button>
                                                    <button class="btn btn-outline-primary" onclick="shareWishlist()">
                                                        <i class="fas fa-share me-1"></i>
                                                        <?php echo __('share_wishlist', 'Share Wishlist'); ?>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function removeFromWishlist(productId) {
    if (confirm('<?php echo __('confirm_remove_wishlist', 'Are you sure you want to remove this item from your wishlist?'); ?>')) {
        window.location.href = '<?php echo BASE_URL; ?>account/wishlist.php?action=remove&product_id=' + productId;
    }
}

function addToCart(productId) {
    // Add to cart and optionally remove from wishlist
    window.location.href = '<?php echo BASE_URL; ?>account/wishlist.php?action=add_to_cart&product_id=' + productId + '&remove_from_wishlist=true';
}

function clearWishlist() {
    if (confirm('<?php echo __('confirm_clear_wishlist', 'Are you sure you want to clear your entire wishlist? This action cannot be undone.'); ?>')) {
        // In a real implementation, you would make an AJAX call to clear all items
        alert('<?php echo __('feature_coming_soon', 'This feature is coming soon!'); ?>');
    }
}

function shareWishlist() {
    // In a real implementation, you would generate a shareable link
    const shareUrl = window.location.href;
    
    if (navigator.share) {
        navigator.share({
            title: '<?php echo __('my_wishlist', 'My Wishlist'); ?>',
            text: '<?php echo __('share_wishlist_text', 'Check out my wishlist!'); ?>',
            url: shareUrl
        });
    } else {
        // Fallback: copy to clipboard
        navigator.clipboard.writeText(shareUrl).then(() => {
            alert('<?php echo __('wishlist_link_copied', 'Wishlist link copied to clipboard!'); ?>');
        });
    }
}
</script>

<?php include '../includes/footer.php'; ?>
